---
layout: default
permalink: ai/debugging
title: Troubleshooting the Embedded AI Assistant
---

# Troubleshooting the Embedded AI Assistant

If the embedded AI assistant isn't doing what you expect, for example it thinks for a long time and then gives no answer, you can turn on _debug mode_. In debug mode, the assistant writes a line in your browser's console for every request it sends to the AI, and for every tool it uses. This shows you, step by step, what happened. You can also copy these lines into a [bug report](../report-a-bug/).

Debug mode only affects your own browser. It doesn't change anything for anyone else using your Formulize system.

## Turn on debug mode

1. **Open your browser's console.** Go to any page of your Formulize site, then:

    * _Chrome or Edge:_ press __Ctrl+Shift+J__ (Windows/Linux) or __Cmd+Option+J__ (Mac)
    * _Firefox:_ press __Ctrl+Shift+K__ (Windows/Linux) or __Cmd+Option+K__ (Mac)
    * _Safari:_ first turn on _Show features for web developers_ in __Settings > Advanced__, then press __Cmd+Option+C__

2. **Type or paste this line into the console, and press Enter:**

   ```js
   localStorage.setItem('formulize_ai_debug', '1')
   ```

    The first time you paste something into the console, your browser may warn you about it and block the paste. If that happens, follow the instructions in the warning (in Chrome, you type _allow pasting_ and press Enter), then paste the line again.

3. **Reload the page.** Debug mode starts when the assistant loads. If you use the assistant in the slide-out panel, reload the page and then open the panel again.

Leave the console open, and use the assistant as normal.

## What you'll see

Each time the assistant sends a request to the AI, a line like this appears in the console:

```
[Formulize AI] claude round 1 {requestChars: 48213, elapsedMs: 71354, status: 200, stop_reason: 'max_tokens', ...}
```

Click the line to expand it. These are the parts to look at:

* __round__ - one message from you can take several rounds. The AI asks for a tool, gets the result, and then continues. The rounds are numbered from 1 for each message you send.
* __elapsedMs__ - how long the AI took to answer, in milliseconds. 60000 is one minute.
* __status__ - 200 means the request worked. A status of 502, 503 or 504 means your web server stopped waiting for the AI before it answered.
* __stop_reason__ (Claude), __finish_reason__ (OpenAI and Ollama), or __finishReason__ (Gemini) - why the AI stopped:
    * _end_turn_, _stop_ or _STOP_ - it finished normally
    * _tool_use_ or _tool_calls_ - it wants to use a tool, and another round will follow
    * _max_tokens_, _length_ or _MAX_TOKENS_ - its answer reached the maximum length for a single response and was cut off. On a big job, this can happen while the AI is still planning, before it has done anything. When the AI is cut off before it finishes what it was doing, the assistant tells it to work in smaller steps, and it tries again. If it keeps getting cut off without getting anything done, the assistant stops and asks you to ask for less at once.
* __usage__ - how many tokens the request used. This is the amount your AI provider bills you for.
* __error__ - the error message, if there was one.

Each time the AI uses one of the Formulize tools, a line like this appears:

```
[Formulize AI] tool create_entries {argsChars: 5120, resultChars: 812, elapsedMs: 2350}
```

It shows the name of the tool, the size of the request the AI made and of the result it got back, how long the tool took, and any error.

## Send the log with a bug report

To [report a bug](../report-a-bug/), copy the console lines after you reproduce the problem, and include them in your report. In Chrome and Edge, right-click in the console and choose _Save as..._ to save everything as a file. In any browser, you can also select the lines with your mouse and copy them.

Expand the lines first, so the details are included. The log records sizes, timings and error messages, not the content of your conversation, and it never contains your API key. Error messages can mention the names of forms or elements, so read through the log before you share it.

## Test with a short response limit

To see how the assistant copes when an answer is too long, without asking for something huge, you can make the maximum response length much shorter. Debug mode has to be on for this to work. In the console, enter:

```js
localStorage.setItem('formulize_ai_max_tokens', '700')
```

Then reload the page. Every answer is now limited to about 700 tokens, which is roughly 500 words. Ask the assistant to create ten entries in a test form. You'll usually see one or two rounds with _max_tokens_ (or _length_, or _MAX_TOKENS_), followed by the AI making several smaller tool requests, one per round.

Different AI models need different limits for this. If all ten entries arrive in one request, lower the number. If the assistant stops and tells you it kept reaching the length limit, even a small step didn't fit, so raise the number.

Remove the limit when you're done:

```js
localStorage.removeItem('formulize_ai_max_tokens')
```

## Turn off debug mode

In the console, enter this line, then reload the page:

```js
localStorage.removeItem('formulize_ai_debug')
```

---

- [Set up the embedded AI assistant](../ai/setup-embedded)
- [Read more about AI and Formulize](../ai/)
