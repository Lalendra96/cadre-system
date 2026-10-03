# Employee Profile Allocation Update — 2026-09-18

## Behaviour

- HR **position responsibility** says which positions a Subject Officer may manage.
- **Employee Profile allocation** says which individual employees that officer may actually open.
- A Subject Officer sees only explicitly allocated Employee Profiles, even when several officers share the same position.
- A profile has one active Subject Officer allocation at a time. Reallocation ends the previous allocation but retains history.
- Super Admin allocates profiles from **HR Responsibilities → Allocate Employee Profiles to Subject Officers**.
- Admin Group does not receive Employee Profile access; its employee view remains aggregated **Employee Counts** only.
- Super Admin and Planning Officer retain full Employee Profile oversight.

## Migration baseline

`2026_09_18_000102_create_employee_hr_allocations.php` creates the allocation table.

For safe rollout it automatically allocates employees only when a position currently has exactly one effective Subject Officer (including the legacy Subject Code fallback). Positions shared by two or more officers are deliberately left unallocated until Super Admin distributes those profiles.

## Navigation

Legacy emoji/symbol prefixes are removed from persisted nav labels. The sidebar now uses one consistent inline SVG icon strategy, preventing duplicate icons such as `⇄ 🔄 Transfer Records`.

## Apply

```bash
php artisan migrate
php artisan optimize:clear
```

Then log in as Super Admin and open **HR Responsibilities**. For positions with shared responsibility, allocate the individual Employee Profiles to the appropriate Subject Officer(s).

## Validation

```bash
php artisan tinker
```

```php
DB::table('employee_hr_allocations')->whereNull('ended_at')->count();
DB::table('employees')->where('is_active', true)->count();

// Unallocated active profiles
DB::table('employees as e')
    ->leftJoin('employee_hr_allocations as a', function ($join) {
        $join->on('a.employee_id', '=', 'e.id')->whereNull('a.ended_at');
    })
    ->where('e.is_active', true)
    ->whereNull('a.id')
    ->select('e.id', 'e.name', 'e.position_id')
    ->get();
```
