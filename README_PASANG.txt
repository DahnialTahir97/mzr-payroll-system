MZR FOOD SERVICES - SYSTEM UPGRADE

PAKEJ INI MENAMBAH BAIK:
- Dashboard baharu dengan ringkasan pekerja, kehadiran dan payroll.
- Pengurusan pekerja: tambah, edit, carian, status aktif/tidak aktif dan gaji asas.
- Sidebar dan tema dashboard yang lebih kemas/responsif.
- Database connection menggunakan mzr_payroll_db dan PDO variable $conn serta $pdo.

CARA PASANG:
1. BACKUP folder C:\xampp\htdocs\mzr_payroll_system dahulu.
2. Extract kandungan ZIP.
3. Copy folder/file dalam ZIP ke C:\xampp\htdocs\mzr_payroll_system\ dan pilih Replace.
4. Pastikan database mzr_payroll_db wujud dan jadual employees mempunyai medan:
   id, employee_code, full_name, position, basic_salary, phone, email, employment_status.
5. Buka http://localhost/mzr_payroll_system/

PENTING:
- Pakej ini tidak memadam atau mengubah data dalam database.
- Fail attendance.php, components.php, payroll.php, payslip.php dan reports.php sedia ada tidak diganti dalam pakej ini.
- Pautan menu dashboard merujuk kepada fail modul sedia ada.
- Jika halaman lama menggunakan includes/header.php, ia kekal berasingan daripada layout baharu.
- Pengiraan caruman tidak diubah dalam pakej UI ini. Gunakan payroll patch yang telah dipasang sebelum ini dan semak status DRAFT.
