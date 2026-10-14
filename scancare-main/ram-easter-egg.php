<?php
session_start();

if (($_SESSION['user_type'] ?? '') !== 'easter_egg' || ($_SESSION['ram_easter_egg_access'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A little surprise</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Georgia, 'Times New Roman', serif;
            background: #171a12;
            color: #f6f1df;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(ellipse at top, #454b1b 0%, #25291a 38%, #171a12 78%);
        }

        main {
            width: min(100%, 760px);
            text-align: center;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2rem, 6vw, 4rem);
            font-weight: 500;
        }

        p { color: #d5d0bd; }

        .player {
            overflow: hidden;
            margin-top: 28px;
            border: 1px solid #777b51;
            border-radius: 10px;
            background: #090a08;
            box-shadow: 0 24px 70px #0008;
            aspect-ratio: 16 / 9;
        }

        iframe { width: 100%; height: 100%; border: 0; }

        a { color: #dce59b; }
    </style>
</head>
<body>
    <main>
        <h1>OOIIIIAAAA</h1>
        <p>Spin to win</p>
        <div class="player">
            <iframe
                src="https://www.youtube-nocookie.com/embed/bN1shALfJqg?autoplay=1&playsinline=1&rel=0"
                title="A surprise song for Ram"
                allow="autoplay; encrypted-media; picture-in-picture"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen>
            </iframe>
        </div>
        <a href="landing.php">Back to ScanCare</a>
    </main>
</body>
</html>