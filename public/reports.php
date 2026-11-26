<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

/* =========================
   FILTER
========================= */

$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';
$type      = $_GET['type'] ?? '';

$where = [];
$params = [];

if ($date_from !== '') {
    $where[] = "f.flight_date >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $where[] = "f.flight_date <= ?";
    $params[] = $date_to;
}

if ($type !== '' && in_array($type, ['arrival', 'departure'])) {
    $where[] = "f.type = ?";
    $params[] = $type;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}


/* =========================
   DATA LAPORAN
========================= */

$sql = "
    SELECT
        f.id,
        f.flight_number,
        f.registration,
        f.route,
        f.type,
        f.schedule,
        f.flight_date,
        f.status AS flight_status,

        b.id AS baggage_id,
        b.total_koli,
        b.total_kg,
        b.koli_selesai,
        b.sisa_koli

    FROM flights f

    LEFT JOIN baggage_records b
        ON b.flight_id = f.id

    $whereSql

    ORDER BY
        f.flight_date DESC,
        f.schedule DESC,
        f.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   RINGKASAN
========================= */

$totalFlight = count($rows);

$totalKoli = 0;
$totalKg = 0;
$totalKoliSelesai = 0;
$totalSisaKoli = 0;

$totalArrival = 0;
$totalDeparture = 0;

foreach ($rows as $row) {

    $totalKoli += (int)($row['total_koli'] ?? 0);

    $totalKg += (float)($row['total_kg'] ?? 0);

    $totalKoliSelesai += (int)($row['koli_selesai'] ?? 0);

    $totalSisaKoli += (int)($row['sisa_koli'] ?? 0);

    if ($row['type'] === 'arrival') {
        $totalArrival++;
    }

    if ($row['type'] === 'departure') {
        $totalDeparture++;
    }
}


/* =========================
   HELPER
========================= */

function formatTanggal($date)
{
    if (!$date) {
        return '-';
    }

    return date('d/m/Y', strtotime($date));
}

function formatJam($time)
{
    if (!$time) {
        return '-';
    }

    return date('H:i', strtotime($time));
}

function formatKg($kg)
{
    if ($kg === null || $kg === '') {
        return '-';
    }

    return number_format(
        (float)$kg,
        2,
        ',',
        '.'
    );
}

function formatAngka($value)
{
    return number_format(
        (int)$value,
        0,
        ',',
        '.'
    );
}

