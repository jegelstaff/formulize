const { test, expect } = require('@playwright/test')
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from './config';
import { login, saveAdminForm, waitForAdminPageReady, addElementForm, ElementType, openElementAccordion } from '../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.getByRole('link', { name: 'Admin' }).click();
})

test('Create Artifacts Form', async ({ page }) => {
	await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page)
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Artifacts');
	await page.locator('#applications-name').fill('Museum');
	await page.locator('input[name="pi_new_caption"]').fill('ID Number');
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

	test('Update ID Number Element', async ({ page }) => {
		await openElementAccordion(page, 'ID Number Text Box');
		await page.getByRole('link', { name: 'Configure' }).click();
		await expect(page.getByRole('heading')).toContainText('Element: ID Number (Text Box)');
		await waitForAdminPageReady(page)
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
		await saveAdminForm(page);
	});

	test('Create Short Name Element', async ({ page }) => {
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Short name');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_short_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Short name');
	});

	test('Create Full description Element', async ({ page }) => {
		await addElementForm(page, ElementType.textarea);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Full description');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_full_description');
		await page.locator('textarea[name="elements-ele_desc"]').fill('For use in brochures, on websites, etc');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('checkbox', { name: 'Display this element using a' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Full description');
	});

	test('Create Height Element', async ({ page }) => {
		await addElementForm(page, ElementType.textNumber);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Height');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_height');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Height');
	});

	test('Create Width Element', async ({ page }) => {
		await addElementForm(page, ElementType.textNumber);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Width');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_width');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Width');
	});

	test('Create Depth Element', async ({ page }) => {
		await addElementForm(page, ElementType.textNumber);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Depth');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_depth');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Depth');
	});

	test('Create Dimensions Element', async ({ page }) => {
		await addElementForm(page, ElementType.grid);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Dimensions');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Height,Width,Depth');
		await page.getByLabel('Choose the first element,').selectOption('Height');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Dimensions');
		// Complex assertion could be added to verify that the position/order of the element has been assigned immediately before the Height box in the form
	});

	test('Create Year Element', async ({ page }) => {
		await addElementForm(page, ElementType.textNumber);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Year');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Options' }).click();
	  await page.getByRole('textbox', { name: 'Separate thousands with this' }).fill('');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Year');
	});

	test('Create Era Element', async ({ page }) => {
		await addElementForm(page, ElementType.radio);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_era');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('CE');
  	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
  	await page.locator('input[name="ele_value\\[1\\]"]').fill('BCE');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Era');
	});

	test('Create Date of origin Element', async ({ page }) => {
		await addElementForm(page, ElementType.grid);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Date of origin');
  	await page.getByRole('link', { name: 'Options' }).click();
	  await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Year,Era');
	  await page.getByLabel('Choose the first element,').selectOption('Year');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Date of origin');
		// Complex assertion could be added to verify that the position/order of the element has been assigned immediately before the Year box in the form
	});

	test('Create Year-Era Element', async ({ page }) => {
		await addElementForm(page, ElementType.derived);
		await waitForAdminPageReady(page)
  	await page.locator('input[name="elements-ele_caption"]').fill('Year-Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year_era');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('div:nth-child(5) > pre:nth-child(2)').click();
  	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$value = "artifacts_year"."artifacts_era";');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Year-Era');
	});

	test('Create Date of acquisition Element', async ({ page }) => {
		await addElementForm(page, ElementType.date);
		await waitForAdminPageReady(page)
  	await page.locator('input[name="elements-ele_caption"]').fill('Date of acquisition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_date_of_acquisition');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Date of acquisition');
	});

	test('Create Donated to museum Element', async ({ page }) => {
		await addElementForm(page, ElementType.radioYN)
		await waitForAdminPageReady(page)
   	await page.locator('input[name="elements-ele_caption"]').fill('Was the artifact donated to the museum?');
   	await page.locator('input[name="elements-ele_colhead"]').fill('Donated to museum');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donated_to_museum');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('radio', { name: 'No' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Donated to museum');
	});
})

