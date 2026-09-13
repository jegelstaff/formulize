/*
  Shared client-side helpers for reading the "News" form out of the live
  Formulize system (formulize.net) via the Public API, and rendering it
  safely. See /developers/Public_API/read for the API itself.

  The News form is open to the Anonymous group, so no API key is used here
  on purpose -- embedding a key in this file would expose it to anyone who
  views source, and it would run as whatever user the key belongs to.
*/

const NEWS_API_BASE = 'https://formulize.net/services/formulize-public-api/v1';
const NEWS_FORM = 'news';

async function readNewsForm(body) {
  let res;
  try {
    res = await fetch(`${NEWS_API_BASE}/form/${NEWS_FORM}/read`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
  } catch (networkError) {
    // fetch() gives no further detail than this for a CORS or network failure --
    // browsers withhold the real reason from JS on purpose, for privacy reasons.
    // The browser's own devtools console (not this catch block) will usually show
    // the actual cause right above this, e.g. a CORS policy rejection naming the
    // blocked origin. Log the original error and keep it as .cause so it isn't
    // lost even though the message shown to visitors stays generic.
    console.error('Underlying fetch error (see the browser console above this for the real reason, e.g. a CORS rejection):', networkError);
    throw new Error('Could not reach the news system. It may be down, or this site may not be allowed to call it.', { cause: networkError });
  }

  const json = await res.json().catch(() => null);

  if (!res.ok) {
    const error = new Error(json?.error?.message ?? `The news system responded with http status ${res.status}`);
    error.status = res.status;
    error.code = json?.error?.code;
    error.hint = json?.error?.hint;
    throw error;
  }
  return json;
}

// Builds an element and adds contents as text (or child elements), never as
// HTML -- values from the API must never pass through innerHTML. See
// "Cross-site Scripting Risks" on the Public API read page for why.
// attrs may be an attributes object, a content value (string/Node), or
// null/undefined for "no attributes" -- el('div', null, child1, child2).
function el(tag, attrs, ...contents) {
  const element = document.createElement(tag);
  if (attrs === null || attrs === undefined) {
    // no attributes
  } else if (typeof attrs === 'object' && !(attrs instanceof Node)) {
    for (const [key, value] of Object.entries(attrs)) {
      if (key === 'class') element.className = value;
      else element.setAttribute(key, value);
    }
  } else {
    contents = [attrs, ...contents];
  }
  element.append(...contents);
  return element;
}

function formatNewsDate(datetime) {
  // "2026-09-13 13:26:08" -> a Date the browser can format locally.
  const d = new Date(datetime.replace(' ', 'T'));
  if (isNaN(d)) return datetime;
  return d.toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' });
}

function newsIsoDate(datetime) {
  const d = new Date(datetime.replace(' ', 'T'));
  return isNaN(d) ? '' : d.toISOString();
}

// news_body is a plain textarea, not rich text, deliberately -- a WYSIWYG
// field would return real HTML (needing a sanitizer) and would invite
// formatting that isn't part of the site's design. The only markup
// recognized here is [link text](https://url), a tiny parser instead of a
// full rich text editor. Requiring http(s):// rules out javascript: and
// other unsafe schemes by construction. Everything else is inserted as
// plain text, never HTML.
const NEWS_LINK_RE = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;

function renderInlineLinks(text) {
  const parts = [];
  let lastIndex = 0;
  let match;
  NEWS_LINK_RE.lastIndex = 0;
  while ((match = NEWS_LINK_RE.exec(text)) !== null) {
    if (match.index > lastIndex) parts.push(text.slice(lastIndex, match.index));
    parts.push(el('a', { href: match[2], target: '_blank', rel: 'noopener noreferrer' }, match[1]));
    lastIndex = match.index + match[0].length;
  }
  if (lastIndex < text.length) parts.push(text.slice(lastIndex));
  return parts;
}

function renderNewsBody(container, text) {
  const paragraphs = (text ?? '').split(/\r?\n\r?\n/).map(p => p.trim()).filter(Boolean);
  container.append(...paragraphs.map(p => el('p', null, ...renderInlineLinks(p))));
}

function newsTeaserText(entry, maxLength = 130) {
  const source = entry.news_teaser || entry.news_body || '';
  const flat = source.replace(/\r?\n+/g, ' ').trim();
  return flat.length > maxLength ? flat.slice(0, maxLength - 1).trimEnd() + '…' : flat;
}
