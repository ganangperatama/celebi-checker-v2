<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalFlights = (int)$pdo->query("
    SELECT COUNT(*)
    FROM flights
    WHERE flight_date = CURDATE()
")->fetchColumn();

$arrivalFlights = (int)$pdo->query("
    SELECT COUNT(*)
    FROM flights
    WHERE flight_date = CURDATE()
    AND type = 'arrival'
")->fetchColumn();

$departureFlights = (int)$pdo->query("
    SELECT COUNT(*)
    FROM flights
    WHERE flight_date = CURDATE()
    AND type = 'departure'
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| Recent Flights
|--------------------------------------------------------------------------
*/

$recent = $pdo->query("
    SELECT
        f.*,
        COALESCE(b.total_koli, 0) AS total_koli,
        COALESCE(b.total_kg, 0) AS total_kg
    FROM flights f
    LEFT JOIN baggage_records b
        ON b.flight_id = f.id
    WHERE f.flight_date = CURDATE()
    ORDER BY f.schedule ASC, f.id DESC
    LIMIT 10
")->fetchAll();

?>
<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= htmlspecialchars($config['app_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>

<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">

        <div class="brand">
            CELEBI <span>Checker</span>
        </div>

        <nav>

            <!-- DASHBOARD -->

            <a
                class="active"
                href="index.php"
            >
                Dashboard
            </a>


            <div class="nav-title">
                OPERASIONAL
            </div>


            <!-- ARRIVAL -->

            <a href="flights.php?type=arrival">
                Arrival 1
            </a>


            <!-- DEPARTURE -->

            <a href="flights.php?type=departure">
                Departure
            </a>


            <!-- DATA PENERBANGAN -->

            <a href="flights.php">
                Data Penerbangan
            </a>


            <!-- DATA BAGASI -->

            <a href="baggage.php">
                Data Bagasi
            </a>


            <!-- LAPORAN -->

            <a href="reports.php">
                Laporan
            </a>


            <!-- SCAN BAGASI -->

            <a href="scan.php">
                Scan Bagasi
            </a>


            <div class="nav-title">
                AKUN
            </div>


            <!-- LOGOUT -->

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </aside>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="main">

        <!-- HEADER -->

        <header>

            <div>

                <p class="eyebrow">
                    GROUND HANDLING
                </p>

                <h1>
                    Dashboard
                </h1>

                <p class="muted">
                    Ringkasan pencatatan data bagasi hari ini.
                </p>

            </div>


            <div class="user">

                👤
                <?= htmlspecialchars($_SESSION['user']['username']) ?>

            </div>

        </header>


        <!-- =====================================================
             DASHBOARD CARDS
        ====================================================== -->

        <section class="cards">

            <!-- TOTAL FLIGHT -->

            <div class="card">

                <span>
                    Flight Hari Ini
                </span>

                <strong>
                    <?= $totalFlights ?>
                </strong>

            </div>


            <!-- ARRIVAL -->

            <div class="card">

                <span>
                    Arrival
                </span>

                <strong>
                    <?= $arrivalFlights ?>
                </strong>

            </div>


            <!-- DEPARTURE -->

            <div class="card">

                <span>
                    Departure
                </span>

                <strong>
                    <?= $departureFlights ?>
                </strong>

            </div>

        </section>


        <!-- =====================================================
             FLIGHT TABLE
        ====================================================== -->

        <section class="panel">

            <div class="panel-head">

                <div>

                    <h2>
                        Flight Hari Ini
                    </h2>

                    <p class="muted">
                        Data penerbangan yang tercatat.
                    </p>

                </div>


                <!-- TAMBAH FLIGHT -->

                <a
                    class="btn"
                    href="flight_create.php"
                >
                    + Tambah Flight
                </a>

            </div>


            <!-- TABLE -->

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Flight
                            </th>

                            <th>
                                Route
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Schedule
                            </th>

                            <th>
                                Koli
                            </th>

                            <th>
                                KG
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$recent): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >
                                Belum ada data penerbangan hari ini.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($recent as $row): ?>

                            <tr>

                                <!-- FLIGHT -->

                                <td>

                                    <b>
                                        <?= htmlspecialchars(
                                            $row['flight_number']
                                        ) ?>
                                    </b>

                                </td>


                                <!-- ROUTE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['route']
                                    ) ?>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <span
                                        class="badge <?= htmlspecialchars(
                                            $row['type']
                                        ) ?>"
                                    >

                                        <?= strtoupper(
                                            htmlspecialchars(
                                                $row['type']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- SCHEDULE -->

                                <td>

                                    <?php if ($row['schedule']): ?>

                                        <?= substr(
                                            $row['schedule'],
                                            0,
                                            5
                                        ) ?>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>


                                <!-- KOLI -->

                                <td>

                                    <?= (int)$row['total_koli'] ?>

                                </td>


                                <!-- KG -->

                                <td>

                                    <?= number_format(
                                        (float)$row['total_kg'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['status']
                                    ) ?>

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