<?php
session_start();
include 'config.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Inisialisasi Keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- LOGIKA TAMBAH/HAPUS/UPDATE ITEM KERANJANG ---
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add' && isset($_GET['product_id'])) {
        $product_id = (int)$_GET['product_id'];

        // Ambil data produk
        $stmt = $pdo->prepare("SELECT id, nama_produk, harga_jual, stok FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Cek apakah produk sudah ada di keranjang
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['qty']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'nama' => $product['nama_produk'],
                    'harga' => $product['harga_jual'],
                    'qty' => 1
                ];
            }
        }
        // Redirect untuk membersihkan parameter GET
        header('Location: dashboard.php');
        exit();
    }

    if ($_GET['action'] == 'remove' && isset($_GET['product_id'])) {
        $product_id = (int)$_GET['product_id'];
        unset($_SESSION['cart'][$product_id]);
        header('Location: dashboard.php');
        exit();
    }

    if ($_GET['action'] == 'clear') {
        $_SESSION['cart'] = [];
        header('Location: dashboard.php');
        exit();
    }
}

// --- LOGIKA PEMBAYARAN/CHECKOUT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $checkout_message = "Keranjang kosong, tidak bisa checkout.";
    } else {
        $metode = $_POST['metode_pembayaran'];
        $uang_dibayar = (float)$_POST['uang_dibayar'];
        $diskon_persen = (float)$_POST['diskon_persen'] / 100;
        $total_sebelum_diskon = 0;

        foreach ($_SESSION['cart'] as $item) {
            $total_sebelum_diskon += ($item['harga'] * $item['qty']);
        }
        
        $diskon_nilai = $total_sebelum_diskon * $diskon_persen;
        $total_transaksi = $total_sebelum_diskon - $diskon_nilai;
        $kembalian = $uang_dibayar - $total_transaksi;

        if ($kembalian < 0 && $metode == 'Tunai') {
             $checkout_message = "Pembayaran tunai kurang. Silakan cek kembali nominal bayar.";
        } else {
            try {
                // 1. Simpan Header Transaksi
                $sql_trans = "INSERT INTO transactions (tanggal_transaksi, total_transaksi, metode_pembayaran, uang_dibayar, kembalian, kasir_id) VALUES (NOW(), ?, ?, ?, ?, ?)";
                $stmt_trans = $pdo->prepare($sql_trans);
                $stmt_trans->execute([$total_transaksi, $metode, $uang_dibayar, $kembalian, $_SESSION['user_id']]);
                $transaction_id = $pdo->lastInsertId();

                // 2. Simpan Detail Transaksi & Kurangi Stok
                $pdo->beginTransaction();
                $sql_detail = "INSERT INTO transaction_details (transaction_id, product_id, qty, harga_saat_transaksi, diskon_item, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_detail = $pdo->prepare($sql_detail);
                $sql_update_stock = "UPDATE products SET stok = stok - ? WHERE id = ?";
                $stmt_stock = $pdo->prepare($sql_update_stock);

                foreach ($_SESSION['cart'] as $item) {
                    $item_subtotal = $item['harga'] * $item['qty'];
                    $stmt_detail->execute([$transaction_id, $item['id'], $item['qty'], $item['harga'], 0, $item_subtotal]);
                    $stmt_stock->execute([$item['qty'], $item['id']]);
                }
                
                $pdo->commit();

                $checkout_message = "Transaksi Berhasil! Total: Rp " . number_format($total_transaksi, 0, ',', '.') . ". Kembalian: Rp " . number_format($kembalian, 0, ',', '.');
                
                // Clear cart setelah sukses
                $_SESSION['cart'] = [];
                $_SESSION['last_transaction_id'] = $transaction_id;

                // Redirect ke halaman cetak struk (jika diperlukan) atau kembali ke dashboard
                // header('Location: receipt.php?id=' . $transaction_id);
                // exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                $checkout_message = "Checkout Gagal: " . $e->getMessage();
            }
        }
    }
}

