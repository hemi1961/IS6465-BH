# Peer Tutoring Hub — Lab 7 (User Management, Authentication & Authorization)

Extension of the IS 6465 Lab 6 three-tier PHP/PDO/SQLite Peer Tutoring Hub adding complete User Management (**UM-01 through UM-10**), password hashing, secure session management, CSRF protection, role-based access control (RBAC), and SQL-enforced record ownership.

Uses PHP 7.0+ and PHP 8.x compatible procedural PHP syntax (requires `pdo_sqlite` and `mbstring` extensions).

## Architecture (Three-Tier)

- **Presentation (`php/`, `includes/`, `css/`):** HTML5/Bootstrap pages, shared layout templates, and `includes/auth.php` session/role/CSRF guards.
- **Business Logic (`services/`):** `userService.php` (UM-01..UM-10 rules and transactions) and `requestService.php` (UC01/UC02 and actor-scoped request workflows).
- **Data Access (`repositories/`, `config/`, `database/`):** `userPDO.php`, `studentPDO.php`, `requestPDO.php`, `config/db.php`, `database/setup-database.sql`, and `database/seed-database.php`.

## Demo Classroom Accounts (Fictional Test Data)

Pre-seeded in `database/tutoring.db` (and reproducible via `php database/seed-database.php`):

| Role | Name | Email | Password |
|---|---|---|---|
| Student | Maria Lopez | `maria.lopez@example.edu` | `StudentPass123!` |
| Student | Jordan Lee | `jordan.lee@example.edu` | `StudentPass123!` |
| Tutor | Taylor Kim | `taylor.kim@example.edu` | `TutorPass123!` |
| Administrator | Alex Morgan | `admin@example.edu` | `AdminPass123!` |

## Setup & Run in GitHub Codespaces

1. Check PHP version:
   ```bash
   php -v
   ```
2. (Optional) Rebuild and re-seed the SQLite database from scratch:
   ```bash
   cd lab7
   rm -f database/tutoring.db
   php database/seed-database.php
   ```
3. Start the built-in PHP server with the security router from inside `lab7/`:
   ```bash
   php -S 0.0.0.0:8000 php/router.php
   ```
4. Open forwarded port `8000`. Visiting `/` redirects to `/php/login.php` (or `/php/profile.php` when authenticated).

## Verification & Automated Tests

Lint all PHP files:
```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Run all automated CLI tests (uses isolated `sqlite::memory:` databases — never touches `database/tutoring.db`):
```bash
php tests/user-management-tests.php
php tests/integration-tests.php
php tests/validation-tests.php
```

## Security Controls Implemented

- **Passwords:** One-way salted hashing with `password_hash(..., PASSWORD_DEFAULT)` and `password_verify()`, plus transparent `password_needs_rehash()`.
- **Sessions & Cookies:** `httponly`, `samesite=Lax`, HTTPS-aware `secure`, `session_regenerate_id(true)` on login and password change, minimal session payload (`user_id`, `display_name`, `role`, `last_activity`), 20-minute inactivity timeout, and fresh DB reload on every protected request (detects deactivated accounts immediately).
- **CSRF Protection:** Random 256-bit per-session tokens verified with `hash_equals()` on every state-changing POST endpoint (including Logout).
- **Authorization & Ownership:** Shared `requireLogin()` and `requireRole()` guards on every protected page, reinforced by service-layer checks and SQL `WHERE s.user_id = ?` ownership constraints on tutoring requests.
- **Retention & Safeguards:** Prevents public Administrator registration, blocks profile role/status tampering, protects the last active Administrator from demotion/deactivation/deletion, and blocks deleting users who have related tutoring request records.

## Classroom Scope Notice

This application is built for IS 6465 classroom instruction and is not hardened for public internet deployment (for example, it does not include login rate-limiting, email verification, password reset emails, audit log tables, or a dedicated web server public document root outside the PHP built-in server).
