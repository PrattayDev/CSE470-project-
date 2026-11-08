# UniNexus — Feature 9 (Rownak Jahan — ID: `23101159`)

UniNexus is a Laravel 10 MVC application scoped in this branch (`rownak-sprint1`) to **Feature 9** for Sprint 1:

- **Feature 9: Document Version Control & SRS/Report Submission Portal** (`DocumentVersionController`, `DocumentSubmission` Model, `resources/views/documents/index.blade.php`)
  - Commit sequential semantic document versions (`v1.0` → `v1.1` → `v2.0`) for SRS, UML Class Diagram, Sprint Reports, and Final Reports with private file storage (`storage/app/private/documents`) or Drive URLs, team ownership checks, and supervisor review/approval.

## Shared Infrastructure Retained
Authentication (`AuthController`), Dashboard (`DashboardController`), MySQL Schema (`database/uninexus_mysql_dump.sql`), and Migrations (`database/migrations/2026_10_15_000001_create_uninexus_all_tables.php`).
