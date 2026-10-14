<?php


require_once 'appointment_system.php';


$metrics = getAppointmentMetrics();
$upcoming_appointments = getUpcomingAppointments();

?>

<!
     APPOINTMENT METRICS SECTION - BHW DASHBOARD
     ===================================================================== 
<section class="mb-12">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">
        <i class="fas fa-calendar-check mr-3 text-emerald-600"></i>Appointment Management
    </h2>

    <!
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Total Appointments</p>
                    <p class="text-4xl font-bold text-emerald-600 mt-2"><?php echo $metrics['total_appointments']; ?></p>
                </div>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 rounded-lg p-3">
                    <i class="fas fa-calendar text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Pending Consultations</p>
                    <p class="text-4xl font-bold text-yellow-600 mt-2"><?php echo $metrics['pending_consultations']; ?></p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3">
                    <i class="fas fa-hourglass-end text-2xl text-yellow-600"></i>
                </div>
            </div>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Completed</p>
                    <p class="text-4xl font-bold text-blue-600 mt-2"><?php echo $metrics['completed_consultations']; ?></p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3">
                    <i class="fas fa-check-circle text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Cancelled</p>
                    <p class="text-4xl font-bold text-red-600 mt-2"><?php echo $metrics['cancelled_appointments']; ?></p>
                </div>
                <div class="bg-red-100 dark:bg-red-900/30 rounded-lg p-3">
                    <i class="fas fa-times-circle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

    </div>

    <!
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
        
        <!
        <div class="bg-slate-50 dark:bg-slate-700/50 p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                <i class="fas fa-list mr-2 text-emerald-600"></i>Upcoming Appointments
            </h3>
        </div>

        <!
        <div class="overflow-x-auto">
            <?php if (empty($upcoming_appointments)): ?>
                
                <!
                <div class="p-12 text-center">
                    <i class="fas fa-inbox text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">No upcoming appointments</p>
                </div>

            <?php else: ?>
                
                <!
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Date & Time</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Resident Name</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Contact</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Purpose</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Status</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_appointments as $appt): ?>
                            <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                
                                <!
                                <td class="p-4">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?>
                                        </p>
                                        <p class="text-slate-600 dark:text-slate-400 text-xs">
                                            <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                        </p>
                                    </div>
                                </td>

                                <!
                                <td class="p-4">
                                    <p class="font-medium text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($appt['resident_name']); ?>
                                    </p>
                                </td>

                                <!
                                <td class="p-4">
                                    <div class="text-sm">
                                        <p class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($appt['contact_number']); ?></p>
                                        <p class="text-slate-600 dark:text-slate-400 text-xs"><?php echo htmlspecialchars($appt['email']); ?></p>
                                    </div>
                                </td>

                                <!
                                <td class="p-4">
                                    <p class="text-slate-700 dark:text-slate-300">
                                        <?php echo htmlspecialchars($appt['purpose']); ?>
                                    </p>
                                </td>

                                <!
                                <td class="p-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                        <?php if ($appt['status'] === 'pending'): ?>
                                            bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        <?php elseif ($appt['status'] === 'scheduled'): ?>
                                            bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                        <?php else: ?>
                                            bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                        <?php endif; ?>
                                    ">
                                        <?php echo ucfirst($appt['status']); ?>
                                    </span>
                                </td>

                                <!
                                <td class="p-4">
                                    <form method="POST" action="appointment_confirm.php" class="inline">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition text-sm">
                                            <i class="fas fa-check-circle mr-1"></i>Confirm Checkup
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div>

    </div>

</section>

