// @ts-check
const { defineConfig, devices } = require('@playwright/test');
const { resolveBaseUrl } = require('./base-url');

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({

  globalSetup: require.resolve('./htaccess-setup.js'),

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
		[['list'], ['github'], ['html', { outputFolder: './test-report' }], ['./php-error-reporter.js'], ['./entry-lock-reporter.js']] : [['list'], ['html', { outputFolder: './test-report' }], ['./php-error-reporter.js'], ['./entry-lock-reporter.js']],
	/* Adjust the timeout for slow tests. E2E_TEST_TIMEOUT in .env overrides it, for slow machines. */
	timeout: Number(process.env.E2E_TEST_TIMEOUT) || 120000,
  retries: 0,
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. Follows the port
       Docker published the site on. See base-url.js. */
    baseURL: resolveBaseUrl(),

    /* A page that never finishes loading fails here, saying so, rather than using up the whole test and
       showing as net::ERR_ABORTED when the test closes it. E2E_NAVIGATION_TIMEOUT in .env overrides it. */
    navigationTimeout: Number(process.env.E2E_NAVIGATION_TIMEOUT) || 60000,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'retain-on-failure',

		/* Video */
		video: 'retain-on-failure',

		/* Ignore HTTPS errors */
		ignoreHTTPSErrors: true,
  },

	expect: {
    // Maximum time expect() should wait for the condition to be met.
    timeout: 20000,
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
      use: { ...devices['Desktop Chrome'] }
    }
  ]
});

