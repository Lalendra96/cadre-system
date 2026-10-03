# Car Pass Eligible Post Picker UI Update — 2026-09-23

## Updated area
Car Pass Administration → Upload Pass Format → Eligible Posts.

## Changes
- Replaced the browser-native multi-select list with a professional searchable checkbox picker.
- Added selected-post count.
- Added searchable filtering by post title or post code.
- Added **Select visible** for filtered bulk selection.
- Added **Clear** action.
- Added removable selected-post chips.
- Added a bounded scrollable two-column post list to prevent layout overflow.
- Existing `position_ids[]` request format is preserved, so existing validation and template-post mappings remain unchanged.
- Previous form selections are restored automatically after validation errors.
- UI changes are scoped to Car Pass Administration only.

## Governance
The selector only changes usability. It does not automatically decide pass eligibility. System Admin remains responsible for explicitly mapping each pass-template version to authorised posts.
