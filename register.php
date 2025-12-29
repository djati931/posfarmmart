<?php
session_start();
include 'config.php';

$message = '';
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');

    // 1. Validasi Input Dasar
    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $message = "Semua field harus diisi.";
        $error = true;
    } elseif (strlen($password) < 6) {
        $message = "Kata sandi minimal harus 6 karakter.";
        $error = true;
    } else {
        try {
            // 2. Cek apakah username sudah ada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Username sudah terdaftar. Silakan gunakan username lain.";
                $error = true;
            } else {
                // 3. Hash Kata Sandi untuk Keamanan
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4. Masukkan data ke Database
                $sql = "INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $hashed_password, $nama_lengkap]);

                $message = "Akun berhasil didaftarkan! Anda sekarang dapat <a href='index.php'>Login</a>.";
                $error = false;
            }
        } catch (PDOException $e) {
            $message = "Terjadi kesalahan database: " . $e->getMessage();
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun - Kasir Pertanian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .register-container { max-width: 500px; margin-top: 50px; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); background-color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container mx-auto">
            <h2 class="text-center mb-4">Registrasi Akun Pengguna</h2>
            <h5 class="text-center mb-4 text-muted">Kasir / Admin Toko Pertanian</h5>

            <?php if ($message): ?>
                <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?>" role="alert">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($nama_lengkap ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Daftar Akun Baru</button>
                </div>
                <p class="mt-3 text-center">
                    Sudah punya akun? <a href="index.php">Login di sini</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>