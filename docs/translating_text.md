---
layout: default
permalink: documentation/translating_text/
title: Translating text
---

# Translating text

Every word Formulize shows (labels, buttons, messages, errors) comes from a language file, so that
a site can show it in its own language. This covers words that PHP prints and words that scripts
show. Both come from the same place.

This is about Formulize's own words. Words that people type into Formulize, like form names and
element captions, are translated differently, with language tags. See
[trans](/documentation/functions/trans).

## Where the words are

The words are PHP constants, defined in the files in `modules/formulize/language/`. There is a
folder for each language, named the way ImpressCMS names it (`english`, `french` and so on).
`english` always has every word. The others may lag behind, so a page falls back to English for a
file that a language doesn't have yet.

- `main.php` has the words of the pages people use: forms, lists, screens.
- `admin.php` has the words of the admin pages.
- `modinfo.php` has the module's name, menus and preferences, for ImpressCMS.
- A feature with a lot of words of its own can have a file of its own, like `appearance.php` (looks
  and the Appearance editor) and `ai_chat.php`.

Each word is a `define()`, named for where it's used. Admin words start with `_AM_`, and the
others mostly with `_formulize_`. Add a short comment wherever a translator would need to know what
the word is for:

```php
define('_AM_APPEARANCE_LOOKS_RENAME', 'Rename');
define('_AM_APPEARANCE_LOOKS_USED_SCREENS', '%s screens'); // in the Used by column
```

## Loading a language file

Load a file with ImpressCMS's `icms_loadLanguageFile()`. It loads the site's language's copy of the
file, or the English one when there isn't a copy, and loads it only once however often it's called:

```php
icms_loadLanguageFile('formulize', 'appearance');
```

Load it where the code that uses the words begins. For example, `include/appearance.php` loads
`appearance.php`, so every page that includes it has those words.

## Writing words that translate well

A translator sees each word on its own, with no idea where it goes. Write words that a translator can
put into their language whole.

**Write whole sentences.** Don't build a sentence out of pieces, because other languages put the
pieces in a different order. Use a blank (`%s`) for whatever changes:

```php
// not: _AM_MYFEATURE_MOVED . ' ' . $name . ' ' . _AM_MYFEATURE_TO . ' ' . $folder
define('_AM_MYFEATURE_DONE_MOVE', 'Moved %1$s to %2$s.');
```

**Number the blanks when there is more than one** (`%1$s`, `%2$s`), so that a translation can put
them in a different order. When there is only one, `%s` is enough.

**Write a % sign as `%%`** in a word that has a blank. In a word with no blanks, write it as it is.

**Give singular and plural their own constants.** Don't add an "s". Pick between them in the code:

```php
define('_AM_APPEARANCE_LOOKS_USED_SCREEN', '%s screen');
define('_AM_APPEARANCE_LOOKS_USED_SCREENS', '%s screens');
```

**Keep markup out of the words** where you can. Pass it in through a blank instead, so a translator
never has to edit HTML.

## Words that PHP prints

Fill in the blanks with `sprintf()`, and escape the result the way you would escape anything else
you print:

```php
echo htmlspecialchars(sprintf(_AM_MYFEATURE_DONE_MOVE, $name, $folder), ENT_QUOTES);
```

In a Smarty template, use `<{$smarty.const._AM_SOME_WORD}>`.

## Words that scripts show

A script gets its words from the same language files. Formulize has two pieces for this:

- `formulize_scriptText($prefix)`, in `modules/formulize/include/functions.php`, gathers every constant
  whose name starts with `$prefix`, keyed by the rest of its name.
- `formulizeText()`, in `modules/formulize/libraries/formulize-text.js`, gives each word back by its
  key in the script, with its blanks filled in.

### 1. Name the script's words with a prefix

Give every word the script needs a prefix of its own that ends in `_JS_`, and keep them together in
the language file:

```php
// ---- The script (include/js/my_feature.js) ----
// Every constant starting _AM_MYFEATURE_JS_ goes to the script, which knows it by the rest of its
// name: _AM_MYFEATURE_JS_SAVE is SAVE there.
define('_AM_MYFEATURE_JS_SAVE', 'Save');
define('_AM_MYFEATURE_JS_SAVED_COUNT', 'Saved %s entries.');
define('_AM_MYFEATURE_JS_EDITING', 'Editing %s');
```

There's no list to keep up to date. Every constant with the prefix goes to the script, so a new
word is ready to use as soon as it's defined. If the script needs a word that another file already
has, like "Cancel", define it again under the prefix. The word may need translating differently in
your context anyway.

### 2. Pass the words to the page

Load the language file, then print the words as JSON before the script that uses them:

```php
icms_loadLanguageFile('formulize', 'my_feature');
$text = json_encode(formulize_scriptText('_AM_MYFEATURE_JS_'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
echo "<script>window.myFeatureText = $text;</script>";
```

Keep the `JSON_HEX_` flags. They stop a word that contains `</script>` or a quote from breaking out of
the script tag.

If the page already passes its script a block of data, put the words in it instead, as the
Appearance editor does with `'text' => formulize_scriptText('_AM_APPEARANCE_EDITOR_JS_')`.

### 3. Load formulize-text.js before your script

On a page drawn by the theme:

```php
global $xoTheme;
$xoTheme->addScript(formulize_textScriptPath());
```

On a page that prints its own head:

```php
<script src="<?php echo XOOPS_URL . formulize_textScriptPath(); ?>"></script>
```

It can be loaded more than once, harmlessly.

### 4. Use the words in the script

```javascript
var t = formulizeText(window.myFeatureText);

button.textContent = t.text('SAVE');
note.textContent = t.text('SAVED_COUNT', 3);
heading.innerHTML = t.html('EDITING', '<strong>' + t.escape(name) + '</strong>');
```

- **`t.text(key, …)`** gives the word as plain text. Use it for `textContent`, `title`,
  `aria-label`, `alert()` and anywhere else that isn't HTML.
- **`t.html(key, …)`** gives the word as HTML. It escapes the word, then fills in its blanks with what
  you pass, unescaped, so that a blank can hold markup. Escape anything you pass that isn't HTML
  already, like a name someone typed, with `t.escape()`.

Both fill in the blanks the way `sprintf()` does: `%s` in order, `%1$s` and `%2$s` by number, and
`%%` as a % sign. So a word works the same way in PHP and in a script. A key with no word shows as
the key itself, `SAVED_COUNT` for example, so a word you forgot to define is easy to spot.

## Older patterns

You'll find other ways of getting words into scripts in older code. Some templates print a constant
straight into inline script. Some PHP `json_encode()`s constants one at a time. The list filters
build a hand-written list for `window.formulizeFilterPopoverLang`. They work, and there's no need to
change them unless you're working on that code anyway. New scripts should use `formulizeText()`.
