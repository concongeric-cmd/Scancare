<?php







session_start();
require_once 'db_config.php';




if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    header('Location: login.php');
    exit;
}

$resident_id = $_SESSION['resident_id'];




try {
    $pdo = getDatabaseConnection();
    
    $query = "SELECT resident_id, full_name, email, qr_code_token FROM resident WHERE resident_id = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$resident_id]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resident) {
        die('Resident not found.');
    }
    
    
    if (empty($resident['qr_code_token'])) {
        $qr_token = bin2hex(random_bytes(32)); 
        $update_query = "UPDATE resident SET qr_code_token = ? WHERE resident_id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$qr_token, $resident_id]);
        $resident['qr_code_token'] = $qr_token;
    }
    
    
    $request_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $request_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $request_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $qr_url = $request_scheme . '://' . $request_host . $request_path . '/verify_patient.php?token=' .
              urlencode($resident['qr_code_token']);
    
    
    $qr_image_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' .
                    urlencode($qr_url);
    
} catch (Exception $e) {
    error_log("QR Code Error: " . $e->getMessage());
    die('Error loading QR code. Please try again.');
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Health QR Pass — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 font-body">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 lg:px-6 py-4 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-lg font-extrabold text-slate-900">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>
            <a href="dashboard.php" class="text-slate-600 hover:text-slate-900 font-medium text-sm">← Back to Dashboard</a>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 py-12">
        
        <!
        <div class="text-center mb-12">
            <h1 class="text-4xl font-heading font-bold text-slate-900 mb-2">My Health QR Pass</h1>
            <p class="text-slate-600">Show this QR code to Barangay Health Workers or Admins for quick health record access</p>
        </div>

        <!
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            
            <!
            <div class="bg-gradient-to-r from-[#454B1B]/10 to-slate-100 rounded-xl p-6 mb-8">
                <h2 class="text-lg font-heading font-bold text-slate-900 mb-1">
                    <?php echo htmlspecialchars($resident['full_name']); ?>
                </h2>
                <p class="text-sm text-slate-600">Resident ID: <?php echo str_pad($resident_id, 8, '0', STR_PAD_LEFT); ?></p>
            </div>

            <!
            <div class="flex flex-col items-center bg-slate-50 rounded-xl p-8 mb-8 border-2 border-dashed border-slate-200">
                <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                    <img src="<?php echo htmlspecialchars($qr_image_url); ?>" 
                         alt="Health QR Code" 
                         class="w-64 h-64 object-contain">
                </div>
                <p class="text-sm text-slate-600 text-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    This QR code links to your health profile
                </p>
            </div>

            <!
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-blue-100 text-blue-600">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-slate-900">How BHW Uses It</h3>
                        <p class="text-sm text-slate-600 mt-1">BHW scans this code with their phone to instantly access your medical records and appointment history</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-green-100 text-green-600">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-slate-900">Save Your Pass</h3>
                        <p class="text-sm text-slate-600 mt-1">Download or screenshot your QR code to your phone for easy access during clinic visits</p>
                    </div>
                </div>
            </div>

            <!
            <div class="flex gap-4 flex-col sm:flex-row">
                <button onclick="downloadQRCode()" 
                        class="flex-1 px-6 py-3 rounded-lg font-semibold text-white transition-all flex items-center justify-center gap-2"
                        style="background-color: #454B1B;">
                    <i class="fas fa-download"></i> Download QR Code
                </button>
                <button onclick="printQRCode()" 
                        class="flex-1 px-6 py-3 rounded-lg font-semibold border-2 border-slate-300 text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-print"></i> Print Pass
                </button>
            </div>
        </div>

        <!
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-900 flex items-start gap-3">
                <i class="fas fa-shield-alt flex-shrink-0 mt-0.5"></i>
                <span><strong>Privacy Notice:</strong> Your QR code is unique and secure. It only grants access to your basic health information and appointments. No sensitive financial or personal data is shared.</span>
            </p>
        </div>

    </main>

    <script>
        function downloadQRCode() {
            const link = document.createElement('a');
            link.href = '<?php echo htmlspecialchars($qr_image_url); ?>';
            link.download = 'health_qr_pass_<?php echo $resident_id; ?>.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function printQRCode() {
            const printWindow = window.open('', '_blank');
            const qrImageUrl = '<?php echo htmlspecialchars($qr_image_url); ?>';
            const residentName = '<?php echo htmlspecialchars($resident['full_name']); ?>';
            const residentId = '<?php echo str_pad($resident_id, 8, "0", STR_PAD_LEFT); ?>';
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Health QR Pass</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; }
                        .container { max-width: 400px; margin: 0 auto; }
                        h1 { font-size: 24px; margin-bottom: 10px; }
                        p { font-size: 14px; color: #666; margin: 10px 0; }
                        img { max-width: 100%; height: auto; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>${residentName}</h1>
                        <p>Resident ID: ${residentId}</p>
                        <img src="${qrImageUrl}" alt="Health QR Code">
                        <p>Show this QR code to healthcare workers</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 500);
        }
    </script>

</body>
</html>

