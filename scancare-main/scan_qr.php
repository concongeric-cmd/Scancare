<?php







session_start();
require_once 'db_config.php';




if (!isset($_SESSION['worker_id']) || $_SESSION['user_type'] !== 'health_worker') {
    header('Location: login.php');
    exit;
}

$bhw_name = $_SESSION['worker_name'] ?? 'BHW';
$bhw_id = $_SESSION['worker_id'];

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .scanner-status {
            transition: all 0.3s ease;
        }
        
        .scanner-active {
            border-color: #454B1B;
            box-shadow: 0 0 20px rgba(69, 75, 27, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 to-slate-800 font-body text-white min-h-screen">

    <nav class="bg-slate-800/50 border-b border-slate-700 sticky top-0 z-50 backdrop-blur-md">
        <div class="max-w-4xl mx-auto px-4 lg:px-6 py-4 flex items-center justify-between">
            <a href="dashboard_bhw.php" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-lg font-extrabold">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-400">Welcome, <?php echo htmlspecialchars($bhw_name); ?></span>
                <a href="dashboard_bhw.php" class="text-slate-400 hover:text-white text-sm font-medium">← Back</a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!
        <div class="text-center mb-8">
            <h1 class="text-4xl font-heading font-bold mb-2">Patient QR Scanner</h1>
            <p class="text-slate-400">Point your camera at a patient's QR code to access their health record</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!
            <div class="lg:col-span-2">
                <div class="bg-slate-800 border-2 border-slate-700 rounded-2xl p-6 scanner-status">
                    
                    <!
                    <div id="qr-reader" class="bg-black rounded-lg mb-4 overflow-hidden"></div>
                    
                    <!
                    <div id="scanner-status" class="bg-slate-700/50 rounded-lg p-4 text-center text-sm">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                            <span>Initializing camera...</span>
                        </div>
                    </div>

                    <!
                    <div class="flex gap-3 mt-6">
                        <button onclick="startScanner()" 
                                id="btn-start"
                                class="flex-1 px-4 py-3 rounded-lg font-semibold bg-green-600 hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-play"></i> Start Scanner
                        </button>
                        <button onclick="stopScanner()" 
                                id="btn-stop"
                                class="flex-1 px-4 py-3 rounded-lg font-semibold bg-red-600 hover:bg-red-700 transition-all flex items-center justify-center gap-2"
                                disabled>
                            <i class="fas fa-stop"></i> Stop Scanner
                        </button>
                    </div>
                </div>
            </div>

            <!
            <div class="lg:col-span-1">
                
                <!
                <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-heading font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-history"></i> Last Scan
                    </h3>
                    <div id="last-scan-info" class="text-sm text-slate-400">
                        <p>No scans yet today</p>
                    </div>
                </div>

                <!
                <div class="bg-blue-900/30 border border-blue-800 rounded-xl p-6">
                    <h3 class="text-lg font-heading font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i> How to Use
                    </h3>
                    <ul class="text-sm space-y-3 text-slate-300">
                        <li class="flex gap-2">
                            <span class="text-blue-400 font-bold">1.</span>
                            <span>Click "Start Scanner" to activate camera</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-blue-400 font-bold">2.</span>
                            <span>Point camera at patient's QR code</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-blue-400 font-bold">3.</span>
                            <span>Wait for automatic scan detection</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-blue-400 font-bold">4.</span>
                            <span>You'll be redirected to patient record</span>
                        </li>
                    </ul>
                </div>

            </div>

        </div>

    </main>

    <!
    <div id="success-toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg hidden flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">QR code scanned successfully!</span>
    </div>

    <!
    <div id="error-toast" class="fixed bottom-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg hidden flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i>
        <span id="error-message">Error scanning QR code</span>
    </div>

    <script>
        let html5QrcodeScanner;
        let isScanning = false;
        let lastScanTime = null;

        
        
        
        function initScanner() {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            updateScannerStatus('ready');
        }

        
        
        
        function startScanner() {
            Html5Qrcode.getCameras()
                .then(cameras => {
                    if (cameras && cameras.length > 0) {
                        
                        const cameraId = cameras[cameras.length - 1].id;
                        
                        html5QrcodeScanner.start(
                            cameraId,
                            { fps: 10, qrbox: { width: 250, height: 250 } },
                            onScanSuccess,
                            onScanError
                        );
                        
                        isScanning = true;
                        document.getElementById('btn-start').disabled = true;
                        document.getElementById('btn-stop').disabled = false;
                        document.getElementById('qr-reader').classList.add('scanner-active');
                        updateScannerStatus('scanning');
                    }
                })
                .catch(err => {
                    showError('Camera not available. Please check permissions.');
                    updateScannerStatus('error');
                });
        }

        
        
        
        function stopScanner() {
            html5QrcodeScanner.stop()
                .then(() => {
                    isScanning = false;
                    document.getElementById('btn-start').disabled = false;
                    document.getElementById('btn-stop').disabled = true;
                    document.getElementById('qr-reader').classList.remove('scanner-active');
                    updateScannerStatus('stopped');
                })
                .catch(err => console.error('Error stopping scanner:', err));
        }

        
        
        
        function onScanSuccess(decodedText, decodedResult) {
            
            const now = Date.now();
            if (lastScanTime && now - lastScanTime < 2000) {
                return;
            }
            lastScanTime = now;

            
            let token = decodedText;
            if (decodedText.includes('token=')) {
                token = new URLSearchParams(new URL(decodedText).search).get('token');
            }

            if (token) {
                
                logScan(token);
                
                
                showSuccess('QR code scanned! Redirecting...');
                
                
                setTimeout(() => {
                    window.location.href = '<?php echo dirname($_SERVER['PHP_SELF']); ?>/verify_patient.php?token=' + encodeURIComponent(token);
                }, 1000);
            }
        }

        
        
        
        function onScanError(error) {
            
        }

        
        
        
        function updateScannerStatus(status) {
            const statusDiv = document.getElementById('scanner-status');
            
            const statusMessages = {
                'ready': '<div class="flex items-center justify-center gap-2"><div class="w-2 h-2 bg-blue-500 rounded-full"></div><span>Ready to scan</span></div>',
                'scanning': '<div class="flex items-center justify-center gap-2"><div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div><span>Scanning active - point at QR code</span></div>',
                'stopped': '<div class="flex items-center justify-center gap-2"><div class="w-2 h-2 bg-yellow-500 rounded-full"></div><span>Scanner stopped</span></div>',
                'error': '<div class="flex items-center justify-center gap-2"><div class="w-2 h-2 bg-red-500 rounded-full"></div><span>Camera error - check permissions</span></div>'
            };
            
            statusDiv.innerHTML = statusMessages[status] || statusMessages['ready'];
        }

        
        
        
        function logScan(token) {
            const lastScanDiv = document.getElementById('last-scan-info');
            const now = new Date().toLocaleTimeString();
            lastScanDiv.innerHTML = `
                <p class="text-slate-300 mb-2"><strong>Scan detected:</strong></p>
                <p class="text-xs text-slate-500">Time: ${now}</p>
                <p class="text-xs text-slate-500 mt-1 truncate">Token: ${token}</p>
            `;
        }

        
        
        
        function showSuccess(message) {
            const toast = document.getElementById('success-toast');
            document.getElementById('toast-message').textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        
        
        
        function showError(message) {
            const toast = document.getElementById('error-toast');
            document.getElementById('error-message').textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        
        
        
        document.addEventListener('DOMContentLoaded', initScanner);

        
        
        
        window.addEventListener('beforeunload', () => {
            if (isScanning) {
                stopScanner();
            }
        });
    </script>

</body>
</html>

