# Contacts directory

## Where contacts live

Contact entries are **not** stored in a database. They are loaded from **text files** in:

- **Directory:** `app/data/contacts/`
- **Format:** CSV with header (see below).

The Contacts page reads all `*.csv` files in that directory and merges them into one list. No contacts are hard-coded in the PHP pages.

## File format

Use **CSV (comma-separated values)** with this exact header line:

```csv
name,role,email,phone,discord
```

- **name** — Display name (e.g. "Support", "Sales").
- **role** — Role or department (e.g. "Support", "Partnerships").
- **email** — Email address (e.g. `support@passandplay.com`).
- **phone** — Phone number (optional; can be empty).
- **discord** — Discord handle or similar (optional; can be empty).

Example:

```csv
name,role,email,phone,discord
Support,Support,support@passandplay.com,+1 (555) 100-1000,support#0001
Sales,Sales,sales@passandplay.com,+1 (555) 100-2000,sales#0002
```

- Use one row per contact.
- If the header does not match exactly, that file is skipped.
- Empty rows (no name and no email) are skipped.
- All output is escaped when rendered to prevent XSS.

## How to add or edit contacts

1. Open or create a `.csv` file under `app/data/contacts/` (e.g. `contacts.csv`).
2. Ensure the first line is: `name,role,email,phone,discord`.
3. Add one line per contact with comma-separated values. If a value contains commas, wrap it in double quotes.
4. Save the file. The Contacts page will pick it up on the next load (no deploy step beyond updating the file in the repo or on the server).

## Implementation notes

- Loading is done in `app/includes/contacts_loader.php` (function `load_contacts_from_files()`).
- The Contacts page (`contacts.php`) only uses that function and never embeds contact data in code.
- Missing or unreadable files/directories result in an empty list; the page shows a friendly “no contacts” message.
