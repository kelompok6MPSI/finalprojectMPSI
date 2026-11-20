
<?php
require_once __DIR__ . '/../includes/role.php';
require_role('mahasiswa');

$page_title = 'Detail Lomba';
$user_id = (int) current_user()['id'];

$lomba_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$lomba_id) {
    set_flash('error', 'Lomba tidak valid.');
    redirect('mahasiswa/informasi_lomba.php');
}

$stmt = $pdo->prepare(
    "SELECT l.*,
        CASE
            WHEN l.status = 'dipublikasikan'
            AND l.deadline_pendaftaran >= CURDATE()
            THEN 1 ELSE 0
        END AS pendaftaran_dibuka,
        (SELECT COUNT(*)
         FROM pendaftaran_lomba p
         WHERE p.lomba_id = l.id
         AND p.mahasiswa_id = ?) AS sudah_daftar
     FROM lomba l
     WHERE l.id = ?
     AND l.status IN ('dipublikasikan', 'selesai')"
);

$stmt->execute([$user_id, $lomba_id]);
$lomba = $stmt->fetch();

if (!$lomba) {
    set_flash('error', 'Lomba tidak ditemukan.');
    redirect('mahasiswa/informasi_lomba.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_mahasiswa.php';
?>

<section class="page-heading">
    <div>
        <h1><?= e($lomba['judul']) ?></h1>
        <p>Detail informasi lomba.</p>
    </div>

    <a
        href="<?= e(url('mahasiswa/informasi_lomba.php')) ?>"
        class="btn btn-outline"
    >
        Kembali
    </a>
</section>

<section class="card">

    <?php if (!empty($lomba['poster'])): ?>
        <div style="margin-bottom:20px;">
            <img
                src="<?= e(url('uploads/poster/' . basename($lomba['poster']))) ?>"
                alt="Poster <?= e($lomba['judul']) ?>"
                style="max-width:100%;max-height:350px;object-fit:contain;"
            >
        </div>
    <?php endif; ?>

    <p>
        <strong>Penyelenggara:</strong>
        <?= e($lomba['penyelenggara'] ?? '-') ?>
    </p>

    <p>
        <strong>Kategori:</strong>
        <?= e($lomba['kategori'] ?? '-') ?>
    </p>

    <p>
        <strong>Tingkat:</strong>
        <?= e($lomba['tingkat'] ?? '-') ?>
    </p>

    <p>
        <strong>Lokasi:</strong>
        <?= e($lomba['lokasi'] ?? '-') ?>
    </p>

    <p>
        <strong>Periode:</strong>
        <?= e($lomba['tanggal_mulai'] ?? '-') ?>
        s/d
        <?= e($lomba['tanggal_selesai'] ?? '-') ?>
    </p>

    <p>
        <strong>Deadline Pendaftaran:</strong>
        <?= e($lomba['deadline_pendaftaran'] ?? '-') ?>
    </p>

    <div style="margin:20px 0;">
        <strong>Deskripsi:</strong>
        <p><?= nl2br(e($lomba['deskripsi'] ?? '-')) ?></p>
    </div>

    <?php if (!empty($lomba['link_panduan'])): ?>

        <p>
            <strong>Panduan Lomba:</strong>
            <a
                href="<?= e($lomba['link_panduan']) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                Lihat Panduan Lomba
            </a>
        </p>

    <?php endif; ?>

    <?php if (!empty($lomba['kontak_person'])): ?>
        <p>
            <strong>Kontak Person:</strong>
            <?= e($lomba['kontak_person']) ?>
        </p>
    <?php endif; ?>

    <hr>

    <div class="form-actions">

        <?php if ($lomba['sudah_daftar']): ?>

            <span class="badge badge-success">
                Anda Sudah Terdaftar
            </span>

        <?php elseif ($lomba['pendaftaran_dibuka']): ?>

            <a
                href="<?= e(url('mahasiswa/daftar_lomba.php?id=' . (int)$lomba['id'])) ?>"
                class="btn btn-primary"
            >
                Daftar Sekarang
            </a>

        <?php else: ?>

            <span class="badge">
                Pendaftaran Ditutup
            </span>

        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
