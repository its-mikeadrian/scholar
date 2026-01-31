## Scope
- Admin UI only: remove any remaining GWA and “Inputted Grade/Grade” displays from admin pages.
- Keep backend/data structures intact unless they only exist as sample/demo UI data.

## What I Found
- Admin Applications page [menu_2.php](file:///c:/xampp/htdocs/scholar/public/menu_2.php):
  - Sort dropdown has Grade ↑/↓
  - Table column labeled “Inputted Grade” and renders `s.grade`
  - CSV export includes “Inputted Grade” and `s.grade`
  - Fallback sample data includes `grade`
- Admin Dashboard [menu_1.php](file:///c:/xampp/htdocs/scholar/public/menu_1.php):
  - “Top 5 by Grade” card shows a Grade column and sorts by `a.grade`
- Shared admin header sample data [header.php](file:///c:/xampp/htdocs/scholar/public/header.php):
  - Demo `window.AppData.applications` includes `grade` fields
- GWA is already removed from the codebase.

## Changes To Make
1) Update admin Applications page (menu_2.php)
- Remove Grade sorting options from the Sort dropdown.
- Remove the “Inputted Grade” table header and the grade `<td>` in each row.
- Update sorting logic so it no longer expects the `grade` sort key.
- Update Export CSV to exclude grade (remove column + header).
- Remove `grade` from the fallback sample student objects.

2) Update admin Dashboard page (menu_1.php)
- Remove the entire “Top 5 by Grade” card/table.
- Remove the associated JS that sorts by `grade` and renders grade values.
- Adjust layout so the remaining analytics sections still look correct.

3) Clean up shared demo data (header.php)
- Remove `grade` from the sample `window.AppData.applications` objects (since admin pages won’t display it).

## Verification
- Repo-wide search confirms no `gwa/GWA` remains.
- After edits, search for `Inputted Grade` and `\bgrade\b` in `public/menu_*.php` and `public/header.php` to ensure admin UI no longer references them.
- Run editor diagnostics to ensure no JS/PHP syntax issues introduced.