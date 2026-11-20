<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');

$page_title = 'Dashboard PIC';
$user_id = (int) current_user()['id'];

$counts = [
    'lomba' => 0, 'menunggu_verifikasi' => 0, 'perlu_lengkapi' => 0,
    'siap_publikasi' => 0, 'dipublikasikan' => 0, 'peserta' => 0,
    'peserta_menunggu' => 0, 'sertifikat' => 0
];

$queries = [
    'lomba' => ["SELECT COUNT(*) FROM lomba WHERE pic_id = ?", [$user_id]],
    'menunggu_verifikasi' => ["SELECT COUNT(*) FROM lomba WHERE pic_id = ? AND status = 'menunggu'", [$user_id]],
    'perlu_lengkapi' => ["SELECT COUNT(*) FROM lomba WHERE pic_id = ? AND status = 'disetujui'", [$user_id]],
    'siap_publikasi' => ["SELECT COUNT(*) FROM lomba WHERE pic_id = ? AND status = 'siap_publikasi'", [$user_id]],
    'dipublikasikan' => ["SELECT COUNT(*) FROM lomba WHERE pic_id = ? AND status = 'dipublikasikan'", [$user_id]],
    'peserta' => ["SELECT COUNT(*) FROM pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id WHERE l.pic_id = ?", [$user_id]],
    'peserta_menunggu' => ["SELECT COUNT(*) FROM pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id WHERE l.pic_id = ? AND p.status='menunggu'", [$user_id]],
    'sertifikat' => ["SELECT COUNT(*) FROM sertifikat WHERE pic_id = ?", [$user_id]]
];
foreach ($queries as $key => [$sql, $params]) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $counts[$key] = (int) $stmt->fetchColumn();
}

$stmt = $pdo->prepare("SELECT id, judul, status, deadline_pendaftaran, created_at FROM lomba WHERE pic_id = ? ORDER BY created_at DESC LIMIT 8");
$stmt->execute([$user_id]);
$lomba_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading">
    <div><h1>Dashboard PIC</h1><p>Ringkasan pengajuan lomba, peserta, dan sertifikat yang Anda kelola.</p></div>
    <a href="<?= e(url('pic/ajukan_lomba.php')) ?>" class="btn btn-primary">+ Ajukan Lomba</a>
</section>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-icon">🏆</div><div><p>Total Lomba</p><h2><?= number_format($counts['lomba']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">⏳</div><div><p>Menunggu Verifikasi</p><h2><?= number_format($counts['menunggu_verifikasi']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">📝</div><div><p>Perlu Dilengkapi</p><h2><?= number_format($counts['perlu_lengkapi']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">📢</div><div><p>Siap Publikasi</p><h2><?= number_format($counts['siap_publikasi']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">👥</div><div><p>Total Pendaftar</p><h2><?= number_format($counts['peserta']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">🔔</div><div><p>Pendaftar Menunggu</p><h2><?= number_format($counts['peserta_menunggu']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">📜</div><div><p>Sertifikat Diunggah</p><h2><?= number_format($counts['sertifikat']) ?></h2></div></article>
    <article class="stat-card"><div class="stat-icon">🌐</div><div><p>Lomba Dipublikasikan</p><h2><?= number_format($counts['dipublikasikan']) ?></h2></div></article>
</section>
<section class="card">
    <div class="card-header"><div><h2>Lomba Terbaru</h2><p>Daftar lomba yang pernah Anda ajukan.</p></div><a href="<?= e(url('pic/kelola_informasi_lomba.php')) ?>" class="btn btn-outline">Kelola Lomba</a></div>
    <div class="table-wrapper"><table><thead><tr><th>No.</th><th>Nama Lomba</th><th>Deadline</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php if (!$lomba_terbaru): ?><tr><td colspan="5" class="empty-state">Anda belum mengajukan lomba.</td></tr>
    <?php else: foreach ($lomba_terbaru as $i => $item): ?>
        <tr><td><?= $i + 1 ?></td><td><?= e($item['judul']) ?></td><td><?= e($item['deadline_pendaftaran']) ?></td><td><span class="badge badge-info"><?= e(ucfirst(str_replace('_',' ',$item['status']))) ?></span></td><td><a class="btn btn-sm btn-outline" href="<?= e(url('pic/detail_lomba.php?id='.(int)$item['id'])) ?>">Detail</a></td></tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
