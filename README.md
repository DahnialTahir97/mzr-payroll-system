# MZR FOOD SERVICES Payroll System

PHP and MySQL payroll management application.

## Local setup (XAMPP)
1. Copy the project folder into `C:/xampp/htdocs/`.
2. Create a MySQL database named `mzr_payroll_db`.
3. Import your database schema using your own secured SQL backup. Do not commit real employee or payroll data.
4. Configure the database connection in `config/database.php` or set the environment variables below.
5. Open `http://localhost/mzr_payroll_system/`.

## Database environment variables
Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the hosting platform. Never commit production credentials.

## Security notes
- This repository intentionally excludes database dumps, local backups, IDE private settings, and account-reset/setup utilities.
- Use a private repository.
- Change all default/test passwords and store password hashes using PHP `password_hash()`.
- Configure HTTPS, secure session cookies, access controls, backups, and database network restrictions before using real payroll data.
- This source package has not yet been validated for production hosting. Confirm PHP runtime, writable session storage, and persistent MySQL connectivity before deployment.
