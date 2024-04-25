# Testing

## e2e testings

We use the [Playwright](https://playwright.dev/) framework for configuring and executing e2e tests across browsers.

Core formulize e2e test are located in the `/tests/e2e/formulize-core` directory.

When writing your own tests for your site/application place them in an appropriately named directory inside `/tests/e2e`.

### Running core tests locally

#### Requirements
* Node.js (18+)
* Docker (optional)

#### Steps
1. Get a local instance of the application set up which is accessible at `http://localhost:8080`. We recommend using the included docker-compose file to quickly spin up an environment using docker.
2. Navigate to the tests folder `/tests/e2e` and perform an `npm install`
3. While still in the `/tests/e2e` directory run the test with `npm t` This will run the tests in headless mode. If you'd like to have the browser load to watch the progress use `npm run test:debug`.
