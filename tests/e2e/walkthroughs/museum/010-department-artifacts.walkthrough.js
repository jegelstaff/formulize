// Walkthrough of the museum system the e2e tests build: each department sees only its own artifacts, and a curator
// sees them all. It only reads, so it can be recorded again at any time. Needs all of formulize-core/01-setup, on
// Anari. The artifacts and counts are the ones 02-validate/026 checks.
import { test, expect } from '../walkthrough.js';

test('Each department sees its own artifacts', async ({ walk, page }) => {
	await walk.say('The museum keeps its artifacts in one form, but each department only sees the artifacts its own staff added.');

	await walk.signIn('ahstaff', { label: 'Ancient History staff' });
	await walk.menu('Artifacts');
	await expect(page.getByText('Showing entries: 1 to 8 of 8')).toBeVisible();
	await walk.say('Ancient History staff see their department\'s eight artifacts, such as the <b>Roman Coin</b>.');
	await walk.highlight(page.getByText('Roman Coin', { exact: true }).first());
	await walk.highlight(page.getByText('Showing entries: 1 to 8 of 8'), 2000);

	await walk.signIn('mhstaff', { label: 'Modern History staff' });
	await walk.menu('Artifacts');
	await expect(page.getByText('Florentine Book', { exact: true }).first()).toBeVisible();
	await expect(page.getByText('Roman Coin', { exact: true })).not.toBeVisible();
	await walk.say('Modern History staff see their own eight, such as the <b>Florentine Book</b>. The Roman Coin isn\'t listed.');
	await walk.highlight(page.getByText('Florentine Book', { exact: true }).first());

	await walk.signIn('curator1', { label: 'Curator in both departments' });
	await walk.menu('Artifacts');
	await expect(page.getByText('of 16')).toBeVisible();
	await walk.say('A curator can see every artifact, so both departments\' sixteen are listed.');
	await walk.highlight(page.getByText('of 16'), 2000);

	await walk.finish('Permissions are given to the Departments template groups, and each department\'s groups follow them.');
});
