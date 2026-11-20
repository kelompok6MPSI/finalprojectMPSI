<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title = 'Kelola Informasi Lomba';
$user_id = (int) current_user()['id'];
$stmt = $pdo->prepare("SELECT l.*, (SELECT COUNT(*) FROM pendaftaran_lomba p WHERE p.lomba_id=l.id) AS jumlah_peserta FROM lomba l WHERE l.pic_id=? ORDER BY l.created_at DESC");
$stmt->execute([$user_id]);
$lomba_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$status_label = ['menunggu'=>'Menunggu Verifikasi','disetujui'=>'Disetujui - Perlu Dilengkapi','ditolak'=>'Ditolak','siap_publikasi'=>'Menunggu Publikasi AKAMA','dipublikasikan'=>'Dipublikasikan','selesai'=>'Selesai'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Kelola Informasi Lomba</h1><p>Pantau status pengajuan, publikasi, dan jumlah mahasiswa yang mendaftar.</p></div><a href="<?= e(url('pic/ajukan_lomba.php')) ?>" class="btn btn-primary">+ Ajukan Lomba</a></section>
<section class="card"><div class="table-wrapper"><table><thead><tr><th>No.</th><th>Nama Lomba</th><th>Deadline</th><th>Peserta</th><th>Status</th><th>Catatan AKAMA</th><th>Aksi</th></tr></thead><tbody>
<?php if (!$lomba_list): ?><tr><td colspan="7" class="empty-state">Belum ada lomba yang diajukan.</td></tr><?php else: foreach ($lomba_list as $i=>$lomba): $st=$lomba['status']; ?>
<tr><td><?= $i+1 ?></td><td><?= e($lomba['judul']) ?></td><td><?= e($lomba['deadline_pendaftaran']) ?></td><td><?= number_format((int)$lomba['jumlah_peserta']) ?></td><td><span class="badge <?= $st==='ditolak'?'badge-danger':(in_array($st,['menunggu','disetujui','siap_publikasi'],true)?'badge-warning':'badge-success') ?>"><?= e($status_label[$st] ?? $st) ?></span></td><td><?= e($lomba['catatan_verifikasi'] ?: '-') ?></td><td><div class="form-actions"><a class="btn btn-sm btn-outline" href="<?= e(url('pic/detail_lomba.php?id='.(int)$lomba['id'])) ?>">Detail</a><?php if ($st==='disetujui'): ?><a class="btn btn-sm btn-primary" href="<?= e(url('pic/lengkapi_lomba.php?id='.(int)$lomba['id'])) ?>">Lengkapi</a><?php elseif ($st==='dipublikasikan'): ?><a class="btn btn-sm btn-outline" href="<?= e(url('pic/kelola_data_peserta.php?lomba_id='.(int)$lomba['id'])) ?>">Peserta</a><?php elseif ($st==='siap_publikasi'): ?><span>Menunggu AKAMA</span><?php endif; ?></div></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
