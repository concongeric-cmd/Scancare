<?php
session_start();
require_once 'db_config.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database connection unavailable.');
}
ensureFileRequestsTable($pdo);
$requestedCode = trim($_GET['code'] ?? '');
$residentId = (int) ($_SESSION['resident_id'] ?? 0);
$stmt = $requestedCode !== ''
    ? $pdo->prepare('SELECT resident_id, full_name, patient_code FROM resident WHERE patient_code = ? LIMIT 1')
    : $pdo->prepare('SELECT resident_id, full_name, patient_code FROM resident WHERE resident_id = ? LIMIT 1');
$stmt->execute([$requestedCode !== '' ? $requestedCode : $residentId]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$resident) {
    http_response_code(404);
    exit('Resident not found.');
}
if ($requestedCode !== '' && !hash_equals((string) ($resident['patient_code'] ?? ''), $requestedCode)) {
    http_response_code(403);
    exit('This QR code belongs to a different resident.');
}
$residentId = (int) $resident['resident_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm File Request - ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-8">
        <section class="w-full rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#454B1B]">ScanCare File Services</p>
            <h1 class="mt-3 text-2xl font-bold">Confirm your file request</h1>
            <p class="mt-2 text-sm text-slate-500">Your health worker will review the request and prepare your file after approval.</p>
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm text-slate-500">Resident</p>
                <p class="font-semibold"><?php echo htmlspecialchars($resident['full_name']); ?></p>
                <p class="mt-1 text-xs text-slate-500">Patient ID: <?php echo htmlspecialchars($resident['patient_code'] ?: str_pad((string) $residentId, 6, '0', STR_PAD_LEFT)); ?></p>
            </div>
            <form method="post" action="request_file_action.php" class="mt-6 space-y-4">
                <input type="hidden" name="resident_id" value="<?php echo $residentId; ?>">
                <input type="hidden" name="patient_code" value="<?php echo htmlspecialchars((string) ($resident['patient_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <label class="block text-sm font-semibold" for="requested_file">File requested</label>
                <select id="requested_file" name="requested_file" class="w-full rounded-lg border border-slate-300 px-3 py-3" required>
                    <option value="Medical Record">Medical Record</option>
                </select>
                <button type="submit" class="w-full rounded-lg bg-[#454B1B] px-4 py-3 font-semibold text-white hover:opacity-90">Confirm Request</button>
            </form>
            <a href="dashboard.php" class="mt-4 block text-center text-sm font-semibold text-slate-500 hover:text-slate-900">Cancel</a>
        </section>
    </main>
</body>
</html>
