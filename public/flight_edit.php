<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
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
| Ambil data flight
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
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

$error = '';

/*
|--------------------------------------------------------------------------
| Update data
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $flightNumber = trim($_POST['flight_number'] ?? '');
    $route        = trim($_POST['route'] ?? '');
    $type         = $_POST['type'] ?? '';
    $flightDate   = $_POST['flight_date'] ?? '';
    $schedule     = $_POST['schedule'] ?? '';
    $status       = trim($_POST['status'] ?? '');

    if (
        $flightNumber === '' ||
        $route === '' ||
        $type === '' ||
        $flightDate === '' ||
        $schedule === '' ||
        $status === ''
    ) {

        $error = 'Semua data wajib diisi.';

    } elseif (!in_array($type, ['arrival', 'departure'], true)) {

        $error = 'Type penerbangan tidak valid.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Cek duplikat
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM flights
            WHERE flight_number = ?
            AND flight_date = ?
            AND type = ?
            AND id != ?
        ");

        $check->execute([
            $flightNumber,
            $flightDate,
            $type,
            $id
        ]);

        if ((int)$check->fetchColumn() > 0) {

            $error = 'Flight dengan tanggal dan type tersebut sudah ada.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update
            |--------------------------------------------------------------------------
            */

            $update = $pdo->prepare("
                UPDATE flights
                SET
                    flight_number = ?,
                    route = ?,
                    type = ?,
                    flight_date = ?,
                    schedule = ?,
                    status = ?
                WHERE id = ?
            ");

            $update->execute([
                $flightNumber,
                $route,
                $type,
                $flightDate,
                $schedule,
                $status,
                $id
            ]);

            header('Location: flights.php');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pertahankan input kalau validasi gagal
    |--------------------------------------------------------------------------
    */

    $flight['flight_number'] = $flightNumber;
    $flight['route'] = $route;
    $flight['type'] = $type;
    $flight['flight_date'] = $flightDate;
    $flight['schedule'] = $schedule;
    $flight['status'] = $status;
}

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
        Edit Flight - <?= htmlspecialchars($config['app_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>

<div class="app">

    <!-- SIDEBAR -->

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

            <a class="active" href="flights.php">
                Data Penerbangan
            </a>

            <a href="baggage.php">
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


    <!-- MAIN -->

    <main class="main">

        <header>

            <div>

                <p class="eyebrow">
                    OPERASIONAL
                </p>

                <h1>
                    Edit Flight
                </h1>

                <p class="muted">
                    Perbarui informasi penerbangan.
                </p>

            </div>

            <div class="user">

                👤
                <?= htmlspecialchars($_SESSION['user']['username']) ?>

            </div>

        </header>


        <!-- FORM -->

        <section class="panel">

            <div class="panel-head">

                <div>

                    <h2>
                        Data Penerbangan
                    </h2>

                    <p class="muted">
                        Ubah data yang diperlukan.
                    </p>

                </div>

            </div>


            <?php if ($error): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:12px 15px;
                        border-radius:10px;
                        background:rgba(220,38,38,.12);
                        border:1px solid rgba(220,38,38,.35);
                        color:#fca5a5;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <form method="POST">

                <!-- FLIGHT NUMBER -->

                <div style="margin-bottom:18px;">

                    <label>
                        Flight Number
                    </label>

                    <input
                        type="text"
                        name="flight_number"
                        value="<?= htmlspecialchars(
                            $flight['flight_number']
                        ) ?>"
                        required
                    >

                </div>


                <!-- ROUTE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Route
                    </label>

                    <input
                        type="text"
                        name="route"
                        value="<?= htmlspecialchars(
                            $flight['route']
                        ) ?>"
                        required
                    >

                </div>


                <!-- TYPE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Type Penerbangan
                    </label>

                    <select
                        name="type"
                        required
                    >

                        <option
                            value="arrival"
                            <?= $flight['type'] === 'arrival'
                                ? 'selected'
                                : '' ?>
                        >
                            Arrival
                        </option>

                        <option
                            value="departure"
                            <?= $flight['type'] === 'departure'
                                ? 'selected'
                                : '' ?>
                        >
                            Departure
                        </option>

                    </select>

                </div>


                <!-- DATE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Tanggal Flight
                    </label>

                    <input
                        type="date"
                        name="flight_date"
                        value="<?= htmlspecialchars(
                            $flight['flight_date']
                        ) ?>"
                        required
                    >

                </div>


                <!-- SCHEDULE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Schedule
                    </label>

                    <input
                        type="time"
                        name="schedule"
                        value="<?= htmlspecialchars(
                            substr($flight['schedule'], 0, 5)
                        ) ?>"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div style="margin-bottom:25px;">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <?php
                        $statuses = [
                            'Scheduled',
                            'On Progress',
                            'Completed',
                            'Delayed',
                            'Cancelled'
                        ];
                        ?>

                        <?php foreach ($statuses as $status): ?>

                            <option
                                value="<?= htmlspecialchars($status) ?>"
                                <?= $flight['status'] === $status
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars($status) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- BUTTON -->

                <div style="display:flex; gap:10px;">

                    <button
                        type="submit"
                        class="btn"
                    >
                        Simpan Perubahan
                    </button>

                    <a
                        href="flights.php"
                        class="btn"
                    >
                        Batal
                    </a>

                </div>

            </form>

        </section>

    </main>

</div>

</body>

</html>