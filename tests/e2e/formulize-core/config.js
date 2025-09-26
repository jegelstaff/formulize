import 'dotenv/config'

export const E2E_TEST_ADMIN_USERNAME = process.env.E2E_TEST_ADMIN_USERNAME || 'admin'
export const E2E_TEST_ADMIN_PASSWORD = process.env.E2E_TEST_ADMIN_PASSWORD || 'password'
export const E2E_TEST_BASE_URL = process.env.E2E_TEST_BASE_URL || 'http://localhost:8080'
