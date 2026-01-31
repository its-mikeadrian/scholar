## What
- Update the Applications page status control in [menu_2.php](file:///c:/xampp/htdocs/scholar/public/menu_2.php) to support two additional statuses: **Incomplete** and **Rejected**.

## Changes
1) Add new status options to the status `<select>`
- Keep existing: For Review, Accepted
- Add: Incomplete, Rejected

2) Update status styling (badge + select)
- Accepted: green (existing behavior)
- For Review: amber (existing behavior)
- Incomplete: gray (new)
- Rejected: red (new)

3) Keep existing behavior
- The row badge text continues to show the selected status.
- The change handler continues to update `students[i].status` and `window.AppData.applications`.
- CSV export already includes Status and will automatically export the new values.

## Verification
- Check diagnostics for JS errors.
- Quick search in menu_2.php ensures the select contains the 4 statuses and the styling logic handles them.