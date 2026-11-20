PAKET MODUL PIC SILOMBA

Isi:
- dashboard.php
- ajukan_lomba.php
- kelola_informasi_lomba.php
- lengkapi_lomba.php
- detail_lomba.php
- kelola_data_peserta.php
- unggah_sertifikat.php

Pemasangan:
1. Backup folder pic proyek Anda.
2. Salin semua file PHP ke folder pic/ dan timpa file lama setelah backup.
3. Pastikan folder uploads/poster/ dan uploads/sertifikat/ dapat ditulis oleh PHP. Kode akan mencoba membuat folder bila belum ada.
4. Pastikan proyek menyediakan includes/role.php, includes/header.php, includes/sidebar_pic.php, includes/footer.php serta helper $pdo, require_role(), current_user(), verify_csrf_token(), csrf_token(), set_flash(), redirect(), url(), dan e().

Alur:
1. PIC mengajukan lomba; status awal menunggu.
2. AKAMA memverifikasi. Jika disetujui, PIC melengkapi link_panduan dan kontak_person.
3. Status berubah menjadi siap_publikasi; AKAMA melakukan publikasi.
4. Mahasiswa mendaftar melalui SILOMBA, data masuk ke pendaftaran_lomba dengan status menunggu.
5. PIC menerima atau menolak pendaftaran.
6. PIC dapat mengunggah PDF sertifikat untuk peserta yang diterima. Setelah sertifikat tersimpan, status pendaftaran menjadi selesai.

Catatan penting:
- Tidak ada form atau query yang menggunakan link_pendaftaran. Kolom lama boleh tetap ada di database, tetapi tidak dipakai untuk alur pendaftaran.
- Tidak ada tabel atau kolom baru yang ditambahkan oleh paket ini.
- Unggah sertifikat dibuat satu peserta per satu PDF karena tabel sertifikat memiliki UNIQUE KEY pada pendaftaran_id.
- Pastikan aturan akses file uploads sesuai kebijakan server Anda. Untuk keamanan lebih tinggi, file sertifikat sebaiknya disajikan melalui endpoint PHP yang memeriksa hak akses, bukan dibuka langsung dari folder uploads.
- Paket mempertahankan nama helper dan pola layout yang tampak pada file proyek yang diberikan. Uji di salinan/backup terlebih dahulu.
