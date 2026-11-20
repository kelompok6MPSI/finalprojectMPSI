
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

/*
|--------------------------------------------------------------------------
| 1. PERIKSA METODE REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 2. VALIDASI CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash(
        'error',
        'Sesi formulir tidak valid. Silakan coba kembali.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 3. AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

$nama = trim($_POST['nama'] ?? '');
$nim = trim($_POST['nim'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$username = trim($_POST['username'] ?? '');

$password = $_POST['password'] ?? '';
$konfirmasi = $_POST['konfirmasi_password'] ?? '';

$role = $_POST['role'] ?? 'mahasiswa';
$persetujuan = $_POST['persetujuan'] ?? '';

/*
|--------------------------------------------------------------------------
| 4. VALIDASI ROLE
|--------------------------------------------------------------------------
*/

$allowed_roles = ['mahasiswa', 'pic'];

if (!in_array($role, $allowed_roles, true)) {
    set_flash(
        'error',
        'Pilihan jenis akun tidak valid.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 5. VALIDASI KOLOM WAJIB
|--------------------------------------------------------------------------
*/

if (
    $nama === '' ||
    $nim === '' ||
    $email === '' ||
    $username === '' ||
    $password === '' ||
    $konfirmasi === ''
) {
    set_flash(
        'error',
        'Semua kolom wajib diisi.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 6. VALIDASI PERSETUJUAN
|--------------------------------------------------------------------------
*/

if ($persetujuan !== '1') {
    set_flash(
        'error',
        'Anda harus menyetujui ketentuan pendaftaran.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 7. VALIDASI PANJANG KARAKTER
|--------------------------------------------------------------------------
*/

if (
    mb_strlen($nama) > 100 ||
    mb_strlen($nim) > 30 ||
    mb_strlen($email) > 150 ||
    mb_strlen($username) > 50
) {
    set_flash(
        'error',
        'Terdapat data yang melebihi batas karakter.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 8. VALIDASI EMAIL
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash(
        'error',
        'Format email tidak valid.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 9. VALIDASI USERNAME
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[A-Za-z0-9_.-]{4,50}$/', $username)) {
    set_flash(
        'error',
        'Username minimal 4 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 10. VALIDASI PASSWORD
|--------------------------------------------------------------------------
*/

if (strlen($password) < 8) {
    set_flash(
        'error',
        'Kata sandi minimal 8 karakter.'
    );

    redirect('auth/register.php');
}

if ($password !== $konfirmasi) {
    set_flash(
        'error',
        'Konfirmasi kata sandi tidak sama.'
    );

    redirect('auth/register.php');
}

/*
|--------------------------------------------------------------------------
| 11. PROSES REGISTRASI
|--------------------------------------------------------------------------
*/

try {

    // Periksa apakah username, email, atau NIM sudah digunakan.

    $check = $pdo->prepare(
        'SELECT id
         FROM users
         WHERE username = :username
            OR email = :email
            OR nim = :nim
         LIMIT 1'
    );

    $check->execute([
        'username' => $username,
        'email' => $email,
        'nim' => $nim,
    ]);

    if ($check->fetch(PDO::FETCH_ASSOC)) {

        set_flash(
            'error',
            'Username, email, atau NIM sudah terdaftar.'
        );

        redirect('auth/register.php');
    }

    /*
    |--------------------------------------------------------------------------
    | 12. HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($hash === false) {
        throw new RuntimeException(
            'Gagal mengenkripsi password.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 13. SIMPAN DATA PENGGUNA
    |--------------------------------------------------------------------------
    */

    $insert = $pdo->prepare(
        'INSERT INTO users
            (
                nama,
                nim,
                email,
                username,
                password,
                role,
                status,
                created_at
            )
         VALUES
            (
                :nama,
                :nim,
                :email,
                :username,
                :password,
                :role,
                :status,
                NOW()
            )'
    );

    $insert->execute([
        'nama' => $nama,
        'nim' => $nim,
        'email' => $email,
        'username' => $username,
        'password' => $hash,
        'role' => $role,
        'status' => 'aktif',
    ]);

    /*
    |--------------------------------------------------------------------------
    | 14. BERHASIL
    |--------------------------------------------------------------------------
    */

    set_flash(
        'success',
        'Pendaftaran berhasil. Silakan masuk menggunakan akun Anda.'
    );

    redirect('auth/login.php');

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | 15. PENANGANAN ERROR DATABASE
    |--------------------------------------------------------------------------
    */

    error_log(
        'Registrasi SILOMBA gagal: ' . $e->getMessage()
    );

    if ($e->getCode() === '23000') {

        set_flash(
            'error',
            'Username, email, atau NIM sudah digunakan.'
        );

    } else {

        set_flash(
            'error',
            'Pendaftaran belum berhasil. Periksa konfigurasi database.'
        );
    }

    redirect('auth/register.php');

} catch (Throwable $e) {

    error_log(
        'Kesalahan registrasi SILOMBA: ' . $e->getMessage()
    );

    set_flash(
        'error',
        'Terjadi kesalahan saat memproses pendaftaran.'
    );

    redirect('auth/register.php');
}
