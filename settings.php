<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Untuk contoh, kita buat data toko statis atau ambil dari tabel setting jika ada
$store_name = "Toko Pertanian Sederhana";
$store_address = "Jl. Proyek Tugas Kuliah No. 45";
$store_phone = "0812-3456-7890";
$message = '';

// Logika untuk menyimpan perubahan pengaturan (Sederhana)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Di aplikasi nyata, data ini akan disimpan ke tabel 'settings'
    // Untuk tujuan tugas, kita hanya tampilkan pesan sukses
    $store_name = $_POST['store_name'] ?? $store_name;
    $store_address = $_POST['store_address'] ?? $store_address;
    $store_phone = $_POST['store_phone'] ?? $store_phone;
    $message = "Pengaturan Toko berhasil disimpan!";
}

// Ambil daftar pengguna
$users = $pdo->query("SELECT id, username, nama_lengkap FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Aplikasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Pengaturan Aplikasi</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Informasi Toko</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="store_name" class="form-label">Nama Toko</label>
                        <input type="text" class="form-control" id="store_name" name="store_name" value="<?= htmlspecialchars($store_name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="store_address" class="form-label">Alamat Toko</label>
                        <input type="text" class="form-control" id="store_address" name="store_address" value="<?= htmlspecialchars($store_address) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="store_phone" class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" id="store_phone" name="store_phone" value="<?= htmlspecialchars($store_phone) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan Toko</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Daftar Akun Pengguna</div>
            <div class="card-body">
                <p>Tambah pengguna baru melalui halaman <a href="register.php">Registrasi Akun</a>.</p>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>