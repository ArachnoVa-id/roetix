const { chromium } = require('playwright');

const PASSWORD = 'password123';
const dontCareMode = process.argv[2] === 'true' || process.argv[5] === '1';
const userCount = parseInt(process.argv[3], 10) || 20;
const userPerSecond = parseInt(process.argv[4], 10) || 1;
const batchSize = parseInt(process.argv[5], 10) || 50;
const batchDelay = parseInt(process.argv[6], 10) || 5000; // milliseconds
let DOMAIN = process.argv[7] || 'http://test.dev-staging-novatix.id';
const startUser = parseInt(process.argv[8], 10) || 1;

// Clean up domain URL to handle trailing slashes
DOMAIN = DOMAIN.replace(/\/+$/, '');

const USERS = Array.from({ length: userCount }, (_, i) => ({
    email: `testuser${startUser + i}@example.com`,
    password: PASSWORD,
}));

const sessions = [];
const statistics = {
    online: 0,
    inQueue: 0,
    sessionExpired: 0,
    unknownExit: 0,
    errors: 0,
};

// Reporting controls
const reportingControls = {
    queueProgress: false,
    sessionProgress: false,
};

// Display management - Footer instead of header
const displayFooter = () => {
    const footer = [
        '═'.repeat(60),
        '🚀 Enhanced Queue Simulation',
        `📋 Configuration:`,
        `   - Users: ${userCount} (${startUser} to ${startUser + userCount - 1})`,
        `   - Users per second: ${userPerSecond}`,
        `   - Batch size: ${batchSize}`,
        `   - Batch delay: ${batchDelay}ms`,
        `   - Domain: ${DOMAIN}`,
        `   - Mode: ${dontCareMode ? "SPAM (Don't Care)" : 'MONITOR'}`,
        `   - User range: testuser${startUser}@example.com to testuser${startUser + userCount - 1}@example.com`,
        '',
        '🎮 Controls:',
        '   - Press "q" to gracefully logout all users',
        '   - Press "r" to toggle queue progress reporting',
        '   - Press "t" to toggle session progress reporting',
        '   - Press "h" for help',
        '   - Press Ctrl+C to force exit',
        '═'.repeat(60),
    ].join('\n');

    return footer;
};

const displayStatistics = () => {
    const stats = [
        '═'.repeat(60),
        '📊 Live Statistics:',
        `🟢 Online Users: ${statistics.online}`,
        `🪑 Waiting in Queue: ${statistics.inQueue}`,
        `⏱️  Session Expired: ${statistics.sessionExpired}`,
        `❓ Unknown Exit: ${statistics.unknownExit}`,
        `❌ Errors: ${statistics.errors}`,
        `📱 Total Active Sessions: ${sessions.length}`,
        '',
        `🔧 Reporting Controls:`,
        `   Queue Progress: ${reportingControls.queueProgress ? 'ENABLED' : 'DISABLED'}`,
        `   Session Progress: ${reportingControls.sessionProgress ? 'ENABLED' : 'DISABLED'}`,
    ];

    if (sessions.length > 0) {
        stats.push('');
        stats.push('Current Session Details:');
        sessions.forEach(({ label, status, latestInfo }) => {
            const statusEmoji = {
                starting: '🔄',
                logged_in: '🔑',
                in_queue: '🪑',
                active: '🟢',
                expired: '⏱️ ',
                unknown: '❓',
                error: '❌',
            };

            let statusText = status || 'running';

            // Add latest info if available
            if (latestInfo) {
                if (status === 'in_queue' && latestInfo.queueTimeLeft) {
                    statusText += ` (${latestInfo.queueTimeLeft})`;
                } else if (status === 'active' && latestInfo.sessionTimeLeft) {
                    statusText += ` (${latestInfo.sessionTimeLeft})`;
                } else if (latestInfo.lastUpdate) {
                    // Show last update time for other statuses
                    const timeDiff = Date.now() - latestInfo.lastUpdate;
                    const minutes = Math.floor(timeDiff / 60000);
                    if (minutes > 0) {
                        statusText += ` (${minutes}m ago)`;
                    } else {
                        statusText += ` (now)`;
                    }
                }
            }

            stats.push(
                `   ${statusEmoji[status] || '❓'} ${label}: ${statusText}`,
            );
        });
    }

    return stats.join('\n');
};

