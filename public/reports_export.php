<?php

session_start();

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

if (
    $type !== '' &&
    in_array($type, ['arrival', 'departure'])
) {
    $where[] = "f.type = ?";
    $params[] = $type;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' .
        implode(' AND ', $where);
}


/* =========================
   CSV HEADER
========================= */

header(
    'Content-Type: text/csv; charset=utf-8'
);

header(
    'Content-Disposition: attachment; filename="Laporan_Bagasi_Celebi_' .
    date('Y-m-d') .
    '.csv"'
);


/* =========================
   OUTPUT
========================= */

$output = fopen(
    'php://output',
    'w'
);


/* BOM UNTUK EXCEL */

fprintf(
    $output,
    chr(0xEF) .
    chr(0xBB) .
    chr(0xBF)
);


/* =========================
   HEADER KOLOM
========================= */

fputcsv(
    $output,
    [
        'ID',
        'Jenis Operasi',
        'Nomor Flight',
        'Kode Registrasi Pesawat',
        'Route',
        'Tanggal',
        'Waktu',
        'Jumlah Koli',
        'Berat Aktual (Kg)',
        'Koli Selesai',
        'Sisa Koli',
        'Status Keterangan'
    ],
    ';'
);


/* =========================
   QUERY
========================= */

$sql = "
    SELECT

        f.id,
        f.type,
        f.flight_number,
        f.registration,
        f.route,
        f.flight_date,
        f.schedule,

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


/* =========================
   EXPORT
========================= */

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $status = 'Belum Diinput';

    if ($row['total_koli'] !== null) {
        $status = 'Sudah Diinput';
    }


    fputcsv(
        $output,
        [
            $row['id'],

            strtoupper(
                $row['type'] ?? '-'
            ),

            $row['flight_number'] ?? '-',

            $row['registration'] ?? '-',

            $row['route'] ?? '-',

            $row['flight_date'] ?? '-',

            $row['schedule'] ?? '-',

            $row['total_koli'] ?? 0,

            $row['total_kg'] ?? 0,

            $row['koli_selesai'] ?? 0,

            $row['sisa_koli'] ?? 0,

            $status
        ],
        ';'
    );
}


fclose($output);

exit;