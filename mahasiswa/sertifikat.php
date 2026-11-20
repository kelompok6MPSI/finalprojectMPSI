
<?php
require_once __DIR__ . '/../includes/role.php';
require_role('mahasiswa');

$page_title = 'Sertifikat';

$user_id = (int) current_user()['id'];
$lomba_id = filter_input(INPUT_GET, 'lomba_id', FILTER_VALIDATE_INT);
$cari = trim($_GET['cari'] ?? '');

if (!$lomba_id || $lomba_id < 1) {
    $lomba_id = null;
}

$lomba_list = [];
$sertifikat = [];
$lomba = null;
$errors = [];

// HALAMAN DAFTAR LOMBA
if (!$lomba_id) {

    $stmt = $pdo->query(
        "SELECT
            l.id,
            l.judul,
            COUNT(s.id) AS jumlah
         FROM lomba l
         INNER JOIN sertifikat s ON s.lomba_id = l.id
         GROUP BY l.id, l.judul
         ORDER BY l.judul ASC"
    );

    $lomba_list = $stmt->fetchAll();

// HALAMAN DETAIL SERTIFIKAT LOMBA
} else {

    // Memastikan lomba tersedia
    $stmt = $pdo->prepare(
        "SELECT
            l.id,
            l.judul,
            COUNT(s.id) AS jumlah
         FROM lomba l
         LEFT JOIN sertifikat s ON s.lomba_id = l.id
         WHERE l.id = ?
         GROUP BY l.id, l.judul"
    );

    $stmt->execute([$lomba_id]);
    $lomba = $stmt->fetch();

    if (!$lomba) {
        $errors[] = 'Lomba tidak ditemukan.';
        $lomba_id = null;

    } elseif ((int) $lomba['jumlah'] === 0) {
        $errors[] = 'Belum ada sertifikat untuk lomba ini.';

    } else {

        // Mengambil sertifikat berdasarkan lomba
        $sql = "
            SELECT
                s.id,
                s.nomor_sertifikat,
                s.file_sertifikat,
                s.tanggal_unggah,
                u.nama AS nama_mahasiswa,
                u.nim
            FROM sertifikat s
            INNER JOIN users u ON u.id = s.mahasiswa_id
            WHERE s.lomba_id = ?
        ";

        $params = [$lomba_id];

        // Pencarian nama atau NIM
        if ($cari !== '') {
            $sql .= "
                AND (
                    u.nama LIKE ?
                    OR u.nim LIKE ?
                )
            ";

            $params[] = '%' . $cari . '%';
            $params[] = '%' . $cari . '%';
        }

        $sql .= " ORDER BY u.nama ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $sertifikat = $stmt->fetchAll();
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_mahasiswa.php';
?>

<section class="page-heading">
    <div>
        <h1>Sertifikat</h1>
        <p>
            Lihat dan unduh sertifikat peserta yang telah mengikuti lomba.
        </p>
    </div>

    <?php if ($lomba_id): ?>
        <a
            href="<?= e(url('mahasiswa/sertifikat.php')) ?>"
            class="btn btn-outline"
        >
            Kembali
        </a>
    <?php endif; ?>
</section>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>


<!-- HALAMAN DAFTAR LOMBA -->
<?php if (!$lomba_id): ?>

<section class="card">

    <div class="card-header">
        <h2>Daftar Sertifikat Lomba</h2>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Lomba</th>
                    <th>Jumlah Sertifikat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($lomba_list)): ?>

                    <tr>
                        <td colspan="4" class="empty-state">
                            Belum ada sertifikat yang tersedia.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($lomba_list as $i => $l): ?>

                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <?= e($l['judul']) ?>
                            </td>

                            <td>
                                <?= (int) $l['jumlah'] ?>
                                Sertifikat
                            </td>

                            <td>
                                <a
                                    class="btn btn-sm btn-primary"
                                    href="<?= e(
                                        url(
                                            'mahasiswa/sertifikat.php?lomba_id='
                                            . (int) $l['id']
                                        )
                                    ) ?>"
                                >
                                    Lihat Sertifikat
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>


<!-- HALAMAN DETAIL SERTIFIKAT -->
<?php elseif ($lomba && (int) $lomba['jumlah'] > 0): ?>

<section class="card">

    <div class="card-header">
        <div>
            <h2><?= e($lomba['judul']) ?></h2>
            <p>
                Total <?= (int) $lomba['jumlah'] ?> sertifikat tersedia.
            </p>
        </div>
    </div>

    <!-- FORM PENCARIAN -->
    <form method="GET" class="filter-form">

        <input
            type="hidden"
            name="lomba_id"
            value="<?= (int) $lomba_id ?>"
        >

        <input
            type="search"
            class="form-control"
            name="cari"
            placeholder="Cari nama peserta atau NIM..."
            value="<?= e($cari) ?>"
        >

        <button type="submit" class="btn btn-primary">
            Cari
        </button>

        <?php if ($cari !== ''): ?>

            <a
                href="<?= e(
                    url(
                        'mahasiswa/sertifikat.php?lomba_id='
                        . (int) $lomba_id
                    )
                ) ?>"
                class="btn btn-outline"
            >
                Reset
            </a>

        <?php endif; ?>

    </form>

    <div class="table-wrapper">

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Peserta</th>
                    <th>NIM</th>
                    <th>Nomor Sertifikat</th>
                    <th>Tanggal Unggah</th>
                    <th>File Sertifikat</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($sertifikat)): ?>

                    <tr>
                        <td colspan="6" class="empty-state">
                            Sertifikat yang dicari tidak ditemukan.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($sertifikat as $i => $s): ?>

                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <?= e($s['nama_mahasiswa']) ?>
                            </td>

                            <td>
                                <?= e($s['nim'] ?? '-') ?>
                            </td>

                            <td>
                                <?= e($s['nomor_sertifikat']) ?>
                            </td>

                            <td>
                                <?= e($s['tanggal_unggah'] ?? '-') ?>
                            </td>

                            <td>

                                <?php
                                $nama_file = basename(
                                    $s['file_sertifikat'] ?? ''
                                );

                                $file_path = __DIR__
                                    . '/../uploads/sertifikat/'
                                    . $nama_file;

                                $file_url = url(
                                    'uploads/sertifikat/'
                                    . rawurlencode($nama_file)
                                );
                                ?>

                                <?php if (
                                    $nama_file !== ''
                                    && is_file($file_path)
                                ): ?>

                                    <a
                                        href="<?= e($file_url) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Buka PDF
                                    </a>

                                    <a
                                        href="<?= e($file_url) ?>"
                                        download
                                        class="btn btn-sm btn-outline"
                                    >
                                        Unduh
                                    </a>

                                <?php else: ?>

                                    <span>-</span>

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p>
        <small>
            Menampilkan <?= count($sertifikat) ?> sertifikat
            dari <?= (int) $lomba['jumlah'] ?> sertifikat tersedia.
        </small>
    </p>

</section>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
