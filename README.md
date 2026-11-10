# Notebook for web2project

Notebook keeps notes in web2project. A note has a title, a plain-text body, a
category, a status and an optional URL. It can be filed on a company, a
project or a task, and those pages get a Notes tab listing their notes. Each
user's page has a Notes tab with the notes they wrote.

Version 5.0.0 is for **web2project 5.0 or later** and **PHP 8.3 or later**.
Versions 3.x and 4.x of the module are for web2project 3.x and 4.x and do not
work on 5.0.

## Installing

1. Copy the `notebook` folder into web2project's `modules/` folder, so the
   module is at `modules/notebook/`.
2. In web2project, go to System Admin › View Modules.
3. Click "install" next to Notebook, then "disabled" to enable it, and
   "hidden" to show it in the menu.
4. Give your roles access to the Notebook module (System Admin › User Roles).

## Upgrading from 3.x or 4.x

**Back up your database first.** Then replace `modules/notebook/` with this
version and click "upgrade" next to Notebook in System Admin › View Modules.

The upgrade changes your data:

- **Note bodies become plain text.** Earlier versions used the TinyMCE editor,
  which web2project 5 no longer ships. Bodies that contain HTML are converted:
  paragraphs and line breaks are kept, list items become lines starting with
  "- ", and links become "text (url)". Images, tables and text formatting are
  lost. Bodies without HTML are not changed.
- **Creators are filled in.** Earlier versions never saved who created a note.
  Where web2project's history has the note's "Create" entry, its user is used.
  Other notes keep an unknown creator.
- The old `note_title` column is dropped. Titles have been kept in
  `note_name` since 4.0.

## Using it

- **Notebook** in the menu lists all the notes you can see, with a tab per
  category. Filter by company, project and status, or search titles and
  bodies. The filters are remembered while you are logged in.
- **Add Note** on a company, project or task's Notes tab files the new note
  there. A note on a task belongs to that task's project, and a note on a
  project to that project's company.
- **Private** notes are shown only to the user who wrote them. Private notes
  from before 5.0 whose creator is unknown are shown to system administrators,
  so they can be reassigned or removed.
- A note on a project or company is only shown to users who can see that
  project or company. Your role's Notebook permissions decide who can add,
  edit and delete notes.

Categories and statuses are System Lookup Values (`NoteCategory` and
`NoteStatus`) and can be changed in System Admin › System Lookup Values.

## Translations

The module's strings are listed in `notebook/locales/en.inc`. To translate
them, add `notebook/locales/<code>.inc` (for example `fr.inc`) with one
`"English"=>"Translation",` line per string.

## License

This module is licensed GPL (http://www.gnu.org/copyleft/gpl.html), Clear BSD
or MIT, whatever suits you best and where/when applicable.

Copyright (c) 2007-2010 Pedro Azevedo, web2Project Team

All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted (subject to the limitations in the
disclaimer below) provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.

 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the
   distribution.

 * Neither the name of Pedro Azevedo and web2Project Team nor the names of its
   contributors may be used to endorse or promote products derived
   from this software without specific prior written permission.

NO EXPRESS OR IMPLIED LICENSES TO ANY PARTY'S PATENT RIGHTS ARE
GRANTED BY THIS LICENSE.  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT
HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
