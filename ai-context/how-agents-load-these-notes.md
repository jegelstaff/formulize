# How AI tools load these notes

`AGENTS.md` and `ai-context/` are written for any AI coding tool. Each tool finds instruction files in its own way, so a few small files make sure each one reaches the right notes. None of them hold facts of their own, so there is still only one copy of each topic.

## The files

| File | What's in it |
|---|---|
| `AGENTS.md` at the root | The rules for every change, and the table of topics, with each topic's key fact. This is the file every tool reads. |
| `CLAUDE.md` at the root | `@AGENTS.md` and `@ai-context/coding-guidelines.md`, so Claude Code loads both at the start of a session. |
| `GEMINI.md` at the root | `@ai-context/coding-guidelines.md`. Gemini CLI reads `AGENTS.md` itself, so importing it here would load it twice. |
| `.gemini/settings.json` | `context.fileName` lists `AGENTS.md` and `GEMINI.md`, so Gemini CLI reads both. |
| `AGENTS.md` in a folder | One line and a list of links to the topic files for that folder. Nothing else. |
| `CLAUDE.md` in a folder | `@AGENTS.md`, then an `@` import of each topic file that folder's `AGENTS.md` links to. Nothing else. |

To see which folders have them, run `git ls-files '*AGENTS.md'`.

## Pointers in the code

Some topics are about particular files or functions, not a whole folder, such as conditions, relationships or password hashing. For those, the file or the function's docblock has a comment naming the topic file, such as `See ai-context/conditions.md`. An agent reads a file before changing it, so this reaches every tool. To find them all, run `git grep -n "ai-context/"`.

## Keep facts out of these files

A fact goes in its topic file, and its most-missed part can also go in that topic's line in the root table. Never write one into a folder's `AGENTS.md` or `CLAUDE.md`, into a pointer comment, or into the root `CLAUDE.md` or `GEMINI.md`, because then there are copies to drift apart. When a topic file is renamed or merged, update every pointer to it.

Don't use `AGENTS.override.md`. Codex uses it in place of a folder's `AGENTS.md`, not as well as it.

## How each tool picks them up

Checked in November 2026. The tools change, so check again before relying on any detail.

| Tool | At the start of a session | Later |
|---|---|---|
| Claude Code | The root `CLAUDE.md`, with its imports | A folder's `CLAUDE.md`, with its imports, the first time it reads a file in that folder or below. After the conversation is compacted, these aren't loaded again. |
| Gemini CLI | Every file named in `context.fileName`, in each folder from the repository root down to where it started, with their imports | The same files in a folder below, when it works on a file there. Older versions read every folder below at startup instead, and sent all of them with every prompt. |
| Codex | `AGENTS.md` in each folder from the repository root down to where it started. No imports. It stops at 32 KiB in all. | Nothing more. A folder's `AGENTS.md` below that is only read if the agent opens it, which is why the root `AGENTS.md` tells agents to. |

From this:

- **A folder's `AGENTS.md` stays short.** An older Gemini CLI loads all of them into every prompt.
- **Only `CLAUDE.md` files import topic files.** Claude Code loads them when the work reaches that folder, much as a memory is recalled. A folder's `GEMINI.md` would load whole topic files into every prompt on an older Gemini CLI, so don't add one.
- **Keep the root `AGENTS.md` well under 32 KiB,** with room for the folder files Codex adds when it starts in a folder.
- **A tool can lose a folder's notes.** Claude Code doesn't reload them after compaction, and Codex only sees them if it opens them. That's why every plan and report lists the `ai-context/` files read: the person can see what was missed.

## Adding a folder

1. Write the folder's `AGENTS.md`: the line "Read these before you change anything in this folder:", then a link to each topic file. Link to a section of a long file with an anchor, such as `coding-guidelines.md#admin-pages-and-saves`.
2. Write its `CLAUDE.md`: `@AGENTS.md`, then `@` and the relative path of each topic file, such as `@../ai-context/mcp-and-public-api.md`. Leave out `coding-guidelines.md`, because the root `CLAUDE.md` already loads it, and leave out a file that's only linked for one section of it.
3. Check that nothing in the code lists that folder's files and would pick up a `.md` file. The ones checked so far list only folders (`icms_core_Filesystem::getDirList()`), only `*.php` (patches), only `*Element.php` (element types), only folders named with a screen id (screen templates), or only files it can edit (the theme editor). Never put one in `modules/formulize/code/`, because site sync puts that whole folder in its export.
4. In `docs/`, the three file names are already left out of the site in `_config.yml`. Anything else under `docs/` that has no front matter is published as it is.

## Checking what a tool loaded

- **Claude Code:** `/memory` lists the instruction files, and `/context` shows what they cost. Read a file in `mcp/`, and `mcp-and-public-api.md` appears.
- **Gemini CLI:** `/memory show` shows everything loaded, with the imports filled in.
- **Codex:** ask it which instruction files it was given. Then ask it to change a file in `mcp/`, and check that it opens `mcp/AGENTS.md` and the topic file.
