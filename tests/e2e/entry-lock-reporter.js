// Lists the pages that tests left without their entry lock release ever reaching the server. leavePage() in utils.js
// waits for each release, and when one doesn't arrive it notes what's still locked on the test, as an annotation,
// rather than failing it, because often nothing depends on that lock. A later test that opens one of those entries as
// someone else finds it read-only, so this is the list to check first when a form is drawn read-only.
//
// In GitHub Actions the list also goes in the run's summary, as the coverage report does, and each note is a warning.

const fs = require('fs');

// The annotation leavePage() adds
const UNRELEASED_LOCKS_NOTE = 'entry locks not released';

class EntryLockReporter {
	constructor() {
		this.notes = [];
	}

	onTestEnd(test, result) {
		const annotations = result.annotations || test.annotations || [];
		for (const annotation of annotations) {
			if (annotation.type === UNRELEASED_LOCKS_NOTE) {
				this.notes.push({ title: test.titlePath().filter(Boolean).join(' › '), file: test.location.file, line: test.location.line, description: annotation.description });
			}
		}
	}

	onEnd() {
		if (this.notes.length === 0) {
			return;
		}
		console.log(`\nEntry locks not released (${this.notes.length}):`);
		for (const note of this.notes) {
			console.log(`  ${note.title}\n    ${note.description}`);
		}
		if (!process.env.GITHUB_ACTIONS) {
			return;
		}
		const repoRoot = require('path').resolve(__dirname, '../..');
		for (const note of this.notes) {
			const file = require('path').relative(repoRoot, note.file).replace(/\\/g, '/');
			console.log(`::warning file=${file},line=${note.line},title=Entry locks not released::${note.description.replace(/\n/g, '%0A')}`);
		}
		if (process.env.GITHUB_STEP_SUMMARY) {
			const lines = [
				'',
				`### ⚠️ Entry locks not released (${this.notes.length})`,
				'',
				'These tests left a page whose release of its entry locks never reached the server, so the entries stayed locked. A later test that opens one of them as someone else finds it read-only.',
				'',
				'| Test | What stayed locked |',
				'|---|---|',
				...this.notes.map(note => `| ${note.title.replace(/\|/g, '\\|')} | ${note.description.replace(/\|/g, '\\|')} |`),
				'',
			];
			fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, lines.join('\n'));
		}
	}
}

module.exports = EntryLockReporter;
