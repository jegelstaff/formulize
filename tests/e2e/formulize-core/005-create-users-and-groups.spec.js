const { test, expect } = require('../test-fixtures');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from './config';
import { login } from '../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

test.describe('Create Groups', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
  	await page.getByRole('link', { name: 'arrow System' }).click();
  	await page.getByRole('link', { name: 'Groups Groups' }).click();
  })

	test('Create Ancient History group', async ({ page }) => {
		await page.locator('#name').fill('Ancient History');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await expect(page.getByRole('rowgroup')).toContainText('Ancient History');
	})

	test('Create Modern History group', async ({ page }) => {
		await page.locator('#name').fill('Modern History');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await expect(page.getByRole('rowgroup')).toContainText('Modern History');
	})

	test('Create Curators group', async ({ page }) => {
		await page.locator('#name').fill('Curators');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await expect(page.getByRole('rowgroup')).toContainText('Curators');
	})

	test('Create Staff Only group', async ({ page }) => {
		await page.locator('#name').fill('Staff Only');
		await page.locator('#desc_tarea').fill('No curators');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await expect(page.getByRole('rowgroup')).toContainText('Staff Only');
	})
})

test.describe('Create Users', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
  	await page.getByRole('link', { name: 'arrow System' }).click();
		await page.getByRole('link', { name: 'Edit Users Edit Users' }).click();
  })

	test('Create Ancient History user', async ({ page }) => {
		await page.locator('#login_name').fill('ahstaff');
		await page.locator('#uname').fill('Ancient History Staff');
		await page.locator('#email').fill('ahstaff@museum.formulize.net');
 		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['Ancient History', 'Registered Users', 'Staff Only']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#buttonbar').getByRole('link', { name: 'Find users' }).click();
		await page.locator('#user_email').fill('ahstaff@museum.formulize.net');
		await page.getByRole('button', { name: 'Submit' }).click();
  	await expect(page.getByRole('rowgroup')).toContainText('Ancient History Staff');
	})

	test('Create Modern History user', async ({ page }) => {
		await page.locator('#login_name').fill('mhstaff');
		await page.locator('#uname').fill('Modern History Staff');
		await page.locator('#email').fill('mhstaff@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['Modern History', 'Registered Users', 'Staff Only']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#buttonbar').getByRole('link', { name: 'Find users' }).click();
		await page.locator('#user_email').fill('mhstaff@museum.formulize.net');
		await page.getByRole('button', { name: 'Submit' }).click();
  	await expect(page.getByRole('rowgroup')).toContainText('Modern History Staff');
	})

	test('Create Curator One user', async ({ page }) => {
		await page.locator('#login_name').fill('curator1');
		await page.locator('#uname').fill('Curator One');
		await page.locator('#email').fill('c1@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['Ancient History', 'Curators', 'Modern History', 'Registered Users']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#buttonbar').getByRole('link', { name: 'Find users' }).click();
		await page.locator('#user_email').fill('c1@museum.formulize.net');
		await page.getByRole('button', { name: 'Submit' }).click();
  	await expect(page.getByRole('rowgroup')).toContainText('Curator One');
	})

	test('Create Curator Two user', async ({ page }) => {
		await page.locator('#login_name').fill('curator2');
		await page.locator('#uname').fill('Curator Two');
		await page.locator('#email').fill('c2@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['Ancient History', 'Curators', 'Modern History', 'Registered Users']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#buttonbar').getByRole('link', { name: 'Find users' }).click();
		await page.locator('#user_email').fill('c2@museum.formulize.net');
		await page.getByRole('button', { name: 'Submit' }).click();
  	await expect(page.getByRole('rowgroup')).toContainText('Curator Two');
	})
})
