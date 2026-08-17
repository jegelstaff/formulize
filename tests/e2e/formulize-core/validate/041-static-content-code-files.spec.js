const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, openElementAccordion, setCodeMirrorValue } from '../../utils';

// Static content elements (fullWidthContent, captionedContent) can hold plain text/HTML or PHP code. When
// the content contains $value it is treated as code: setVar writes it out to
// modules/formulize/code/<ele_type>_<handle>.php and getVar reads it back from there, so the file is where
// the content really lives. Everything in this file is about that code path - plain HTML, which is what the
// Survey Intro element is created with in setup/010, never goes near any of it.
//
// The bug this protects against: saving an element sets ele_value TWICE - once in the type handler's
// adminSave(), and again inside upsertElementSchemaAndResources(), which is handed whatever the first pass
// left on the object. While setVar blanked the content after writing the file, the second pass saw empty
// content, read that as "the admin removed the code", and deleted the file the first pass had just written.
// The content was then gone from the file and from ele_value both, and the Options tab came back empty.
//
// So this spec changes the Survey Intro's content from HTML to code, saves it twice (the second save is an
// element that already has a code file and a blank stored value - a different starting state, and the one
// an admin hits when editing content they saved earlier), and then loads the survey the way
// setup/020-data-entry.spec.js does to check the content is on screen.
//
// It asserts the SENTENCE THE CODE COMPUTES rather than the heading, and that matters: a fullWidthContent
// element falls back to displaying its caption when its content is blank ($useCaptionAsContentFallback),
// and the caption here is that same heading - so asserting the heading would pass even if the content had
// been destroyed. The number in the sentence only appears if the code was kept AND evaluated, since a
// content that reached the screen as unevaluated text would read '".$questions."' instead.
//
// The mechanism underneath (all six element types that keep code in files, in both directions) is covered
// by tests/element_code_files_test.php.
//
// This spec modifies a shared element rather than building its own form, which is safe here because no
// other spec in the validate suite touches the Surveys form, and because the content it leaves behind
// renders the same heading the element showed before.

test.describe.configure({ mode: 'serial' });

const SURVEY_INTRO_CAPTION = 'Thank you for visiting the Museum!';
const SURVEY_INTRO_CODE = '<?php\n$questions = 3;\n$value = "<h1>' + SURVEY_INTRO_CAPTION + '</h1><p>Please answer all ".$questions." questions.</p>";';
const COMPUTED_SENTENCE = 'Please answer all 3 questions.';

// The element's content box. setCodeMirrorValue() writes it through the CodeMirror editor that
// ui.html puts over every .code-textarea; reading the underlying textarea's value property is
// reliable either way, because the editor copies the text back into it.
const CONTENT_BOX = 'textarea[name="elements-ele_value[0]"]';
const contentBox = (page) => page.locator(CONTENT_BOX);

/**
 * Open the Survey Intro element's Options tab, from wherever the page currently is.
 * Navigates by name rather than by position: 029 creates and deletes a form in the Museum application
 * while this suite runs in parallel, so any nth() index into the form list is not dependable.
 */
async function openSurveyIntroOptions(page) {
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	// A form's name appears on this page more than once: visibly in its own box, and again inside the
	// Connections list of every form it is related to, which relationship_listing.html renders as
	// <em>Surveys</em> in the .form-listing-details panel - hidden until that form's box is clicked. So
	// getByText('Surveys').first() lands on a hidden <em> in an earlier box and never becomes clickable.
	// Address the form's own box through its visible name element, the way deleteMuseumForm() does.
	const surveysBox = page.locator('div.form-listing-box')
		.filter({ has: page.locator('.form-name-text').filter({ hasText: /^Surveys$/ }) });
	await expect(surveysBox).toBeVisible();
	await surveysBox.locator('.form-name-text').click();
	// and this form's own Elements link: there is one in every box, so an unscoped .first() would open
	// whichever form happens to be listed first rather than this one
	await surveysBox.getByRole('link', { name: 'Elements' }).first().click();
	await openElementAccordion(page, SURVEY_INTRO_CAPTION);
	await page.getByRole('link', { name: 'Configure' }).click();
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	await waitForAdminPageReady(page);
}

test('Static content saved as PHP code is kept, over two saves', async ({ page }) => {
	await login(page, 'admin');

	// HTML -> code. The file does not exist yet, so this is the "first write" case.
	await openSurveyIntroOptions(page);
	await setCodeMirrorValue(page, CONTENT_BOX, SURVEY_INTRO_CODE);
	await saveAdminForm(page);

	// Reopening is the point: the content has to come back out of the file it was written to, which only a
	// fresh load exercises. This is where the bug showed up - the box came back empty.
	await openSurveyIntroOptions(page);
	expect(await contentBox(page).inputValue()).toContain('$value = "<h1>');
	expect(await contentBox(page).inputValue()).toContain('$questions');

	// Save a second time, now that the element has a code file and a blank stored value. Nothing is typed
	// first, exactly as if an admin opened the element and pressed save.
	await saveAdminForm(page);
	await openSurveyIntroOptions(page);
	expect(await contentBox(page).inputValue()).toContain('$value = "<h1>');
	expect(await contentBox(page).inputValue()).toContain('$questions');
});

test('The survey form shows the value the static content code computes', async ({ page }) => {
	// Anonymous, the same way setup/020-data-entry.spec.js reaches the survey, including its fallback for
	// when the rewrite rule is not in effect.
	let loaded = false;
	try {
		await page.goto('/survey');
		await page.waitForLoadState('networkidle');
		await page.waitForLoadState('domcontentloaded');
		loaded = await page.locator('div.formulize-label-surveys_your_name').count() > 0;
	} catch {
		loaded = false;
	}
	if (!loaded) {
		// Survey multipage screen is sid=16 after the 005 form/screen additions (see 020).
		await page.goto('/modules/formulize/index.php?sid=16');
	}
	await expect(page.getByRole('textbox', { name: 'Your name' })).toBeVisible();
	// The heading is not asserted here on purpose - see the note at the top of this file about the caption
	// fallback making that assertion pass even when the content has been lost.
	await expect(page.getByText(COMPUTED_SENTENCE)).toBeVisible();
	await page.goto('/'); // leave no entry lock behind
});
