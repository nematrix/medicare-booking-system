<?php
session_start();
require "db.php";
require_once "notification_functions.php";

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$patient = $_SESSION['user'];
$patient_id = $patient['id'];

$message = "";
$messageType = "";

/* =========================
   BOOK APPOINTMENT
========================= */
if (isset($_POST['book'])) {

    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $date      = trim($_POST['date'] ?? '');
    $time      = trim($_POST['time'] ?? '');
    $reason    = trim($_POST['reason'] ?? '');

    $appointment_date = $date . " " . $time . ":00";

    /* VALIDATION */
    if (!$doctor_id || !$date || !$time || !$reason) {
        $message = "All fields are required.";
        $messageType = "error";
    } else {

        $dayOfWeek = date("l", strtotime($date));

        $selectedTime = strtotime($time);
        $openingTime = strtotime("07:00");
        $closingTime = strtotime("17:00");

        /* WEEKEND BLOCK */
        if ($dayOfWeek === "Saturday" || $dayOfWeek === "Sunday") {
            $message = "Appointments allowed Monday to Friday only.";
            $messageType = "error";
        }

        /* TIME BLOCK */
        elseif ($selectedTime < $openingTime || $selectedTime > $closingTime) {
            $message = "Appointments allowed between 7:00 AM and 5:00 PM.";
            $messageType = "error";
        }

        else {

            /* CHECK DUPLICATE */
            $check = $conn->prepare("
                SELECT id 
                FROM appointments
                WHERE doctor_id = ? 
                AND appointment_date = ?
            ");
            $check->bind_param("is", $doctor_id, $appointment_date);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "This slot is already booked.";
                $messageType = "error";
            } else {

                /* INSERT */
                $stmt = $conn->prepare("
                    INSERT INTO appointments (
                        patient_id,
                        doctor_id,
                        appointment_date,
                        reason,
                        status
                    )
                    VALUES (?, ?, ?, ?, 'pending')
                ");

                $stmt->bind_param(
                    "iiss",
                    $patient_id,
                    $doctor_id,
                    $appointment_date,
                    $reason
                );

                if ($stmt->execute()) {

                    /* ✅ FIXED HERE */
                    $appointment_id = $conn->insert_id;

                    /* NOTIFICATIONS */

                    sendNotification(
                        $conn,
                        $doctor_id,
                        "doctor",
                        $patient['name'] . " booked an appointment on " .
                        date("M d, Y h:i A", strtotime($appointment_date)),
                        $appointment_id
                    );

                    sendNotification(
                        $conn,
                        $patient_id,
                        "patient",
                        "Your appointment was booked successfully.",
                        $appointment_id
                    );

                    $admins = $conn->query("SELECT id FROM users WHERE role='admin'");

                    while ($admin = $admins->fetch_assoc()) {
                        sendNotification(
                            $conn,
                            $admin['id'],
                            "admin",
                            "New appointment booked by " . $patient['name'],
                            $appointment_id
                        );
                    }

                    $message = "Appointment booked successfully!";
                    $messageType = "success";

                } else {
                    $message = "Failed to book appointment.";
                    $messageType = "error";
                }
            }
        }
    }
}

/* =========================
   FETCH DOCTORS
========================= */
$doctors = $conn->query("
    SELECT id, name 
    FROM users 
    WHERE role='doctor'
    ORDER BY name ASC
");

/* =========================
   FETCH SCHEDULES
========================= */
$schedules = [];

$res = $conn->query("
    SELECT ds.*, u.name AS doctor_name
    FROM doctor_schedules ds
    JOIN users u ON u.id = ds.doctor_id
    WHERE ds.is_available = 1
");

while ($row = $res->fetch_assoc()) {
    $schedules[$row['doctor_id']]['name'] = $row['doctor_name'];
    $schedules[$row['doctor_id']]['slots'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tailwind.config = {
            darkMode: "class"
        }
    </script>

    <style>
        .card {
            background: #e6ebf5;
            border-radius: 18px;
            box-shadow: 8px 8px 16px #c8ced9,
                        -8px -8px 16px #ffffff;
        }

        .dark .card {
            background: #111827;
            box-shadow: 8px 8px 16px #0b0f1a,
                        -8px -8px 16px #1a2235;
        }
    </style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex min-h-screen flex-col lg:flex-row">

    <!-- Sidebar -->
    <aside class="w-full lg:w-64 card m-4 p-6">
        <h2 class="text-2xl font-bold mb-6">🏥 Patient Panel</h2>

        <nav class="space-y-3">
            <a href="patient.php" class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="grid"></i> Dashboard
            </a>

            <a href="book_appointment.php" class="flex items-center gap-2 p-3 rounded-xl bg-blue-500 text-white">
                <i data-feather="calendar"></i> Book Appointment
            </a>

            <a href="patient_appointment.php" class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="file-text"></i> My Appointments
            </a>

            <a href="logout.php" class="flex items-center gap-2 p-3 rounded-xl text-red-500 hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="log-out"></i> Logout
            </a>
        </nav>
    </aside>


    <!-- Main Content -->
    <main class="flex-1 p-6 lg:p-10">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Book Appointment</h1>
                <p class="text-gray-500">
                    Monday - Friday | 7:00 AM - 5:00 PM
                </p>
            </div>

            <button onclick="document.documentElement.classList.toggle('dark')"
                class="card px-4 py-2 rounded-xl">
                🌙 Theme
            </button>
        </div>


        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl text-white 
                <?= $messageType === 'success' ? 'bg-green-500' : 'bg-red-500' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>


        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            <!-- Booking Form -->
            <div class="card p-6">
                <h3 class="text-xl font-bold mb-4">
                    Appointment Form
                </h3>

                <form method="POST" class="space-y-4">

                    <select name="doctor_id"
                        class="w-full p-3 rounded-xl border bg-transparent"
                        required>
                        <option value="">Select Doctor</option>

                        <?php while ($doc = $doctors->fetch_assoc()): ?>
                            <option value="<?= $doc['id'] ?>">
                                Dr. <?= htmlspecialchars($doc['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>


                    <input type="date"
                        name="date"
                        id="appointmentDate"
                        class="w-full p-3 rounded-xl border bg-transparent"
                        required>


                    <input type="time"
                        name="time"
                        min="07:00"
                        max="17:00"
                        class="w-full p-3 rounded-xl border bg-transparent"
                        required>


                    <textarea name="reason"
                        rows="4"
                        placeholder="Reason for visit"
                        class="w-full p-3 rounded-xl border bg-transparent"
                        required></textarea>


                    <button type="submit"
                        name="book"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-xl font-bold">
                        Book Appointment
                    </button>
                </form>
            </div>


            <!-- Doctor Availability -->
            <div class="card p-6">
                <h3 class="text-xl font-bold mb-4">
                    Doctor Availability
                </h3>

                <div class="space-y-4 max-h-[500px] overflow-y-auto">

                    <?php foreach ($schedules as $doctor): ?>
                        <div class="p-4 rounded-xl bg-white/20 dark:bg-black/20">

                            <h4 class="font-bold text-blue-500 mb-3">
                                Dr. <?= htmlspecialchars($doctor['name']) ?>
                            </h4>

                            <?php foreach ($doctor['slots'] as $slot): ?>
                                <div class="flex justify-between py-2 border-b border-gray-300 dark:border-gray-700">
                                    <span><?= $slot['day_of_week'] ?></span>

                                    <span>
                                        <?= date("h:i A", strtotime($slot['start_time'])) ?>
                                        -
                                        <?= date("h:i A", strtotime($slot['end_time'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

        </div>
    </main>
</div>


<script>
feather.replace();

/* Prevent weekend booking */
document.getElementById("appointmentDate").addEventListener("change", function () {
    let selectedDate = new Date(this.value);
    let day = selectedDate.getDay();

    if (day === 0 || day === 6) {
        alert("Saturday and Sunday are holidays. Please select Monday to Friday.");
        this.value = "";
    }
});
</script>

</body>
</html>