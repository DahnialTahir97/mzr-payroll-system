USE mzr_payroll_db;
-- Jalankan sekali sahaja. Jika nama database sebenar berbeza, tukar baris USE.
ALTER TABLE users MODIFY role ENUM('admin','manager','employee') NOT NULL DEFAULT 'employee';
ALTER TABLE users ADD COLUMN employee_id INT NULL;
ALTER TABLE users ADD UNIQUE KEY uq_users_employee (employee_id);
-- Pautan employee_id kepada employees.id dibuat selepas akaun pekerja diwujudkan.
