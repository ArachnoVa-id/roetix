const { chromium } = require('playwright');

const PASSWORD = 'password123';
const userCount = parseInt(process.argv[2], 10) || 20;
const DOMAIN = process.argv[3] || 'http://test.dev-staging-novatix.id';
const startUser = parseInt(process.argv[4], 10) || 1;

const USERS = Array.from({ length: userCount }, (_, i) => ({
    email: `testuser${startUser + i}@example.com`,
    password: PASSWORD,
}));

const sessions = [];

// Extract queue information from the page
const extractQueueInfo = async (page, label) => {
    try {
        const queueInfo = await page.evaluate(() => {
            const titleElement = document.querySelector(
                '#queue-wrapper h2:first-of-type',
            );
            const messageElement = document.querySelector('#queue-wrapper p');
            const timeElement = document.querySelector(
                '#queue-wrapper [style*="color"] p',
            );

            return {
                eventName: titleElement?.textContent?.trim() || 'Unknown Event',
                queueTitle:
                    document
                        .querySelector('#queue-wrapper .text-xl')
                        ?.textContent?.trim() || 'In Queue',
                queueMessage:
                    messageElement?.textContent?.trim() || 'Please wait',
                timeLeft: timeElement?.textContent?.trim() || null,
                url: window.location.href,
            };
        });

        console.log(`📊 [${label}] Queue Info:`, {
            event: queueInfo.eventName,
            title: queueInfo.queueTitle,
            message: queueInfo.queueMessage,
            timeLeft: queueInfo.timeLeft,
        });

        return queueInfo;
    } catch (error) {
        console.log(
            `⚠️ [${label}] Could not extract queue info: ${error.message}`,
        );
        return null;
    }
};

// Check if user is on landing page
const checkLandingPage = async (page, label) => {
    try {
        await page.locator('#landing-wrapper').waitFor({ timeout: 5000 });
        console.log(`✅ [${label}] Landing page detected.`);
        return true;
    } catch {
        return false;
    }
};

// Check if user is in queue
const checkQueuePage = async (page, label) => {
    try {
        await page.locator('#queue-wrapper').waitFor({ timeout: 5000 });
        console.log(`🪑 [${label}] Queue page detected.`);
        return true;
    } catch {
        return false;
    }
};

// Monitor queue status and wait for promotion
const monitorQueue = async (page, label) => {
    console.log(`👀 [${label}] Starting queue monitoring...`);

    const maxWaitTime = 600000; // 10 minutes max wait
    const pollInterval = 5000; // Check every 5 seconds
    const startTime = Date.now();

    while (Date.now() - startTime < maxWaitTime) {
        try {
            // Check if we're still in queue
            const inQueue = await checkQueuePage(page, label);

            if (!inQueue) {
                // Check if we've moved to landing page
                const onLanding = await checkLandingPage(page, label);
                if (onLanding) {
                    console.log(
                        `🎯 [${label}] Successfully promoted from queue to landing page!`,
                    );
                    return 'promoted';
                }

                // Check if we've been redirected to login (session expired)
                if (
                    page.url().includes('/login') ||
                    page.url().includes('/privateLogin')
                ) {
                    console.log(
                        `🔁 [${label}] Session expired - redirected to login`,
                    );
                    return 'expired';
                }

                console.log(`❓ [${label}] Unknown state - URL: ${page.url()}`);
                return 'unknown';
            }

            // Extract and log current queue information
            const queueInfo = await extractQueueInfo(page, label);

            if (
                queueInfo &&
                queueInfo.timeLeft &&
                queueInfo.timeLeft !== "Time's up!"
            ) {
                console.log(
                    `⏱️ [${label}] Still in queue - Time left: ${queueInfo.timeLeft}`,
                );
            } else {
                console.log(
                    `⏳ [${label}] Still in queue - waiting for update...`,
                );
            }

            // Wait before next check
            await page.waitForTimeout(pollInterval);
        } catch (error) {
            console.log(
                `⚠️ [${label}] Error during queue monitoring: ${error.message}`,
            );
            await page.waitForTimeout(pollInterval);
        }
    }

    console.log(`⏱️ [${label}] Queue monitoring timeout reached`);
    return 'timeout';
};

