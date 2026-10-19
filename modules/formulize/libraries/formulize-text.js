/* Formulize text: the words a script shows, in the site's language.
 *
 * The words are constants in Formulize's language files, like the rest of its
 * text. PHP gathers the ones a script needs with formulize_scriptText($prefix)
 * (in modules/formulize/include/functions.php): every constant whose name starts
 * with the prefix, keyed by the rest of its name. The page passes them to its
 * script as JSON, and the script hands them to formulizeText():
 *
 *   var t = formulizeText(window.myPageText);
 *   button.textContent = t.text('SAVE');
 *   note.textContent = t.text('SAVED_COUNT', 3);
 *   heading.innerHTML = t.html('EDITING', '<strong>' + t.escape(name) + '</strong>');
 *
 * text() gives a word as text, for textContent, title, aria-label and the like.
 * html() gives it as HTML: the word escaped, then its blanks filled with what is
 * passed, which must be HTML already (so escape anything that isn't). Both fill
 * in the blanks the way PHP's sprintf does: %s in order, %1$s and %2$s by number
 * (so a translation can put them in a different order), and %% for a % sign. A
 * key with no word shows as the key itself, so a missing one is easy to spot.
 *
 * See "Translating text" in the docs (docs/translating_text.md). It can be
 * loaded twice, harmlessly.
 */
(function () {
	'use strict';
	if (window.formulizeText) {
		return;
	}

	function escape(value) {
		return String(value).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	// sprintf's %s, %1$s and %%, and nothing else of it
	function fill(word, values) {
		var next = 0;
		return word.replace(/%%|%(?:(\d+)\$)?s/g, function (blank, number) {
			if (blank === '%%') {
				return '%';
			}
			var value = values[number ? number - 1 : next++];
			return value === undefined ? '' : String(value);
		});
	}

	window.formulizeText = function (words) {
		words = words || {};
		function word(key) {
			return Object.prototype.hasOwnProperty.call(words, key) ? String(words[key]) : key;
		}
		return {
			text: function (key) {
				return fill(word(key), Array.prototype.slice.call(arguments, 1));
			},
			html: function (key) {
				return fill(escape(word(key)), Array.prototype.slice.call(arguments, 1));
			},
			escape: escape
		};
	};
})();
