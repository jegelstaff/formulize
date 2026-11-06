# Reconciling your memory with these notes

Use this when someone asks you to check their AI tool's memory, or their personal notes, against `AGENTS.md` and `ai-context/`. Don't start it on your own. Memory belongs to the person, so only they can decide to change it.

The aim is to remove what's duplicated or out of date, so that memory only holds what these notes can't: things that are personal or private, or that aren't settled yet.

## Where memory is

It depends on the tool. Claude Code keeps it in `~/.claude/projects/<project>/memory/`, as one file per memory, with an index in `MEMORY.md`. Each clone of the repository has its own folder. For another tool, check its documentation, or ask the person. Memory for a different repository isn't a duplicate of these notes, even if the subject overlaps, because a session there can't see them.

## What to do

1. **Back it up.** Copy the whole memory folder somewhere safe before anything else. Deleted memory can't be recovered.
2. **Protect credentials.** Memory can hold them, so mask anything that looks like a password, key or token before reading, and don't open a memory described as holding one. See [Keep credentials out of the conversation](agent-conduct.md#keep-credentials-out-of-the-conversation).
3. **Read every memory and the notes it relates to.** Compare what they say. A script that lists the identifiers a memory has and the notes don't is a good way to find where to look, but it can't decide anything: most differences are only wording, and a rule can be missing from the notes with no unusual identifier to show for it. Before calling something missing from the notes, or deleting a memory because they cover it, read the note it belongs in. A search can miss the same rule in different words.
4. **Check what the memory says about the present.** Much of what goes stale isn't a fact about the code, but a status: a branch waiting to merge, a fix waiting to deploy, a workaround to remove later. Check the branch, the live site or the code. Where memory and a note disagree, check the code, because either could be wrong. If a note is wrong or missing something, tell the person, so it can be fixed like any other change to the repository.
5. **Sort each memory:**
   - **Delete:** everything in it is in the notes, or is no longer true.
   - **Trim:** the general point is in the notes, but it also holds something private or specific worth keeping.
   - **Correct:** it's worth keeping, but part of it is out of date.
   - **Keep:** everything else.
6. **Ask about what you can't judge.** Whether work is finished, and whether an idea is worth keeping, are the person's to say. So is whether local details are worth keeping at all: a local environment may be throwaway, so table prefixes and snapshot paths may be worth nothing.
7. **Show the person the list, with a reason for each change.** Change nothing until they've approved it, or told you to go ahead.
8. **Make the changes,** then update any index, and check that nothing in it points at a deleted file and nothing is missing from it.
9. **Tell the person where the backup is,** and that it still holds everything, including anything you deleted because it was sensitive.

## What stays in memory

If in doubt, keep it. These are examples of things that belong in memory and not in the notes, because the repository is public:

- Personal preferences and working habits
- Details of particular sites, clients or machines
- Security findings that haven't been fixed
- Where credentials are kept
- Plans and work in progress
- Decisions that aren't visible in the code

Don't move anything from memory into the notes unless the person asks. If you find something that belongs there, keep the memory and tell them.

Look out for instructions in memory that are out of date and do damage if followed, like a script for resetting a database. Those are worth finding first.