const refreshDisplay = () => {
    // Clear screen and move cursor to top
    process.stdout.write('\x1b[2J\x1b[H');

    // Display statistics first, then footer (LOG FIRST, THEN PANEL)
    console.log(displayStatistics());
    console.log('');
    console.log(displayFooter());
};

// Session management utilities
const updateSessionStatus = (sessionIndex, status, additionalInfo = null) => {
    if (sessions[sessionIndex]) {
        const oldStatus = sessions[sessionIndex].status;
        sessions[sessionIndex].status = status;

        // Update latest info if provided
        if (additionalInfo) {
            sessions[sessionIndex].latestInfo = {
                ...sessions[sessionIndex].latestInfo,
                ...additionalInfo,
                lastUpdate: Date.now(),
            };
        } else {
            // Always update timestamp
            sessions[sessionIndex].latestInfo = {
                ...sessions[sessionIndex].latestInfo,
                lastUpdate: Date.now(),
            };
        }

        // Update statistics
        updateStatistics(oldStatus, status);

        // Refresh display after status change
        refreshDisplay();
    }
};

const updateStatistics = (oldStatus, newStatus) => {
    // Decrement old status count only if we're changing status, not removing
    if (oldStatus && newStatus) {
        switch (oldStatus) {
            case 'active':
                statistics.online = Math.max(0, statistics.online - 1);
                break;
            case 'in_queue':
                statistics.inQueue = Math.max(0, statistics.inQueue - 1);
                break;
            case 'expired':
                statistics.sessionExpired = Math.max(
                    0,
                    statistics.sessionExpired - 1,
                );
                break;
            case 'unknown':
                statistics.unknownExit = Math.max(
                    0,
                    statistics.unknownExit - 1,
                );
                break;
            case 'error':
                statistics.errors = Math.max(0, statistics.errors - 1);
                break;
        }
    }

    // Increment new status count only if newStatus is provided
    if (newStatus) {
        switch (newStatus) {
            case 'active':
                statistics.online++;
                break;
            case 'in_queue':
                statistics.inQueue++;
                break;
            case 'expired':
                statistics.sessionExpired++;
                break;
            case 'unknown':
                statistics.unknownExit++;
                break;
            case 'error':
                statistics.errors++;
                break;
        }
    }
};

// Extract queue information from the page
const extractQueueInfo = async (page, label, sessionIndex = null) => {
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

        // Update session with latest queue info
        if (sessionIndex !== null && queueInfo.timeLeft) {
            updateSessionStatus(sessionIndex, 'in_queue', {
                queueTimeLeft: queueInfo.timeLeft,
            });
        }

        if (reportingControls.queueProgress) {
            console.log(`📊 [${label}] Queue Info:`, {
                event: queueInfo.eventName,
                title: queueInfo.queueTitle,
                message: queueInfo.queueMessage,
                timeLeft: queueInfo.timeLeft,
            });
        }

        return queueInfo;
    } catch (error) {
        if (reportingControls.queueProgress) {
            console.log(
                `⚠️ [${label}] Could not extract queue info: ${error.message}`,
            );
        }
        return null;
    }
};

// Extract session information from the page
const extractSessionInfo = async (page, label, sessionIndex = null) => {
    try {
        const sessionInfo = await page.evaluate(() => {
            // Look for the landing wrapper (main session container)
            const sessionElement = document.querySelector('#landing-wrapper');

            // Look for the specific session timer element
            let sessionTimeLeft = null;

            // Try to find the session timer by its ID
            const sessionTimer = document.querySelector('#session-timer');
            if (sessionTimer) {
                const timerText = sessionTimer.textContent?.trim();
                if (timerText) {
                    // Check if it contains "Remaining Time:" and extract the time part
                    if (timerText.includes('Remaining Time:')) {
                        sessionTimeLeft = timerText
                            .replace('Remaining Time:', '')
                            .trim();
                    } else if (timerText.includes('View')) {
                        // This means there's no countdown (e.g., "Admin View", "User View")
                        sessionTimeLeft = 'No time limit';
                    } else {
                        // Just in case the format is different, capture the whole text
                        sessionTimeLeft = timerText;
                    }
                }
            }

            return {
                isActive: !!sessionElement,
                timeLeft: sessionTimeLeft,
                url: window.location.href,
                hasTimer: !!sessionTimer,
            };
        });

        // Update session with latest session info
        if (sessionIndex !== null && sessionInfo.timeLeft) {
            updateSessionStatus(sessionIndex, 'active', {
                sessionTimeLeft: sessionInfo.timeLeft,
            });
        }

        if (reportingControls.sessionProgress) {
            if (
                sessionInfo.timeLeft &&
                sessionInfo.timeLeft !== 'No time limit'
            ) {
                console.log(`🔐 [${label}] Session Info:`, {
                    timeLeft: sessionInfo.timeLeft,
                    isActive: sessionInfo.isActive,
                    hasTimer: sessionInfo.hasTimer,
                });
            } else if (sessionInfo.timeLeft === 'No time limit') {
                console.log(`🔐 [${label}] Session active with no time limit`);
            }
        }

        return sessionInfo;
    } catch (error) {
        if (reportingControls.sessionProgress) {
            console.log(
                `⚠️ [${label}] Could not extract session info: ${error.message}`,
            );
        }
        return null;
    }
};

