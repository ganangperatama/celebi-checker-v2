<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(f.flight_number LIKE ? OR f.route LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type === 'arrival' || $type === 'departure') {
    $where[] = "f.type = ?";
    $params[] = $type;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        f.id,
        f.flight_number,
        f.route,
        f.type,
        f.flight_date,
        f.schedule,
        f.status,
        COALESCE(b.total_koli, 0) AS total_koli,
        COALESCE(b.total_kg, 0) AS total_kg
    FROM flights f
    LEFT JOIN baggage_records b ON b.flight_id = f.id
    $whereSql
    ORDER BY f.flight_date DESC, f.schedule ASC, f.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Bagasi - <?= htmlspecialchars($config['app_name']) ?></title>

    <link rel="stylesheet" href="../assets/css/app.css">

    <style>
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input,
        .filter-bar select {
            min-width: 180px;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge.arrival {
            background: rgba(34, 197, 94, .12);
            color: #4ade80;
        }

        .badge.departure {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        .number {
            font-weight: 700;
        }

        .kg {
            color: #aab4c3;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-bar input,
            .filter-bar select,
            .filter-bar .btn {
                width: 100%;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 900px;
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
                    Data Bagasi
                </h1>

                <p class="muted">
                    Pencatatan dan rekapitulasi data bagasi berdasarkan penerbangan.
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
                        Daftar Data Bagasi
                    </h2>

                    <p class="muted">
                        Pilih penerbangan untuk menginput atau mengubah data bagasi.
                    </p>
                </div>

            </div>

            <form method="GET" class="filter-bar">

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari flight / route..."
                >

                <select name="type">

                    <option value="">
                        Semua Type
                    </option>

                    <option value="arrival" <?= $type === 'arrival' ? 'selected' : '' ?>>
                        Arrival
                    </option>

                    <option value="departure" <?= $type === 'departure' ? 'selected' : '' ?>>
                        Departure
                    </option>

                </select>

                <button type="submit" class="btn">
                    Cari
                </button>

                <a href="baggage.php" class="btn">
                    Reset
                </a>

            </form>

        </section>

        <section class="panel">

            <div class="table-wrap">

                <table>

                    <thead>

                    <tr>
                        <th>Flight</th>
                        <th>Route</th>
                        <th>Type</th>
                        <th>Tanggal</th>
                        <th>Schedule</th>
                        <th>Total Koli</th>
                        <th>Total KG</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if (!$rows): ?>

                        <tr>
                            <td colspan="9" class="empty">
                                Belum ada data penerbangan.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($rows as $row): ?>

                            <tr>

                                <td>
                                    <b>
                                        <?= htmlspecialchars($row['flight_number']) ?>
                                    </b>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['route']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $row['type'] ?>">
                                        <?= strtoupper($row['type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date('d/m/Y', strtotime($row['flight_date'])) ?>
                                </td>

                                <td>
                                    <?= $row['schedule']
                                        ? substr($row['schedule'], 0, 5)
                                        : '-' ?>
                                </td>

                                <td class="number">
                                    <?= number_format((int)$row['total_koli'], 0, ',', '.') ?>
                                </td>

                                <td class="number kg">
                                    <?= number_format((float)$row['total_kg'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['status']) ?>
                                </td>

                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="baggage_create.php?flight_id=<?= (int)$row['id'] ?>"
                                            class="btn btn-small"
                                        >
                                            Input Bagasi
                                        </a>

                                        <?php if ((int)$row['total_koli'] > 0 || (float)$row['total_kg'] > 0): ?>

                                            <a
                                                href="baggage_edit.php?flight_id=<?= (int)$row['id'] ?>"
                                                class="btn btn-small"
                                            >
                                                Edit
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>