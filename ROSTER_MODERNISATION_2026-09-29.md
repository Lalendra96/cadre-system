# Roster Modernisation — 2026-09-29

## Scope
The Roster Plan Builder and Roster Template Builder were modernised while retaining the Carder Management MD3/custom Material token system and existing server-side workflow.

## Implemented changes
- Custom date-range roster calendar.
- Visual duty cards with employee initials.
- Initials are generated from the decrypted employee name without salutation: `Keerthi Tennakoon` → `KT`.
- Full employee name remains visible in duty details and calendar hover title.
- Time-slot legend: Morning, Evening, Night, On-call, Cross-unit and Conflict/Leave.
- Calendar day click creates a duty for that date.
- Drag-and-drop an existing duty card to another calendar day to reschedule it.
- Reusable template selection applies configured template slots to the plan and honours each slot's required-staff count.
- Schedule summary: duties, employees, cross-unit assignments and warnings.
- Existing conflict/leave checking is surfaced visually in calendar cards.
- Duty duplication/removal controls.
- Searchable employee assignment editor.
- Cross-unit coverage remains separate from the permanent employee unit.
- 24-hour visual coverage map in Template Builder.
- Template slot duplication and deletion.
- Required staffing count remains configurable per template slot.
- Sticky save/status actions for long roster plans.
- Responsive MD3 styling without a new UI framework or npm dependency.

## Design references
The interaction model adapts established workforce-scheduling patterns such as reusable schedule templates, copy/duplicate operations, availability/conflict visibility, visual shift calendars and rapid shift creation. It does not copy another product's visual design.

## PII handling
Roster employee values are supplied through the `Employee` Eloquent model. The roster JavaScript payload receives already-decrypted display values from the application layer; the database ciphertext is never rendered directly and no client-side decryption is introduced.