// Check if user is on landing page
const checkLandingPage = async (page, label) => {
    try {
        await page.locator('#landing-wrapper').waitFor({ timeout: 5000 });
        if (reportingControls.queueProgress) {
            console.log(`✅ [${label}] Landing page detected.`);
        }
        return true;
    } catch {
        return false;
    }
};

// Check if user is in queue
const checkQueuePage = async (page, label) => {
    try {
        await page.locator('#queue-wrapper').waitFor({ timeout: 5000 });
        if (reportingControls.queueProgress) {
            console.log(`🪑 [${label}] Queue page detected.`);
        }
        return true;
    } catch {
        return false;
    }
};

// Check if user is on login page (session expired)
const checkLoginPage = async (page) => {
    try {
        const url = page.url();
        if (url.includes('/login') || url.includes('/privateLogin')) {
            return true;
        }

        // Also check for login form elements
        const loginForm = await page.locator('form').count();
        const emailField = await page.locator('#email').count();
        const passwordField = await page.locator('#password').count();

        return loginForm > 0 && emailField > 0 && passwordField > 0;
    } catch {
        return false;
    }
};

// Monitor queue status and wait for promotion (without timeout)
const monitorQueue = async (page, label, sessionIndex) => {
    if (reportingControls.queueProgress) {
        console.log(`👀 [${label}] Starting queue monitoring...`);
    }

    const pollInterval = 5000; // Check every 5 seconds

    while (true) {
        try {
            // Check if page is closed first
            if (page.isClosed()) {
                console.log(
                    `🚪 [${label}] Page closed during queue monitoring`,
                );
                return 'unknown';
            }

            // First check if session expired (redirected to login)
            const onLogin = await checkLoginPage(page, label);
            if (onLogin) {
                if (reportingControls.queueProgress) {
                    console.log(
                        `🔁 [${label}] Session expired - redirected to login`,
                    );
                }
                return 'expired';
            }

            // Check if we're still in queue
            const inQueue = await checkQueuePage(page, label);

            if (!inQueue) {
                // Check if we've moved to landing page
                const onLanding = await checkLandingPage(page, label);
                if (onLanding) {
                    if (reportingControls.queueProgress) {
                        console.log(
                            `🎯 [${label}] Successfully promoted from queue to landing page!`,
                        );
                    }
                    return 'promoted';
                }

                // Check if redirected to login (session expired)
                const backToLogin = await checkLoginPage(page, label);
                if (backToLogin) {
                    if (reportingControls.queueProgress) {
                        console.log(
                            `🔁 [${label}] Session expired during queue wait`,
                        );
                    }
                    return 'expired';
                }

                if (reportingControls.queueProgress) {
                    console.log(
                        `❓ [${label}] Unknown state - URL: ${page.url()}`,
                    );
                }
                return 'unknown';
            }

            // Extract and log current queue information
            const queueInfo = await extractQueueInfo(page, label, sessionIndex);

            if (reportingControls.queueProgress) {
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
            }

            // Wait before next check
            await page.waitForTimeout(pollInterval);
        } catch (error) {
            if (reportingControls.queueProgress) {
                console.log(
                    `⚠️ [${label}] Error during queue monitoring: ${error.message}`,
                );
            }

            // Check if page is closed or context is destroyed
            if (page.isClosed()) {
                console.log(
                    `🚪 [${label}] Page closed during queue monitoring error`,
                );
                return 'unknown';
            }

            // Check if it's a navigation error (might indicate session expired)
            if (
                error.message.includes('Navigation') ||
                error.message.includes('net::')
            ) {
                try {
                    const isOnLogin = await checkLoginPage(page, label);
                    if (isOnLogin) {
                        return 'expired';
                    }
                } catch (checkError) {
                    console.log(
                        `⚠️ [${label}] Could not verify login status: ${checkError.message}`,
                    );
                }
            }

            await page.waitForTimeout(pollInterval);
        }
    }
};

