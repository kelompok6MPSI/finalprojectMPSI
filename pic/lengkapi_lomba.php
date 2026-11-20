<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title = 'Lengkapi Detail Lomba';
$user_id = (int) current_user()['id'];
$lomba_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$lomba_id) { set_flash('error','ID lomba tidak valid.'); redirect('pic/kelola_informasi_lomba.php'); }
$stmt = $pdo->prepare("SELECT * FROM lomba WHERE id=? AND pic_id=? AND status='disetujui' LIMIT 1");
$stmt->execute([$lomba_id,$user_id]);
$lomba = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lomba) { set_flash('error','Lomba tidak ditemukan, bukan milik Anda, atau belum disetujui AKAMA.'); redirect('pic/kelola_informasi_lomba.php'); }
$errors=[];
$link_panduan = trim($_POST['link_panduan'] ?? ($lomba['link_panduan'] ?? ''));
$kontak_person = trim($_POST['kontak_person'] ?? ($lomba['kontak_person'] ?? ''));
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { set_flash('error','Token keamanan tidak valid.'); redirect('pic/lengkapi_lomba.php?id='.$lomba_id); }
    if ($link_panduan==='') $errors[]='Link panduan lomba wajib diisi.';
    elseif (!filter_var($link_panduan,FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($link_panduan,PHP_URL_SCHEME) ?? ''),['http','https'],true)) $errors[]='Link panduan harus berupa URL HTTP atau HTTPS yang valid.';
    if (mb_strlen($kontak_person)>255) $errors[]='Kontak person maksimal 255 karakter.';
    if (!$errors) {
        $stmt=$pdo->prepare("UPDATE lomba SET link_panduan=:panduan,kontak_person=:kontak,status='siap_publikasi',updated_at=NOW() WHERE id=:id AND pic_id=:pic AND status='disetujui'");
        $stmt->execute([':panduan'=>$link_panduan,':kontak'=>$kontak_person!==''?$kontak_person:null,':id'=>$lomba_id,':pic'=>$user_id]);
        if ($stmt->rowCount()>0) set_flash('success','Detail lomba tersimpan dan menunggu publikasi AKAMA.');
        else set_flash('error','Data tidak berubah atau lomba sudah diproses.');
        redirect('pic/kelola_informasi_lomba.php');
    }
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Lengkapi Detail Lomba</h1><p>Isi panduan lomba sebelum informasi diajukan untuk publikasi AKAMA.</p></div><a href="<?= e(url('pic/kelola_informasi_lomba.php')) ?>" class="btn btn-outline">Kembali</a></section>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="card"><div class="card-header"><div><h2><?= e($lomba['judul']) ?></h2><p>Status: <span class="badge badge-success">Disetujui AKAMA</span></p></div></div>
<form method="POST" data-validate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-group"><label for="link_panduan">Link Panduan Lomba *</label><input type="url" class="form-control" id="link_panduan" name="link_panduan" required placeholder="https://contoh.com/panduan-lomba" value="<?= e($link_panduan) ?>"><div class="form-help">Masukkan tautan panduan atau petunjuk teknis yang dapat diakses mahasiswa. Pendaftaran dilakukan langsung melalui SILOMBA.</div></div>
<div class="form-group"><label for="kontak_person">Kontak Person (opsional)</label><input type="text" class="form-control" id="kontak_person" name="kontak_person" maxlength="255" placeholder="Nama dan nomor kontak" value="<?= e($kontak_person) ?>"></div>
<div class="form-actions"><button type="submit" class="btn btn-primary">Kirim untuk Publikasi</button><a href="<?= e(url('pic/kelola_informasi_lomba.php')) ?>" class="btn btn-outline">Batal</a></div>
</form></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
