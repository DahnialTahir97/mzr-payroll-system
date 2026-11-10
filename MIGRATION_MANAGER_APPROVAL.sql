USE mzr_payroll_db;

-- Jalankan sekali sahaja selepas backup database.
ALTER TABLE payroll
    ADD COLUMN approval_status ENUM('pending','approved','rejected')
        NOT NULL DEFAULT 'pending',
    ADD COLUMN approved_by INT NULL,
    ADD COLUMN approved_at DATETIME NULL;

-- Payroll sedia ada ditetapkan pending secara default.
-- Manager perlu semak dan sahkan semula sebelum pekerja boleh melihat slip.
