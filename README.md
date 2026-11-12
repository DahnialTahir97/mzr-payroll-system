# MZR FOOD SERVICES Payroll System

PHP 8.4 + MySQL payroll management application for MZR FOOD SERVICES.

## Main modules

- Role-based login: Admin, Manager and Employee
- Employee records: create, view, edit and delete
- Attendance and overtime records; employee self-service check-in/check-out
- Monthly payroll generation and editing
- Allowances and deductions
- Manager payroll review, approval and rejection
- Employee payslips for approved payroll only
- Monthly payroll reports
- Responsive desktop and mobile layout

## Local setup (XAMPP)

1. Copy the project folder into `C:/xampp/htdocs/mzr_payroll_system/`.
2. Create a MySQL database named `mzr_payroll_db`.
3. Import your existing, trusted MZR Payroll database backup. This repository intentionally does not contain a live database dump or employee records.
4. Configure `config/database.php` locally or set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
5. Open `http://localhost/mzr_payroll_system/login.php`.

## Deploy to Vercel

The repository includes `Dockerfile.vercel` and `Caddyfile`. Vercel Container deployment detects `Dockerfile.vercel` at the repository root and runs the PHP/FrankenPHP HTTP server.

1. Push the latest `main` branch to GitHub.
2. In Vercel, import `DahnialTahir97/mzr-payroll-system`.
3. Set the Framework Preset to **Container** if Vercel does not select it automatically. Keep the Root Directory as `./`.
4. Provision an external MySQL database (for example Railway MySQL). Import the existing MZR Payroll schema and required data/migrations into it. Do not use a local XAMPP `localhost` address for a hosted database.
5. Add these Environment Variables in Vercel for Production (and Preview if Preview deployments will be tested):
   - `DB_HOST`: public MySQL hostname
   - `DB_PORT`: public MySQL TCP port
   - `DB_NAME`: database name
   - `DB_USER`: database username
   - `DB_PASS`: database password
6. Redeploy after setting the variables.
7. Open `https://YOUR-DOMAIN/health.php`. A successful response includes `"status":"ok"` and `"database":"connected"`.
8. Test Admin, Manager and Employee login, logout, CRUD, check-in/out, payroll approval and payslip access before using real records.

### Shared login sessions

Vercel container instances are stateless and can route different requests to different instances. The application now stores PHP sessions in the same MySQL database using `mzr_app_sessions`; the table is created automatically at first request. The database account therefore needs permission to create and modify tables. Sessions expire after two hours of inactivity. Session cookies are HttpOnly, SameSite=Lax and Secure on HTTPS.

### Database requirements

The database must already contain the application tables and columns expected by the PHP modules, including `users`, `employees`, `attendance`, `salary_components`, and `payroll`. Apply the supplied migrations only when they have not already been applied:

- `MIGRATION_LOGIN_ROLE.sql`
- `MIGRATION_MANAGER_APPROVAL.sql`

Back up the database before applying migrations. The migration scripts are not idempotent and should not be run twice.

## Payroll calculation notice

The current payroll generator contains percentage-based PERKESO/SIP calculations explicitly marked in the source as project estimates, not the official contribution tables. PCB is currently set to zero. Do not use generated values for statutory remittance or real payroll processing until the official, effective-dated contribution tables and PCB calculation have been configured and independently verified. The `contribution_calculator.php` helper expects populated `contribution_rates` records; no official rate dataset is bundled in this repository.

## Security and operations

- Never commit `.env`, passwords, employee details, payroll exports or database backups.
- Change all test/default passwords and store password hashes using PHP `password_hash()`.
- Keep the database private; use the provider's public TCP endpoint only when connecting from Vercel, with TLS/network restrictions where supported.
- Back up the database regularly.
- Review role permissions and test with one account per role.
- This repository is a project deployment package, not a certified payroll compliance product.

## Health check

`/health.php` verifies PHP and database connectivity without exposing credentials or SQL error details.
