<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;

            header('Location: index.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Celebi Checker</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #07111f;
            --panel: rgba(10, 24, 42, .78);
            --line: rgba(255,255,255,.11);
            --text: #f4f8ff;
            --muted: #91a2b8;
            --blue: #2ea7ff;
            --blue2: #65c2ff;
        }

        body {
            min-height: 100vh;
            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 12% 18%,
                    rgba(38, 135, 255, .20),
                    transparent 32%
                ),
                radial-gradient(
                    circle at 88% 82%,
                    rgba(0, 196, 255, .14),
                    transparent 32%
                ),
                var(--bg);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(
                    rgba(255,255,255,.025) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255,255,255,.025) 1px,
                    transparent 1px
                );
            background-size: 42px 42px;
            mask-image: linear-gradient(
                to bottom,
                black,
                transparent 90%
            );
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(1000px, 100%);
            min-height: 590px;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            position: relative;
            overflow: hidden;

            border: 1px solid var(--line);
            border-radius: 28px;

            background: rgba(8, 21, 37, .72);
            box-shadow:
                0 35px 100px rgba(0,0,0,.45);

            backdrop-filter: blur(20px);
        }

        /* LEFT */
        .visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    rgba(18, 57, 96, .78),
                    rgba(5, 16, 29, .95)
                );

            border-right: 1px solid var(--line);
        }

        .visual-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 40px;
        }

        .logo-mark {
            width: 78px;
            height: 78px;
            margin: 0 auto 22px;

            display: grid;
            place-items: center;

            border: 1px solid rgba(111, 199, 255, .35);
            border-radius: 24px;

            background: rgba(255,255,255,.06);
            box-shadow:
                0 15px 45px rgba(0, 132, 255, .18);

            font-size: 37px;
        }

        .visual h2 {
            font-size: 31px;
            letter-spacing: -.5px;
            margin-bottom: 10px;
        }

        .visual h2 span {
            color: var(--blue2);
        }

        .visual p {
            max-width: 360px;
            margin: auto;
            color: #a8b8cb;
            line-height: 1.7;
            font-size: 14px;
        }

        .system-label {
            margin-top: 28px;
            display: inline-flex;
            padding: 9px 14px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 999px;
            color: #91a8c0;
            background: rgba(255,255,255,.035);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .orbit {
            position: absolute;
            border: 1px solid rgba(75, 178, 255, .20);
            border-radius: 50%;
        }

        .orbit.one {
            width: 520px;
            height: 210px;
            transform: rotate(-25deg);
        }

        .orbit.two {
            width: 380px;
            height: 150px;
            transform: rotate(28deg);
        }

        .plane {
            position: absolute;
            z-index: 1;
            right: 16%;
            top: 18%;
            font-size: 72px;
            opacity: .12;
            transform: rotate(-18deg);
        }

        .ground {
            position: absolute;
            left: 34px;
            bottom: 30px;

            color: rgba(220,235,255,.40);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 3px;
        }

        /* RIGHT */
        .form-area {
            display: flex;
            align-items: center;
            padding: 52px;
        }

        .form {
            width: 100%;
            max-width: 380px;
            margin: auto;
        }

        .brand {
            color: #fff;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: .4px;
            margin-bottom: 30px;
        }

        .brand span {
            color: var(--blue2);
        }

        .form h1 {
            font-size: 30px;
            line-height: 1.2;
            margin-bottom: 9px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .error {
            margin-bottom: 20px;
            padding: 12px 14px;

            color: #ffbaba;
            background: rgba(220, 54, 54, .11);
            border: 1px solid rgba(255, 110, 110, .25);

            border-radius: 11px;
            font-size: 13px;
        }

        .field {
            display: block;
            margin-bottom: 20px;
        }

        .field span {
            display: block;
            margin-bottom: 8px;

            color: #dbe8f7;
            font-size: 12px;
            font-weight: 800;
        }

        .field input {
            width: 100%;
            padding: 14px 15px;

            color: #fff;
            background: rgba(255,255,255,.055);

            border: 1px solid rgba(255,255,255,.14);
            border-radius: 12px;

            outline: none;
            font-size: 14px;

            transition: .2s ease;
        }

        .field input::placeholder {
            color: #667a92;
        }

        .field input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(46,167,255,.10);
            background: rgba(255,255,255,.075);
        }

        .button {
            width: 100%;
            border: 0;
            border-radius: 12px;

            padding: 14px 16px;

            color: #fff;
            background:
                linear-gradient(
                    135deg,
                    #147fda,
                    #35b5ff
                );

            font-size: 14px;
            font-weight: 900;

            cursor: pointer;

            box-shadow:
                0 14px 32px rgba(25, 145, 235, .25);

            transition: .2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
        }

        .demo {
            margin-top: 17px;
            text-align: center;
            color: #667b93;
            font-size: 11px;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            color: #5f7289;
            font-size: 10px;
            letter-spacing: .8px;
        }

        @media (max-width: 760px) {
            .page {
                padding: 14px;
            }

            .shell {
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 22px;
            }

            .visual {
                display: none;
            }

            .form-area {
                padding: 38px 26px;
            }

            .form {
                max-width: none;
            }

            .form h1 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div class="shell">

        <section class="visual">

            <div class="orbit one"></div>
            <div class="orbit two"></div>

            <div class="plane">✈</div>

            <div class="visual-content">

                <div class="logo-mark">✈</div>

                <h2>
                    CELEBI <span>Checker</span>
                </h2>

                <p>
                    Sistem pencatatan dan rekapitulasi
                    data bagasi untuk mendukung
                    kegiatan operasional Ground Handling.
                </p>

                <div class="system-label">
                    BAGGAGE RECORDING SYSTEM
                </div>

            </div>

            <div class="ground">
                GROUND HANDLING
            </div>

        </section>

        <section class="form-area">

            <form class="form" method="post">

                <div class="brand">
                    CELEBI <span>Checker</span>
                </div>

                <h1>Selamat Datang</h1>

                <p class="subtitle">
                    Masuk untuk mengakses sistem pencatatan
                    dan pelaporan data bagasi.
                </p>

                <?php if ($error): ?>
                    <div class="error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <label class="field">
                    <span>USERNAME</span>
                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                    >
                </label>

                <label class="field">
                    <span>PASSWORD</span>
                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <button class="button" type="submit">
                    MASUK KE SISTEM
                </button>

                <div class="demo">
                    Demo: checker / password
                </div>

                <div class="footer">
                    CELEBI Aviation Indonesia · Ground Handling
                </div>

            </form>

        </section>

    </div>

</div>

</body>
</html>