// --- AMBIL DAFTAR PRODUK (Pencarian/Filter) ---
$search = $_GET['search'] ?? '';
$sql_products = "SELECT * FROM products WHERE nama_produk LIKE ? OR barcode LIKE ? ORDER BY nama_produk ASC";
$stmt_products = $pdo->prepare($sql_products);
$stmt_products->execute(["%$search%", "%$search%"]);
$product_list = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

// Hitung total keranjang
$grand_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $grand_total += ($item['harga'] * $item['qty']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Halaman Kasir (POS) - Kasir Pertanian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pos-container { display: flex; height: calc(100vh - 56px); }
        .product-area { flex: 2; overflow-y: auto; padding: 15px; border-right: 1px solid #ccc; }
        .cart-area { flex: 1; padding: 15px; background-color: #e9ecef; display: flex; flex-direction: column; }
        .cart-details { flex-grow: 1; overflow-y: auto; }
        .product-card { cursor: pointer; transition: transform 0.1s; }
        .product-card:hover { transform: scale(1.02); background-color: #f0f0f0; }
        .total-box { background-color: #343a40; color: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="pos-container">
        <div class="product-area">
            <h3>Pilih Produk</h3>
            <?php if (isset($checkout_message)): ?>
                <div class="alert <?= strpos($checkout_message, 'Gagal') !== false ? 'alert-danger' : 'alert-success' ?>">
                    <?= htmlspecialchars($checkout_message) ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Cari Produk atau Barcode..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">Cari</button>
                    <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="row row-cols-2 row-cols-md-3 g-3">
                <?php foreach ($product_list as $product): ?>
                    <div class="col">
                        <div class="card product-card" onclick="window.location.href='dashboard.php?action=add&product_id=<?= $product['id'] ?>'">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['nama_produk']) ?></h5>
                                <p class="card-text mb-1">Rp <?= number_format($product['harga_jual'], 0, ',', '.') ?></p>
                                <small class="text-muted">Stok: <?= $product['stok'] ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($product_list)): ?>
                    <div class="col-12"><p class="text-center text-muted">Produk tidak ditemukan.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cart-area">
            <h3 class="mb-3">Keranjang Belanja</h3>
            
            <div class="cart-details">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="text-center text-muted mt-5">Keranjang Kosong</div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($_SESSION['cart'] as $item_id => $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($item['nama']) ?></h6>
                                    <small class="text-muted"><?= $item['qty'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                </div>
                                <div>
                                    Rp <?= number_format($item['harga'] * $item['qty'], 0, ',', '.') ?>
                                    <a href="dashboard.php?action=remove&product_id=<?= $item_id ?>" class="btn btn-sm btn-outline-danger ms-2">x</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="dashboard.php?action=clear" class="btn btn-sm btn-outline-secondary w-100 mb-3">Kosongkan Keranjang</a>
                <?php endif; ?>
            </div>

            <div class="checkout-form mt-auto">
                <div class="total-box mb-3">
                    <h5 class="mb-0">TOTAL:</h5>
                    <h1 id="grandTotal" class="fw-bold">Rp <?= number_format($grand_total, 0, ',', '.') ?></h1>
                </div>

                <form method="POST">
                    <input type="hidden" name="checkout" value="1">
                    
                    <div class="mb-3">
                        <label for="diskon_persen" class="form-label">Diskon Global (%)</label>
                        <input type="number" class="form-control" id="diskon_persen" name="diskon_persen" value="0" min="0" max="100">
                    </div>

                    <div class="mb-3">
                        <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                        <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="uang_dibayar" class="form-label">Uang Dibayar</label>
                        <input type="number" class="form-control form-control-lg" id="uang_dibayar" name="uang_dibayar" value="<?= $grand_total ?>" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                            Bayar & Selesaikan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>