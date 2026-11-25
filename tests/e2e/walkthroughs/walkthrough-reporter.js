// Keeps each walkthrough's video: converts Playwright's .webm to .mp4 with ffmpeg, or copies the .webm when ffmpeg
// isn't installed, and writes a subtitle file from the captions walkthrough.js recorded. The files are named after
// the test, in WALKTHROUGH_OUTPUT or walkthroughs/videos/. A walkthrough that didn't pass gets "-failed" in its name.

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const OUTPUT = process.env.WALKTHROUGH_OUTPUT ? path.resolve(process.env.WALKTHROUGH_OUTPUT) : path.join(__dirname, 'videos');

function slug(text) {
	return text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'walkthrough';
}

function srtTime(seconds) {
	const ms = Math.max(0, Math.round(seconds * 1000));
	const pad = (n, width = 2) => String(n).padStart(width, '0');
	return `${pad(Math.floor(ms / 3600000))}:${pad(Math.floor(ms / 60000) % 60)}:${pad(Math.floor(ms / 1000) % 60)},${pad(ms % 1000, 3)}`;
}

function srt(captions) {
	return captions.map((c, i) => `${i + 1}\n${srtTime(c.start)} --> ${srtTime(c.end ?? c.start + 3)}\n${c.text}\n`).join('\n');
}

function hasFfmpeg() {
	return spawnSync('ffmpeg', ['-version'], { stdio: 'ignore' }).status === 0;
}

class WalkthroughReporter {
	onBegin() {
		this.ffmpeg = hasFfmpeg();
	}

	onTestEnd(test, result) {
		const video = result.attachments.find(a => a.name === 'video' && a.path && fs.existsSync(a.path));
		if (!video) {
			return;
		}
		fs.mkdirSync(OUTPUT, { recursive: true });
		const name = slug(test.title) + (result.status === 'passed' ? '' : '-failed');
		let saved;
		if (this.ffmpeg) {
			saved = path.join(OUTPUT, name + '.mp4');
			const conversion = spawnSync('ffmpeg', ['-v', 'error', '-y', '-i', video.path, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '23', '-movflags', '+faststart', saved], { encoding: 'utf8' });
			if (conversion.status !== 0) {
				console.log(`Walkthrough: ffmpeg couldn't convert ${video.path}: ${conversion.stderr}`);
				saved = null;
			}
		}
		if (!saved) {
			saved = path.join(OUTPUT, name + '.webm');
			fs.copyFileSync(video.path, saved);
		}
		const captions = result.attachments.find(a => a.name === 'walkthrough-captions' && a.body);
		if (captions) {
			fs.writeFileSync(path.join(OUTPUT, name + '.srt'), srt(JSON.parse(captions.body.toString())));
		}
		console.log(`Walkthrough saved: ${saved}`);
	}
}

module.exports = WalkthroughReporter;
