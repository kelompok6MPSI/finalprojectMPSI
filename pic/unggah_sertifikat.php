<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title='Unggah Sertifikat';
$user_id=(int)current_user()['id'];
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf_token($_POST['csrf_token']??null)) { set_flash('error','Token keamanan tidak valid.'); redirect('pic/unggah_sertifikat.php'); }
    $pendaftaran_id=filter_input(INPUT_POST,'pendaftaran_id',FILTER_VALIDATE_INT);
    $nomor=trim($_POST['nomor_sertifikat']??'');
    if (!$pendaftaran_id || $nomor==='') $errors[]='Pilih peserta dan isi nomor sertifikat.';
    if (mb_strlen($nomor)>100) $errors[]='Nomor sertifikat maksimal 100 karakter.';
    if (!isset($_FILES['file_sertifikat']) || $_FILES['file_sertifikat']['error']!==UPLOAD_ERR_OK) $errors[]='File sertifikat PDF wajib diunggah.';
    elseif ($_FILES['file_sertifikat']['size']>5*1024*1024) $errors[]='Ukuran sertifikat maksimal 5 MB.';
    if (!$errors) {
        $stmt=$pdo->prepare("SELECT p.id,p.lomba_id,p.mahasiswa_id,p.status,l.judul FROM pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id WHERE p.id=? AND l.pic_id=? AND p.status IN ('diterima','selesai') LIMIT 1");
        $stmt->execute([$pendaftaran_id,$user_id]);
        $peserta=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$peserta) $errors[]='Peserta tidak ditemukan, bukan peserta lomba Anda, atau belum diterima.';
        $finfo=new finfo(FILEINFO_MIME_TYPE);
        $mime=$finfo->file($_FILES['file_sertifikat']['tmp_name']);
        if ($mime!=='application/pdf') $errors[]='File sertifikat harus berupa PDF.';
        if (!$errors) {
            $folder=__DIR__.'/../uploads/sertifikat/';
            if (!is_dir($folder) && !mkdir($folder,0755,true)) $errors[]='Folder penyimpanan sertifikat tidak dapat dibuat.';
            if (!$errors) {
                $nama_file=bin2hex(random_bytes(16)).'.pdf';
                if (!move_uploaded_file($_FILES['file_sertifikat']['tmp_name'],$folder.$nama_file)) $errors[]='File sertifikat gagal disimpan.';
                else {
                    try {
                        $pdo->beginTransaction();
                        $stmt=$pdo->prepare("INSERT INTO sertifikat (pendaftaran_id,lomba_id,mahasiswa_id,pic_id,nomor_sertifikat,file_sertifikat,tanggal_unggah,created_at) VALUES (:pendaftaran,:lomba,:mahasiswa,:pic,:nomor,:file,CURDATE(),NOW())");
                        $stmt->execute([':pendaftaran'=>$peserta['id'],':lomba'=>$peserta['lomba_id'],':mahasiswa'=>$peserta['mahasiswa_id'],':pic'=>$user_id,':nomor'=>$nomor,':file'=>$nama_file]);
                        $stmt=$pdo->prepare("UPDATE pendaftaran_lomba SET status='selesai' WHERE id=? AND status IN ('diterima','selesai')");
                        $stmt->execute([$pendaftaran_id]);
                        $pdo->commit();
                        set_flash('success','Sertifikat berhasil diunggah dan status peserta menjadi selesai.');
                        redirect('pic/unggah_sertifikat.php');
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        if (is_file($folder.$nama_file)) unlink($folder.$nama_file);
                        $errors[]='Sertifikat gagal disimpan. Pastikan peserta belum memiliki sertifikat dan nomor sertifikat belum digunakan.';
                    }
                }
            }
        }
    }
}

$stmt=$pdo->prepare("SELECT p.id AS pendaftaran_id,p.status AS status_pendaftaran,u.nama,u.nim,l.judul,s.id AS sertifikat_id,s.nomor_sertifikat,s.file_sertifikat FROM pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id JOIN users u ON u.id=p.mahasiswa_id LEFT JOIN sertifikat s ON s.pendaftaran_id=p.id WHERE l.pic_id=? AND p.status IN ('diterima','selesai') ORDER BY l.judul,u.nama");
$stmt->execute([$user_id]);
$daftar=$stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Unggah Sertifikat</h1><p>Unggah satu file PDF untuk setiap peserta yang telah diterima.</p></div></section>
<?php if($errors): ?><div class="alert alert-error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="card"><form method="POST" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-group"><label for="pendaftaran_id">Peserta *</label><select class="form-control" id="pendaftaran_id" name="pendaftaran_id" required><option value="">Pilih peserta</option><?php foreach($daftar as $item): if(!$item['sertifikat_id']): ?><option value="<?= (int)$item['pendaftaran_id'] ?>"><?= e($item['nama'].' - '.($item['nim']?:'Tanpa NIM').' | '.$item['judul']) ?></option><?php endif; endforeach; ?></select></div>
<div class="form-group"><label for="nomor_sertifikat">Nomor Sertifikat *</label><input class="form-control" type="text" id="nomor_sertifikat" name="nomor_sertifikat" maxlength="100" required value="<?= e($_POST['nomor_sertifikat']??'') ?>"></div>
<div class="form-group"><label for="file_sertifikat">File Sertifikat (PDF, maksimal 5 MB) *</label><input class="form-control" type="file" id="file_sertifikat" name="file_sertifikat" accept="application/pdf,.pdf" required></div>
<button class="btn btn-primary" type="submit">Unggah Sertifikat</button></form></section>
<section class="card"><div class="card-header"><div><h2>Riwayat Sertifikat</h2><p>Sertifikat yang sudah diunggah oleh Anda.</p></div></div><div class="table-wrapper"><table><thead><tr><th>No.</th><th>Mahasiswa</th><th>Lomba</th><th>Nomor Sertifikat</th><th>File</th></tr></thead><tbody>
<?php $ada=false; foreach($daftar as $i=>$item): if($item['sertifikat_id']): $ada=true; ?><tr><td><?= $i+1 ?></td><td><?= e($item['nama']) ?> (<?= e($item['nim']?:'-') ?>)</td><td><?= e($item['judul']) ?></td><td><?= e($item['nomor_sertifikat']) ?></td><td><a href="<?= e(url('uploads/sertifikat/'.rawurlencode(basename($item['file_sertifikat'])))) ?>" target="_blank" rel="noopener noreferrer">Lihat PDF</a></td></tr><?php endif; endforeach; if(!$ada): ?><tr><td colspan="5" class="empty-state">Belum ada sertifikat yang diunggah.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
