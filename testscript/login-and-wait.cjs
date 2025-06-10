const { chromium } = require('playwright');

const PASSWORD = 'password123';
const dontCareMode = process.argv[2] === 'true' || process.argv[5] === '1';
const userCount = parseInt(process.argv[3], 10) || 20;
const userPerSecond = parseInt(process.argv[4], 10) || 1;
const batchSize = parseInt(process.argv[5], 10) || 50;
const batchDelay = parseInt(process.argv[6], 10) || 5000;
let DOMAIN = process.argv[7] || 'http://test.dev-staging-novatix.id';
const startUser = parseInt(process.argv[8], 10) || 1;

// Clean up domain URL
DOMAIN = DOMAIN.replace(/\/+$/, '');

const USERS = Array.from({ length: userCount }, (_, i) => ({
    email: `testuser${startUser + i}@example.com`,
    password: PASSWORD,
}));

const sessions = new Map(); // Use Map for better performance
const statistics = {
    online: 0,
    inQueue: 0,
    sessionExpired: 0,
    unknownExit: 0,
    errors: 0,
    completed: 0, // Track completed sessions
};

// Reporting controls
const reportingControls = {
    queueProgress: false,
    sessionProgress: false,
};

// Optimized display management
let lastDisplayUpdate = 0;
const DISPLAY_THROTTLE = dontCareMode ? 2000 : 1000; // Throttle display updates

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
        '',
        '🎮 Controls: Press "q" to quit, "h" for help',
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
        `✅ Completed: ${statistics.completed}`,
        `📱 Total Active Sessions: ${sessions.size}`,
        `🧠 Memory Usage: ${Math.round(process.memoryUsage().heapUsed / 1024 / 1024)}MB`,
    ];

    // Only show detailed session info in monitor mode and when count is reasonable
    if (!dontCareMode && sessions.size > 0 && sessions.size < 50) {
        stats.push('');
        stats.push('Recent Session Details:');
        let count = 0;
        for (const [label, sessionData] of sessions) {
            if (count >= 10) break; // Limit display to 10 sessions
            const { status, latestInfo } = sessionData;

            const statusEmoji = {
                starting: '🔄',
                logged_in: '🔑',
                in_queue: '🪑',
                active: '🟢',
                expired: '⏱️',
                unknown: '❓',
                error: '❌',
            };

            let statusText = status || 'running';
            if (latestInfo?.queueTimeLeft && status === 'in_queue') {
                statusText += ` (${latestInfo.queueTimeLeft})`;
            } else if (latestInfo?.sessionTimeLeft && status === 'active') {
                statusText += ` (${latestInfo.sessionTimeLeft})`;
            }

            stats.push(
                `   ${statusEmoji[status] || '❓'} ${label}: ${statusText}`,
            );
            count++;
        }
        if (sessions.size > 10) {
            stats.push(`   ... and ${sessions.size - 10} more sessions`);
        }
    }

    return stats.join('\n');
};

const refreshDisplay = () => {
    const now = Date.now();
    if (now - lastDisplayUpdate < DISPLAY_THROTTLE) return; // Throttle updates

    lastDisplayUpdate = now;
    process.stdout.write('\x1b[2J\x1b[H');
    console.log(displayStatistics());
    console.log('');
    console.log(displayFooter());
};

// Optimized session management
const updateSessionStatus = (sessionId, status, additionalInfo = null) => {
    const sessionData = sessions.get(sessionId);
    if (!sessionData) return;

    const oldStatus = sessionData.status;
    sessionData.status = status;
    sessionData.lastUpdate = Date.now();

    if (additionalInfo) {
        sessionData.latestInfo = {
            ...sessionData.latestInfo,
            ...additionalInfo,
        };
    }

    // Update statistics
    updateStatistics(oldStatus, status);

    // Throttled display refresh
    if (!dontCareMode || Math.random() < 0.1) {
        // Only refresh 10% of the time in don't care mode
        refreshDisplay();
    }
};

