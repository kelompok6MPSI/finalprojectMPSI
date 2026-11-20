
<?php
require_once __DIR__ . '/../includes/role.php';
require_role('mahasiswa');

$page_title = 'Pendaftaran Lomba';
$user_id = (int) current_user()['id'];

$lomba_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect('mahasiswa/informasi_lomba.php');
    }

    $lomba_id = filter_var(
        $_POST['lomba_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$lomba_id || empty($_POST['persetujuan'])) {
        set_flash('error', 'Data pendaftaran tidak valid. Silakan setujui pernyataan terlebih dahulu.');
        redirect('mahasiswa/informasi_lomba.php');
    }

    // Periksa apakah lomba masih dibuka
    $stmt = $pdo->prepare(
        "SELECT id, judul
         FROM lomba
         WHERE id = ?
         AND status = 'dipublikasikan'
         AND deadline_pendaftaran >= CURDATE()"
    );

    $stmt->execute([$lomba_id]);
    $lomba_post = $stmt->fetch();

    if (!$lomba_post) {
        set_flash('error', 'Lomba tidak tersedia atau pendaftaran sudah ditutup.');
        redirect('mahasiswa/informasi_lomba.php');
    }

    // Periksa pendaftaran yang sudah ada
    $stmt = $pdo->prepare(
        "SELECT id
         FROM pendaftaran_lomba
         WHERE lomba_id = ?
         AND mahasiswa_id = ?
         LIMIT 1"
    );

    $stmt->execute([$lomba_id, $user_id]);

    if ($stmt->fetch()) {
        set_flash('error', 'Anda sudah mendaftar lomba ini.');
        redirect('mahasiswa/riwayat_lomba.php');
    }

    try {

        $stmt = $pdo->prepare(
            "INSERT INTO pendaftaran_lomba
             (lomba_id, mahasiswa_id, tanggal_daftar, status, created_at)
             VALUES (?, ?, NOW(), 'menunggu', NOW())"
        );

        $stmt->execute([$lomba_id, $user_id]);

        set_flash('success', 'Pendaftaran lomba berhasil dikirim.');

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            set_flash('error', 'Anda sudah terdaftar pada lomba ini.');
        } else {
            set_flash('error', 'Pendaftaran gagal disimpan.');
        }
    }

    redirect('mahasiswa/riwayat_lomba.php');
}

// Mengambil informasi lomba untuk halaman konfirmasi
if (!$lomba_id) {
    set_flash('error', 'Lomba tidak valid.');
    redirect('mahasiswa/informasi_lomba.php');
}

$stmt = $pdo->prepare(
    "SELECT l.*
     FROM lomba l
     WHERE l.id = ?
     AND l.status = 'dipublikasikan'
     AND l.deadline_pendaftaran >= CURDATE()"
);

$stmt->execute([$lomba_id]);
$lomba = $stmt->fetch();

// Periksa apakah mahasiswa sudah pernah mendaftar
$sudah_daftar = false;

if ($lomba) {
    $stmt = $pdo->prepare(
        "SELECT id
         FROM pendaftaran_lomba
         WHERE lomba_id = ?
         AND mahasiswa_id = ?
         LIMIT 1"
    );

    $stmt->execute([$lomba_id, $user_id]);
    $sudah_daftar = (bool) $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_mahasiswa.php';
?>

<section class="page-heading">
    <div>
        <h1>Daftar Lomba</h1>
        <p>Pastikan informasi lomba benar sebelum mendaftar.</p>
    </div>
</section>

<section class="card">

    <?php if (!$lomba): ?>

        <p>
            Lomba tidak ditemukan, belum dipublikasikan,
            atau sudah melewati deadline.
        </p>

        <a
            class="btn btn-primary"
            href="<?= e(url('mahasiswa/informasi_lomba.php')) ?>"
        >
            Kembali ke Informasi Lomba
        </a>

    <?php elseif ($sudah_daftar): ?>

        <p>Anda sudah terdaftar pada lomba ini.</p>

        <a
            class="btn btn-primary"
            href="<?= e(url('mahasiswa/riwayat_lomba.php')) ?>"
        >
            Lihat Riwayat Pendaftaran
        </a>

    <?php else: ?>

        <h2><?= e($lomba['judul']) ?></h2>

        <p>
            <strong>Penyelenggara:</strong>
            <?= e($lomba['penyelenggara'] ?? '-') ?>
        </p>

        <p>
            <strong>Kategori:</strong>
            <?= e($lomba['kategori'] ?? '-') ?>
        </p>

        <p>
            <strong>Deadline:</strong>
            <?= e($lomba['deadline_pendaftaran']) ?>
        </p>

        <p><?= nl2br(e($lomba['deskripsi'] ?? '-')) ?></p>

        <?php if (!empty($lomba['link_panduan'])): ?>
            <p>
                <strong>Panduan Lomba:</strong>
                <a
                    href="<?= e($lomba['link_panduan']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Baca Panduan Lomba
                </a>
            </p>
        <?php endif; ?>

        <hr>

        <form method="POST" data-validate>

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="lomba_id"
                value="<?= (int)$lomba['id'] ?>"
            >

            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="persetujuan"
                    value="1"
                    required
                >
                Saya telah membaca informasi dan panduan lomba,
                serta menyetujui untuk mendaftar pada lomba ini.
            </label>

            <div class="form-actions">

                <button
                    class="btn btn-primary"
                    type="submit"
                    data-confirm="Kirim pendaftaran lomba ini?"
                >
                    Konfirmasi Pendaftaran
                </button>

                <a
                    class="btn btn-outline"
                    href="<?= e(url('mahasiswa/detail_lomba.php?id=' . (int)$lomba['id'])) ?>"
                >
                    Batal
                </a>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
