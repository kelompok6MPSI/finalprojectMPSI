<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title = 'Ajukan Lomba';
$user_id = (int) current_user()['id'];
$errors = [];
$fields = ['judul','kategori','deskripsi','penyelenggara','tingkat','lokasi','tanggal_mulai','tanggal_selesai','deadline_pendaftaran'];
$data = [];
foreach ($fields as $field) $data[$field] = trim($_POST[$field] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('pic/ajukan_lomba.php');
    }
    if ($data['judul'] === '' || $data['penyelenggara'] === '' || $data['deskripsi'] === '') $errors[] = 'Nama lomba, penyelenggara, dan deskripsi wajib diisi.';
    foreach (['tanggal_mulai'=>'Tanggal mulai','tanggal_selesai'=>'Tanggal selesai','deadline_pendaftaran'=>'Deadline pendaftaran'] as $field=>$label) {
        $date = DateTime::createFromFormat('!Y-m-d', $data[$field]);
        if (!$date || $date->format('Y-m-d') !== $data[$field]) $errors[] = $label . ' tidak valid.';
    }
    if (!$errors && ($data['tanggal_selesai'] < $data['tanggal_mulai'] || $data['deadline_pendaftaran'] > $data['tanggal_mulai'])) $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai dan deadline harus sebelum atau sama dengan tanggal mulai.';
    if (mb_strlen($data['judul']) > 200) $errors[] = 'Nama lomba maksimal 200 karakter.';

    $poster = null;
    if (!$errors && isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['poster'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) $errors[] = 'Poster gagal diunggah atau ukurannya melebihi 3 MB.';
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($allowed[$mime])) $errors[] = 'Poster harus JPG, PNG, atau WEBP.';
            else {
                $folder = __DIR__ . '/../uploads/poster/';
                if (!is_dir($folder) && !mkdir($folder, 0755, true)) $errors[] = 'Folder poster tidak dapat dibuat.';
                if (!$errors) {
                    $poster = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                    if (!move_uploaded_file($file['tmp_name'], $folder . $poster)) { $poster = null; $errors[] = 'Poster tidak dapat disimpan.'; }
                }
            }
        }
    }
    if (!$errors) {
        try {
            $stmt = $pdo->prepare("INSERT INTO lomba (pic_id,judul,kategori,deskripsi,penyelenggara,tingkat,lokasi,tanggal_mulai,tanggal_selesai,deadline_pendaftaran,poster,status,created_at,updated_at) VALUES (:pic_id,:judul,:kategori,:deskripsi,:penyelenggara,:tingkat,:lokasi,:mulai,:selesai,:deadline,:poster,'menunggu',NOW(),NOW())");
            $stmt->execute([
                ':pic_id'=>$user_id, ':judul'=>$data['judul'], ':kategori'=>$data['kategori'] ?: null,
                ':deskripsi'=>$data['deskripsi'], ':penyelenggara'=>$data['penyelenggara'],
                ':tingkat'=>$data['tingkat'] ?: null, ':lokasi'=>$data['lokasi'] ?: null,
                ':mulai'=>$data['tanggal_mulai'], ':selesai'=>$data['tanggal_selesai'],
                ':deadline'=>$data['deadline_pendaftaran'], ':poster'=>$poster
            ]);
            set_flash('success','Pengajuan lomba berhasil dikirim ke AKAMA.');
            redirect('pic/kelola_informasi_lomba.php');
        } catch (PDOException $e) {
            if ($poster && is_file(__DIR__ . '/../uploads/poster/' . $poster)) unlink(__DIR__ . '/../uploads/poster/' . $poster);
            $errors[] = 'Pengajuan gagal disimpan. Periksa koneksi dan struktur database.';
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Ajukan Lomba</h1><p>Lengkapi informasi lomba untuk diajukan kepada AKAMA. Pendaftaran mahasiswa nantinya dilakukan melalui SILOMBA.</p></div></section>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="card"><form method="POST" enctype="multipart/form-data" data-validate>
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-group"><label for="judul">Nama Lomba *</label><input class="form-control" id="judul" name="judul" maxlength="200" required value="<?= e($data['judul']) ?>"></div>
<div class="form-group"><label for="penyelenggara">Penyelenggara *</label><input class="form-control" id="penyelenggara" name="penyelenggara" required value="<?= e($data['penyelenggara']) ?>"></div>
<div class="form-group"><label for="kategori">Kategori</label><input class="form-control" id="kategori" name="kategori" value="<?= e($data['kategori']) ?>" placeholder="Akademik, olahraga, seni, dan sebagainya"></div>
<div class="form-group"><label for="tingkat">Tingkat Lomba</label><select class="form-control" id="tingkat" name="tingkat"><option value="">Pilih tingkat</option><?php foreach (['Lokal','Kampus','Kota','Provinsi','Nasional','Internasional'] as $item): ?><option value="<?= e($item) ?>" <?= $data['tingkat']===$item?'selected':'' ?>><?= e($item) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="deskripsi">Deskripsi Lomba *</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?= e($data['deskripsi']) ?></textarea></div>
<div class="form-group"><label for="lokasi">Lokasi atau Tautan Pelaksanaan</label><input class="form-control" id="lokasi" name="lokasi" value="<?= e($data['lokasi']) ?>"></div>
<div class="form-group"><label for="deadline_pendaftaran">Deadline Pendaftaran *</label><input type="date" class="form-control" id="deadline_pendaftaran" name="deadline_pendaftaran" required value="<?= e($data['deadline_pendaftaran']) ?>"></div>
<div class="form-group"><label for="tanggal_mulai">Tanggal Mulai *</label><input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required value="<?= e($data['tanggal_mulai']) ?>"></div>
<div class="form-group"><label for="tanggal_selesai">Tanggal Selesai *</label><input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required value="<?= e($data['tanggal_selesai']) ?>"></div>
<div class="form-group"><label for="poster">Poster (JPG, PNG, WEBP; maksimal 3 MB)</label><input type="file" class="form-control" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">Kirim Pengajuan</button><a href="<?= e(url('pic/kelola_informasi_lomba.php')) ?>" class="btn btn-outline">Batal</a></div>
</form></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
