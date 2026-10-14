<?php

session_start();

require_once __DIR__ . '/app_config.php';

if (!isset($_SESSION['resident_id']) || ($_SESSION['user_type'] ?? '') !== 'resident') {
    header('Location: ' . appPath('login.php'));
    exit;
}

header('Location: ' . appPath('dashboard_resident.php'));
exit;


$patientData = [
    'fullName' => 'Gabriel Tala',
    'firstName' => 'Gabriel',
    'lastName' => 'Tala',
    'middleInitial' => 'C',
    'patientId' => 'SC-2026-00847',
    'emergencyContact' => 'Juan Santos',
    'emergencyPhone' => '09123456789',
    'serviceArea' => 'Barangay Gutad',
    'purok' => 'Purok 2 (East Residential)',
    'qrCode' => 'SC-2026-00847' 
];


function formatNameFormal($firstName, $lastName, $middleInitial = '') {
    $formal = $lastName . ', ' . $firstName;
    if (!empty($middleInitial)) {
        $formal .= ' ' . strtoupper(substr($middleInitial, 0, 1)) . '.';
    }
    return $formal;
}


$patientData['formalName'] = formatNameFormal($patientData['firstName'], $patientData['lastName'], $patientData['middleInitial']);


$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($patientData['qrCode']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient QR ID Card — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        
        .patient-id-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(69, 75, 27, 0.1);
            max-width: 420px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .patient-id-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(69, 75, 27, 0.15);
        }

        
        .card-header {
            background: linear-gradient(135deg, #454B1B 0%, #6b7333 100%);
            color: white;
            padding: 20px 24px;
            text-align: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-header p {
            margin: 4px 0 0 0;
            font-size: 12px;
            opacity: 0.9;
        }

        
        .card-content {
            padding: 24px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        
        .qr-container {
            flex-shrink: 0;
            background: #f8faf6;
            border: 2px solid #454B1B;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-container img {
            width: 140px;
            height: 140px;
            object-fit: contain;
        }

        
        .patient-details {
            flex: 1;
        }

        .detail-group {
            margin-bottom: 16px;
        }

        .detail-group:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }

        .detail-subtext {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        
        .card-footer {
            background: #f8faf6;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 20px;
            }

            .no-print {
                display: none;
            }

            .patient-id-card {
                box-shadow: none;
                max-width: 100%;
            }
        }

        
        @media (max-width: 640px) {
            .card-content {
                flex-direction: column;
                align-items: center;
            }

            .patient-details {
                text-align: center;
                width: 100%;
            }

            .qr-container {
                width: 100%;
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased">

    <!
    <div class="no-print bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-6 py-4 text-center">
            <h1 class="text-2xl font-heading font-bold text-slate-900">Patient QR ID Card</h1>
            <p class="text-sm text-slate-600 mt-1">Your unique health identifier card with QR code</p>
        </div>
    </div>

    <!
    <main class="max-w-4xl mx-auto px-6 py-12">
        
        <!
        <div class="flex justify-center">
            <div class="patient-id-card">
                
                <!
                <div class="card-header">
                    <h3>ScanCare Health Card</h3>
                    <p>Barangay Gutad Rural Health Unit</p>
                </div>

                <!
                <div class="card-content">
                    
                    <!
                    <div class="qr-container">
                        <img src="<?php echo $qrCodeUrl; ?>" alt="Patient QR Code">
                    </div>

                    <!
                    <div class="patient-details">
                        
                        <!
                        <div class="detail-group">
                            <span class="detail-label">Patient ID</span>
                            <div class="detail-value" style="color: #454B1B; font-size: 16px; font-weight: 700;">
                                <?php echo htmlspecialchars($patientData['patientId']); ?>
                            </div>
                        </div>

                        <!
                        <div class="detail-group">
                            <span class="detail-label">Full Name</span>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($patientData['formalName']); ?>
                            </div>
                        </div>

                        <!
                        <div class="detail-group">
                            <span class="detail-label">Service Area</span>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($patientData['serviceArea']); ?>
                            </div>
                            <div class="detail-subtext">
                                <?php echo htmlspecialchars($patientData['purok']); ?>
                            </div>
                        </div>

                        <!
                        <div class="detail-group">
                            <span class="detail-label">Emergency Contact</span>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($patientData['emergencyContact']); ?>
                            </div>
                            <div class="detail-subtext">
                                <?php echo htmlspecialchars($patientData['emergencyPhone']); ?>
                            </div>
                        </div>
                        
                    </div>

                </div>

                <!
                <div class="card-footer">
                    <i class="fa-solid fa-shield mr-2" style="color: #454B1B;"></i>
                    Keep this card with you at all times for quick health record access
                </div>

            </div>
        </div>

        <!
        <div class="mt-12 grid md:grid-cols-2 gap-8">
            
            <!
            <div class="bg-white border border-slate-200 rounded-xl p-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-[#454B1B]/10 flex items-center justify-center">
                        <i class="fa-solid fa-mobile text-[#454B1B]"></i>
                    </div>
                    <h3 class="text-lg font-heading font-bold text-slate-900">How to Use Your Card</h3>
                </div>
                <ul class="space-y-3 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <span class="text-[#454B1B] font-bold">1.</span>
                        <span>Keep your physical card in your wallet or bag</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-[#454B1B] font-bold">2.</span>
                        <span>Present the QR code at the health center</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-[#454B1B] font-bold">3.</span>
                        <span>Staff will scan for instant health record access</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-[#454B1B] font-bold">4.</span>
                        <span>Use the printed card for emergencies</span>
                    </li>
                </ul>
            </div>

            <!
            <div class="bg-white border border-slate-200 rounded-xl p-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-info text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-heading font-bold text-slate-900">Your Information</h3>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Patient ID:</span>
                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($patientData['patientId']); ?></span>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                        <span class="text-slate-600">Registered Name:</span>
                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($patientData['formalName']); ?></span>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                        <span class="text-slate-600">Service Area:</span>
                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($patientData['serviceArea']); ?></span>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                        <span class="text-slate-600">Emergency Contact:</span>
                        <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($patientData['emergencyContact']); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <!
        <div class="mt-12 flex gap-4 justify-center">
            <button onclick="window.print()" class="px-6 py-3 bg-[#454B1B] text-white rounded-lg font-medium hover:opacity-90 transition-opacity flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print My Card
            </button>
            <a href="resident-portal.php" class="px-6 py-3 border border-slate-200 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition-colors">
                Return to Portal
            </a>
        </div>

    </main>

    <!
    <style>
        @page {
            size: 8.5in 5.5in;
            margin: 0;
        }

        @media print {
            html, body {
                width: 8.5in;
                height: 5.5in;
            }

            main {
                max-width: 8.5in;
                height: 5.5in;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .patient-id-card {
                max-width: 100%;
            }
        }
    </style>

</body>
</html>

