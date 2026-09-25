/* The buttons on code blocks: copy, and the light/dark switch.
 *
 * The scheme itself is applied by the inline snippet in _includes/head-custom.html,
 * which runs before the first paint. This file does the things that can
 * safely wait until the document exists: it wraps each code block so the
 * buttons have somewhere to sit, it copies a block's code when asked, and it
 * keeps every light/dark button, the <html> attribute and the cookie in step
 * when one of them is pressed.
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

  // The half-filled circle is the usual "contrast" symbol for a light/dark
  // switch. It is the same in both states; the word beside it says which way
  // the button goes.
  var THEME_ICON = '<svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false">' +
    '<circle cx="8" cy="8" r="6.3" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
    '<path d="M8 1.7a6.3 6.3 0 0 1 0 12.6z" fill="currentColor"/>' +
    '</svg>';

  function label(button, theme) {
    var next = theme === 'dark' ? 'light' : 'dark';
    button.innerHTML = THEME_ICON + '<span>' + (next === 'dark' ? 'Dark' : 'Light') + '</span>';
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

  /* The copy button. navigator.clipboard only exists in a secure context, so
   * the site served over plain http - `jekyll serve --host` on a LAN address,
   * for one - falls back to the old select-and-execCommand route rather than
   * showing a button that does nothing. */
  var COPY_ICON = '<svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false">' +
    '<rect x="5.5" y="5.5" width="8" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
    '<path d="M10.5 3.5v-.5A1.5 1.5 0 0 0 9 1.5H4A1.5 1.5 0 0 0 2.5 3v6A1.5 1.5 0 0 0 4 10.5h.5" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
    '</svg>';

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.top = '-1000px';
      document.body.appendChild(area);
      area.select();
      var ok = false;
      try {
        ok = document.execCommand('copy');
      } catch (e) {}
      document.body.removeChild(area);
      if (ok) {
        resolve();
      } else {
        reject();
      }
    });
  }

  function showCopyState(button, text) {
    button.innerHTML = COPY_ICON + '<span>' + text + '</span>';
  }

  function copy(button, block) {
    var pre = block.tagName === 'PRE' ? block : block.querySelector('pre');
    // Kramdown ends every block with a newline; pasting it would leave the
    // reader a stray blank line under the example.
    var text = (pre || block).textContent.replace(/\n$/, '');
    copyText(text).then(function () {
      showCopyState(button, 'Copied');
    }, function () {
      showCopyState(button, 'Failed');
    });
    clearTimeout(button._reset);
    button._reset = setTimeout(function () {
      showCopyState(button, 'Copy');
    }, 1800);
  }

  function decorate(block) {
    // A block already inside a wrapper - or one that is itself the inner
    // .highlight of a .highlighter-rouge we have already handled - must not get
    // a second set of buttons.
    if (block.closest('.code-block')) {
      return;
    }
    var wrapper = document.createElement('div');
    wrapper.className = 'code-block';
    block.parentNode.insertBefore(wrapper, block);
    wrapper.appendChild(block);

    var tools = document.createElement('div');
    tools.className = 'code-block-tools';

    var copyButton = document.createElement('button');
    copyButton.type = 'button';
    copyButton.className = 'code-block-button code-copy';
    // The visible word is the accessible name, and the live region announces
    // it changing to Copied - an aria-label would pin the name at Copy.
    copyButton.setAttribute('aria-live', 'polite');
    showCopyState(copyButton, 'Copy');
    copyButton.addEventListener('click', function () {
      copy(copyButton, block);
    });
    tools.appendChild(copyButton);

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-block-button code-theme-toggle';
    label(button, readTheme());
    button.addEventListener('click', toggle);
    tools.appendChild(button);

    wrapper.appendChild(tools);
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
