// Records walkthrough videos. Run from tests/e2e with `npm run walkthrough -- <file>`. See
// docs/recording_walkthroughs.md.
//
// WALKTHROUGHS_DIR    where to look for *.walkthrough.js files, if not here. Use an absolute path.
// WALKTHROUGH_OUTPUT  where the videos go, if not walkthroughs/videos/.
// WALKTHROUGH_SLOWMO  milliseconds added before each browser action, 120 unless set.
const path = require('path');
const { defineConfig, devices } = require('@playwright/test');
const { resolveBaseUrl } = require('../base-url');

const size = { width: 1280, height: 800 };

module.exports = defineConfig({
	testDir: process.env.WALKTHROUGHS_DIR ? path.resolve(process.env.WALKTHROUGHS_DIR) : __dirname,
	testMatch: /\.walkthrough\.js$/,
	outputDir: path.join(__dirname, 'results'),
	// A walkthrough is slow on purpose, and usually changes data, so it runs once, alone.
	timeout: Number(process.env.WALKTHROUGH_TIMEOUT) || 15 * 60 * 1000,
	workers: 1,
	retries: 0,
	fullyParallel: false,
	reporter: [['list'], [path.join(__dirname, 'walkthrough-reporter.js')], [path.join(__dirname, '../entry-lock-reporter.js')]],
	expect: { timeout: 30000 },
	use: {
		...devices['Desktop Chrome'],
		baseURL: resolveBaseUrl(),
		viewport: size,
		video: { mode: 'on', size },
		launchOptions: { slowMo: Number(process.env.WALKTHROUGH_SLOWMO ?? 120) },
		navigationTimeout: 60000,
		actionTimeout: 30000,
		trace: 'retain-on-failure',
		ignoreHTTPSErrors: true,
	},
});