// Monitor session after reaching landing page (without timeout)
const monitorSession = async (page, label, sessionIndex) => {
    if (reportingControls.sessionProgress) {
        console.log(`🔐 [${label}] Starting session monitoring...`);
    }

    const pollInterval = 10000; // Check every 10 seconds for session info

    while (true) {
        try {
            // Check if page is closed first
            if (page.isClosed()) {
                console.log(
                    `🚪 [${label}] Page closed during session monitoring`,
                );
                return 'unknown';
            }

            // First check if session expired (redirected to login)
            const onLogin = await checkLoginPage(page, label);
            if (onLogin) {
                if (reportingControls.sessionProgress) {
                    console.log(
                        `🔁 [${label}] Session expired (redirected to login)`,
                    );
                }
                return 'expired';
            }

            // Check if we're still on the landing page
            const onLanding = await checkLandingPage(page, label);

            if (!onLanding) {
                // Double-check if we're on login page
                const backToLogin = await checkLoginPage(page, label);
                if (backToLogin) {
                    if (reportingControls.sessionProgress) {
                        console.log(
                            `🔁 [${label}] Session expired during active session`,
                        );
                    }
                    return 'expired';
                }

                if (reportingControls.sessionProgress) {
                    console.log(
                        `❓ [${label}] No longer on landing page - URL: ${page.url()}`,
                    );
                }
                return 'unknown';
            }

            // Extract session information
            await extractSessionInfo(page, label, sessionIndex);

            // Wait before next check
            await page.waitForTimeout(pollInterval);
        } catch (error) {
            if (reportingControls.sessionProgress) {
                console.log(
                    `⚠️ [${label}] Error during session monitoring: ${error.message}`,
                );
            }

            // Check if page is closed or context is destroyed
            if (page.isClosed()) {
                console.log(
                    `🚪 [${label}] Page closed during session monitoring error`,
                );
                return 'unknown';
            }

            // Check if it's a navigation error (might indicate session expired)
            if (
                error.message.includes('Navigation') ||
                error.message.includes('net::')
            ) {
                try {
                    const isOnLogin = await checkLoginPage(page, label);
                    if (isOnLogin) {
                        return 'expired';
                    }
                } catch (checkError) {
                    console.log(
                        `⚠️ [${label}] Could not verify login status: ${checkError.message}`,
                    );
                }
            }

            await page.waitForTimeout(pollInterval);
        }
    }
};

