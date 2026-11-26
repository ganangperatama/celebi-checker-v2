<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$flightId = (int)($_GET['flight_id'] ?? $_POST['flight_id'] ?? 0);

if ($flightId <= 0) {
    header('Location: baggage.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil data flight
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM flights
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$flightId]);
$flight = $stmt->fetch();

if (!$flight) {
    header('Location: baggage.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek apakah data bagasi sudah ada
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare("
    SELECT *
    FROM baggage_records
    WHERE flight_id = ?
    LIMIT 1
");

$check->execute([$flightId]);
$existing = $check->fetch();

/*
|--------------------------------------------------------------------------
| Jika sudah ada, arahkan ke edit
|--------------------------------------------------------------------------
*/

if ($existing) {
    header('Location: baggage_edit.php?flight_id=' . $flightId);
    exit;
}

$error = '';

$totalKoli = '';
$totalKg = '';

/*
|--------------------------------------------------------------------------
| Proses simpan
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $totalKoli = trim($_POST['total_koli'] ?? '');
    $totalKg = trim($_POST['total_kg'] ?? '');

    if ($totalKoli === '' || $totalKg === '') {
        $error = 'Total koli dan total KG wajib diisi.';
    } elseif (!is_numeric($totalKoli) || (int)$totalKoli < 0) {
        $error = 'Total koli harus berupa angka yang valid.';
    } elseif (!is_numeric($totalKg) || (float)$totalKg < 0) {
        $error = 'Total KG harus berupa angka yang valid.';
    } else {

        $insert = $pdo->prepare("
            INSERT INTO baggage_records
            (
                flight_id,
                total_koli,
                total_kg
            )
            VALUES (?, ?, ?)
        ");

        $insert->execute([
            $flightId,
            (int)$totalKoli,
            (float)$totalKg
        ]);

        header('Location: baggage.php');
        exit;
    }
}

?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        Input Bagasi - <?= htmlspecialchars($config['app_name']) ?>
    </title>

    <link rel="stylesheet" href="../assets/css/app.css">

    <style>

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
        }

        .flight-info {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .info-box {
            padding: 15px;
            border-radius: 12px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .info-box span {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 5px;
        }

        .info-box strong {
            font-size: 15px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .error-box {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 10px;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
        }

        @media (max-width: 768px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .flight-info {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

    </style>

</head>

<body>

<div class="app">

    <aside class="sidebar">

        <div class="brand">
            CELEBI <span>Checker</span>
        </div>

        <nav>

            <a href="index.php">
                Dashboard
            </a>

            <div class="nav-title">
                OPERASIONAL
            </div>

            <a href="flights.php?type=arrival">
                Arrival
            </a>

            <a href="flights.php?type=departure">
                Departure
            </a>

            <a href="flights.php">
                Data Penerbangan
            </a>

            <a class="active" href="baggage.php">
                Data Bagasi
            </a>

            <a href="reports.php">
                Laporan
            </a>

            <a href="scan.php">
                Scan Bagasi
            </a>

            <div class="nav-title">
                AKUN
            </div>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </aside>

    <main class="main">

        <header>

            <div>

                <p class="eyebrow">
                    GROUND HANDLING
                </p>

                <h1>
                    Input Data Bagasi
                </h1>

                <p class="muted">
                    Tambahkan data bagasi untuk penerbangan yang dipilih.
                </p>

            </div>

            <div class="user">
                👤 <?= htmlspecialchars($_SESSION['user']['username']) ?>
            </div>

        </header>

        <section class="panel">

            <div class="panel-head">

                <div>

                    <h2>
                        Informasi Penerbangan
                    </h2>

                    <p class="muted">
                        Data penerbangan yang akan dicatat bagasinya.
                    </p>

                </div>

            </div>

            <div class="flight-info">

                <div class="info-box">

                    <span>
                        Flight
                    </span>

                    <strong>
                        <?= htmlspecialchars($flight['flight_number']) ?>
                    </strong>

                </div>

                <div class="info-box">

                    <span>
                        Route
                    </span>

                    <strong>
                        <?= htmlspecialchars($flight['route']) ?>
                    </strong>

                </div>

                <div class="info-box">

                    <span>
                        Type
                    </span>

                    <strong>
                        <?= strtoupper(htmlspecialchars($flight['type'])) ?>
                    </strong>

                </div>

                <div class="info-box">

                    <span>
                        Schedule
                    </span>

                    <strong>
                        <?= $flight['schedule']
                            ? substr($flight['schedule'], 0, 5)
                            : '-' ?>
                    </strong>

                </div>

            </div>

        </section>

        <section class="panel">

            <div class="panel-head">

                <div>

                    <h2>
                        Data Bagasi
                    </h2>

                    <p class="muted">
                        Masukkan total bagasi yang diterima atau tercatat pada flight.
                    </p>

                </div>

            </div>

            <?php if ($error): ?>

                <div class="error-box">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="flight_id"
                    value="<?= $flightId ?>"
                >

                <div class="form-grid">

                    <div class="form-group">

                        <label for="total_koli">
                            Total Koli
                        </label>

                        <input
                            type="number"
                            id="total_koli"
                            name="total_koli"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars($totalKoli) ?>"
                            placeholder="Contoh: 125"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="total_kg">
                            Total KG
                        </label>

                        <input
                            type="number"
                            id="total_kg"
                            name="total_kg"
                            min="0"
                            step="0.01"
                            value="<?= htmlspecialchars($totalKg) ?>"
                            placeholder="Contoh: 1850.50"
                            required
                        >

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn">
                        Simpan Data Bagasi
                    </button>

                    <a href="baggage.php" class="btn">
                        Batal
                    </a>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>