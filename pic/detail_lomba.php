<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title='Detail Lomba';
$user_id=(int)current_user()['id'];
$lomba_id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$lomba_id) { set_flash('error','ID lomba tidak valid.'); redirect('pic/kelola_informasi_lomba.php'); }
$stmt=$pdo->prepare("SELECT l.*, (SELECT COUNT(*) FROM pendaftaran_lomba p WHERE p.lomba_id=l.id) AS jumlah_peserta FROM lomba l WHERE l.id=? AND l.pic_id=? LIMIT 1");
$stmt->execute([$lomba_id,$user_id]);
$lomba=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$lomba) { set_flash('error','Lomba tidak ditemukan atau bukan milik Anda.'); redirect('pic/kelola_informasi_lomba.php'); }
$stmt=$pdo->prepare("SELECT p.id,p.tanggal_daftar,p.status,u.nama,u.nim,u.email FROM pendaftaran_lomba p JOIN users u ON u.id=p.mahasiswa_id WHERE p.lomba_id=? ORDER BY p.tanggal_daftar DESC LIMIT 10");
$stmt->execute([$lomba_id]);
$peserta=$stmt->fetchAll(PDO::FETCH_ASSOC);
$status_label=['menunggu'=>'Menunggu Verifikasi','disetujui'=>'Disetujui - Perlu Dilengkapi','ditolak'=>'Ditolak','siap_publikasi'=>'Menunggu Publikasi','dipublikasikan'=>'Dipublikasikan','selesai'=>'Selesai'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Detail Lomba</h1><p>Informasi lengkap lomba yang Anda kelola.</p></div><a href="<?= e(url('pic/kelola_informasi_lomba.php')) ?>" class="btn btn-outline">Kembali</a></section>
<section class="card"><div class="card-header"><div><h2><?= e($lomba['judul']) ?></h2><p><span class="badge badge-info"><?= e($status_label[$lomba['status']]??$lomba['status']) ?></span></p></div></div>
<?php if (!empty($lomba['poster'])): ?><p><img src="<?= e(url('uploads/poster/'.rawurlencode(basename($lomba['poster'])))) ?>" alt="Poster lomba" style="max-width:260px;height:auto"></p><?php endif; ?>
<div class="table-wrapper"><table><tbody>
<?php foreach (['Penyelenggara'=>'penyelenggara','Kategori'=>'kategori','Tingkat'=>'tingkat','Lokasi'=>'lokasi','Tanggal Mulai'=>'tanggal_mulai','Tanggal Selesai'=>'tanggal_selesai','Deadline Pendaftaran'=>'deadline_pendaftaran','Kontak Person'=>'kontak_person'] as $label=>$field): ?><tr><th><?= e($label) ?></th><td><?= e($lomba[$field] ?: '-') ?></td></tr><?php endforeach; ?>
<tr><th>Deskripsi</th><td><?= nl2br(e($lomba['deskripsi'])) ?></td></tr>
<tr><th>Link Panduan</th><td><?php if (!empty($lomba['link_panduan']) && filter_var($lomba['link_panduan'],FILTER_VALIDATE_URL)): ?><a href="<?= e($lomba['link_panduan']) ?>" target="_blank" rel="noopener noreferrer">Buka Panduan</a><?php else: ?>-<?php endif; ?></td></tr>
<tr><th>Jumlah Pendaftar</th><td><?= number_format((int)$lomba['jumlah_peserta']) ?></td></tr>
<tr><th>Catatan AKAMA</th><td><?= nl2br(e($lomba['catatan_verifikasi'] ?: '-')) ?></td></tr>
</tbody></table></div>
<div class="form-actions" style="margin-top:16px"><?php if ($lomba['status']==='disetujui'): ?><a class="btn btn-primary" href="<?= e(url('pic/lengkapi_lomba.php?id='.$lomba_id)) ?>">Lengkapi Detail</a><?php endif; ?><?php if (in_array($lomba['status'],['dipublikasikan','selesai'],true)): ?><a class="btn btn-primary" href="<?= e(url('pic/kelola_data_peserta.php?lomba_id='.$lomba_id)) ?>">Kelola Peserta</a><?php endif; ?></div>
</section>
<section class="card"><div class="card-header"><div><h2>Peserta Terbaru</h2><p>Maksimal 10 pendaftar terbaru pada lomba ini.</p></div></div><div class="table-wrapper"><table><thead><tr><th>No.</th><th>Nama</th><th>NIM</th><th>Email</th><th>Tanggal Daftar</th><th>Status</th></tr></thead><tbody>
<?php if (!$peserta): ?><tr><td colspan="6" class="empty-state">Belum ada mahasiswa yang mendaftar.</td></tr><?php else: foreach($peserta as $i=>$item): ?><tr><td><?= $i+1 ?></td><td><?= e($item['nama']) ?></td><td><?= e($item['nim']?:'-') ?></td><td><?= e($item['email']) ?></td><td><?= e($item['tanggal_daftar']) ?></td><td><?= e(ucfirst($item['status'])) ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