const loginAndMonitor = async (user, label) => {
    if (reportingControls.queueProgress) {
        console.log(`🧪 [${label}] Launching browser...`);
    }
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    const context = await browser.newContext({
        timeout: 30000,
    });
    const page = await context.newPage();

    // Add to sessions tracking with proper initialization
    const sessionData = {
        label,
        browser,
        context,
        page,
        status: 'starting',
        latestInfo: {},
    };
    sessions.push(sessionData);
    const sessionIndex = sessions.length - 1;
    updateSessionStatus(sessionIndex, 'starting');

    try {
        if (reportingControls.queueProgress) {
            console.log(`🌐 [${label}] Navigating to login page...`);
        }
        await page.goto(DOMAIN + '/privateLogin', { waitUntil: 'networkidle' });

        if (reportingControls.queueProgress) {
            console.log(`📝 [${label}] Filling in credentials...`);
        }
        await page.fill('#email', user.email);
        await page.fill('#password', user.password);

        if (reportingControls.queueProgress) {
            console.log(`🔓 [${label}] Submitting login form...`);
        }

        await Promise.all([
            page.waitForNavigation({
                waitUntil: 'networkidle',
                timeout: 30000,
            }),
            page.click('form button[type=submit]'),
        ]);

        if (reportingControls.queueProgress) {
            console.log(
                `🧭 [${label}] Post-login navigation completed. URL: ${page.url()}`,
            );
        }

        updateSessionStatus(sessionIndex, 'logged_in');

        // Wait for either queue or landing page to appear (without timeout)
        if (reportingControls.queueProgress) {
            console.log(
                `⏳ [${label}] Waiting for page to load (queue or landing page)...`,
            );
        }

        let isQueueVisible = false;
        let isLandingVisible = false;

        try {
            // Wait for either queue wrapper or landing wrapper to appear
            await Promise.race([
                page.locator('#queue-wrapper').waitFor(),
                page.locator('#landing-wrapper').waitFor(),
            ]);

            if (reportingControls.queueProgress) {
                console.log(
                    `✅ [${label}] Page content loaded, checking current state...`,
                );
            }

            isQueueVisible = await page.locator('#queue-wrapper').isVisible();
            isLandingVisible = await page
                .locator('#landing-wrapper')
                .isVisible();
        } catch (error) {
            if (reportingControls.queueProgress) {
                console.log(
                    `⚠️ [${label}] Error waiting for page elements: ${error.message}`,
                );
            }

            isQueueVisible = await page.locator('#queue-wrapper').isVisible();
            isLandingVisible = await page
                .locator('#landing-wrapper')
                .isVisible();
        }

        if (dontCareMode) {
            // Don't care mode - just confirm we reached queue/landing then exit
            if (isLandingVisible) {
                console.log(
                    `🎯 [${label}] Reached landing page - closing (don't-care mode)`,
                );
                updateSessionStatus(sessionIndex, 'active');
            } else if (isQueueVisible) {
                console.log(
                    `🪑 [${label}] Reached queue - closing (don't-care mode)`,
                );
                updateSessionStatus(sessionIndex, 'in_queue');
            } else {
                console.log(
                    `❓ [${label}] Unknown state - closing (don't-care mode)`,
                );
                updateSessionStatus(sessionIndex, 'unknown');
            }
            // Exit immediately without monitoring
            return;
        } else {
            if (isLandingVisible) {
                if (reportingControls.queueProgress) {
                    console.log(
                        `🎯 [${label}] Directly landed on main page - no queue!`,
                    );
                }
                updateSessionStatus(sessionIndex, 'active');

                // Monitor session indefinitely
                const sessionResult = await monitorSession(
                    page,
                    label,
                    sessionIndex,
                );
                updateSessionStatus(sessionIndex, sessionResult);
            } else if (isQueueVisible) {
                if (reportingControls.queueProgress) {
                    console.log(
                        `🪑 [${label}] Entered queue - starting monitoring...`,
                    );
                }
                updateSessionStatus(sessionIndex, 'in_queue');

                // Extract initial queue information
                await extractQueueInfo(page, label, sessionIndex);

                // Monitor queue until promotion (no timeout)
                const queueResult = await monitorQueue(
                    page,
                    label,
                    sessionIndex,
                );

                if (queueResult === 'promoted') {
                    updateSessionStatus(sessionIndex, 'active');
                    if (reportingControls.queueProgress) {
                        console.log(
                            `🎉 [${label}] Successfully promoted! Starting session monitoring...`,
                        );
                    }

                    // Monitor session after promotion
                    const sessionResult = await monitorSession(
                        page,
                        label,
                        sessionIndex,
                    );
                    updateSessionStatus(sessionIndex, sessionResult);
                } else if (queueResult === 'expired') {
                    updateSessionStatus(sessionIndex, 'expired');
                    if (reportingControls.queueProgress) {
                        console.log(
                            `❌ [${label}] Session expired while in queue`,
                        );
                    }
                } else {
                    updateSessionStatus(sessionIndex, 'unknown');
                    if (reportingControls.queueProgress) {
                        console.log(`❓ [${label}] Unknown queue exit state`);
                    }
                }
            } else {
                if (reportingControls.queueProgress) {
                    console.log(
                        `❓ [${label}] Neither queue nor landing page detected initially. URL: ${page.url()}`,
                    );

                    // Wait more with reasonable timeout and try again
                    console.log(
                        `🔍 [${label}] Waiting up to 30 seconds for content to appear...`,
                    );
                }

                try {
                    await Promise.race([
                        page
                            .locator('#queue-wrapper')
                            .waitFor({ timeout: 30000 }),
                        page
                            .locator('#landing-wrapper')
                            .waitFor({ timeout: 30000 }),
                    ]);

                    const isQueueVisibleRetry = await page
                        .locator('#queue-wrapper')
                        .isVisible();
                    const isLandingVisibleRetry = await page
                        .locator('#landing-wrapper')
                        .isVisible();

                    if (isLandingVisibleRetry) {
                        if (reportingControls.queueProgress) {
                            console.log(
                                `🎯 [${label}] Landing page appeared after additional wait!`,
                            );
                        }
                        updateSessionStatus(sessionIndex, 'active');
                        const sessionResult = await monitorSession(
                            page,
                            label,
                            sessionIndex,
                        );
                        updateSessionStatus(sessionIndex, sessionResult);
                    } else if (isQueueVisibleRetry) {
                        if (reportingControls.queueProgress) {
                            console.log(
                                `🪑 [${label}] Queue page appeared after additional wait!`,
                            );
                        }
                        updateSessionStatus(sessionIndex, 'in_queue');
                        await extractQueueInfo(page, label, sessionIndex);
                        const queueResult = await monitorQueue(
                            page,
                            label,
                            sessionIndex,
                        );

                        if (queueResult === 'promoted') {
                            updateSessionStatus(sessionIndex, 'active');
                            if (reportingControls.queueProgress) {
                                console.log(
                                    `🎉 [${label}] Successfully promoted! Starting session monitoring...`,
                                );
                            }
                            const sessionResult = await monitorSession(
                                page,
                                label,
                                sessionIndex,
                            );
                            updateSessionStatus(sessionIndex, sessionResult);
                        } else if (queueResult === 'expired') {
                            updateSessionStatus(sessionIndex, 'expired');
                            if (reportingControls.queueProgress) {
                                console.log(
                                    `❌ [${label}] Session expired while in queue`,
                                );
                            }
                        } else {
                            updateSessionStatus(sessionIndex, 'unknown');
                            if (reportingControls.queueProgress) {
                                console.log(
                                    `❓ [${label}] Unknown queue exit after retry`,
                                );
                            }
                        }
                    } else {
                        throw new Error(
                            'Still no valid page state after retry',
                        );
                    }
                } catch (retryError) {
                    if (reportingControls.queueProgress) {
                        console.log(
                            `❌ [${label}] Still unknown state after 30s wait: ${retryError.message}`,
                        );
                    }
                    updateSessionStatus(sessionIndex, 'unknown');

                    // Debug information
                    if (reportingControls.queueProgress) {
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
                        console.log(
                            `📄 [${label}] Page title: ${await page.title()}`,
                        );
                    }
                }
            }
        }
    } catch (err) {
        if (reportingControls.queueProgress) {
            console.error(`❗ [${label}] Error: ${err.message}`);
        }
        updateSessionStatus(sessionIndex, 'error');
    } finally {
        if (reportingControls.queueProgress) {
            console.log(
                `🚪 [${label}] Closing browser. Final status: ${sessions[sessionIndex]?.status}`,
            );
        }

        try {
            await browser.close();
        } catch (closeError) {
            console.log(
                `⚠️ [${label}] Error closing browser: ${closeError.message}`,
            );
        }

        // Remove from sessions and update statistics
        const index = sessions.findIndex((s) => s.label === label);
        if (index !== -1) {
            const finalStatus = sessions[index].status;
            sessions.splice(index, 1);
            console.log(
                `🗑️ [${label}] Session removed from tracking (was: ${finalStatus})`,
            );
        }

        // Refresh display after session removal
        refreshDisplay();
    }
};