// Monitor session after reaching landing page
const monitorSession = async (page, label) => {
    console.log(`🔐 [${label}] Starting session monitoring...`);

    const sessionTimeout = 600000; // 10 minutes

    try {
        // Wait for either redirect to login or timeout
        await Promise.race([
            page.waitForURL('**/login', { timeout: sessionTimeout }),
            page.waitForURL('**/privateLogin', { timeout: sessionTimeout }),
        ]);
        console.log(`🔁 [${label}] Session expired (redirected to login)`);
        return 'expired';
    } catch {
        console.log(
            `🟢 [${label}] Session still active after ${sessionTimeout / 1000 / 60} minutes`,
        );
        return 'active';
    }
};

const loginAndMonitor = async (user, label) => {
    console.log(`🧪 [${label}] Launching browser...`);
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'], // For better stability
        // Uncomment the line below to use system Chrome instead of Playwright's Chromium
        // executablePath: '/usr/bin/google-chrome', // Adjust path as needed
    });
    const context = await browser.newContext({
        // Set reasonable timeouts
        timeout: 30000,
    });
    const page = await context.newPage();

    // Add to sessions tracking
    sessions.push({ label, browser, context, page, status: 'starting' });
    const sessionIndex = sessions.length - 1;

    try {
        console.log(`🌐 [${label}] Navigating to login page...`);
        await page.goto(DOMAIN + '/privateLogin', { waitUntil: 'networkidle' });

        console.log(`📝 [${label}] Filling in credentials...`);
        await page.fill('#email', user.email);
        await page.fill('#password', user.password);

        console.log(`🔓 [${label}] Submitting login form...`);

        // Use more robust login handling
        await Promise.all([
            page.waitForNavigation({
                waitUntil: 'networkidle',
                timeout: 30000,
            }),
            page.click('form button[type=submit]'),
        ]);

        console.log(
            `🧭 [${label}] Post-login navigation completed. URL: ${page.url()}`,
        );

        // Update session status
        if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
            sessions[sessionIndex].status = 'logged_in';

        // Wait for either queue or landing page to appear (no fixed timeout)
        console.log(
            `⏳ [${label}] Waiting for page to load (queue or landing page)...`,
        );

        let isQueueVisible = false;
        let isLandingVisible = false;

        try {
            // Wait for either queue wrapper or landing wrapper to appear
            // This will wait indefinitely until one of them shows up
            await Promise.race([
                page.locator('#queue-wrapper').waitFor({ timeout: 0 }), // No timeout
                page.locator('#landing-wrapper').waitFor({ timeout: 0 }), // No timeout
            ]);

            console.log(
                `✅ [${label}] Page content loaded, checking current state...`,
            );

            // Now check which one is actually visible
            isQueueVisible = await page.locator('#queue-wrapper').isVisible();
            isLandingVisible = await page
                .locator('#landing-wrapper')
                .isVisible();
        } catch (error) {
            console.log(
                `⚠️ [${label}] Error waiting for page elements: ${error.message}`,
            );

            // Fallback: check current visibility anyway
            isQueueVisible = await page.locator('#queue-wrapper').isVisible();
            isLandingVisible = await page
                .locator('#landing-wrapper')
                .isVisible();
        }

        if (isLandingVisible) {
            console.log(
                `🎯 [${label}] Directly landed on main page - no queue!`,
            );
            if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                sessions[sessionIndex].status = 'active';

            // Monitor session
            const sessionResult = await monitorSession(page, label);
            if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                sessions[sessionIndex].status = sessionResult;
        } else if (isQueueVisible) {
            console.log(`🪑 [${label}] Entered queue - starting monitoring...`);
            if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                sessions[sessionIndex].status = 'in_queue';

            // Extract initial queue information
            await extractQueueInfo(page, label);

            // Monitor queue until promotion or timeout
            const queueResult = await monitorQueue(page, label);

            if (queueResult === 'promoted') {
                if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                    sessions[sessionIndex].status = 'active';
                console.log(
                    `🎉 [${label}] Successfully promoted! Starting session monitoring...`,
                );

                // Monitor session after promotion
                const sessionResult = await monitorSession(page, label);
                if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                    sessions[sessionIndex].status = sessionResult;
            } else if (queueResult === 'expired') {
                if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                    sessions[sessionIndex].status = 'expired';
                console.log(`❌ [${label}] Session expired while in queue`);
            } else {
                if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                    sessions[sessionIndex].status = 'timeout';
                console.log(`⏱️ [${label}] Queue monitoring timed out`);
            }
        } else {
            console.log(
                `❓ [${label}] Neither queue nor landing page detected initially. URL: ${page.url()}`,
            );

            // Wait a bit more with a reasonable timeout and try again
            console.log(
                `🔍 [${label}] Waiting up to 30 seconds for content to appear...`,
            );

            try {
                await Promise.race([
                    page.locator('#queue-wrapper').waitFor({ timeout: 30000 }),
                    page
                        .locator('#landing-wrapper')
                        .waitFor({ timeout: 30000 }),
                ]);

                // Re-check after waiting
                const isQueueVisibleRetry = await page
                    .locator('#queue-wrapper')
                    .isVisible();
                const isLandingVisibleRetry = await page
                    .locator('#landing-wrapper')
                    .isVisible();

                if (isLandingVisibleRetry) {
                    console.log(
                        `🎯 [${label}] Landing page appeared after additional wait!`,
                    );
                    if (
                        sessions[sessionIndex] &&
                        sessions[sessionIndex]?.status
                    )
                        sessions[sessionIndex].status = 'active';
                    const sessionResult = await monitorSession(page, label);
                    if (
                        sessions[sessionIndex] &&
                        sessions[sessionIndex]?.status
                    )
                        sessions[sessionIndex].status = sessionResult;
                } else if (isQueueVisibleRetry) {
                    console.log(
                        `🪑 [${label}] Queue page appeared after additional wait!`,
                    );
                    if (
                        sessions[sessionIndex] &&
                        sessions[sessionIndex]?.status
                    )
                        sessions[sessionIndex].status = 'in_queue';
                    await extractQueueInfo(page, label);
                    const queueResult = await monitorQueue(page, label);

                    if (queueResult === 'promoted') {
                        if (
                            sessions[sessionIndex] &&
                            sessions[sessionIndex]?.status
                        )
                            sessions[sessionIndex].status = 'active';
                        console.log(
                            `🎉 [${label}] Successfully promoted! Starting session monitoring...`,
                        );
                        const sessionResult = await monitorSession(page, label);
                        if (
                            sessions[sessionIndex] &&
                            sessions[sessionIndex]?.status
                        )
                            sessions[sessionIndex].status = sessionResult;
                    } else if (queueResult === 'expired') {
                        if (
                            sessions[sessionIndex] &&
                            sessions[sessionIndex]?.status
                        )
                            sessions[sessionIndex].status = 'expired';
                        console.log(
                            `❌ [${label}] Session expired while in queue`,
                        );
                    } else {
                        if (
                            sessions[sessionIndex] &&
                            sessions[sessionIndex]?.status
                        )
                            sessions[sessionIndex].status = 'timeout';
                        console.log(`⏱️ [${label}] Queue monitoring timed out`);
                    }
                } else {
                    throw new Error('Still no valid page state after retry');
                }
            } catch (retryError) {
                console.log(
                    `❌ [${label}] Still unknown state after 30s wait: ${retryError.message}`,
                );
                if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
                    sessions[sessionIndex].status = 'unknown';

                // Debug information
                const pageContent = await page.content();
                console.log(`🔍 [${label}] Page content analysis:`);

                if (
                    pageContent.includes('queue') ||
                    pageContent.includes('Queue')
                ) {
                    console.log(
                        `🪑 [${label}] Page contains queue-related content`,
                    );
                } else if (
                    pageContent.includes('landing') ||
                    pageContent.includes('dashboard')
                ) {
                    console.log(
                        `🎯 [${label}] Page contains landing/dashboard content`,
                    );
                } else if (
                    pageContent.includes('login') ||
                    pageContent.includes('Login')
                ) {
                    console.log(
                        `🔁 [${label}] Still on login page - credentials might be wrong`,
                    );
                } else {
                    console.log(`❓ [${label}] Unknown page content`);
                }

                console.log(`🌐 [${label}] Current URL: ${page.url()}`);
                console.log(`📄 [${label}] Page title: ${await page.title()}`);
            }
        }
    } catch (err) {
        console.error(`❗ [${label}] Error: ${err.message}`);
        if (sessions[sessionIndex] && sessions[sessionIndex]?.status)
            sessions[sessionIndex].status = 'error';
    } finally {
        console.log(
            `🚪 [${label}] Closing browser. Final status: ${sessions[sessionIndex]?.status}`,
        );
        await browser.close();

        // Remove from sessions
        const index = sessions.findIndex((s) => s.label === label);
        if (index !== -1) sessions.splice(index, 1);
    }
};

