MZR PAYROLL - PEMBETULAN KEHADIRAN, KOMPONEN DAN OT

Fail yang perlu diganti dalam folder C:\xampp\htdocs\mzr_payroll_system:
1. attendance.php
2. components.php
3. payroll.php

Fungsi:
- Kehadiran: Edit dan Padam setiap rekod.
- Elaun & Potongan: Edit dan Padam setiap komponen.
- Payroll: buang medan gandaan OT. Sistem jumlahkan overtime_hours daripada jadual attendance mengikut bulan secara automatik.
- Kadar OT ditetapkan 1.5 dalam kod projek; jumlah jam OT tetap berpandukan rekod kehadiran bulan tersebut.
- Jana semula bulan yang sama akan mengemas kini rekod payroll sedia ada, bukan menambah rekod berganda.

Pemasangan:
1. Backup folder projek dan database.
2. Extract ZIP.
3. Copy tiga fail PHP di atas ke folder projek dan replace.
4. Pastikan database menggunakan jadual/kolum: employees.employment_status, employees.employee_code, attendance.overtime_hours, salary_components.component_month, payroll.salary_month.
5. Buka payroll.php, pilih bulan dan klik Jana / Kemas kini.

Nota pengiraan:
KWSP pekerja 11%; SIP pekerja/majikan 0.2%; PERKESO dalam patch ini masih anggaran 0.15% pekerja dan 1.5% majikan. Ini bukan jadual rasmi PERKESO. PCB tidak dikira. Sahkan semua kadar sebelum menggunakan payslip sebagai dokumen rasmi.
