# MZR FOOD SERVICES Payroll System

PHP and MySQL payroll management application.

## Repository
This repository is public for academic demonstration. Do not commit real employee information, payroll records, database passwords, `.env` files, or SQL dumps containing personal data.

## Local setup (XAMPP)
1. Copy the project folder into `C:/xampp/htdocs/`.
2. Create a MySQL database named `mzr_payroll_db`.
3. Import the database schema from a secured local backup. Do not upload real employee or payroll data to this repository.
4. Configure `config/database.php` locally or set the database environment variables below.
5. Open `http://localhost/mzr_payroll_system/`.

## Database environment variables
The application reads these environment variables:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

For Railway public networking, use the public TCP host and port, not the private `.internal` hostname.

## Vercel container
The `Dockerfile.vercel` and `Caddyfile` files configure FrankenPHP with the PDO MySQL extension. In Vercel, select the **Container** preset and set the five database environment variables above.

## Important deployment limitations
This package still requires deployment validation. PHP sessions currently use PHP's default session handling; Vercel container instances are stateless and local session files are not a reliable shared session store across instances. Before using real accounts or payroll data, configure a shared session store (for example Redis or a database-backed PHP session handler), test login persistence, and review all authorization and CSRF protections. Also change all test/default passwords and store password hashes using PHP `password_hash()`.
