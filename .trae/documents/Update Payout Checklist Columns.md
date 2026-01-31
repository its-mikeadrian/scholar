## Goal
- In [menu_3.php](file:///c:/xampp/htdocs/scholar/public/menu_3.php), remove the “Action” column and add a “Semester” column with values “1st Sem” / “2nd Sem”.

## What Will Change
1) Table columns (UI)
- Replace the current headers:
  - Remove “Action”.
  - Add “Semester” between “Year Level” and “Paid”.
- Update each row to render `s.semester` in the new column.

2) Data model (JS)
- Extend each payout checklist item to include `semester`.
- For fallback sample data, add `semester: '1st Sem'` / `semester: '2nd Sem'`.
- Keep existing `window.AppData.checklist` usage; only add the new property.

3) Sorting / filtering / chips
- Add optional Semester filter dropdown (All / 1st Sem / 2nd Sem) so the new column is actually usable.
- Update sort dropdown to support sorting by semester (or keep only Name/Year Level and leave Semester sortable via the column header).

4) Remove “Action” behavior
- Remove the View button in each row.
- Remove click-handler logic for `data-view-idx`.
- Remove the unused doc modal section (or leave markup but detach all triggers); preferred: remove the modal markup since it becomes unreachable.

5) Export CSV
- Update CSV export to include Semester and remove any Action-related output.

## Verification
- Ensure the table renders with the new column.
- Confirm no remaining references to `data-view-idx` / “Action” in `menu_3.php`.
- Run editor diagnostics to confirm no JS errors.

If you confirm, I’ll apply the edits directly to `menu_3.php`.