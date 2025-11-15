// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: './',
	outputDir: './test-results',
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Opt out of parallel tests on CI. */
  workers: 1,
	/* If a test fails, bail on everything */
	maxFailures: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: process.env.GITHUB_ACTIONS ?
		[['list'], ['github'], ['html', { outputFolder: './test-report' }]] : [['list'], ['html', { outputFolder: './test-report' }]],
	/* Adjust the timeout for slow tests */
	timeout: 120000,
  retries: 0,
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: 'http://localhost:8080',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'retain-on-failure',

		/* Video */
		video: 'on',

		/* Ignore HTTPS errors */
		ignoreHTTPSErrors: true,
  },

	expect: {
    // Maximum time expect() should wait for the condition to be met.
    timeout: 120000,
    toHaveScreenshot: {
      // An acceptable amount of pixels that could be different, unset by default.
      maxDiffPixels: 10,
    },

    toMatchSnapshot: {
      // An acceptable ratio of pixels that are different to the
      // total amount of pixels, between 0 and 1.
      maxDiffPixelRatio: 0.1,
    },
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ]
});

