const { chromium } = require('playwright');

const PASSWORD = 'password123';
const userCount = parseInt(process.argv[2], 10) || 20;
const DOMAIN = process.argv[3] || 'http://test.dev-staging-novatix.id';
const DELAY = parseInt(process.argv[4], 10) || 20000;

const USERS = Array.from({ length: userCount }, (_, i) => ({
    email: `testuser${i + 1}@example.com`,
    password: PASSWORD,
}));

const sessions = [];

const checkLandingPage = async (page, label) => {
    try {
        await page.locator('#landing-wrapper').waitFor({ timeout: 120_000 });
        console.log(`✅ [${label}] Landing page detected.`);
        return true;
    } catch {
        console.log(`⏳ [${label}] Landing page not detected yet.`);
        return false;
    }
};

const loginAndMonitor = async (user, label) => {
    console.log(`🧪 [${label}] Launching browser...`);
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();
    sessions.push({ label, browser, context, page });

    try {
        console.log(`🌐 [${label}] Navigating to login page...`);
        await page.goto(DOMAIN + '/privateLogin');

        console.log(`📝 [${label}] Filling in credentials...`);
        await page.fill('#email', user.email);
        await page.fill('#password', user.password);

        console.log(`🔓 [${label}] Submitting login form...`);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'load', timeout: 10_000 }),
            page.click('form button[type=submit]'),
        ]);

        // Wait for page to change or DOM mutation
        console.log(`🧭 [${label}] Waiting for post-login state...`);
        await page.waitForFunction(
            (expectedUrl) => window.location.href === expectedUrl,
            DOMAIN + '/',
            { timeout: 120_000 },
        );

        console.log(
            `✅ [${label}] Login successful. Current URL: ${page.url()}`,
        );

        // wait the page to stabilize
        await page.waitForTimeout(DELAY);

        const isQueueVisible = await page.locator('#queue-wrapper').isVisible();
        const isLandingVisible = await page
            .locator('#landing-wrapper')
            .isVisible();

        if (isLandingVisible) {
            console.log(`🎯 [${label}] Landed on landing page.`);
        } else if (isQueueVisible) {
            console.log(`🪑 [${label}] Entered queue.`);
            const timeout = 120_000;
            const startTime = Date.now();
            let landed = false;

            while (!landed && Date.now() - startTime < timeout) {
                landed = await checkLandingPage(page, label);
                if (!landed) await page.waitForTimeout(2000);
            }

            if (landed) {
                console.log(
                    `🎯 [${label}] Proceeded from queue to landing page.`,
                );
            } else {
                console.log(`⏱️  [${label}] Still in queue or timed out.`);
            }
        } else {
            console.log(`❌ [${label}] Unknown post-login state.`);
        }

        // Session monitor
        const landed = await page.locator('#landing-wrapper').isVisible();
        if (landed) {
            try {
                await page.waitForURL('**/login', { timeout: 600_000 });
                console.log(
                    `🔁 [${label}] Session expired (redirected to login).`,
                );
            } catch {
                console.log(`🟢 [${label}] Still logged in after 10 minutes.`);
            }
        }
    } catch (err) {
        console.error(`❗ [${label}] Error: ${err.message}`);
    } finally {
        console.log(`🚪 [${label}] Closing browser.`);
        await browser.close();
        const index = sessions.findIndex((s) => s.label === label);
        if (index !== -1) sessions.splice(index, 1);
    }
};
const gracefulLogout = async () => {
    console.log('\n🚦 Logging out all users...');
    for (const { label, page, context, browser } of sessions) {
        try {
            console.log(`🚪 [${label}] Attempting logout via UI...`);
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'load', timeout: 5_000 }),
                page.click('#logout', { timeout: 5_000 }),
            ]);
            console.log(`✅ [${label}] Logged out via UI.`);
        } catch {
            try {
                console.log(
                    `📡 [${label}] Sending POST /logout as fallback...`,
                );
                const res = await context.request.post(
                    'http://dev-staging-novatix.id/auth/logout',
                );
                if (res.ok()) {
                    console.log(`✅ [${label}] Logout via POST succeeded.`);
                } else {
                    console.log(
                        `⚠️ [${label}] Logout via POST failed (status ${res.status()}).`,
                    );
                }
            } catch (err) {
                console.log(`❌ [${label}] Logout error: ${err.message}`);
            }
        }

        await browser.close();
        console.log(`🔚 [${label}] Browser closed.`);
    }

    console.log('🏁 All users logged out. Exiting.');
    process.exit(0);
};

// Keyboard listener for logout
process.stdin.setRawMode(true);
process.stdin.resume();
process.stdin.setEncoding('utf8');
process.stdin.on('data', async (key) => {
    if (key === 'q') await gracefulLogout();
    if (key === '\u0003') process.exit(); // Ctrl+C
});

(async () => {
    console.log('🚀 Starting user sessions...');
    console.log('Press "q" to log out users gracefully.');
    await Promise.all(
        USERS.map((user, i) => loginAndMonitor(user, `User ${i + 1}`)),
    );
})();