const updateStatistics = (oldStatus, newStatus) => {
    // Decrement old status
    if (oldStatus && statistics[getStatKey(oldStatus)] !== undefined) {
        statistics[getStatKey(oldStatus)] = Math.max(
            0,
            statistics[getStatKey(oldStatus)] - 1,
        );
    }

    // Increment new status
    if (newStatus && statistics[getStatKey(newStatus)] !== undefined) {
        statistics[getStatKey(newStatus)]++;
    }
};

const getStatKey = (status) => {
    const mapping = {
        active: 'online',
        in_queue: 'inQueue',
        expired: 'sessionExpired',
        unknown: 'unknownExit',
        error: 'errors',
    };
    return mapping[status] || status;
};

// Optimized browser management
const createBrowser = async () => {
    return await chromium.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage', // Reduce memory usage
            '--disable-gpu',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--memory-pressure-off', // Disable memory pressure signals
        ],
    });
};

// Immediate cleanup function for don't care mode
const immediateCleanup = async (browser, sessionId, label, status) => {
    try {
        await browser.close();
        sessions.delete(sessionId);
        statistics.completed++;
        updateStatistics(status, null); // Remove from old status count

        if (reportingControls.queueProgress) {
            console.log(`🗑️ [${label}] Immediately cleaned up (${status})`);
        }
    } catch (error) {
        console.log(`⚠️ [${label}] Cleanup error: ${error.message}`);
    }
};

const loginAndMonitor = async (user, label) => {
    const sessionId = `${label}-${Date.now()}`;
    let browser = null;

    try {
        if (reportingControls.queueProgress) {
            console.log(`🧪 [${label}] Starting...`);
        }

        browser = await createBrowser();
        const context = await browser.newContext({ timeout: 30000 });
        const page = await context.newPage();

        // Add to sessions tracking
        sessions.set(sessionId, {
            label,
            status: 'starting',
            latestInfo: {},
            lastUpdate: Date.now(),
        });

        updateSessionStatus(sessionId, 'starting');

        // Navigate and login
        await page.goto(DOMAIN + '/privateLogin', { waitUntil: 'networkidle' });
        await page.fill('#email', user.email);
        await page.fill('#password', user.password);

        await Promise.all([
            page.waitForNavigation({
                waitUntil: 'networkidle',
                timeout: 30000,
            }),
            page.click('form button[type=submit]'),
        ]);

        updateSessionStatus(sessionId, 'logged_in');

        // Wait for page to load
        await Promise.race([
            page.locator('#queue-wrapper').waitFor({ timeout: 10000 }),
            page.locator('#landing-wrapper').waitFor({ timeout: 10000 }),
        ]);

        const isQueueVisible = await page.locator('#queue-wrapper').isVisible();
        const isLandingVisible = await page
            .locator('#landing-wrapper')
            .isVisible();

        if (dontCareMode) {
            // Don't care mode - determine status and immediately cleanup
            let finalStatus = 'unknown';
            if (isLandingVisible) {
                finalStatus = 'active';
                console.log(`🎯 [${label}] Reached landing page - completing`);
            } else if (isQueueVisible) {
                finalStatus = 'in_queue';
                console.log(`🪑 [${label}] Reached queue - completing`);
            }

            updateSessionStatus(sessionId, finalStatus);
            await immediateCleanup(browser, sessionId, label, finalStatus);
            return;
        }

        // Monitor mode - continue with full monitoring
        if (isLandingVisible) {
            updateSessionStatus(sessionId, 'active');
            await monitorSession(page, label, sessionId);
        } else if (isQueueVisible) {
            updateSessionStatus(sessionId, 'in_queue');
            const queueResult = await monitorQueue(page, label, sessionId);

            if (queueResult === 'promoted') {
                updateSessionStatus(sessionId, 'active');
                await monitorSession(page, label, sessionId);
            } else {
                updateSessionStatus(sessionId, queueResult);
            }
        } else {
            updateSessionStatus(sessionId, 'unknown');
        }
    } catch (err) {
        console.error(`❗ [${label}] Error: ${err.message}`);
        updateSessionStatus(sessionId, 'error');
    } finally {
        // Final cleanup
        if (browser) {
            try {
                await browser.close();
            } catch (closeError) {
                console.log(
                    `⚠️ [${label}] Browser close error: ${closeError.message}`,
                );
            }
        }

        sessions.delete(sessionId);
        statistics.completed++;

        if (reportingControls.queueProgress) {
            console.log(`🔚 [${label}] Session completed and cleaned up`);
        }
    }
};

