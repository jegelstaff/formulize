import 'dotenv/config'
import { resolveBaseUrl } from '../base-url.js'

export const E2E_TEST_ADMIN_USERNAME = process.env.E2E_TEST_ADMIN_USERNAME || 'admin'
export const E2E_TEST_ADMIN_PASSWORD = process.env.E2E_TEST_ADMIN_PASSWORD || 'password'
// Follows the port Docker published the site on, so there is nothing to set here
// when a copy of Formulize is running somewhere other than 8080. See base-url.js.
export const E2E_TEST_BASE_URL = resolveBaseUrl()
