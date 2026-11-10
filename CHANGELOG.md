# Changelog

## 5.0.0 (unreleased)

For web2project 5.0 and PHP 8.3 or later.

**Security**
- Search terms are quoted in the notes list (SQL injection).
- Stored values are escaped on the add/edit form (stored XSS).
- The Notebook pages check module access, and notes on projects or companies
  a user can't see are hidden from the list, the tabs and SmartSearch.
- Private notes are shown only to their creator.

**Changed**
- TinyMCE is gone (web2project 5 doesn't ship it). Note bodies are plain text;
  the upgrade converts stored HTML to text.
- Setup: `note_name` is in the table definition, requirements are checked,
  failed installs report why, and the stale `note_title` column is dropped.
- "New Note" and "Add Note" are Bootstrap buttons, and the pages drop their
  leftover inline layout.
- An English locale file lists the module's strings for translators.

**Fixed**
- Notes keep their project, task and company when added or edited, and a
  note on a task gets the task's project.
- The creator is saved, and filled in from history on upgrade.
- Private can be unticked.
- The category tabs, the Users tab, the filters and the page links on the
  Notes tabs work.
- The task name shows when editing, the URL "test" link handles https, the
  list is newest first, and the view page shows Edit/Delete only when allowed.
- PHP notices and warnings on the add/edit, view and tab pages.

## 4.0.0

For web2project 4.x. `note_title` renamed to `note_name`; the list uses the
core list table.

## 3.0.0

For web2project 3.x.
