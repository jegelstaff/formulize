# English and French, at least

Every word Formulize shows comes from a language constant. That's how a site can show it in its own language. [Translating text](../docs/translating_text.md) covers where the constants go and how to write them.

**English and French are the minimum.** Every constant you add goes in both the `english` and the `french` copy of its file, under the same name, in the same change. Other languages, such as `german`, `italian` and `portuguesebr`, can lag behind.

- **Module words** are in `modules/formulize/language/english/` and `modules/formulize/language/french/`. **Core words** are in `language/english/` and `language/french/`.
- **Script words** too. A `_JS_` constant that `formulize_scriptText()` hands to a script needs its French copy like any other.
- **Change the French when you change the English.** If the English gains a `%s`, or says something different, the French has to as well.
- **Fill the gaps you meet.** Some French files are missing constants that are older than this rule. When your change works with words that have no French, add it.
- **Write the French as a French speaker would say it**, with French punctuation, such as a space before `:`. Keep the code's names, placeholders like `{NOW}`, and blanks like `%s` exactly as they are in the English.
- **Say in your report that you added French,** and which constants you weren't sure how to translate, so someone can check them.

## Why gaps go unnoticed

A missing French constant doesn't break a French site, so nothing tells you it's missing. Many French files end by including their English copy, which defines whatever the French file left out. `icms_loadLanguageFile()` also falls back to the English file when a language has no copy of a file at all. So a French site shows the English words, and the gap is only seen by someone reading the site in French. Check the French file yourself.
