const { test, expect } = require('@playwright/test')
import { loginAsAdmin, saveChanges } from '../utils';

async function setStandardPermissions(page) {
	await page.locator('#groups').selectOption(['4', '5', '6']);
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
	await saveChanges(page);
}

async function setGlobalPermissions(page) {
	await page.locator('#groups').selectOption(['4', '5', '6']);
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
	await saveChanges(page);
}

async function setAnonPermissions(page) {
	await page.locator('#groups').selectOption(['3']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Anonymous Users' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Update entries made by themselves').check();
	await saveChanges(page);
}

test.describe('Set Permissions, Menu Entries, Screen elements, Procedures', () => {

	test('Set Permissions on first five forms', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=1&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=2&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=3&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=4&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=5&aid=1&tab=permissions');
		await setGlobalPermissions(page);
		await setAnonPermissions(page);

	}),
	test('Set Anon Access to Survey Form', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=screen&aid=1&fid=5&sid=9');
	  await page.getByText('No, only permission to view').click();
		await saveChanges(page);

	}),
	test('Set Menu Entries', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=menu%20entries');
	  await page.getByRole('link', { name: 'Artifacts' }).click();
    await page.waitForTimeout(500);
	  await page.locator('#groups0').selectOption(['1', '4', '5']);
		await page.locator('#defaultScreenGroups0').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Donors' }).click();
	  await page.waitForTimeout(500);
	  await page.locator('#groups1').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Collections' }).click();
		await page.waitForTimeout(500);
	  await page.locator('#groups2').selectOption(['1', '4', '5']);
  	await page.getByRole('link', { name: 'Exhibits' }).click();
		await page.waitForTimeout(500);
	  await page.locator('#groups3').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Surveys' }).click();
		await page.waitForTimeout(500);
	  await page.locator('#groups4').selectOption(['1', '4', '5']);
		await saveChanges(page);

	}),
	test('Set columns and elements for screens', async ({ page }) => {

		await loginAsAdmin(page);

		// Donors form
		await page.goto('/modules/formulize/admin/ui.php?page=screen&sid=3&fid=2&aid=1');
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
  	await expect(page.getByText('Title for page number')).toBeVisible();
  	await page.getByLabel('Form elements to display on').selectOption(['14', '15', '16', '17', '18', '19', '20', '21', '24', '22', '23', '25', '26']);
  	await saveChanges(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-0').selectOption('donors_name');
  	await page.locator('#cols-1').selectOption('donors_phone');
  	await page.locator('#cols-2').selectOption('donors_email');
		await saveChanges(page);

		// Artifacts form
  	await page.getByRole('link', { name: 'Museum' }).click();
  	await page.locator('#form-details-box-1-1').getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Artifact', exact: true }).click();
  	await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
  	await page.getByLabel('Form elements to display on').selectOption(['1', '2', '3', '7', '4', '5', '6', '10', '8', '9', '12', '13', '27', '28', '31']);
  	await saveChanges(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
	  await page.getByRole('button', { name: 'Add Column' }).click();
		await page.getByRole('button', { name: 'Add Column' }).click();
		await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-0').selectOption('artifacts_id_number');
  	await page.locator('#cols-1').selectOption('artifacts_short_name');
  	await page.locator('#cols-2').selectOption('artifacts_year_era');
    await page.locator('#cols-3').selectOption('artifacts_date_of_acquisition');
    await page.locator('#cols-4').selectOption('artifacts_collections');
  	await page.getByRole('row', { name: '≡ Artifacts: Date of' }).getByRole('img').click();
  	await saveChanges(page);

		// Collections form
  	await page.getByRole('link', { name: 'Museum' }).click();
  	await page.locator('#form-details-box-1-3').getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Collection', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await page.getByLabel('Form elements to display on').selectOption(['29', '30']);
		await saveChanges(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Collections' }).click();
	  await page.getByRole('link', { name: 'Collections' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-0').selectOption('collections_name');
  	await page.getByRole('row', { name: '≡ No element selected Search' }).getByRole('img').click();
  	await saveChanges(page);

		// Exhibits form
	  await page.getByRole('link', { name: 'Museum' }).click();
  	await page.locator('#form-details-box-1-4').getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Exhibit', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
   	await page.getByLabel('Form elements to display on').selectOption(['32', '33', '34', '35']);
		await saveChanges(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-0').selectOption('exhibits_name');
		await page.locator('#cols-1').selectOption('exhibits_curator');
		await page.locator('#cols-2').selectOption('exhibits_collections');
		await page.locator('#cols-3').selectOption('exhibits_artifacts');
		await saveChanges(page);

		// Surveys form
  	await page.getByRole('link', { name: 'Museum' }).click();
  	await page.locator('#form-details-box-1-5').getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Survey', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await page.getByLabel('Form elements to display on').selectOption(['36', '37', '38', '39']);
		await saveChanges(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-0').selectOption('creation_datetime');
		await page.locator('#cols-1').selectOption('surveys_your_name');
		await page.locator('#cols-2').selectOption('surveys_exhibit');
		await page.locator('#cols-3').selectOption('surveys_favourite_artifact');
		await page.locator('#cols-4').selectOption('surveys_rating');
		await saveChanges(page);

	}),
	test('Procedures for Artifacts form', async ({ page }) => {

		await loginAsAdmin(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&aid=1&fid=1&tab=procedures');
  	await page.getByRole('group', { name: 'Before Saving' }).getByRole('textbox').fill('// standardize the artifacts ID numbers\nif(!$artifacts_id_number) {\n\t$idLength = strlen($entry_id);\n\t$zeros = 3 - $idLength;\n\t$zeros = $zeros < 0 ? 0 : $zeros;\n\t$artifacts_id_number = "M";\n\tfor($i=1;$i<=$zeros;$i++) {\n\t\t$artifacts_id_number .= "0";\n\t}\n\t$artifacts_id_number .= $entry_id;\n}');
		await saveChanges(page);
	})
});


