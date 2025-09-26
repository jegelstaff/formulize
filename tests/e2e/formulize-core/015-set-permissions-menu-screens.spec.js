const { test, expect } = require('@playwright/test')
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from './config';
import { login, saveAdminForm } from '../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.getByRole('link', { name: 'Admin' }).click();
})

async function setStandardPermissions(page) {
	await page.locator('#groups').selectOption(['Ancient History', 'Modern History', 'Curators']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Ancient History' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Update entries made by their').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Delete entries made by their').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View entries by other users').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Update entries made by their').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Delete entries made by their').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View entries by other users').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View entries by all other').check();
}

async function setGlobalPermissions(page) {
	await page.locator('#groups').selectOption(['Ancient History', 'Modern History', 'Curators']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Ancient History' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View entries by all other').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View entries by all other').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View entries by all other').check();
	await saveAdminForm(page);
}

async function setAnonPermissions(page) {
	await page.locator('#groups').selectOption(['Anonymous Users']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Anonymous Users' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Update entries made by themselves').check();
	await saveAdminForm(page);
}

test.describe('Set Permissions', () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	})

	test('Set permissions for Artifacts', async ({ page }) => {
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setStandardPermissions(page);
		await saveAdminForm(page);
	})

	test('Set permissions for Donors', async ({ page }) => {
		await page.getByText('Donors').first().click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setStandardPermissions(page);
		await saveAdminForm(page);
	})

	test('Set permissions for Collections', async ({ page }) => {
		await page.getByText('Collections').first().click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setStandardPermissions(page);
		await saveAdminForm(page);
	})

	test('Set permissions for Exhibits', async ({ page }) => {
		await page.getByText('Exhibits').nth(2).click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setStandardPermissions(page);
		await saveAdminForm(page);
	})

	test('Set permissions for Surveys', async ({ page }) => {
		await page.getByText('Surveys').nth(2).click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setGlobalPermissions(page);
		await saveAdminForm(page);
		await setAnonPermissions(page);
		await saveAdminForm(page);
	})

	test('Set Anonymous permissions to Survey Form', async ({ page }) => {
		await page.getByText('Surveys').nth(2).click();
		await page.locator('#form-details-box-1-5').getByRole('link', { name: 'Screens' }).first().click();
		await page.getByRole('link', { name: 'Survey', exact: true }).click();
	  await page.getByText('No, only permission to view').click();
		await saveAdminForm(page);
	})
})


test.describe('Set Menu Entries', () => {

	test('Set Menu Entry for Artifacts', async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
	  await page.getByRole('link', { name: 'Artifacts' }).click();
 	  await page.locator('#groups0').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await page.locator('#defaultScreenGroups0').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await saveAdminForm(page);
	})
	test('Set Menu Entry for Donors', async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
	  await page.getByRole('link', { name: 'Artifacts' }).click();
	  await page.getByRole('link', { name: 'Donors' }).click();
 	  await page.locator('#groups1').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await saveAdminForm(page);
	})
	test('Set Menu Entry for Collections', async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
	  await page.getByRole('link', { name: 'Collections' }).click();
 	  await page.locator('#groups2').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await saveAdminForm(page);
	})
	test('Set Menu Entry for Exhibits', async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
	  await page.getByRole('link', { name: 'Exhibits' }).click();
 	  await page.locator('#groups3').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await saveAdminForm(page);
	})
	test('Set Menu Entry for Surveys', async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
	  await page.getByRole('link', { name: 'Surveys' }).click();
 	  await page.locator('#groups4').selectOption(['Webmasters', 'Ancient History', 'Modern History']);
		await saveAdminForm(page);
	})
})