const gracefulLogout = async () => {
    console.log('\n🚦 Gracefully logging out all active users...');

    const activeSessions = [...sessions];

    for (const { label, page, context, browser } of activeSessions) {
        try {
            if (page.isClosed()) {
                console.log(`⚠️ [${label}] Page already closed`);
                continue;
            }

            console.log(`🚪 [${label}] Attempting logout via UI...`);

            const logoutButton = await page.locator('#logout').first();
            if (await logoutButton.isVisible({ timeout: 2000 })) {
                await Promise.all([
                    page
                        .waitForNavigation({
                            waitUntil: 'networkidle',
                            timeout: 10000,
                        })
                        .catch(() => {}),
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

    sessions.length = 0;
    // Reset statistics
    Object.keys(statistics).forEach((key) => (statistics[key] = 0));

    console.log('🏁 All users logged out. Exiting...');
    setTimeout(() => process.exit(0), 1000);
};

const toggleReporting = (type) => {
    reportingControls[type] = !reportingControls[type];
    const status = reportingControls[type] ? 'ENABLED' : 'DISABLED';
    console.log(`\n🔧 ${type} reporting: ${status}`);
    refreshDisplay();
};

// Set up periodic display refresh
let displayInterval;

// Enhanced keyboard listener
process.stdin.setRawMode(true);
process.stdin.resume();
process.stdin.setEncoding('utf8');
process.stdin.on('data', async (key) => {
    if (key === 'q' || key === 'Q') {
        console.log('\n🛑 Logout requested by user...');
        if (displayInterval) clearInterval(displayInterval);
        await gracefulLogout();
    }
    if (key === 'r' || key === 'R') {
        toggleReporting('queueProgress');
    }
    if (key === 't' || key === 'T') {
        toggleReporting('sessionProgress');
    }
    if (key === 'h' || key === 'H') {
        console.log(
            `   Mode: ${dontCareMode ? 'SPAM (users login then disconnect)' : 'MONITOR (full tracking)'}`,
        );
        console.log('\n🎮 Available Controls:');
        console.log('   q/Q - Gracefully logout all users');
        console.log('   r/R - Toggle queue progress reporting');
        console.log('   t/T - Toggle session progress reporting');
        console.log('   h/H - Show this help');
        console.log('   Ctrl+C - Force exit');
        setTimeout(() => refreshDisplay(), 2000);
    }
    if (key === '\u0003') {
        // Ctrl+C
        console.log('\n🚨 Force exit requested...');
        process.exit(0);
    }
});

// Start periodic display refresh (every 5 seconds)
displayInterval = setInterval(() => {
    refreshDisplay();
}, 5000);

// Initial display
refreshDisplay();

// Launch all user sessions
async function main() {
    console.log(
        `🚀 Starting ${dontCareMode ? 'Spam Login' : 'Enhanced Queue'} Simulation...`,
    );
    console.log(`📊 Target users: ${userCount}`);
    console.log(`🌐 Domain: ${DOMAIN}`);
    console.log('⌨️  Press "h" for help');

    // Launch sessions in batches
    const promises = [];
    for (let i = 0; i < USERS.length; i += batchSize) {
        const batch = USERS.slice(i, i + batchSize);
        const batchIndex = Math.floor(i / batchSize);

        batch.forEach((user, userIndex) => {
            const globalIndex = i + userIndex;
            const label = `User ${startUser + globalIndex}`;
            const promise = new Promise((resolve) => {
                setTimeout(
                    async () => {
                        try {
                            await loginAndMonitor(user, label);
                            resolve();
                        } catch (error) {
                            console.error(
                                `💥 [${label}] Fatal error: ${error.message}`,
                            );
                            resolve();
                        }
                    },
                    batchIndex * batchDelay +
                        userIndex * (1000 / userPerSecond),
                );
            });
            promises.push(promise);
        });
    }

    // Wait for all sessions to complete
    await Promise.all(promises);

    console.log('🏁 All sessions completed');
    if (displayInterval) clearInterval(displayInterval);

    // Final statistics display
    console.log('\n📊 Final Statistics:');
    console.log(`🟢 Online Users: ${statistics.online}`);
    console.log(`🪑 Waiting in Queue: ${statistics.inQueue}`);
    console.log(`⏱️  Session Expired: ${statistics.sessionExpired}`);
    console.log(`❓ Unknown Exit: ${statistics.unknownExit}`);
    console.log(`❌ Errors: ${statistics.errors}`);

    // Keep process alive to show final stats
    setTimeout(() => {
        console.log('\n👋 Simulation completed. Exiting...');
        process.exit(0);
    }, 5000);
}

// Handle process termination
process.on('SIGINT', async () => {
    console.log('\n🛑 Received SIGINT. Initiating graceful shutdown...');
    if (displayInterval) clearInterval(displayInterval);
    await gracefulLogout();
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Received SIGTERM. Initiating graceful shutdown...');
    if (displayInterval) clearInterval(displayInterval);
    await gracefulLogout();
});

// Prevent unhandled promise rejections from crashing
process.on('unhandledRejection', (reason, promise) => {
    console.log('🚨 Unhandled Rejection at:', promise, 'reason:', reason);
    // Don't exit, just log it
});

// Start the main function
main().catch((error) => {
    console.error('💥 Main function error:', error);
    if (displayInterval) clearInterval(displayInterval);
    process.exit(1);
});