const gracefulLogout = async () => {
    console.log('\n🚦 Gracefully logging out all active users...');

    const activeSessions = [...sessions]; // Create a copy to avoid modification during iteration

    for (const { label, page, context, browser } of activeSessions) {
        try {
            // Check if page is still accessible
            if (page.isClosed()) {
                console.log(`⚠️ [${label}] Page already closed`);
                continue;
            }

            console.log(`🚪 [${label}] Attempting logout via UI...`);

            // Try to find and click logout button
            const logoutButton = await page.locator('#logout').first();
            if (await logoutButton.isVisible({ timeout: 2000 })) {
                await Promise.all([
                    page
                        .waitForNavigation({
                            waitUntil: 'networkidle',
                            timeout: 10000,
                        })
                        .catch(() => {}), // Don't fail if navigation doesn't happen
                    logoutButton.click(),
                ]);
                console.log(`✅ [${label}] Logged out via UI`);
            } else {
                throw new Error('Logout button not found');
            }
        } catch (uiError) {
            try {
                console.log(
                    `📡 [${label}] UI logout failed, trying API logout...`,
                );
                const response = await context.request.post(
                    `${DOMAIN}/auth/logout`,
                    { timeout: 5000 },
                );

                if (response.ok()) {
                    console.log(`✅ [${label}] Logout via API succeeded`);
                } else {
                    console.log(
                        `⚠️ [${label}] Logout via API failed (status ${response.status()})`,
                    );
                }
            } catch (apiError) {
                console.log(
                    `❌ [${label}] Both UI and API logout failed: ${apiError.message}`,
                );
            }
        }

        try {
            await browser.close();
            console.log(`🔚 [${label}] Browser closed`);
        } catch (error) {
            console.log(
                `⚠️ [${label}] Error closing browser: ${error.message}`,
            );
        }
    }

    // Clear sessions array
    sessions.length = 0;

    console.log('🏁 All users logged out. Exiting...');
    setTimeout(() => process.exit(0), 1000);
};