test.describe('Set columns and elements for screens', () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	})

	test('Artifacts form screen', async ({ page }) => {
		await page.locator('#form-details-box-1-1').getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Artifact', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
  	await page.getByLabel('Form elements to display on').selectOption([
			'Artifacts: ID Number',
			'Artifacts: Short name',
			'Artifacts: Full description',
			'Artifacts: Dimensions',
			'Artifacts: Height',
			'Artifacts: Width',
			'Artifacts: Depth',
			'Artifacts: Date of origin',
			'Artifacts: Year',
			'Artifacts: Era',
			'Artifacts: Date of acquisition',
			'Artifacts: Donated to museum',
			'Artifacts: Donor',
			'Artifacts: Condition',
			'Artifacts: Collections'
		]);
  	await saveAdminForm(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('artifacts_id_number');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-1').selectOption('artifacts_short_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-2').selectOption('artifacts_year_era');
  	await page.getByRole('button', { name: 'Add Column' }).click();
    await page.locator('#cols-3').selectOption('artifacts_collections');
		await saveAdminForm(page);
	})

	test('Donors form screen', async ({ page }) => {
		await page.locator('#form-details-box-1-2').getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Donor', exact: true }).click();
  	await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
  	await page.getByLabel('Form elements to display on').selectOption([
			'Donors: Type of donor',
			'Donors: First name',
			'Donors: Last name',
			'Donors: Organization name',
			'Donors: Name',
			'Donors: Phone number',
			'Donors: Email Address',
			'Donors: Street address',
			'Donors: Province, Postal code',
			'Donors: Province',
			'Donors: Postal code',
			'Donors: Favourite colour'
		]);
  	await saveAdminForm(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.locator('#cols-0').selectOption('donors_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-1').selectOption('donors_phone');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-2').selectOption('donors_email');
  	await saveAdminForm(page);
	})

	test('Collections form screen', async ({ page }) => {
		await page.locator('#form-details-box-1-3').getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Collection', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
		await page.getByLabel('Form elements to display on').selectOption([
			'Collections: Name',
			'Collections: Suitable audience'
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Collections' }).click();
	  await page.getByRole('link', { name: 'Collections' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.locator('#cols-0').selectOption('collections_name');
  	await saveAdminForm(page);
	})

	test('Exhibits form screen', async ({ page }) => {
		await page.locator('#form-details-box-1-4').getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Exhibit', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
   	await page.getByLabel('Form elements to display on').selectOption([
			'Exhibits: Name',
			'Exhibits: Curator',
			'Exhibits: Collections',
			'Exhibits: Artifacts'
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('exhibits_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-1').selectOption('exhibits_curator');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-2').selectOption('exhibits_collections');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-3').selectOption('exhibits_artifacts');
		await saveAdminForm(page);
	})

	test('Surveys form screen', async ({ page }) => {
		await page.locator('#form-details-box-1-5').getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Survey', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
		await page.getByLabel('Form elements to display on').selectOption([
			'Surveys: Respondent name',
			'Surveys: Exhibit',
			'Surveys: Favourite artifact',
			'Surveys: Rating'
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('creation_datetime');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-1').selectOption('surveys_your_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-2').selectOption('surveys_exhibit');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-3').selectOption('surveys_favourite_artifact');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-4').selectOption('surveys_rating');
		await saveAdminForm(page);
	})

	test('Procedures for Artifacts form', async ({ page }) => {
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Procedures' }).click();
		await page.getByRole('group', { name: 'After Saving' }).locator('span').first().click();
  	await page.getByRole('group', { name: 'After Saving' }).getByRole('textbox').press('ControlOrMeta+a');
   	await page.getByRole('group', { name: 'After Saving' }).getByRole('textbox').fill('<?php\n\n// standardize the artifacts ID numbers\nif(!$artifacts_id_number) {\n\t$idLength = strlen($entry_id);\n\t$zeros = 3 - $idLength;\n\t$zeros = $zeros < 0 ? 0 : $zeros;\n\t$artifacts_id_number = "M";\n\tfor($i=1;$i<=$zeros;$i++) {\n\t\t$artifacts_id_number .= "0";\n\t}\n\t$artifacts_id_number .= $entry_id;\n\tformulize_writeEntry([\'artifacts_id_number\' => $artifacts_id_number], $entry_id);\n}');
		await saveAdminForm(page);
	})
});


