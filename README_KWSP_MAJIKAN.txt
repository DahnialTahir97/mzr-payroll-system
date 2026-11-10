MZR FOOD SERVICES - KWSP EMPLOYER CONTRIBUTION PATCH

Kandungan:
- payroll.php
- contribution_calculator.php

Perubahan:
KWSP pekerja kekal 11% seperti arahan pengguna. KWSP majikan ditambah berdasarkan kategori berikut:
1. Warganegara Malaysia bawah 60: 13% jika upah <= RM5,000; 12% jika > RM5,000.
2. Warganegara Malaysia umur 60+: 4%.
3. Pemastautin tetap bawah 60: 13% jika upah <= RM5,000; 12% jika > RM5,000 (mengikut kategori Malaysian & PR bawah 60 yang dibekalkan).
4. Pemastautin tetap umur 60+: 6.5% jika upah <= RM5,000; 6% jika > RM5,000.
5. Pekerja bukan warganegara: 2%.

Umur dikira pada hari terakhir bulan payroll. Asas upah yang digunakan ialah gaji asas + elaun (OT dikecualikan dalam patch sedia ada).

PENTING:
- Patch ini mengikut kadar peratus yang dibekalkan pengguna; ia bukan pengiraan tepat jadual caruman KWSP mengikut julat upah dan pembundaran rasmi.
- KWSP pekerja 11% dikekalkan untuk semua kategori dalam versi ringkas ini. Kadar pekerja bagi bukan warganegara / kategori umur tertentu perlu disahkan sebelum slip digunakan secara rasmi.
- PERKESO pekerja 0.15% ialah anggaran berdasarkan contoh pengguna; PERKESO majikan tidak dikira.
- SIP pekerja/majikan 0.2% dikekalkan dengan had upah RM6,000 dalam patch terdahulu.
- PCB belum dikira. Semua payroll kekal berstatus DRAFT.

Cara pasang:
1. Backup folder projek dahulu.
2. Extract ZIP.
3. Salin kedua-dua fail ke C:\xampp\htdocs\mzr_payroll_system\ dan replace fail lama.
4. Pastikan employees.date_of_birth dan employees.citizenship telah diisi.
5. Jana semula payroll bulan berkaitan untuk kemas kini jumlah caruman majikan.
