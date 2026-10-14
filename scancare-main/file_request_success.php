<?php
session_start();

$isSuccess = ($_GET['status'] ?? 'submitted') === 'submitted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $isSuccess ? 'Request Submitted' : 'Request Error'; ?> - ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-8">
        <section class="w-full rounded-2xl bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full <?php echo $isSuccess ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                <i class="fas <?php echo $isSuccess ? 'fa-check' : 'fa-exclamation-triangle'; ?> text-2xl"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold"><?php echo $isSuccess ? 'Request submitted successfully' : 'Request could not be completed'; ?></h1>
            <p class="mt-2 text-sm text-slate-600"><?php echo $isSuccess ? 'Your health worker will review your medical record request.' : 'Please return to your dashboard and try again.'; ?></p>
        </section>
    </main>
</body>
</html>