// Enhanced keyboard listener
process.stdin.setRawMode(true);
process.stdin.resume();
process.stdin.setEncoding('utf8');
process.stdin.on('data', async (key) => {
    if (key === 'q' || key === 'Q') {
        console.log('\n🛑 Logout requested by user...');
        await gracefulLogout();
    }
    if (key === 's' || key === 'S') {
        console.log('\n📊 Session Status:');
        sessions.forEach(({ label, status }) => {
            console.log(`   ${label}: ${status || 'running'}`);
        });
        console.log(`   Total active sessions: ${sessions.length}`);
    }
    if (key === '\u0003') {
        console.log('\n🛑 Force exit...');
        process.exit();
    }
});

// Main execution
(async () => {
    console.log('🚀 Starting enhanced queue simulation...');
    console.log(`📋 Configuration:`);
    console.log(
        `   - Users: ${userCount} (${startUser} to ${startUser + userCount - 1})`,
    );
    console.log(`   - Domain: ${DOMAIN}`);
    console.log(
        `   - User range: testuser${startUser}@example.com to testuser${startUser + userCount - 1}@example.com`,
    );
    console.log('\n🎮 Controls:');
    console.log('   - Press "q" to gracefully logout all users');
    console.log('   - Press "s" to show session status');
    console.log('   - Press Ctrl+C to force exit');
    console.log('\n' + '='.repeat(50));

    try {
        await Promise.all(
            USERS.map((user, i) =>
                loginAndMonitor(user, `User ${startUser + i}`),
            ),
        );

        console.log('\n🎯 All user sessions completed');
    } catch (error) {
        console.error('💥 Fatal error:', error);
    } finally {
        if (sessions.length > 0) {
            console.log('\n🧹 Cleaning up remaining sessions...');
            await gracefulLogout();
        }
    }
})();
