# Dark Mode Integration Update — 2026-09-24

## Purpose
Correct inconsistent dark-mode rendering between the existing Carder Management screens and the newer Enterprise Control screens.

## Updated
- Employee Profile Handling Count Tracking now uses Material theme tokens for cards, notes, tables, filters, badges, progress bars and buttons.
- Profile Handling Chart.js charts now inherit current theme text/grid colours and refresh palette when the Light/Dark toggle is changed without reloading the page.
- Theme toggle now broadcasts a `carder:theme-changed` event for theme-aware widgets.
- Legacy alerts and native form controls receive dark-mode styling through the global theme layer.
- Role analytics/dashboard panels, quick actions, mini tables and status rows now use dark theme surfaces and semantic status colours.
- Car Pass administration post picker no longer uses light-only hard-coded backgrounds.
- Live Letter workspace chrome/statuses/notices now follow dark mode.
- Enterprise status text now uses semantic theme tokens.
- CSS cache versions were increased so browsers fetch the corrected styles.

## Intentionally kept white
Official document/paper previews and signature image canvases remain white because they represent printable stationery rather than application chrome.
