const { test, expect } = require('@playwright/test')
import { saveChanges } from '../utils';
import { loginAsAdmin } from '../utils';

test.describe('Create Museum Forms and Elements', () => {

	test('Create Artifacts Form', async ({ page }) => {

		await loginAsAdmin(page);
  	await page.goto('/modules/formulize/admin');
		await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();

		await page.getByRole('link', { name: 'Create a new form' }).click();
		await expect(page.locator('input[name="forms-title"]')).toBeVisible();
  	await page.getByRole('textbox', { name: 'Form title:' }).fill('Artifacts');
  	await page.locator('#applications-name').fill('Museum');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
  	await page.locator('input[name="elements-ele_caption"]').fill('ID Number');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_id_number');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
	  await page.getByRole('link', { name: 'Display Settings' }).click();
  	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Short name');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_short_name');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=textarea');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Full description');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_full_description');
		await page.locator('textarea[name="elements-ele_desc"]').fill('For use in brochures, on websites, etc');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('checkbox', { name: 'Display this element using a' }).check();
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Height');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_height');
		await page.locator('textarea[name="elements-ele_desc"]').fill('in cm');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await page.getByRole('textbox', { name: 'Number of decimal places:' }).fill('1');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=form&aid=1&fid=1&tab=elements');
		await page.getByRole('link', { name: 'Height Textbox -' }).click();
		await expect(page.locator('a.clonelink[target="4"]')).toBeVisible();
		await page.locator('a.clonelink[target="4"]').click();

		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Width');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_width');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=1&aid=1&tab=elements');
		await page.getByRole('link', { name: 'Width Textbox -' }).click();
		await expect(page.locator('a.clonelink[target="5"]')).toBeVisible();
		await page.locator('a.clonelink[target="5"]').click();

		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Depth');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_depth');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=grid');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Dimensions');
		await page.locator('select[name="orderpref"]').selectOption('3');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Height,Width,Depth');
		await page.getByLabel('Choose the first element,').selectOption('4');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Year');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByText('Numbers Only', { exact: true }).click();
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=radio');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_era');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('CE');
  	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
  	await page.locator('input[name="ele_value\\[1\\]"]').fill('BCE');
  	await saveChanges(page);

  	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=grid');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
  	await page.locator('input[name="elements-ele_caption"]').click();
  	await page.locator('input[name="elements-ele_caption"]').fill('Date of origin');
		await page.locator('select[name="orderpref"]').selectOption('6');
  	await page.getByRole('link', { name: 'Options' }).click();
	  await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill('Year,Era');
	  await page.getByLabel('Choose the first element,').selectOption('8');
	  await saveChanges(page);

	  await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=derived');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
  	await page.locator('input[name="elements-ele_caption"]').fill('Year-Era');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year_era');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('div:nth-child(5) > pre:nth-child(2)').click();
  	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$value = "artifacts_year"."artifacts_era";');
		await page.getByRole('link', { name: 'Name & Settings' }).click();
	  await saveChanges(page);

	  await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=date');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
  	await page.locator('input[name="elements-ele_caption"]').fill('Date of acquisition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_date_of_acquisition');
	  await saveChanges(page);

  	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=yn');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Was the artifact donated to the museum?');
   	await page.locator('input[name="elements-ele_colhead"]').fill('Donated to museum');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donated_to_museum');
   	await saveChanges(page);

	}),
	test('Create Donor Form', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=forms');
   	await page.getByRole('link', { name: 'Create a new form' }).click();
		await expect(page.locator('input[name="forms-title"]')).toBeVisible();
   	await page.getByRole('textbox', { name: 'Form title:' }).click();
   	await page.getByRole('textbox', { name: 'Form title:' }).fill('Donors');
   	await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=radio');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Type of donor');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_type_of_donor');
		await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('button', { name: 'Add' }).click();
   	await page.locator('input[name="ele_value\\[0\\]"]').click();
   	await page.locator('input[name="ele_value\\[0\\]"]').fill('Individual');
   	await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
   	await page.locator('input[name="ele_value\\[1\\]"]').fill('Organization');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('First name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_first_name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('14');
   	await page.locator('#new_elementfilter_term').fill('Individual');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=form&fid=2&aid=1&tab=elements');
   	await page.getByRole('link', { name: 'First name Textbox -' }).click();
		await expect(page.locator('a.clonelink[target="15"]')).toBeVisible();
   	await page.locator('a.clonelink[target="15"]').click();

		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Last name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_last_name');
	  await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Organization name');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('14');
   	await page.locator('#new_elementfilter_term').fill('Organization');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=derived');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_name');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.locator('.CodeMirror-scroll').click();
   	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$type = "donors_type_of_donor";\nif($type == \'Individual\') {\n$value = "donors_first_name".\' \'."donors_last_name";\n} else {\n$value = "donors_organization_name";\n}');
		await page.getByRole('link', { name: 'Name & Settings' }).click();
   	await saveChanges(page);

	  await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=phone');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Phone number');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_phone');
   	await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=email');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Email address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_email');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=textarea');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Street address');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_street_address');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('textbox', { name: 'Rows*' }).fill('2');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=provinceList');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Province');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_province');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Postal code');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_postal_code');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=grid');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Province, Postal code');
   	await page.locator('select[name="orderpref"]').selectOption('21');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByLabel('Choose the first element,').selectOption('22');
   	await page.getByRole('textbox', { name: 'Enter the captions for the columns of this table*' }).fill(',');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=colorpick');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Favourite colour');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_favourite_colour');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=2&aid=1&type=fileUpload');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Backgrounder / Resume');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_backgrounder_resume');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Donor');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_donor');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByText('The values that people have').click();
   	await page.locator('#formlink').selectOption('18');
   	await page.getByRole('link', { name: 'Display Settings' }).click();
   	await page.locator('#new_elementfilter_element').selectOption('13');
   	await page.locator('#new_elementfilter_term').fill('Yes');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=slider');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
   	await page.locator('input[name="elements-ele_caption"]').fill('Condition');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_condition');
   	await page.locator('textarea[name="elements-ele_desc"]').fill('1 - terrible condition, 10 - like new');
   	await page.getByRole('link', { name: 'Options' }).click();
   	await page.getByRole('textbox', { name: 'Maximum value*' }).fill('10');
   	await page.getByRole('textbox', { name: 'Slider step size*' }).fill('1');
   	await page.getByRole('textbox', { name: 'Default value*' }).fill('5');
   	await saveChanges(page);

	});
	test('Create Collections Form', async ({ page }) => {

		await loginAsAdmin(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=forms');
   	await page.getByRole('link', { name: 'Create a new form' }).click();
		await expect(page.locator('input[name="forms-title"]')).toBeVisible();
		await page.getByRole('textbox', { name: 'Form title:' }).click();
   	await page.getByRole('textbox', { name: 'Form title:' }).fill('Collections');
   	await saveChanges(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=3&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
    await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_name');
    await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=form&aid=1&fid=3&tab=elements');
		await page.locator('input[name="elements-ele_required\\[29\\]"]').check();
		await saveChanges(page);

    await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=3&aid=1&type=checkbox');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Suitable for');
		await page.locator('input[name="elements-ele_handle"]').fill('collections_suitable_audience');
		await page.locator('input[name="elements-ele_colhead"]').fill('Suitable audience');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('button', { name: 'Add' }).click();
		await page.locator('input[name="ele_value\\[0\\]"]').click();
		await page.locator('input[name="ele_value\\[0\\]"]').fill('Children');
		await page.locator('input[name="ele_value\\[1\\]"]').fill('Adults');
	  await saveChanges(page);

    await page.goto('/modules/formulize/admin/ui.php?page=form&aid=1&fid=2&tab=elements');
    await page.getByRole('link', { name: 'Name Value derived from other' }).click();
    await page.getByRole('row', { name: 'donors_name Value derived' }).locator('input[name="principalidentifier"]').check();
    await saveChanges(page);

    await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=1&aid=1&type=checkbox');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
		await page.locator('input[name="elements-ele_caption"]').fill('Collections');
		await page.locator('input[name="elements-ele_handle"]').fill('artifacts_collections');
    await page.getByRole('link', { name: 'Options' }).click();
    await page.getByText('The values that people have').click();
    await page.locator('#formlink').selectOption('29');
	  await page.locator('#element-formlink_scope').selectOption(['4', '5']);
	  await page.getByText('Yes. Only use groups that the').click();
    await saveChanges(page);

	}),
	test('Create Exhibits Form', async ({ page }) => {

		await loginAsAdmin(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=forms');
   	await page.getByRole('link', { name: 'Create a new form' }).click();
		await expect(page.locator('input[name="forms-title"]')).toBeVisible();
		await page.getByRole('textbox', { name: 'Form title:' }).click();
   	await page.getByRole('textbox', { name: 'Form title:' }).fill('Exhibits');
		await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
   	await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=4&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').click();
    await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_name');
    await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=4&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
  	await page.locator('input[name="elements-ele_caption"]').fill('Curator');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_curator');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('{USERNAMES}');
  	await saveChanges(page);
  	await page.locator('#element-formlink_scope').selectOption('6');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=4&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
	  await page.locator('input[name="elements-ele_caption"]').fill('Exhibit includes artifacts from these collections');
		await page.locator('input[name="elements-ele_colhead"]').fill('Collections');
  	await page.locator('input[name="elements-ele_handle"]').fill('exhibits_collections');
		await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('This is a "autocompletion"').click();
  	await page.locator('#elements_multiple_allowed_auto').check();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('29');
		await page.locator('#element-formlink_scope').selectOption(['4', '5']);
	  await page.getByText('Yes. Only use groups that the').click();
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=4&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
		await page.locator('input[name="elements-ele_caption"]').fill('Artifacts shown in the exhibit');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Artifacts');
		await page.locator('input[name="elements-ele_handle"]').fill('exhibits_artifacts');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('This is a "autocompletion"').click();
  	await page.getByText('Allowed').nth(2).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('2');
  	await saveChanges(page);
		await page.locator('[id="elements-ele_value\\[17\\]"]').selectOption(['1', '2']);
		await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['1', '2']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['1', '2']);
	  await page.locator('#new_formlinkfilter_element').selectOption('31');
	  await page.locator('#new_formlinkfilter_term').fill('{exhibits_collections}');
		await saveChanges(page);

	}),
	test('Create Surveys Form', async ({ page }) => {

		await loginAsAdmin(page);

   	await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=forms');
   	await page.getByRole('link', { name: 'Create a new form' }).click();
		await expect(page.locator('input[name="forms-title"]')).toBeVisible();
		await page.getByRole('textbox', { name: 'Form title:' }).click();
   	await page.getByRole('textbox', { name: 'Form title:' }).fill('Surveys');
   	await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=5&aid=1&type=text');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
	  await page.locator('input[name="elements-ele_caption"]').fill('Your name');
	  await page.locator('input[name="elements-ele_colhead"]').fill('Respondent name');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=5&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
	  await page.locator('input[name="elements-ele_caption"]').fill('Which exhibit did you see?');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_exhibit');
		await page.locator('input[name="elements-ele_colhead"]').fill('Exhibit');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('32');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=5&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
	  await page.locator('input[name="elements-ele_caption"]').fill('Which was your favourite artifact?');
  	await page.locator('input[name="elements-ele_colhead"]').fill('Favourite artifact');
		await page.locator('input[name="elements-ele_handle"]').fill('surveys_favourite_artifact');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByText('The values that people have').click();
  	await page.locator('#formlink').selectOption('2');
		await saveChanges(page);
		await page.locator('[id="elements-ele_value\\[optionsLimitByElement\\]"]').selectOption('35');
	  await page.locator('[id="elements-ele_value\\[10\\]"]').selectOption(['1', '2']);
		await page.locator('[id="elements-ele_value\\[11\\]"]').selectOption(['1', '2']);
		await saveChanges(page);
  	await page.locator('#new_optionsLimitByElementFilter_element').selectOption('32');
  	await page.locator('#new_optionsLimitByElementFilter_term').fill('{surveys_exhibit}');
		await saveChanges(page);

		await page.goto('/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=5&aid=1&type=select');
		await expect(page.locator('input[name="elements-ele_caption"]')).toBeVisible();
  	await page.locator('input[name="elements-ele_caption"]').fill('How would you rate the exhibit overall?');
  	await page.locator('input[name="elements-ele_colhead"]').fill('Rating');
  	await page.locator('input[name="elements-ele_handle"]').fill('surveys_rating');
  	await page.getByRole('link', { name: 'Options' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.getByRole('button', { name: 'Add' }).click();
  	await page.locator('input[name="ele_value\\[0\\]"]').click();
  	await page.locator('input[name="ele_value\\[0\\]"]').fill('Disappointing');
  	await page.locator('input[name="ele_value\\[1\\]"]').fill('Average');
		await page.locator('input[name="ele_value\\[2\\]"]').fill('Good');
		await page.locator('input[name="ele_value\\[3\\]"]').fill('Excellent');
  	await page.locator('input[name="ele_value\\[4\\]"]').fill('Mind blowing!');
  	await saveChanges(page);

	})
});