// Simplified monitoring functions (keeping the core logic but removing some logging)
const monitorQueue = async (page, label, sessionId) => {
    const pollInterval = 5000;

    while (true) {
        try {
            if (page.isClosed()) return 'unknown';

            const onLogin = await checkLoginPage(page);
            if (onLogin) return 'expired';

            const inQueue = await checkQueuePage(page);
            if (!inQueue) {
                const onLanding = await checkLandingPage(page);
                if (onLanding) return 'promoted';
                return 'unknown';
            }

            // Update queue info less frequently
            if (Math.random() < 0.3) {
                // Only 30% of the time
                await extractQueueInfo(page, label, sessionId);
            }

            await page.waitForTimeout(pollInterval);
        } catch (error) {
            if (page.isClosed()) return 'unknown';
            await page.waitForTimeout(pollInterval);
        }
    }
};

const monitorSession = async (page, label, sessionId) => {
    const pollInterval = 10000;

    while (true) {
        try {
            if (page.isClosed()) return 'unknown';

            const onLogin = await checkLoginPage(page);
            if (onLogin) return 'expired';

            const onLanding = await checkLandingPage(page);
            if (!onLanding) return 'unknown';

            // Update session info less frequently
            if (Math.random() < 0.2) {
                // Only 20% of the time
                await extractSessionInfo(page, label, sessionId);
            }

            await page.waitForTimeout(pollInterval);
        } catch (error) {
            if (page.isClosed()) return 'unknown';
            await page.waitForTimeout(pollInterval);
        }
    }
};

// Keep the utility functions but simplified
const checkLandingPage = async (page) => {
    try {
        await page.locator('#landing-wrapper').waitFor({ timeout: 2000 });
        return true;
    } catch {
        return false;
    }
};

const checkQueuePage = async (page) => {
    try {
        await page.locator('#queue-wrapper').waitFor({ timeout: 2000 });
        return true;
    } catch {
        return false;
    }
};

const checkLoginPage = async (page) => {
    try {
        const url = page.url();
        return url.includes('/login') || url.includes('/privateLogin');
    } catch {
        return false;
    }
};

const extractQueueInfo = async (page, label, sessionId) => {
    try {
        const queueInfo = await page.evaluate(() => {
            const timeElement = document.querySelector(
                '#queue-wrapper [style*="color"] p',
            );
            return {
                timeLeft: timeElement?.textContent?.trim() || null,
            };
        });

        if (queueInfo.timeLeft) {
            updateSessionStatus(sessionId, 'in_queue', {
                queueTimeLeft: queueInfo.timeLeft,
            });
        }

        return queueInfo;
    } catch (error) {
        return null;
    }
};

const extractSessionInfo = async (page, label, sessionId) => {
    try {
        const sessionInfo = await page.evaluate(() => {
            const sessionTimer = document.querySelector('#session-timer');
            let sessionTimeLeft = null;

            if (sessionTimer) {
                const timerText = sessionTimer.textContent?.trim();
                if (timerText?.includes('Remaining Time:')) {
                    sessionTimeLeft = timerText
                        .replace('Remaining Time:', '')
                        .trim();
                }
            }

            return { timeLeft: sessionTimeLeft };
        });

        if (sessionInfo.timeLeft) {
            updateSessionStatus(sessionId, 'active', {
                sessionTimeLeft: sessionInfo.timeLeft,
            });
        }

        return sessionInfo;
    } catch (error) {
        return null;
    }
};

