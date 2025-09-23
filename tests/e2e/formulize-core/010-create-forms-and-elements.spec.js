const { test, expect } = require('@playwright/test')
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from './config';
import { login, saveAdminForm } from '../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.getByRole('link', { name: 'Admin' }).click();
})

test('Create Artifacts Form', async ({ page }) => {
	await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await expect(page.locator('input[name="forms-title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Artifacts');
	await page.locator('#applications-name').fill('Museum');
	await saveAdminForm(page);
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
 	await expect(page.getByRole('tabpanel')).toContainText('Artifacts(id:');
})

test.describe('Artifacts Elements', async () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByText('Artifacts').first().click();
	  await page.getByRole('link', { name: 'Elements' }).first().click();
	})

	test('Create ID Number Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('ID Number');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_id_number');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: ID Number (Textbox)');
	});

	test('Create Short Name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Short name');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_short_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Short name (Textbox)');
	});

	test('Create Full description Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Multi-line text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Multi-line text box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Full description');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_full_description');
		await page.locator('textarea[name="elements-ele_desc"]').fill('For use in brochures, on websites, etc');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('checkbox', { name: 'Display this element using a' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Full description (Multi-line text box)');
	});

	test('Create Height Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Height');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_height');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Height (Textbox)');
	});

	test('Create Width Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Width');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_width');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Width (Textbox)');
	});

	test('Create Depth Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Depth');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_depth');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Depth (Textbox)');
	});

	test('Create Dimensions Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Table of existing elements', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Table of existing elements)');
		await page.locator('input[name="elements-ele_caption"]').fill('Dimensions');
		await page.locator('select[name="orderpref"]').selectOption('After: Full description');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Height,Width,Depth');
		await page.getByLabel('Choose the first element,').selectOption('Height');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Dimensions (Table of existing elements)');
	});

	test('Create Year Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Year');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Year (Textbox)');
	});

	test('Create Era Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Radio buttons', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Radio buttons)');
		await page.locator('input[name="elements-ele_caption"]').fill('Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_era');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('CE');
  	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
  	await page.locator('input[name="ele_value\\[1\\]"]').fill('BCE');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Era (Radio buttons)');
	});

	test('Create Date of origin Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Table of existing elements', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Table of existing elements)');
		await page.locator('input[name="elements-ele_caption"]').fill('Date of origin');
		await page.locator('select[name="orderpref"]').selectOption('After: Depth');
  	await page.getByRole('link', { name: 'Options' }).click();
	  await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Year,Era');
	  await page.getByLabel('Choose the first element,').selectOption('Year');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Date of origin (Table of existing elements)');
	});

	test('Create Year-Era Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Value derived from other elements', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Value derived from other elements)');
  	await page.locator('input[name="elements-ele_caption"]').fill('Year-Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year_era');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('div:nth-child(5) > pre:nth-child(2)').click();
  	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$value = "artifacts_year"."artifacts_era";');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Year-Era (Value derived from other elements)');
	});

	test('Create Date of acquisition Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Date box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Date box)');
  	await page.locator('input[name="elements-ele_caption"]').fill('Date of acquisition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_date_of_acquisition');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Date of acquisition (Date box)');
	});

	test('Create Donated to museum Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Simple yes/no radio buttons', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Yes/No radio buttons)');
   	await page.locator('input[name="elements-ele_caption"]').fill('Was the artifact donated to the museum?');
   	await page.locator('input[name="elements-ele_colhead"]').fill('Donated to museum');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donated_to_museum');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Donated to museum (Yes/No radio buttons)');
	});
})

test('Create Donors Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await expect(page.locator('input[name="forms-title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Donors');
	await saveAdminForm(page);
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
 	await expect(page.getByRole('tabpanel')).toContainText('Donors(id:');
})

