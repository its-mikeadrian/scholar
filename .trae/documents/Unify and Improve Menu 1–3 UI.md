## Goals
- Make menu_1, menu_2, and menu_3 visually consistent (same card layout, spacing, typography, borders/shadows, and control styling).
- Improve readability and hierarchy without changing page behavior or data logic.

## Current State (Quick Audit)
- All three pages already share the same shell (Tailwind CDN, header.php, sidebar.php, main container).
- menu_2 and menu_3 already have a modern “filters card → table card → pagination” layout.
- menu_1 has good content, but its section structure and card styling don’t fully match menu_2/menu_3.

## Design System (Tailwind-only, no new dependencies)
- Standardize section containers to one consistent pattern:
  - Section card: rounded-2xl bg-white p-6 shadow-sm border border-slate-100
- Standardize small UI patterns:
  - Inputs/selects: rounded-xl border px-3 py-2 text-sm + consistent focus ring
  - Primary button: rounded-xl bg-[#1e88e5] text-white hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]
  - Secondary button: rounded-xl border text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]
  - Tables: same header color, row hover, consistent cell padding

## File-Level Changes
### 1) menu_1.php (Dashboard)
- Restructure into two consistent section cards (matching menu_2/menu_3):
  - Card A: “Dashboard Analytics” + the 5 metric tiles with improved spacing, subtle background, and optional icons.
  - Card B: “Year Level Distribution” (move it into its own card and match table-card styling).
- Align headings (text-xl font-semibold) and small labels (text-xs text-[#293D82]) with menu_2/menu_3.

### 2) menu_2.php (Applications)
- Make the top filter card and the table card use the same “section card” pattern (add border to top card, add shadow-sm to the table card).
- Harmonize button hover/focus states (especially Clear Filters / Prev / Next).
- Keep all JS behavior identical.

### 3) menu_3.php (Payout Checklist)
- Apply the same card standardization as menu_2 (border + shadow consistency).
- Align modal buttons (Cancel/Confirm) with the same primary/secondary styles used elsewhere.
- Keep confirm flow and checklist logic unchanged.

## Verification
- Load menu-1/menu-2/menu-3 and verify:
  - Spacing is consistent across pages (top card, second card, pagination).
  - Responsive layout works (mobile → sm → lg) with no sidebar overlap.
  - Modals still open/close properly (menu_2 doc + archive, menu_3 confirm).
  - No JS console errors; metrics still compute from window.AppData.

If you confirm, I’ll implement these UI-only edits in the three PHP files (no backend/auth changes).