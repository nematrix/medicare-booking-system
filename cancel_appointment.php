<?php
session_start();
require "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT a.*, u.name AS doctor_name
    FROM appointments a
    LEFT JOIN users u ON u.id = a.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cancel Appointments</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<style>
body {
    background: linear-gradient(135deg, #e0e7ff, #f8fafc);
    min-height: 100vh;
}

/* GLASS EFFECT */
.glass {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* TABLE ROW HOVER */
.table-row:hover {
    background: rgba(99, 102, 241, 0.08);
    transition: 0.2s ease;
}

/* BADGES */
.badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    color: white;
}
</style>
</head>

<body class="text-gray-800">

<!-- HEADER -->
<div class="flex justify-between items-center p-6">

    <div>
        <h1 class="text-2xl font-bold">Cancel Appointments</h1>
        <p class="text-gray-600">Manage your hospital bookings</p>
    </div>

    <a href="patient.php"
       class="glass px-4 py-2 flex items-center gap-2 font-medium hover:shadow-lg">
        <i data-feather="arrow-left"></i>
        Dashboard
    </a>

</div>

<!-- TABLE CONTAINER -->
<div class="px-6 pb-10">

    <div class="glass overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="p-4 text-left">Doctor</th>
                    <th class="p-4 text-left">Date</th>
                    <th class="p-4 text-left">Reason</th>
                    <th class="p-4 text-left">Status</th>
                    <th class="p-4 text-left">Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($appointments->num_rows > 0): ?>

                <?php while($row = $appointments->fetch_assoc()): ?>

                    <tr class="border-b border-white/30 table-row">

                        <td class="p-4 font-semibold">
                            <?= htmlspecialchars($row['doctor_name'] ?? 'Unknown') ?>
                        </td>

                        <td class="p-4">
                            <?= date("d M Y - H:i", strtotime($row['appointment_date'])) ?>
                        </td>

                        <td class="p-4">
                            <?= htmlspecialchars($row['reason']) ?>
                        </td>

                        <td class="p-4">

                            <?php
                            $status = $row['status'];

                            $color = match($status) {
                                'approved' => 'bg-green-500',
                                'pending' => 'bg-yellow-500',
                                'rejected' => 'bg-red-500',
                                'cancelled' => 'bg-gray-500',
                                default => 'bg-gray-400'
                            };
                            ?>

                            <span class="badge <?= $color ?>">
                                <?= ucfirst($status) ?>
                            </span>

                        </td>

                        <td class="p-4">

                            <?php if ($status !== 'cancelled'): ?>
                                <a href="cancel_action.php?id=<?= $row['id'] ?>"
                                   onclick="return confirm('Cancel this appointment?')"
                                   class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs shadow">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">No Action</span>
                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="text-center p-6 text-gray-500">
                        No appointments found
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<script>
feather.replace();
</script>

</body>
</html>