<?php
session_start();
include 'config.php'; // Hubungkan ke database

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Query untuk mengambil data user
    $stmt = $pdo->prepare("SELECT id, password, nama_lengkap FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verifikasi password (menggunakan password_verify karena kita menyimpan hash)
        if (password_verify($password, $user['password'])) {
            // Login sukses
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

            // Redirect ke halaman utama kasir
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Username atau Password salah.";
        }
    } else {
        $error_message = "Username atau Password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nine Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); background-color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container mx-auto">
            <h2 class="text-center mb-4">NineFarm</h2>
            <h5 class="text-center mb-4 text-muted">Selamat Datang, Silahkan Login! :D</h5>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Login</button>
                <p class="mt-3 text-center">
                    Belum punya akun? <a href="register.php">Daftar Akun Baru</a>
                </p>
            </form>
        </div>
    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>