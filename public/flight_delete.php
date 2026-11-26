<?php
session_start();

require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: flights.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek apakah flight ada
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM flights
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$flight = $stmt->fetch();

if (!$flight) {
    header('Location: flights.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Hapus data baggage terlebih dahulu
|--------------------------------------------------------------------------
*/

$baggage = $pdo->prepare("
    DELETE FROM baggage_records
    WHERE flight_id = ?
");

$baggage->execute([$id]);

/*
|--------------------------------------------------------------------------
| Hapus flight
|--------------------------------------------------------------------------
*/

$delete = $pdo->prepare("
    DELETE FROM flights
    WHERE id = ?
");

$delete->execute([$id]);

/*
|--------------------------------------------------------------------------
| Kembali ke Data Penerbangan
|--------------------------------------------------------------------------
*/

header('Location: flights.php');
exit;