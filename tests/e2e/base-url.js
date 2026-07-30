// Works out which copy of Formulize the end-to-end tests should talk to.
//
// The Docker setup can publish the web container on a port other than 8080, so
// that several copies of Formulize can run at the same time. The port that was
// chosen lives in FORMULIZE_WEB_PORT, in the .env file at the root of the
// repository. That is the only place it is set: the tests read the same setting
// Docker does, and follow the site automatically.
//
// This file is deliberately plain CommonJS so that both playwright.config.js and
// the test suite's config.js can use it, and there is only one place where the
// address of the site under test gets worked out.

const path = require('path');

// The .env file at the root of the repository, two levels up from tests/e2e.
// dotenv does not overwrite variables that are already set, so setting
// FORMULIZE_WEB_PORT on the command line still wins over the file, for a one-off
// run against a different port.
require('dotenv').config({ path: path.resolve(__dirname, '../../.env') });

const DEFAULT_WEB_PORT = 8080;

function resolveBaseUrl() {
	const port = process.env.FORMULIZE_WEB_PORT || DEFAULT_WEB_PORT;
	return `http://localhost:${port}`;
}

module.exports = { resolveBaseUrl, DEFAULT_WEB_PORT };
