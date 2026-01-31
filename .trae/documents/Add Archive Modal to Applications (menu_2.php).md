## Goal
- In [menu_2.php](file:///c:/xampp/htdocs/scholar/public/menu_2.php), add an **Archive** button (after Export CSV) that opens a modal showing **application history**.
- Main Applications table should show **only**: For Review + Incomplete.
- Archived (history) modal should show **only**: Accepted + Rejected.
- When a row is changed to Accepted/Rejected, it disappears from the main table and appears in the archive modal.
- Add **filters inside the archive modal**.

## UI Changes (menu_2.php)
1) Toolbar
- Add a new button `Archive` placed **after** the existing Export CSV button.

2) Archive Modal
- Add a new modal section similar to the existing documents modal.
- Modal content:
  - Filters row: Search, Year Level (All/1st/2nd/3rd/4th), Status (All/Accepted/Rejected)
  - Table: Name, Year Level, Status
  - Pagination + per-page selector (same style as the main page) so history can scale.

## JS/Data Changes (menu_2.php)
1) Split data views (no backend changes)
- Keep `students` array as the full source of truth.
- Add helper filters:
  - Active list: status in {For Review, Incomplete}
  - Archive list: status in {Accepted, Rejected}

2) Main table
- Update `filtered()` to always start from the Active list so Accepted/Rejected never render in the main table.

3) Archive modal rendering
- Add `renderArchive()` that renders from the Archive list and applies modal filters.
- Add open/close handlers for the modal.

4) Status changes
- In the existing `change` handler (`select[data-status-idx]`), after updating `students[i].status`:
  - call `render()` (main)
  - call `renderArchive()` if the archive modal is open

## Verification
- Ensure main table only lists For Review + Incomplete.
- Change a row to Accepted/Rejected and confirm it disappears from main and appears in Archive modal.
- Confirm archive filters work (search/year/status).
- Run editor diagnostics to ensure no JS/PHP errors.