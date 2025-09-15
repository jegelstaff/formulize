const { test, expect } = require('@playwright/test')
import { saveChanges } from '../utils';
import { loginAs } from '../utils';

test.describe('Data Entry', () => {

	test('Create Collections', async ({ page }) => {

		await loginAs('ahstaff', page);



	})
});