test.describe('Donors Elements', async () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(1).click();
	})

	test('Create Type of donor Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Radio buttons', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Radio buttons)');
		await page.locator('input[name="elements-ele_caption"]').fill('Type of donor');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_type_of_donor');
		await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('button', { name: 'Add' }).click();
   	await page.locator('input[name="ele_value\\[0\\]"]').click();
   	await page.locator('input[name="ele_value\\[0\\]"]').fill('Individual');
   	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
   	await page.locator('input[name="ele_value\\[1\\]"]').fill('Organization');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Type of donor (Radio buttons)');
	});

	test('Create First name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('First name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_first_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Individual');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: First name (Textbox)');
	});

	test('Create Last name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Last name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_last_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Individual');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Last name (Textbox)');
	});

	test('Create Organization name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Organization name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_organization_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Organization');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Organization name (Textbox)');
	});

	test('Create Name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Value derived from other elements', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Value derived from other elements)');
  	await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_name');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.locator('.CodeMirror-scroll').click();
   	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$type = "donors_type_of_donor";\nif($type == \'Individual\') {\n$value = "donors_first_name".\' \'."donors_last_name";\n} else {\n$value = "donors_organization_name";\n}');
		await page.getByRole('link', { name: 'Name & Settings' }).click();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Name (Value derived from other elements)');
	});

	test('Create Phone number Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Phone Number', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Phone Number)');
		await page.locator('input[name="elements-ele_caption"]').fill('Phone number');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_phone');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Phone number (Phone Number)');
	});

	test('Create Email Address Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Email Address', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Email Address)');
		await page.locator('input[name="elements-ele_caption"]').fill('Email Address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_email');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Email Address (Email Address)');
	});

	test('Create Street address Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Multi-line text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Multi-line text box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Street address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_street_address');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('textbox', { name: 'Rows*' }).fill('2');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Street address (Multi-line text box)');
	});

	test('Create Province Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Province List', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Province List)');
		await page.locator('input[name="elements-ele_caption"]').fill('Province');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_province');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Province (Province List)');
	});

	test('Create Postal code Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Postal code');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_postal_code');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Postal code (Textbox)');
	});

	test('Create Province, Postal code Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Table of existing elements', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Table of existing elements)');
		await page.locator('input[name="elements-ele_caption"]').fill('Province, Postal code');
   	await page.locator('select[name="orderpref"]').selectOption('After: Street address');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByLabel('Choose the first element,').selectOption('Province');
   	await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill(',');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Province, Postal code (Table of existing elements)');
	});

	test('Create Favourite colour Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Colorpicker', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Color picker)');
		await page.locator('input[name="elements-ele_caption"]').fill('Favourite colour');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_favourite_colour');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Favourite colour (Color picker)');
	});

	test('Create Backgrounder / Resume Element', async ({ page }) => {
		await page.getByRole('link', { name: 'File upload box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (File upload box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Backgrounder / Resume');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_backgrounder_resume');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Backgrounder / Resume (File upload box)');
	});
})

test('Create Collections Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await expect(page.locator('input[name="forms-title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Collections');
	await saveAdminForm(page);
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
 	await expect(page.getByRole('tabpanel')).toContainText('Collections(id:');
})

test.describe('Collections Elements', async () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(1).click();
	})

	test('Create Name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Name (Textbox)');
	});

	test('Create Suitable for Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Check boxes', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Check boxes)');
		await page.locator('input[name="elements-ele_caption"]').fill('Suitable for');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_suitable_audience');
		await page.locator('input[name="elements-ele_colhead"]').fill('Suitable audience');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('button', { name: 'Add' }).click();
		await page.locator('input[name="ele_value\\[0\\]"]').click();
		await page.locator('input[name="ele_value\\[0\\]"]').fill('Children');
		await page.locator('input[name="ele_value\\[1\\]"]').fill('Adults');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Suitable audience (Check boxes)');
	});

})

test('Create Exhibits Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await expect(page.locator('input[name="forms-title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Exhibits');
	await saveAdminForm(page);
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
 	await expect(page.getByRole('tabpanel')).toContainText('Exhibits(id:');
})

test.describe('Exhibits Elements', async () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(3).click();
	})

	test('Create Name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Name (Textbox)');
	});

	test('Create Curator Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Curator');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_curator');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('{USERNAMES}');
  	await saveAdminForm(page);
  	await page.locator('#element-formlink_scope').selectOption('Curators');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Curator (Dropdown box)');
	});

	test('Create Collections Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Exhibit includes artifacts from these collections');
		await page.locator('input[name="elements-ele_colhead"]').fill('Collections');
  	await page.locator('input[name="elements-ele_handle"]').fill('exhibits_collections');
		await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('This is a "autocompletion"').click();
  	await page.locator('#elements_multiple_allowed_auto').check();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('Collections: Name');
		await page.locator('#element-formlink_scope').selectOption(['Ancient History', 'Modern History']);
	  await page.getByText('Yes. Only use groups that the').click();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Collections (Autocomplete box)');
	});

	test('Create Artifacts Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Artifacts shown in the exhibit');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Artifacts');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_artifacts');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('This is a "autocompletion"').click();
  	await page.getByText('Allowed').nth(2).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('Artifacts: Short name');
  	await saveAdminForm(page);
		await page.locator('[id="elements-ele_value\\[17\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		// @todo Figure out why this isn't working
		// await page.locator('#new_formlinkfilter_element').selectOption('Collections');
		// await page.locator('#new_formlinkfilter_op').selectOption('LIKE');
	  // await page.locator('#new_formlinkfilter_term').fill('{exhibits_collections}');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Artifacts (Autocomplete box)');
	});
})

