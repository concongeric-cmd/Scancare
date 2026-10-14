<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['resident_id']) || ($_SESSION['user_type'] ?? '') !== 'resident') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scan QR Code - ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-xl px-4 py-8">
        <a href="dashboard.php" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900"><i class="fas fa-arrow-left"></i> Back to dashboard</a>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#454B1B]/10 text-2xl text-[#454B1B]"><i class="fas fa-qrcode"></i></div>
                <h1 class="text-2xl font-bold">Scan a QR code</h1>
                <p class="mt-2 text-sm text-slate-500">Scan your patient QR to view your medical record, or scan a health worker's QR to request a file.</p>
            </div>
            <div id="qr-reader" class="overflow-hidden rounded-xl bg-slate-950"></div>
            <p id="scan-status" class="mt-4 text-center text-sm text-slate-500">Starting camera...</p>
            <label for="qr-image" class="mt-5 block text-center text-sm font-semibold text-[#454B1B]">Or choose a QR image</label>
            <input id="qr-image" type="file" accept="image/*" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm">
        </section>
    </main>
    <script>
        const statusElement = document.getElementById('scan-status');
        const imageInput = document.getElementById('qr-image');
        const scanner = new Html5Qrcode('qr-reader');
        let redirected = false;
        let cameraStarted = false;

        function onScanSuccess(decodedText) {
            if (redirected) return;
            try {
                const url = new URL(decodedText);
                const path = url.pathname.replace(/\/+$/, '');
                const code = url.searchParams.get('code');
                if (code && (path.endsWith('/confirm_file_request.php') || path.endsWith('/resident_qr.php'))) {
                    redirected = true;
                    const destination = path.endsWith('/resident_qr.php')
                        ? 'resident_qr.php?code='
                        : 'confirm_file_request.php?code=';
                    statusElement.textContent = path.endsWith('/resident_qr.php')
                        ? 'Opening your read-only medical record...'
                        : 'QR code confirmed. Opening request page...';
                    const redirect = () => { window.location.href = destination + encodeURIComponent(code); };
                    if (cameraStarted) {
                        scanner.stop().then(redirect).catch(redirect);
                    } else {
                        redirect();
                    }
                    return;
                }
            } catch (error) {
                statusElement.textContent = 'This is not a supported ScanCare QR code.';
            }
            if (!redirected) statusElement.textContent = 'This is not a supported ScanCare QR code.';
        }

        imageInput.addEventListener('change', async () => {
            const image = imageInput.files && imageInput.files[0];
            if (!image) return;
            statusElement.textContent = 'Reading QR code from image...';
            try {
                if (cameraStarted) {
                    await scanner.stop();
                    cameraStarted = false;
                }
                const decodedText = await scanner.scanFile(image, false);
                onScanSuccess(decodedText);
            } catch (error) {
                statusElement.textContent = 'Could not read a QR code from that image. Choose a clearer image and try again.';
            } finally {
                imageInput.value = '';
            }
        });

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras.length) throw new Error('No camera found');
            return scanner.start(cameras[cameras.length - 1].id, { fps: 10, qrbox: { width: 240, height: 240 } }, onScanSuccess, () => {});
        }).then(() => {
            cameraStarted = true;
            statusElement.textContent = 'Point your camera at the QR code.';
        }).catch(() => {
            statusElement.textContent = 'Camera unavailable. Allow camera access or choose a QR image.';
        });
    </script>
</body>
</html>