test('Create Donors Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page)
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
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
		await addElementForm(page, ElementType.radio);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Type of donor');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_type_of_donor');
		await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('button', { name: 'Add' }).click();
   	await page.locator('input[name="ele_value\\[0\\]"]').click();
   	await page.locator('input[name="ele_value\\[0\\]"]').fill('Individual');
   	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
   	await page.locator('input[name="ele_value\\[1\\]"]').fill('Organization');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Type of donor');
	});

	test('Create First name Element', async ({ page }) => {
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('First name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_first_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Individual');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('First name');
	});

	test('Create Last name Element', async ({ page }) => {
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Last name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_last_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Individual');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Last name');
	});

	test('Create Organization name Element', async ({ page }) => {
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Organization name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_organization_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('Type of donor');
   	await page.locator('#new_elementfilter_term').fill('Organization');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Organization name');
	});

	test('Create Name Element', async ({ page }) => {
		await addElementForm(page, ElementType.derived);
		await waitForAdminPageReady(page)
  	await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_name');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.locator('.CodeMirror-scroll').click();
   	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$type = "donors_type_of_donor";\nif($type == \'Individual\') {\n$value = "donors_first_name".\' \'."donors_last_name";\n} else {\n$value = "donors_organization_name";\n}');
		await page.getByRole('link', { name: 'Name & Settings' }).click();
	  await page.getByRole('radio', { name: 'Yes' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Name');
	});

	test('Create Phone number Element', async ({ page }) => {
		await addElementForm(page, ElementType.textPhone);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Phone number');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_phone');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Phone number');
	});

	test('Create Email Address Element', async ({ page }) => {
		await addElementForm(page, ElementType.textEmail);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Email Address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_email');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Email Address');
	});

	test('Create Street address Element', async ({ page }) => {
		await addElementForm(page, ElementType.textarea);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Street address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_street_address');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('textbox', { name: 'Rows*' }).fill('2');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Street address');
	});

	test('Create Province Element', async ({ page }) => {
		await addElementForm(page, ElementType.selectProvince);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Province');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_province');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Province');
	});

	test('Create Postal code Element', async ({ page }) => {
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Postal code');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_postal_code');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Postal code');
	});

	test('Create Province, Postal code Element', async ({ page }) => {
		await addElementForm(page, ElementType.grid);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Province, Postal code');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByLabel('Choose the first element,').selectOption('Province');
   	await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill(',');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Province, Postal code');
	});

	test('Create Favourite colour Element', async ({ page }) => {
		await addElementForm(page, ElementType.colorpick);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Favourite colour');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_favourite_colour');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Favourite colour');
	});

	test('Create Backgrounder / Resume Element', async ({ page }) => {
		await addElementForm(page, ElementType.fileupload);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Backgrounder / Resume');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_backgrounder_resume');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Backgrounder / Resume');
	});
})

test('Create Collections Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page)
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
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
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Name');
	});

	test('Create Suitable for Element', async ({ page }) => {
		await addElementForm(page, ElementType.checkbox);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Suitable for');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_suitable_audience');
		await page.locator('input[name="elements-ele_colhead"]').fill('Suitable audience');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('button', { name: 'Add' }).click();
		await page.locator('input[name="ele_value\\[0\\]"]').click();
		await page.locator('input[name="ele_value\\[0\\]"]').fill('Children');
		await page.locator('input[name="ele_value\\[1\\]"]').fill('Adults');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Suitable audience');
	});

})

test('Create Exhibits Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page)
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
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
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Name');
	});

	test('Create Curator Element', async ({ page }) => {
		await addElementForm(page, ElementType.selectUsers);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Curator');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_curator');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#element-formlink_scope').selectOption('Curators');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Curator');
	});

	test('Create Collections Element', async ({ page }) => {
		await addElementForm(page, ElementType.autocompleteLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Exhibit includes artifacts from these collections');
		await page.locator('input[name="elements-ele_colhead"]').fill('Collections');
  	await page.locator('input[name="elements-ele_handle"]').fill('exhibits_collections');
		await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#formlink').selectOption('Collections: Name');
		await page.getByRole('radio', { name: 'Allowed', exact: true }).check();
		await page.locator('#element-formlink_scope').selectOption(['Ancient History', 'Modern History']);
	  await page.getByText('Yes. Only use groups that the').click();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Collections');
	});

	test('Create Artifacts Element', async ({ page }) => {
		await addElementForm(page, ElementType.autocompleteLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Artifacts shown in the exhibit');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Artifacts');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_artifacts');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#formlink').selectOption('Artifacts: Short name');
		await page.getByRole('radio', { name: 'Allowed', exact: true }).check();
		await page.getByRole('checkbox', { name: 'Create a Subform interface' }).check();
  	await saveAdminForm(page);
		await page.locator('[id="elements-ele_value\\[17\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Artifacts');
	});
})

