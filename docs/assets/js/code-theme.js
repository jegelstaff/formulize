/* The light/dark switch on code blocks.
 *
 * The scheme itself is applied by the inline snippet in _includes/head-custom.html,
 * which runs before the first paint. This file does the two things that can
 * safely wait until the document exists: it wraps each code block so a button
 * has somewhere to sit, and it keeps every button, the <html> attribute and the
 * cookie in step when one of them is pressed.
 *
 * See the .code-block block in assets/css/formulize.css for the styling and the
 * reasoning behind the wrapper.
 */
(function () {
  'use strict';

  var COOKIE = 'formulize_code_theme';
  var YEAR = 60 * 60 * 24 * 365;

  function readTheme() {
    return document.documentElement.getAttribute('data-code-theme') === 'dark' ? 'dark' : 'light';
  }

  function writeTheme(theme) {
    document.documentElement.setAttribute('data-code-theme', theme);
    // Lax rather than Strict: this is a display preference with nothing to
    // protect, and Strict would drop it when a reader arrives from a link
    // somewhere else, which is most of how these pages are reached.
    document.cookie = COOKIE + '=' + theme + ';path=/;max-age=' + YEAR + ';SameSite=Lax';
  }

  function label(button, theme) {
    var next = theme === 'dark' ? 'light' : 'dark';
    button.textContent = next === 'dark' ? 'Dark' : 'Light';
    button.setAttribute('aria-label', 'Switch code blocks to ' + next + ' colours');
    button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
  }

  function relabelAll(theme) {
    var buttons = document.querySelectorAll('.code-theme-toggle');
    for (var i = 0; i < buttons.length; i++) {
      label(buttons[i], theme);
    }
  }

  function toggle() {
    var theme = readTheme() === 'dark' ? 'light' : 'dark';
    writeTheme(theme);
    relabelAll(theme);
  }

  function decorate(block) {
    // A block already inside a wrapper - or one that is itself the inner
    // .highlight of a .highlighter-rouge we have already handled - must not get
    // a second button.
    if (block.closest('.code-block')) {
      return;
    }
    var wrapper = document.createElement('div');
    wrapper.className = 'code-block';
    block.parentNode.insertBefore(wrapper, block);
    wrapper.appendChild(block);

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-theme-toggle';
    label(button, readTheme());
    button.addEventListener('click', toggle);
    wrapper.insertBefore(button, block);
  }

  function init() {
    /* Three shapes reach the page and only the outermost of any nesting should
     * be wrapped:
     *   - a fenced block with a language:  div.highlighter-rouge > div.highlight > pre
     *   - a fenced block with none:        div.highlight > pre
     *   - an indented or hand-written one: a bare pre
     * Taking them in this order and skipping anything already inside a wrapper
     * (see decorate) gets each block exactly one button.
     *
     * The div. qualifiers are load-bearing, not tidiness. Kramdown puts
     * highlighter-rouge on inline code as well - `like this` in a sentence
     * becomes <code class="language-plaintext highlighter-rouge"> - so a bare
     * .highlighter-rouge selector matches every scrap of inline code on the
     * page and hangs a light/dark button off each one. Only the block form is
     * a <div>.
     */
    var blocks = document.querySelectorAll('div.highlighter-rouge, div.highlight, pre');
    for (var i = 0; i < blocks.length; i++) {
      decorate(blocks[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
