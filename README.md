# UniNexus — Feature 1 & Feature 2 (Prattay Dev Burman — ID: 23101048)

UniNexus is a Laravel 10 MVC application scoped in this branch (prattay-sprint1) to **Feature 1** and **Feature 2** for Sprint 1:

- **Feature 1: Multi-Role Academic Profile & Skill Matrix Management** (ProfileSkillController, User Model, esources/views/profile/index.blade.php)
  - Manage student/faculty academic profiles, technical skill tags, CGPA (students), supervision group quotas (faculty), profile completeness score, and Supervisor Match Score ranking.
- **Feature 2: Smart Team Formation & Teammate Finder** (TeamFormationController, Team, TeamMember, TeamInvitation Models, esources/views/teams/index.blade.php)
  - Initialize 4-member CSE 470 groups, search teammates by skill/section, send/accept/decline invitations, and enforce atomic 4-member auto-lock.

## Shared Infrastructure Retained
Authentication (AuthController), Dashboard (DashboardController), MySQL Schema (database/uninexus_mysql_dump.sql), and Migrations (database/migrations/2026_10_15_000001_create_uninexus_all_tables.php).