test('Create Surveys Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await expect(page.locator('input[name="forms-title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Surveys');
	await saveAdminForm(page);
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
 	await expect(page.getByRole('tabpanel')).toContainText('Surveys(id:');
})

test.describe('Surveys Elements', async () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(4).click();
	})

	test('Create Respondent name Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Text box', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Textbox)');
		await page.locator('input[name="elements-ele_caption"]').fill('Your name');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Respondent name');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Respondent name (Textbox)');
	});

	test('Create Exhibit Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Which exhibit did you see?');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_exhibit');
		await page.locator('input[name="elements-ele_colhead"]').fill('Exhibit');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('Exhibits: Name');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Exhibit (Dropdown box)');
	});

	test('Create Favourite artifact Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Which was your favourite artifact?');
  	await page.locator('input[name="elements-ele_colhead"]').fill('Favourite artifact');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_favourite_artifact');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('Artifacts: Short name');
		await saveAdminForm(page);
		await page.locator('[id="elements-ele_value\\[optionsLimitByElement\\]"]').selectOption('Exhibits: Artifacts shown in the exhibit');
	  await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await saveAdminForm(page);
  	await page.locator('#new_optionsLimitByElementFilter_element').selectOption('Name');
  	await page.locator('#new_optionsLimitByElementFilter_term').fill('{surveys_exhibit}');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Favourite artifact (Dropdown box)');
	});

	test('Create Rating Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('How would you rate the exhibit overall?');
  	await page.locator('input[name="elements-ele_colhead"]').fill('Rating');
  	await page.locator('input[name="elements-ele_handle"]').fill('surveys_rating');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByRole('button', { name: 'Add' }).click({ clickCount: 4});
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('Disappointing');
  	await page.locator('input[name="ele_value\\[1\\]"]').fill('Average');
		await page.locator('input[name="ele_value\\[2\\]"]').fill('Good');
		await page.locator('input[name="ele_value\\[3\\]"]').fill('Excellent');
  	await page.locator('input[name="ele_value\\[4\\]"]').fill('Mind blowing!');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Rating (Dropdown box)');
	});
})

test.describe('Artifacts linked fields', async () => {
	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).first().click();
	})

	test('Create Donor Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Dropdowns, autocompletes, list boxes (HTML <select>)', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Dropdown box or List box)');
		await page.locator('input[name="elements-ele_caption"]').fill('Donor');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donor');
  	await page.getByRole('link', { name: 'Options' }).click();
    await page.getByText('The values that people have').click();
    await page.locator('#formlink').selectOption('Donors: Name');
    await page.getByRole('link', { name: 'Display Settings' }).click();
    await page.locator('#new_elementfilter_element').selectOption('Donated to museum');
    await page.locator('#new_elementfilter_term').fill('Yes');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Donor (Dropdown box)');
	});

	test('Create Condition Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Range Slider', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Range Slider)');
		await page.locator('input[name="elements-ele_caption"]').fill('Condition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_condition');
    await page.locator('textarea[name="elements-ele_desc"]').fill('1 - terrible condition, 10 - like new');
    await page.getByRole('link', { name: 'Options' }).click();
    await page.getByRole('textbox', { name: 'Maximum value*' }).fill('10');
    await page.getByRole('textbox', { name: 'Slider step size*' }).fill('1');
    await page.getByRole('textbox', { name: 'Default value*' }).fill('5');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Condition (Range Slider)');
	});

	test('Create Collections Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Check boxes', exact: true }).click();
		await expect(page.getByRole('heading')).toContainText('Element: New element (Check boxes)');
		await page.locator('input[name="elements-ele_caption"]').fill('Collections');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_collections');
    await page.getByRole('link', { name: 'Options' }).click();
    await page.getByText('The values that people have').click();
    await page.locator('#formlink').selectOption('Collections: Name');
	  await page.locator('#element-formlink_scope').selectOption(['Ancient History', 'Modern History']);
	  await page.getByText('Yes. Only use groups that the').click();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Element: Collections (Check boxes)');
	});
})
