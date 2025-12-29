<?php
session_start();
include 'config.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

$product_to_edit = null; // Variabel untuk menampung data produk yang sedang diedit

// --- FUNGSI CREATE DAN UPDATE (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = $_POST['nama_produk'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    $harga_beli = $_POST['harga_beli'] ?? 0;
    $harga_jual = $_POST['harga_jual'] ?? 0;
    $stok = $_POST['stok'] ?? 0;
    $nama_kategori = $_POST['nama_kategori'] ?? null;
    $is_update = $_POST['is_update'] ?? 0;
    $product_id = $_POST['product_id'] ?? null;

    if ($is_update) {
        // UPDATE
        $sql = "UPDATE products SET barcode=?, nama_produk=?, harga_beli=?, harga_jual=?, stok=?, nama_kategori=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$barcode, $nama_produk, $harga_beli, $harga_jual, $stok, $nama_kategori, $product_id]);
        $message = "Produk **" . htmlspecialchars($nama_produk) . "** berhasil diupdate!";
        // Setelah update, redirect ke halaman bersih
        header('Location: products.php?message=' . urlencode($message));
        exit();
    } else {
        // CREATE
        $sql = "INSERT INTO products (barcode, nama_produk, harga_beli, harga_jual, stok, nama_kategori) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$barcode, $nama_produk, $harga_beli, $harga_jual, $stok, $nama_kategori]);
        $message = "Produk **" . htmlspecialchars($nama_produk) . "** berhasil ditambahkan!";
        header('Location: products.php?message=' . urlencode($message));
        exit();
    }
}

// --- FUNGSI READ (Ambil Data Produk untuk Edit) ---
if ($action == 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product_to_edit) {
        $message = "Produk tidak ditemukan.";
    }
}

// --- FUNGSI DELETE ---
if ($action == 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Produk berhasil dihapus!";
    header('Location: products.php?message=' . urlencode($message));
    exit();
}

// Ambil semua produk untuk ditampilkan di tabel
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil pesan dari URL setelah redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Manajemen Produk</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-<?= $product_to_edit ? 'warning' : 'success' ?> text-white">
                <?= $product_to_edit ? 'Ubah Data Produk: ' . htmlspecialchars($product_to_edit['nama_produk']) : 'Tambah Produk Baru' ?>
            </div>
            <div class="card-body">
                <form method="POST" action="products.php">
                    <input type="hidden" name="is_update" value="<?= $product_to_edit ? 1 : 0 ?>">
                    <input type="hidden" name="product_id" value="<?= $product_to_edit['id'] ?? '' ?>">
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Barcode (Opsional)</label>
                            <input type="text" class="form-control" name="barcode" 
                                value="<?= htmlspecialchars($product_to_edit['barcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" name="nama_produk" required 
                                value="<?= htmlspecialchars($product_to_edit['nama_produk'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="nama_kategori" placeholder="Cth: Pupuk, Benih"
                                value="<?= htmlspecialchars($product_to_edit['nama_kategori'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Harga Beli (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="harga_beli" required 
                                value="<?= htmlspecialchars($product_to_edit['harga_beli'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Harga Jual (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="harga_jual" required 
                                value="<?= htmlspecialchars($product_to_edit['harga_jual'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stok" required 
                                value="<?= htmlspecialchars($product_to_edit['stok'] ?? 0) ?>" 
                                <?= $product_to_edit ? '' : '' ?>>
                        </div>
                        <div class="col-md-3 align-self-end mb-3">
                            <button type="submit" class="btn btn-<?= $product_to_edit ? 'warning' : 'primary' ?> w-100">
                                <?= $product_to_edit ? 'Update Data' : 'Simpan Produk Baru' ?>
                            </button>
                        </div>
                    </div>
                </form>
                <?php if ($product_to_edit): ?>
                    <a href="products.php" class="btn btn-secondary btn-sm mt-2">Batalkan Edit</a>
                <?php endif; ?>
            </div>
        </div>

        <h3>Daftar Produk</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Hrg Beli</th>
                    <th>Hrg Jual</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['barcode'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                    <td><?= htmlspecialchars($p['nama_kategori'] ?? 'Tidak Ada') ?></td>
                    <td>Rp <?= number_format($p['harga_beli'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></td>
                    <td><?= $p['stok'] ?></td>
                    <td>
                        <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning me-2">Edit</a> 
                        
                        <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus produk ini?');">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>