// Optimized cleanup functions
const gracefulLogout = async () => {
    console.log('\n🚦 Gracefully shutting down...');

    const allSessions = Array.from(sessions.values());
    const cleanupPromises = allSessions.map(async (sessionData) => {
        try {
            // Just close browsers, don't worry about UI logout in mass cleanup
            if (
                sessionData.browser &&
                !sessionData.browser.contexts().length === 0
            ) {
                await sessionData.browser.close();
            }
        } catch (error) {
            // Ignore errors during cleanup
        }
    });

    await Promise.allSettled(cleanupPromises);

    sessions.clear();
    Object.keys(statistics).forEach((key) => (statistics[key] = 0));

    console.log('🏁 Cleanup completed. Exiting...');
    process.exit(0);
};

// Optimized keyboard handling
process.stdin.setRawMode(true);
process.stdin.resume();
process.stdin.setEncoding('utf8');
process.stdin.on('data', async (key) => {
    if (key === 'q' || key === 'Q') {
        await gracefulLogout();
    }
    if (key === 'r' || key === 'R') {
        reportingControls.queueProgress = !reportingControls.queueProgress;
        console.log(
            `\n🔧 Queue progress: ${reportingControls.queueProgress ? 'ENABLED' : 'DISABLED'}`,
        );
    }
    if (key === 't' || key === 'T') {
        reportingControls.sessionProgress = !reportingControls.sessionProgress;
        console.log(
            `\n🔧 Session progress: ${reportingControls.sessionProgress ? 'ENABLED' : 'DISABLED'}`,
        );
    }
    if (key === 'h' || key === 'H') {
        console.log(
            '\n🎮 Controls: q=quit, r=toggle queue reports, t=toggle session reports, h=help',
        );
    }
    if (key === '\u0003') {
        process.exit(0);
    }
});

// Optimized display refresh - less frequent in don't care mode
const displayInterval = setInterval(
    () => {
        refreshDisplay();
    },
    dontCareMode ? 5000 : 2000,
);

// Memory monitoring and cleanup
if (dontCareMode) {
    setInterval(() => {
        if (global.gc) {
            global.gc();
        }

        // Clean up old completed sessions from memory
        const now = Date.now();
        for (const [sessionId, sessionData] of sessions) {
            if (now - sessionData.lastUpdate > 30000) {
                // 30 seconds old
                sessions.delete(sessionId);
            }
        }
    }, 10000);
}

// Initial display
refreshDisplay();

// Main execution
async function main() {
    console.log(
        `🚀 Starting ${dontCareMode ? 'Spam Login' : 'Enhanced Queue'} Simulation...`,
    );
    console.log(`📊 Target users: ${userCount}`);
    console.log(`🌐 Domain: ${DOMAIN}`);

    const promises = [];
    for (let i = 0; i < USERS.length; i += batchSize) {
        const batch = USERS.slice(i, i + batchSize);
        const batchIndex = Math.floor(i / batchSize);

        batch.forEach((user, userIndex) => {
            const globalIndex = i + userIndex;
            const label = `User${startUser + globalIndex}`;

            const promise = new Promise((resolve) => {
                setTimeout(
                    async () => {
                        try {
                            await loginAndMonitor(user, label);
                        } catch (error) {
                            console.error(
                                `💥 [${label}] Fatal: ${error.message}`,
                            );
                        }
                        resolve();
                    },
                    batchIndex * batchDelay +
                        userIndex * (1000 / userPerSecond),
                );
            });

            promises.push(promise);
        });
    }

    await Promise.all(promises);

    console.log('\n🏁 All sessions completed');
    console.log(
        `📊 Final Stats: ${statistics.completed} completed, ${statistics.errors} errors`,
    );

    clearInterval(displayInterval);
    setTimeout(() => process.exit(0), 2000);
}

// Error handling
process.on('SIGINT', gracefulLogout);
process.on('SIGTERM', gracefulLogout);
process.on('unhandledRejection', (reason) => {
    console.log('🚨 Unhandled Rejection:', reason);
});

// Start
main().catch(console.error);
