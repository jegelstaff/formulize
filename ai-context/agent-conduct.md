# Working safely

Rules for what an agent does while working on this codebase, each with the reason behind it.

## Testing a guard on a live system

A script that tests "is this operation refused?" must be safe if the operation is **not** refused. Assert the refusal and the unchanged state. Never rely on a restore step at the end of the script to undo an operation you expected to fail, and don't use a real destructive call to find out whether destruction is possible.

- Test refusal paths by asserting the refusal **and** re-reading state to confirm it didn't change. If the guard is missing, the assertion fails loudly instead of the data changing silently.
- Capture the before-state of everything the script could touch, not only what you expect it to touch, and restore unconditionally.
- Take a database dump first. A transaction isn't enough, because a rollback doesn't undo everything a write can trigger, such as derived values written through `writeEntry()`.
- Never aim a destructive probe at the three system groups (Webmasters 1, Registered Users 2, Anonymous Users 3) or at real user accounts. Use a throwaway group or user, or read the code.
- Where the question is "does a guard exist?", reading the code answers it without touching data. Do that first, and only run the operation once the guard is confirmed present.

**Why:** a script once tested an MCP tool by removing a user from the Registered Users group, to see whether mandatory-membership protection held. It didn't, and the removal succeeded. The script's restore logic only covered the operations expected to succeed, so nothing repaired it.

## Testing auth, 2FA and permission flows

Test these against a disposable user, never a real account. Exercising a security path means exercising its side effects, and those land on whichever account you point it at.

- Before you test one of these flows, create a disposable user and point the test at that uid. Never use uid 1, the account of the person you're working with, or any account someone uses.
- Before you touch anything that could be destroyed, capture enough state to restore it, and prefer restoring from a snapshot to rebuilding by hand.
- Assume any code path you exercise may delete more than its comment claims.
- Confirm the target is local-only before you run anything, not after something goes wrong.

**Why:** a test of a 2FA fix once ran against uid 1, and the code deleted more than its comment said it would. The account was locked out, and with no snapshot the data couldn't be recovered. Reading the code first might not have caught this. A throwaway account is for the branch you didn't read.

## Don't make absolute claims from a filtered search

Never make an absolute claim ("this is the only caller", "nothing else uses this", "it never runs") from a search that had an exclusion in it. Either drop the filter before concluding, or state the conclusion in the same narrowed terms the command actually supports.

- To skip a definition, exclude the *definition pattern* (`grep -v "function foo"`), not the file that contains it. Definitions and callers routinely share a file.
- When output is only a few lines, don't filter at all. Read them.
- Before writing "the only", "nothing else" or "never", re-read the command that produced the evidence. If it narrowed anything, narrow the claim.
- Treat a surprising absolute result ("only one caller of a public API?") as a prompt to re-run unfiltered, not as something to report.

**Why:** a search for callers of a method once excluded the file it was defined in, to skip the definition. That also hid an internal caller in the same file, and the one remaining hit was reported as "the only caller in the whole codebase". "The only caller outside this file" would have been true, and would have prompted the right question. The wrong conclusion led directly to the destructive test described above.

## Answer infrastructure questions by running them

When answering questions about Docker, Compose or CI mechanics, such as whether `image:` and `build:` together force a rebuild, whether Compose reads `.env` automatically, or whether a stale local image gets silently reused, build a small isolated reproduction and run it rather than citing remembered documentation.

When a question has a testable yes/no answer and Docker is available, build a minimal throwaway reproduction in a temporary folder, away from the real containers. Run it, inspect the result (labels, `docker inspect`, `docker compose config`), then clean up (`docker compose down`, `docker rmi`) before answering. Don't do this for questions with no testable behaviour, such as pure design trade-offs. Reason those through directly.

**Why:** precedence rules like `pull_policy: missing` are easy to state wrongly from memory. In one review of Docker changes, running tiny compose files caught a subtlety that memory alone would have missed: Compose falls back to building when a brand-new tag can't be pulled.

## Keep git history searches narrow

The history is large, with thousands of commits, and a clone can have hundreds of branches. A search across all of it can run for minutes.

- **Start with the current files.** `grep`, or `git grep` with no branch names, usually answers the question, and it's instant.
- **Limit a history search to a path:** `git log --oneline -S some_term -- tests`. That took under a second here, while the same search with `--all` was still running after 90 seconds.
- **Don't use `--all`, or list branches for `git grep`,** unless you really need to know about a branch that isn't merged. Then name the branch.

## Use the real tools

If an MCP server's tools are missing or out of date, ask the person to reconnect it, or to restart. Don't work around it by writing a script that calls the system with credentials from a configuration file. A diagnostic that uses the server's own local tools is fine.

## Keep credentials out of the conversation

Don't read a file that holds live credentials, such as anything in a site's `trust/` folder, an installed site's `mainfile.php`, a `.env` file, or a settings file with API keys. Whatever a tool prints goes into the conversation unredacted, is sent again with every later request, and stays in the transcript. Once a secret is there, the only remedy is to replace it.

If you need something from one of these files, filter it: use `grep` for the one line you need, or `grep -v` to drop lines with passwords, salts, tokens and keys, or mask the values with `sed`. Tell any subagent to do the same. Don't copy a credential into a note, commit, log or report. The local Docker setup's test logins aren't secrets.

## When your memory disagrees with the notes

Don't pick one, because either could be out of date. Check the code, go with what it shows, and tell the person, so they can correct whichever is wrong. Don't change or delete their memory on your own. If they want it checked as a whole, see [reconciling-memory](reconciling-memory.md).
