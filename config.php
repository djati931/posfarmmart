<?php
// Pengaturan Koneksi Database
$host = 'localhost';
$db   = 'db_kasir_pertanian'; // Ganti jika nama database berbeda
$user = 'root'; // Ganti dengan user database Anda
$pass = ''; // Ganti dengan password database Anda

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set mode error PDO ke exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Koneksi database berhasil!"; // Hapus baris ini setelah pengujian
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>