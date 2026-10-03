# Code Structure Refactor — 2026-09-21

This refactor removes the new Secure Live Letter Editor implementation from a single-file pattern and separates responsibilities into maintainable Laravel files.

## Refactored

- `ServiceLetterController.php`
  - reformatted to PSR/Laravel style
  - validation extracted to Form Request classes
  - repeated revision creation extracted to a controller helper
  - method return types added
  - imports normalized
- `routes/web.php`
  - service-letter route block moved to `routes/service_letters.php`
- `resources/views/service-letters/form.blade.php`
  - reduced to the page shell
  - form sections split into Blade partials
  - inline JavaScript moved to `public/js/service-letter-form.js`
- `resources/views/service-letters/editor.blade.php`
  - reduced to the editor shell
  - header, document, and sidebar split into Blade partials
  - inline CSS moved to `public/css/service-letter-editor.css`
  - inline JavaScript moved to `public/js/service-letter-editor.js`
- Validation classes added under `app/Http/Requests/ServiceLetters/`.

## Design rule going forward

New features should not place controllers, validation, business logic, styling, JavaScript and large UI blocks in one file. Keep:

- request validation in Form Requests;
- authorization/business rules in policies/services where reusable;
- controllers focused on orchestration;
- large Blade pages split into semantic partials/components;
- browser JavaScript/CSS in standalone static files when no build step is required;
- feature routes in dedicated route files when a route block becomes large.

Existing legacy large files elsewhere in the application were not behaviorally rewritten in this patch, to avoid broad regression risk unrelated to the Secure Letter Editor.
