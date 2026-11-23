// @ts-check
const { defineConfig, devices } = require('@playwright/test');
const { resolveBaseUrl } = require('./base-url');

/* The browser the tests run in: chromium (the default), firefox or webkit. Each needs installing first,
   with npx playwright install <browser>. */
const BROWSER_DEVICES = { chromium: 'Desktop Chrome', firefox: 'Desktop Firefox', webkit: 'Desktop Safari' };
const E2E_BROWSER = process.env.E2E_BROWSER || 'chromium';
if (!BROWSER_DEVICES[E2E_BROWSER]) {
  throw new Error(`E2E_BROWSER is "${E2E_BROWSER}". It must be one of: ${Object.keys(BROWSER_DEVICES).join(', ')}`);
}

/* Firefox uses its own user agent. The one Playwright's Firefox profile gives reaches the page and not the service
   worker, which loads the pages (modules/formulize/pwa/worker.js), so the server would see two browsers. A security
   token only works from the browser it was made for, so every save would be refused. */
const device = { ...devices[BROWSER_DEVICES[E2E_BROWSER]] };
if (E2E_BROWSER === 'firefox') {
  delete device.userAgent;
}

/* Where the HTML report goes. Each run empties the folder first, so CI gives each of its runs a folder of its own. */
const REPORT_DIR = process.env.E2E_REPORT_DIR || './test-report';

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
		[['list'], ['github'], ['html', { outputFolder: REPORT_DIR }], ['./php-error-reporter.js'], ['./entry-lock-reporter.js']] : [['list'], ['html', { outputFolder: REPORT_DIR }], ['./php-error-reporter.js'], ['./entry-lock-reporter.js']],
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

  /* One browser per run, chosen by E2E_BROWSER, so a plain run doesn't go through the suite once for each.
     CI runs a job for each browser (.github/workflows/e2e-test-manual.yml). */
  projects: [
    {
      name: E2E_BROWSER,
      use: device
    }
  ]
});