test('Create Surveys Form', async ({ page }) => {
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page)
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
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
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Your name');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Respondent name');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Respondent name');
	});

	test('Create Exhibit Element', async ({ page }) => {
		await addElementForm(page, ElementType.selectLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Which exhibit did you see?');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_exhibit');
		await page.locator('input[name="elements-ele_colhead"]').fill('Exhibit');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#formlink').selectOption('Exhibits: Name');
		await page.getByRole('checkbox', { name: 'Create a Subform interface' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Exhibit');
	});

	test('Create Favourite artifact Element', async ({ page }) => {
		await addElementForm(page, ElementType.selectLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Which was your favourite artifact?');
  	await page.locator('input[name="elements-ele_colhead"]').fill('Favourite artifact');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_favourite_artifact');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#formlink').selectOption('Artifacts: Short name');
		await saveAdminForm(page);
		await page.locator('[id="elements-ele_value\\[optionsLimitByElement\\]"]').selectOption('Exhibits: Artifacts shown in the exhibit');
	  await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['Artifacts: ID Number', 'Artifacts: Short name']);
		await saveAdminForm(page);
  	await page.locator('#new_optionsLimitByElementFilter_element').selectOption('Name');
  	await page.locator('#new_optionsLimitByElementFilter_term').fill('{surveys_exhibit}');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Favourite artifact');
	});

	test('Create Rating Element', async ({ page }) => {
		await addElementForm(page, ElementType.select);
		await waitForAdminPageReady(page)
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
		await expect(page.getByRole('heading')).toContainText('Rating');
	});

	test('Create Flagged Element', async ({ page }) => {
		await addElementForm(page, ElementType.radioYN);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Flagged to the staff');
		await page.locator('input[name="elements-ele_colhead"]').fill('Flagged');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('radio', { name: 'No' }).check();
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('select[name="elements_ele_display[]"]').selectOption([{label: 'Webmasters'},{label: 'Curators'}]);
		await saveAdminForm(page);
	});

	test('Create Comments Element', async ({ page }) => {
		await addElementForm(page, ElementType.textarea);
		await waitForAdminPageReady(page);
  	await page.locator('input[name="elements-ele_caption"]').fill('Staff comments');
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('select[name="elements_ele_display[]"]').selectOption([{label: 'Webmasters'},{label: 'Curators'},{label: 'Staff Only'}]);
		await saveAdminForm(page);
	});

})

test.describe('Set Special Survey Permissions', async () => {
	test('Adjust permission for flagged surveys', async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	  await page.locator('div[id^=form-details-box-]').nth(4).getByText('Surveys').first().click();
  	await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await page.locator('#groups').selectOption({label: 'Staff Only'});
		await page.getByRole('button', { name: 'Show permissions for these' }).click();
		await waitForAdminPageReady(page);
		await page.locator('div.groupselectionbox').nth(1).locator('select').first().selectOption({label: 'Flagged'});
		await page.locator('div.groupselectionbox').nth(1).locator('input').first().fill('Yes');
		await saveAdminForm(page);
	});
})

test.describe('Create linked fields and settings', async () => {
	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	})

	test('Create Donor Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Elements' }).first().click();
		await addElementForm(page, ElementType.selectLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Donor');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donor');
  	await page.getByRole('link', { name: 'Options' }).click();
    await page.locator('#formlink').selectOption('Donors: Name');
    await page.getByRole('link', { name: 'Display Settings' }).click();
    await page.locator('#new_elementfilter_element').selectOption('Donated to museum');
    await page.locator('#new_elementfilter_term').fill('Yes');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Donor');
	});

	test('Create Condition Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Elements' }).first().click();
		await addElementForm(page, ElementType.slider);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Condition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_condition');
    await page.locator('textarea[name="elements-ele_desc"]').fill('1 - terrible condition, 10 - like new');
    await page.getByRole('link', { name: 'Options' }).click();
    await page.getByRole('textbox', { name: 'Maximum value*' }).fill('10');
    await page.getByRole('textbox', { name: 'Slider step size*' }).fill('1');
    await page.getByRole('textbox', { name: 'Default value*' }).fill('5');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Condition');
	});

	test('Create Collections Element', async ({ page }) => {
		await page.getByRole('link', { name: 'Elements' }).first().click();
		await addElementForm(page, ElementType.checkboxLinked);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Collections');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_collections');
    await page.getByRole('link', { name: 'Options' }).click();
    await page.locator('#formlink').selectOption('Collections: Name');
	  await page.locator('#element-formlink_scope').selectOption(['Ancient History', 'Modern History']);
	  await page.getByText('Yes. Only use groups that the').click();
		await page.getByRole('checkbox', { name: 'Create a Subform interface' }).check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Collections');
	});

	test('Filter exhibit artifacts by collection artifacts', async ({ page }) => {
		await page.getByRole('link', { name: 'Elements' }).nth(3).click();
		await openElementAccordion(page, 'Artifacts Linked Autocomplete List');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#new_formlinkfilter_element').selectOption('Collections');
  	await page.locator('#new_formlinkfilter_op').selectOption('LIKE');
  	await page.locator('#new_formlinkfilter_term').fill('{exhibits_collections}');
  	await saveAdminForm(page);
	});
});

