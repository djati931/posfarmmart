<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Inisialisasi rentang tanggal default (Hari Ini)
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$reports = [];
$grand_total_sales = 0;
$total_transactions = 0;

try {
    // Query untuk mengambil data transaksi dalam rentang tanggal
    $sql = "
        SELECT 
            t.id, 
            t.tanggal_transaksi, 
            t.total_transaksi, 
            t.metode_pembayaran, 
            u.nama_lengkap AS nama_kasir
        FROM transactions t
        JOIN users u ON t.kasir_id = u.id
        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
        ORDER BY t.tanggal_transaksi DESC
    ";
    $stmt = $pdo->prepare($sql);
    // Kita gunakan rentang waktu 00:00:00 hingga 23:59:59
    $stmt->execute([$start_date, $end_date]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung Total Statistik
    $total_transactions = count($reports);
    foreach ($reports as $report) {
        $grand_total_sales += $report['total_transaksi'];
    }

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data laporan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Laporan Penjualan</h2>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Filter Laporan</div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan Laporan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Pendapatan Bersih</h5>
                        <p class="card-text fs-2">Rp <?= number_format($grand_total_sales, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Jumlah Transaksi</h5>
                        <p class="card-text fs-2"><?= $total_transactions ?> Transaksi</p>
                    </div>
                </div>
            </div>
        </div>

        <h3>Detail Transaksi (<?= date('d M Y', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date)) ?>)</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID Transaksi</th>
                    <th>Tanggal & Waktu</th>
                    <th>Kasir</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="6" class="text-center">Tidak ada data transaksi dalam periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td>TRX-<?= $r['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['tanggal_transaksi'])) ?></td>
                    <td><?= htmlspecialchars($r['nama_kasir']) ?></td>
                    <td><?= htmlspecialchars($r['metode_pembayaran']) ?></td>
                    <td>Rp <?= number_format($r['total_transaksi'], 0, ',', '.') ?></td>
                    <td>
                        <a href="history.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info">Lihat Struk</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>