function statusBagasi($row)
{
    if ($row['baggage_id'] === null) {
        return 'Belum Diinput';
    }

    return 'Sudah Diinput';
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Laporan Data Bagasi - CELEBI Checker
    </title>

    <link rel="stylesheet"
          href="../assets/css/app.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        /* =========================
           LAYOUT
        ========================= */

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background:
                linear-gradient(
                    180deg,
                    #081b33 0%,
                    #0d2f55 100%
                );
            color: white;
            padding: 22px 16px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px 25px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            margin-bottom: 18px;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: white;
            color: #0d2f55;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .brand-text strong {
            display: block;
            font-size: 16px;
        }

        .brand-text span {
            display: block;
            font-size: 11px;
            color: #b9c9da;
            margin-top: 3px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .menu a {
            color: #dce7f3;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 10px;
            display: block;
            font-size: 14px;
            transition: .2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,.10);
            color: white;
        }

        .menu a.active {
            background: white;
            color: #0d2f55;
            font-weight: 700;
        }

        .logout {
            position: absolute;
            left: 16px;
            right: 16px;
            bottom: 20px;
        }

        .logout a {
            color: #ffdede;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 28px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            color: #0d2f55;
        }

        .page-header p {
            margin: 7px 0 0;
            color: #667085;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            text-decoration: none;
            cursor: pointer;
            padding: 11px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-primary {
            background: #0d6efd;
            color: white;
        }

        .btn-success {
            background: #198754;
            color: white;
        }

        .btn-dark {
            background: #172033;
            color: white;
        }

        .btn-light {
            background: white;
            color: #172033;
            border: 1px solid #dbe2ea;
        }


        /* =========================
           FILTER
        ========================= */

        .filter-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e3e8ef;
            box-shadow: 0 5px 18px rgba(20,40,80,.05);
            margin-bottom: 22px;
        }

        .filter-title {
            font-weight: 800;
            color: #0d2f55;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .filter-form {
            display: grid;
            grid-template-columns:
                1fr 1fr 1fr auto auto;
            gap: 12px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #667085;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            height: 42px;
            border: 1px solid #d7dee8;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            background: white;
        }


        /* =========================
           SUMMARY
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e3e8ef;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 5px 18px rgba(20,40,80,.04);
        }

        .summary-label {
            font-size: 12px;
            color: #667085;
            margin-bottom: 9px;
            font-weight: 600;
        }

        .summary-value {
            font-size: 25px;
            font-weight: 800;
            color: #0d2f55;
        }

        .summary-small {
            font-size: 11px;
            color: #98a2b3;
            margin-top: 4px;
        }


        /* =========================
           REPORT
        ========================= */

        .report-card {
            background: white;
            border: 1px solid #e3e8ef;
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(20,40,80,.05);
            overflow: hidden;
        }

        .report-top {
            padding: 20px 22px;
            border-bottom: 1px solid #e8edf3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-title {
            color: #0d2f55;
            font-size: 17px;
            font-weight: 800;
        }

        .report-subtitle {
            margin-top: 4px;
            color: #667085;
            font-size: 12px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th {
            background: #0d2f55;
            color: white;
            padding: 13px 12px;
            font-size: 11px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #edf0f4;
            font-size: 12px;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f7faff;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .flight {
            font-weight: 800;
            color: #0d2f55;
        }

        .route {
            color: #475467;
        }

        .badge {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .badge-arrival {
            background: #e8f3ff;
            color: #0d6efd;
        }

        .badge-departure {
            background: #eaf8ef;
            color: #198754;
        }

        .badge-success {
            background: #e9f8ef;
            color: #198754;
        }

        .badge-warning {
            background: #fff5db;
            color: #b77900;
        }

        .progress-text {
            font-size: 11px;
            color: #667085;
        }

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #98a2b3;
        }


        /* =========================
           FOOTER
        ========================= */

        .report-footer {
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: #667085;
            font-size: 11px;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 1000px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
                padding: 20px;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {

            .app {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            .logout {
                position: static;
                margin-top: 10px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }
        }


        /* =========================
           PRINT
        ========================= */

        @media print {

            body {
                background: white;
            }

            .sidebar,
            .filter-card,
            .header-actions,
            .no-print {
                display: none !important;
            }

            .main {
                margin: 0;
                width: 100%;
                padding: 0;
            }

            .page-header {
                display: block;
                text-align: center;
                margin-bottom: 15px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .page-header p {
                font-size: 11px;
            }

            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
                margin-bottom: 15px;
            }

            .summary-card {
                box-shadow: none;
                padding: 10px;
                border: 1px solid #ccc;
            }

            .summary-value {
                font-size: 18px;
            }

            .report-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            table {
                min-width: 0;
            }

            th,
            td {
                font-size: 8px;
                padding: 6px 5px;
            }

            .report-footer {
                padding: 10px;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }

    </style>

</head>

<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">

            <div class="brand-logo">
                CC
            </div>

            <div class="brand-text">
                <strong>CELEBI Checker</strong>
                <span>Baggage Monitoring System</span>
            </div>

        </div>


        <nav class="menu">

            <a href="index.php">
                Dashboard
            </a>

            <a href="flights.php?type=arrival">
                Arrival
            </a>

            <a href="flights.php?type=departure">
                Departure
            </a>

            <a href="flights.php">
                Data Penerbangan
            </a>

            <a href="baggage.php">
                Data Bagasi
            </a>

            <a href="reports.php" class="active">
                Laporan
            </a>

            <a href="scan.php">
                Scan Bagasi
            </a>

        </nav>


        <div class="logout">

            <a href="logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN -->
    <main class="main">

        <div class="page-header">

            <div>

                <h1>
                    Laporan Data Bagasi
                </h1>

                <p>
                    Rekapitulasi data penerbangan dan bagasi
                    CELEBI Checker
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="reports_export.php?<?= http_build_query($_GET) ?>"
                    class="btn btn-success">

                    Export CSV

                </a>


                <button
                    type="button"
                    onclick="window.print()"
                    class="btn btn-dark">

                    Cetak Laporan

                </button>

            </div>

        </div>


        <!-- FILTER -->

        <div class="filter-card">

            <div class="filter-title">
                Filter Laporan
            </div>


            <form
                method="GET"
                class="filter-form">

                <div class="form-group">

                    <label>
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        value="<?= htmlspecialchars($date_from) ?>">

                </div>


                <div class="form-group">

                    <label>
                        Tanggal Akhir
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        value="<?= htmlspecialchars($date_to) ?>">

                </div>


                <div class="form-group">

                    <label>
                        Jenis Operasi
                    </label>

                    <select name="type">

                        <option value="">
                            Semua Operasi
                        </option>

                        <option
                            value="arrival"
                            <?= $type === 'arrival'
                                ? 'selected'
                                : '' ?>>

                            Arrival

                        </option>

                        <option
                            value="departure"
                            <?= $type === 'departure'
                                ? 'selected'
                                : '' ?>>

                            Departure

                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary">

                    Terapkan

                </button>


                <a
                    href="reports.php"
                    class="btn btn-light">

                    Reset

                </a>

            </form>

        </div>


        <!-- SUMMARY -->

        <div class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    Total Flight
                </div>

                <div class="summary-value">
                    <?= formatAngka($totalFlight) ?>
                </div>

                <div class="summary-small">
                    Data penerbangan
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Koli
                </div>

                <div class="summary-value">
                    <?= formatAngka($totalKoli) ?>
                </div>

                <div class="summary-small">
                    Total bagasi
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Berat
                </div>

                <div class="summary-value">
                    <?= formatKg($totalKg) ?>
                </div>

                <div class="summary-small">
                    Kilogram
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Arrival / Departure
                </div>

                <div class="summary-value">
                    <?= $totalArrival ?> /
                    <?= $totalDeparture ?>
                </div>

                <div class="summary-small">
                    Jumlah penerbangan
                </div>

            </div>

        </div>


        <!-- TABLE -->

        <div class="report-card">

            <div class="report-top">

                <div>

                    <div class="report-title">
                        Rekapitulasi Data Bagasi
                    </div>

                    <div class="report-subtitle">

                        <?php if ($date_from || $date_to): ?>

                            Periode:
                            <?= $date_from
                                ? formatTanggal($date_from)
                                : 'Awal' ?>

                            -

                            <?= $date_to
                                ? formatTanggal($date_to)
                                : 'Sekarang' ?>

                        <?php else: ?>

                            Seluruh data penerbangan

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th class="center">
                            No
                        </th>

                        <th>
                            Flight
                        </th>

                        <th>
                            Registrasi
                        </th>

                        <th>
                            Route
                        </th>

                        <th>
                            Jenis Operasi
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Waktu
                        </th>

                        <th class="right">
                            Total Koli
                        </th>

                        <th class="right">
                            Total Kg
                        </th>

                        <th class="right">
                            Koli Selesai
                        </th>

                        <th class="right">
                            Sisa Koli
                        </th>

                        <th class="center">
                            Status
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($rows)): ?>

                        <tr>

                            <td
                                colspan="12"
                                class="empty">

                                Belum ada data laporan.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($rows as $index => $row): ?>

                            <tr>

                                <td class="center">
                                    <?= $index + 1 ?>
                                </td>


                                <td class="flight">
                                    <?= htmlspecialchars(
                                        $row['flight_number'] ?? '-'
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $row['registration'] ?? '-'
                                    ) ?>
                                </td>


                                <td class="route">
                                    <?= htmlspecialchars(
                                        $row['route'] ?? '-'
                                    ) ?>
                                </td>


                                <td>

                                    <?php if (
                                        $row['type'] === 'arrival'
                                    ): ?>

                                        <span class="badge badge-arrival">
                                            ARRIVAL
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-departure">
                                            DEPARTURE
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>
                                    <?= formatTanggal(
                                        $row['flight_date']
                                    ) ?>
                                </td>


                                <td>
                                    <?= formatJam(
                                        $row['schedule']
                                    ) ?>
                                </td>


                                <td class="right">

                                    <?= formatAngka(
                                        $row['total_koli'] ?? 0
                                    ) ?>

                                </td>


                                <td class="right">

                                    <?= formatKg(
                                        $row['total_kg'] ?? 0
                                    ) ?>

                                </td>


                                <td class="right">

                                    <?= formatAngka(
                                        $row['koli_selesai'] ?? 0
                                    ) ?>

                                </td>


                                <td class="right">

                                    <?= formatAngka(
                                        $row['sisa_koli'] ?? 0
                                    ) ?>

                                </td>


                                <td class="center">

                                    <?php if (
                                        $row['baggage_id'] !== null
                                    ): ?>

                                        <span class="badge badge-success">
                                            Sudah Diinput
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-warning">
                                            Belum Diinput
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div class="report-footer">

                <div>
                    CELEBI Checker — Sistem Pencatatan Data Bagasi
                </div>

                <div>
                    Dicetak:
                    <?= date('d/m/Y H:i') ?>
                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>