test.describe('Create/Update Subform Interfaces', async () => {

	test('Update Exhibits Subform Interface in Artifacts', async ({ page }) => {
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).first().click();
		await openElementAccordion(page, 'Exhibits Embeded Form (list');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.locator('input[name="elements-ele_caption"]').fill('Appears in these exhibits');
		await page.locator('select[name="orderpref"]').selectOption('bottom');
		await page.getByRole('link', { name: 'Options' }).click();
		await waitForAdminPageReady(page); // had a timing issue here once? might need to wait for admin page ready after every tab click?? this is why we used to have retries 2.
		await page.getByRole('radio', { name: 'No', exact: true }).check();
		await page.getByRole('checkbox', { name: 'Show the Delete button' }).uncheck();
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('ul#multi-screen-tree li.leaf').first().locator('input[type=checkbox]').check();
		await saveAdminForm(page);
	});

	test('Create Artifacts screen for Donors Subform Interface', async ({ page }) => {
		await page.getByRole('link', { name: 'Museum' }).click();
		await page.getByRole('link', { name: 'Screens' }).nth(1).click();
		await page.getByRole('link', { name: 'Create a new Screen' }).click();
		await page.getByRole('textbox', { name: 'Name of the screen:' }).fill('Artifact Form - subform of Donors');
		await page.getByLabel('What kind of screen is this:').selectOption('multiPage');
		await page.getByRole('textbox', { name: 'Handle for the screen:' }).fill('artifacts_donor_subform');
		await saveAdminForm(page);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Pages' }).click();
		await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Form elements to display on page 1:')).toBeVisible();
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
			'Artifacts: Condition',
			'Artifacts: Collections',
			'Artifacts: Appears in these exhibits'
		]);
		await saveAdminForm(page, 'popup');
	});

	test('Create Artifacts Subform Interface in Donors', async ({ page }) => {
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(2).click();
		await addElementForm(page, ElementType.subformFullForm);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Donated artifacts');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#element-subform').selectOption({ label: 'Artifacts' });
		await page.getByRole('listbox').selectOption([{label: 'ID Number'},{label: 'Short name'}]);
		await page.locator('input[name="elements-ele_value[simple_add_one_button_text]"]').fill('Add new artifact');
		await page.getByRole('checkbox', { name: 'Show the Delete button' }).uncheck();
		await page.locator('select[name="elements-ele_value[SortingElement]"]').selectOption({label: 'Date of acquisition'});
		await page.locator('#element-subform-screen').selectOption({label: 'Artifact Form - subform of Donors'});
		await saveAdminForm(page);
	});

	test('Update Artifacts Subform Interface in Collections', async ({ page }) => {
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(1).click();
		await openElementAccordion(page, 'Artifacts Embeded Form (list');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('listbox').selectOption([{label: 'ID Number'},{label: 'Short name'}]);
		await page.locator('input[name="elements-ele_value[simple_add_one_button_text]"]').fill('Add new artifact');
		await page.getByRole('checkbox', { name: 'Show the Delete button' }).uncheck();
		await saveAdminForm(page);
	});

	test('Create Surveys Subform Interface in Exhibits', async ({ page }) => {
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(3).click();
		await openElementAccordion(page, 'Surveys Embeded Form (list');
		page.once('dialog', dialog => {
			console.log(`Dialog message: ${dialog.message()}`);
			dialog.accept().catch(() => {});
		});
		await page.getByRole('link', { name: 'Delete' }).click();
		await waitForAdminPageReady(page);
		await expect(page.getByRole('link', { name: 'Surveys Embeded Form (list' })).not.toBeVisible();
		await addElementForm(page, ElementType.subformEditableRow);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').press('ControlOrMeta+a');
		await page.locator('input[name="elements-ele_caption"]').fill('Surveys');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#element-subform').selectOption({ label: 'Surveys'});
		await page.locator('select[name="elements_ele_value_1[]"]').selectOption([
			{label: 'Which was your favourite artifact?'},
			{label: 'How would you rate the exhibit...'},
			{label: 'Flagged to the staff'},
			{label: 'Staff comments'}]);
		await page.getByRole('radio', { name: 'No', exact: true }).check();
		await page.locator('select[name="elements_ele_value_disabledelements[]"]').selectOption([
			{label: 'Which was your favourite artifact?'},
			{label: 'How would you rate the exhibit...'}]);
		await page.getByRole('radio', { name: 'View buttons are off' }).check();
		await page.locator('input[name="elements-ele_value[SortingDirection]"]').nth(1).check();
		await saveAdminForm(page);
	});

})
