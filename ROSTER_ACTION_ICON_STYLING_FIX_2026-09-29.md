# Roster action icon styling fix — 2026-09-29

## Issue
The dynamic Copy/Delete actions in the Roster Template and Roster Duty cards could render the Material Symbols ligature text (`content_copy`, `delete`) instead of icons. This caused overlapping/clipped text in the card header.

## Fix
- Replaced Material Symbols in dynamic Roster action buttons with inline SVG icons.
- Added fixed MD3 circular icon-button sizing and flex shrink protection.
- Added hover, focus-visible, active and danger-container states using existing Carder MD3 tokens.
- Applied the same correction to template time-slot actions and roster duty duplicate/remove actions.
- No external icon font, CDN, npm package or JavaScript library was introduced.

## Accessibility
Each icon-only button now includes a descriptive `aria-label` and title.
