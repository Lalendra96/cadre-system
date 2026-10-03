# Service Letter Template Library

This update adds **120 starter templates**: 40 administrative occasions × English, Sinhala and Tamil.

## Coverage

1. General service confirmation
2. Detailed service & experience certificate
3. Current employment verification
4. Appointment confirmation
5. Designation & duties confirmation
6. Salary & employment confirmation
7. Work & conduct / good standing
8. Disciplinary-status confirmation
9. Length of service
10. Promotion / grade
11. Increment / salary progression
12. Transfer
13. Release / relieving
14. Secondment / temporary release
15. Retirement / pension
16. Resignation / separation
17. Local employment application
18. Foreign employment application
19. Embassy / visa employment verification
20. Work / residence permit
21. International credential verification (DataFlow / EPIC / WES etc.)
22. Higher education / university application
23. Scholarship / fellowship
24. Local training / course nomination
25. Foreign training / fellowship NOC
26. Overseas conference / workshop NOC
27. Professional registration / licensing
28. Bank / loan / financial institution
29. Housing / rental / workplace confirmation
30. Court / legal / statutory authority
31. Insurance / benefit claim
32. Research / academic appointment
33. Internship / clinical attachment
34. Teaching / training experience
35. Official foreign mission / duty travel
36. General NOC
37. Passport / immigration factual employment letter
38. Former employee service certificate
39. Public service / competitive examination application
40. Factual employer reference

## Install

```bash
php artisan migrate
php artisan db:seed --class=ServiceLetterTemplateSeeder
php artisan optimize:clear
```

The templates are editable drafts. Prompts beginning with `[EDIT: ...]`, `[සංස්කරණය කරන්න: ...]`, or `[திருத்துக: ...]` must be completed/reviewed before approval. Exact specimen wording required by a current circular, foreign authority, regulator, embassy, university or verification provider takes precedence over these generic templates.
