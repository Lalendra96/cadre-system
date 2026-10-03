# Workforce MD3 Layout Fix — 2026-09-28

## Issue
The newly merged Workforce views were rendering outside the shared `layouts.app` shell. This caused the Workforce Configuration page and related Workforce screens to appear as largely unstyled browser HTML (serif font, missing application navigation/header, missing MD3 styling and excessive whitespace).

## Root cause
The affected Blade files included `workforce.partials.style` but did not extend `layouts.app` or define a `content` section. Therefore the project's MD3/custom Material CSS, application shell and navigation were never loaded for those pages.

## Corrections
- Wrapped all new Workforce operational views in `@extends('layouts.app')` and `@section('content')`.
- Added page titles through `@section('title', ...)`.
- Rebuilt `workforce/settings/index.blade.php` using the existing MD3/custom Material card/button/tokens.
- Added responsive module cards, clear status chips, dependencies, safe-disablement guidance, success/error feedback and statutory profile presentation.
- Linked Workforce configuration back to the existing Feature Management screen.
- Kept all styles based on the existing Material token variables; no external CSS/JS dependency was added.

## Views corrected
- Workforce Dashboard
- Attendance
- Leave
- Overtime
- Contracts
- Payroll
- Employee Self-Service
- Locum / Session Payments
- Cost Centre Analytics
- Workforce Module Configuration

## Validation
- All Workforce non-partial views now extend `layouts.app`.
- 562 PHP files in app/routes/database lint successfully.
