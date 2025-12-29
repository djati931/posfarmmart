<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$transaction_id = $_GET['id'] ?? null;
$transaction = null;
$details = [];

if ($transaction_id) {
    try {
        // Ambil data header transaksi
        $sql_header = "
            SELECT t.*, u.nama_lengkap AS nama_kasir
            FROM transactions t
            JOIN users u ON t.kasir_id = u.id
            WHERE t.id = ?
        ";
        $stmt_header = $pdo->prepare($sql_header);
        $stmt_header->execute([$transaction_id]);
        $transaction = $stmt_header->fetch(PDO::FETCH_ASSOC);

        // Ambil data detail item
        $sql_detail = "
            SELECT td.*, p.nama_produk
            FROM transaction_details td
            JOIN products p ON td.product_id = p.id
            WHERE td.transaction_id = ?
        ";
        $stmt_detail = $pdo->prepare($sql_detail);
        $stmt_detail->execute([$transaction_id]);
        $details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "Gagal memuat detail struk: " . $e->getMessage();
    }
} else {
    // Jika tidak ada ID, tampilkan riwayat 10 transaksi terakhir
    $recent_history = $pdo->query("
        SELECT t.id, t.tanggal_transaksi, t.total_transaksi, u.nama_lengkap
        FROM transactions t JOIN users u ON t.kasir_id = u.id
        ORDER BY t.tanggal_transaksi DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Struk & Cetak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body * { visibility: hidden; }
            .struk-print, .struk-print * { visibility: visible; }
            .struk-print { position: absolute; left: 0; top: 0; width: 100%; max-width: 300px; margin: 0 auto; padding: 10px; font-size: 10pt; }
        }
        .struk-preview { max-width: 400px; margin: 20px auto; border: 1px solid #ddd; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Riwayat Struk</h2>

        <?php if ($transaction): ?>
        
            <div class="struk-preview">
                <div class="struk-print text-center">
                    <h5>Nine Farm</h5>
                    <p style="font-size: 9pt; margin-bottom: 5px;">Jl. nin aja dulu, Desa Cianjai, kec. Cianjir</p>
                    <p style="font-size: 9pt; border-top: 1px dashed #000; padding-top: 5px;">
                        Tanggal: <?= date('d/m/Y H:i', strtotime($transaction['tanggal_transaksi'])) ?><br>
                        Kasir: <?= htmlspecialchars($transaction['nama_kasir']) ?><br>
                        TRX ID: TRX-<?= $transaction['id'] ?>
                    </p>
                    <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                        <?php $subtotal_items = 0; ?>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td style="text-align: left; padding: 2px 0;" colspan="2"><?= htmlspecialchars($d['nama_produk']) ?></td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding: 2px 0;"><?= $d['qty'] ?> x <?= number_format($d['harga_saat_transaksi'], 0, ',', '.') ?></td>
                                <td style="text-align: right; padding: 2px 0;"><?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                            <?php $subtotal_items += $d['subtotal']; ?>
                        <?php endforeach; ?>
                    </table>
                    <div style="border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; font-weight: bold;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>SUBTOTAL:</span>
                            <span>Rp <?= number_format($subtotal_items, 0, ',', '.') ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>TOTAL AKHIR:</span>
                            <span>Rp <?= number_format($transaction['total_transaksi'], 0, ',', '.') ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Bayar (<?= $transaction['metode_pembayaran'] ?>):</span>
                            <span>Rp <?= number_format($transaction['uang_dibayar'], 0, ',', '.') ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Kembalian:</span>
                            <span>Rp <?= number_format($transaction['kembalian'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <p style="margin-top: 10px; font-size: 9pt;">*** TERIMA KASIH ***</p>
                </div>
            </div>

            <div class="text-center mt-3">
                <button onclick="window.print()" class="btn btn-info">Cetak Struk</button>
                <a href="history.php" class="btn btn-secondary">Kembali ke Riwayat</a>
            </div>

        <?php else: ?>
            <div class="alert alert-info">Masukkan ID Transaksi, atau lihat riwayat 10 transaksi terakhir di bawah.</div>

            <form method="GET" class="mb-4">
                <div class="input-group" style="max-width: 400px; margin: 0 auto;">
                    <input type="number" class="form-control" name="id" placeholder="Masukkan ID Transaksi (Contoh: 123)">
                    <button class="btn btn-primary" type="submit">Cari Struk</button>
                </div>
            </form>

            <h3>10 Transaksi Terbaru</h3>
            <table class="table table-bordered table-striped" style="max-width: 600px; margin: 0 auto;">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>Total</th>
                        <th>Kasir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_history)): ?>
                        <tr><td colspan="5" class="text-center">Belum ada transaksi.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent_history as $h): ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= date('H:i:s', strtotime($h['tanggal_transaksi'])) ?></td>
                            <td>Rp <?= number_format($h['total_transaksi'], 0, ',', '.') ?></td>
                            <td><?= $h['nama_lengkap'] ?></td>
                            <td><a href="history.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-info">Lihat</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>