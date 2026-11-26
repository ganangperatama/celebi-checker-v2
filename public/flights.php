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
| FILTER
|--------------------------------------------------------------------------
*/

$type = $_GET['type'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

/*
|--------------------------------------------------------------------------
| FILTER TANGGAL
|--------------------------------------------------------------------------
*/

if ($date !== '') {
    $where[] = "f.flight_date = ?";
    $params[] = $date;
}

/*
|--------------------------------------------------------------------------
| FILTER TYPE
|--------------------------------------------------------------------------
*/

if ($type === 'arrival' || $type === 'departure') {
    $where[] = "f.type = ?";
    $params[] = $type;
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {
    $where[] = "(f.flight_number LIKE ? OR f.route LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

/*
|--------------------------------------------------------------------------
| QUERY DATA FLIGHT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        f.*,
        COALESCE(b.total_koli, 0) AS total_koli,
        COALESCE(b.total_kg, 0) AS total_kg
    FROM flights f
    LEFT JOIN baggage_records b
        ON b.flight_id = f.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY
        f.flight_date DESC,
        f.schedule ASC,
        f.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$flights = $stmt->fetchAll();

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
        Data Penerbangan - <?= htmlspecialchars($config['app_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/app.css"
    >

</head>

<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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

            <a
                class="<?= $type === 'arrival' ? 'active' : '' ?>"
                href="flights.php?type=arrival"
            >
                Arrival 
            </a>

            <a
                class="<?= $type === 'departure' ? 'active' : '' ?>"
                href="flights.php?type=departure"
            >
                Departure
            </a>

            <a
                class="<?= $type === '' ? 'active' : '' ?>"
                href="flights.php"
            >
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


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">

        <!-- HEADER -->

        <header>

            <div>

                <p class="eyebrow">
                    OPERASIONAL
                </p>

                <h1>
                    Data Penerbangan
                </h1>

                <p class="muted">
                    Kelola data penerbangan Arrival dan Departure.
                </p>

            </div>

            <div class="user">

                👤
                <?= htmlspecialchars($_SESSION['user']['username']) ?>

            </div>

        </header>


        <!-- =====================================================
             PANEL
        ====================================================== -->

        <section class="panel">

            <!-- PANEL HEADER -->

            <div class="panel-head">

                <div>

                    <h2>
                        Daftar Penerbangan
                    </h2>

                    <p class="muted">
                        Data penerbangan yang sudah tercatat.
                    </p>

                </div>

                <a
                    class="btn"
                    href="flight_create.php"
                >
                    + Tambah Flight
                </a>

            </div>


            <!-- =================================================
                 FILTER
            ================================================== -->

            <form
                method="GET"
                style="
                    display:flex;
                    gap:12px;
                    flex-wrap:wrap;
                    margin-bottom:20px;
                "
            >

                <!-- TANGGAL -->

                <input
                    type="date"
                    name="date"
                    value="<?= htmlspecialchars($date) ?>"
                >


                <!-- TYPE -->

                <select name="type">

                    <option value="">
                        Semua Type
                    </option>

                    <option
                        value="arrival"
                        <?= $type === 'arrival' ? 'selected' : '' ?>
                    >
                        Arrival
                    </option>

                    <option
                        value="departure"
                        <?= $type === 'departure' ? 'selected' : '' ?>
                    >
                        Departure
                    </option>

                </select>


                <!-- SEARCH -->

                <input
                    type="text"
                    name="search"
                    placeholder="Cari flight / route..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <!-- CARI -->

                <button
                    type="submit"
                    class="btn"
                >
                    Cari
                </button>


                <!-- RESET -->

                <a
                    href="flights.php"
                    class="btn"
                >
                    Reset
                </a>

            </form>


            <!-- =================================================
                 TABLE
            ================================================== -->

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
                                Tanggal
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

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$flights): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty"
                            >
                                Belum ada data penerbangan.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($flights as $row): ?>

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


                                <!-- TANGGAL -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['flight_date']
                                    ) ?>

                                </td>


                                <!-- SCHEDULE -->

                                <td>

                                    <?php if (!empty($row['schedule'])): ?>

                                        <?= htmlspecialchars(
                                            substr(
                                                $row['schedule'],
                                                0,
                                                5
                                            )
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


                                <!-- AKSI -->

                                <td>

                                    <a
                                        href="flight_edit.php?id=<?= (int)$row['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    &nbsp;|&nbsp;

                                    <a
                                        href="flight_delete.php?id=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus flight ini?')"
                                    >
                                        Hapus
                                    </a>

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