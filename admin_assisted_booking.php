<?php
session_start();
require "db.php";
require_once "notification_functions.php";

/* AUTH CHECK */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";

/* DOCTORS */
$doctors = $conn->query("
    SELECT id, name 
    FROM users 
    WHERE role='doctor'
    ORDER BY name ASC
");

/* AVAILABILITY */
$availability = $conn->query("
    SELECT ds.*, u.name AS doctor_name
    FROM doctor_schedules ds
    JOIN users u ON u.id = ds.doctor_id
    WHERE ds.is_available = 1
    ORDER BY u.name, ds.day_of_week
");

$schedules = [];

while ($row = $availability->fetch_assoc()) {
    $schedules[$row['doctor_id']]['name'] = $row['doctor_name'];
    $schedules[$row['doctor_id']]['slots'][] = $row;
}

/* BOOK */
if (isset($_POST['book'])) {

    $patient_name = trim($_POST['patient_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $nid          = trim($_POST['nid'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $gender       = $_POST['gender'] ?? '';
    $dob          = $_POST['dob'] ?? '';
    $address      = trim($_POST['address'] ?? '');

    $doctor_id    = intval($_POST['doctor_id'] ?? 0);
    $date         = $_POST['date'] ?? '';
    $time         = $_POST['time'] ?? '';
    $reason       = trim($_POST['reason'] ?? '');

    $appointment_date = $date . " " . $time . ":00";

    if (!$patient_name || !$email || !$doctor_id || !$date || !$time || !$reason) {
        $message = "Fill all required fields";
        $messageType = "error";
    } else {

        try {
            $conn->begin_transaction();

            $password = password_hash("123456", PASSWORD_DEFAULT);

            $u = $conn->prepare("
                INSERT INTO users (name,email,national_identification,password,role)
                VALUES (?,?,?,?, 'patient')
            ");
            $u->bind_param("ssss", $patient_name, $email, $nid, $password);
            $u->execute();

            $patient_id = $u->insert_id;

            $p = $conn->prepare("
                INSERT INTO patients (user_id,phone,gender,date_of_birth,address)
                VALUES (?,?,?,?,?)
            ");
            $p->bind_param("issss", $patient_id, $phone, $gender, $dob, $address);
            $p->execute();

            $a = $conn->prepare("
                INSERT INTO appointments (patient_id,doctor_id,appointment_date,reason,status)
                VALUES (?,?,?,?, 'pending')
            ");
            $a->bind_param("iiss", $patient_id, $doctor_id, $appointment_date, $reason);
            $a->execute();

            $conn->commit();

            $message = "Appointment created successfully";
            $messageType = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Booking</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
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

.dark body {
    background: #0B1220;
}

.input {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: white;
    outline: none;
}

.dark .input {
    background: #1f2937;
    color: white;
}
</style>
</head>

<body class="bg-[#e6ebf5] text-gray-800 dark:text-white">

<div class="max-w-6xl mx-auto p-6">

<!-- HEADER -->
<div class="card p-5 mb-6 flex justify-between items-center">

    <h1 class="text-xl font-bold text-blue-700">
        Admin Appointment Booking
    </h1>

    <div class="flex gap-2">

        <!-- DASHBOARD BUTTON -->
        <a href="admin.php"
           class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold">
            Dashboard
        </a>

        <!-- THEME TOGGLE -->
        <button onclick="document.documentElement.classList.toggle('dark')"
                class="px-4 py-2 rounded-xl bg-gray-700 text-white">
            🌙 Theme
        </button>

    </div>

</div>

<!-- MESSAGE -->
<?php if($message): ?>
<div class="card p-4 mb-4 <?= $messageType=='success'?'text-green-600':'text-red-600' ?>">
    <?= $message ?>
</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-6">

<!-- FORM -->
<div class="card p-6">

<h2 class="font-bold mb-4 text-blue-700">Book Appointment</h2>

<form method="POST" class="grid gap-3">

<input name="patient_name" placeholder="Patient Name" class="input">
<input name="email" placeholder="Email" class="input">
<input name="nid" placeholder="National ID" class="input">
<input name="phone" placeholder="Phone" class="input">

<select name="gender" class="input">
    <option>Male</option>
    <option>Female</option>
</select>

<input type="date" name="dob" class="input">
<textarea name="address" placeholder="Address" class="input"></textarea>

<select name="doctor_id" class="input" required>
    <option value="">Select Doctor</option>
    <?php while($d = $doctors->fetch_assoc()): ?>
        <option value="<?= $d['id'] ?>">
            <?= htmlspecialchars($d['name']) ?>
        </option>
    <?php endwhile; ?>
</select>

<input type="date" name="date" class="input">
<input type="time" name="time" class="input">

<textarea name="reason" placeholder="Reason" class="input"></textarea>

<button name="book"
        class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-xl font-bold">
    Book Appointment
</button>

</form>

</div>

<!-- AVAILABILITY -->
<div class="card p-6">

<h2 class="font-bold mb-4 text-blue-700">Doctor Availability</h2>

<?php foreach ($schedules as $doc): ?>
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl">

        <h3 class="font-bold text-blue-600 mb-2">
            Dr. <?= htmlspecialchars($doc['name']) ?>
        </h3>

        <?php foreach ($doc['slots'] as $slot): ?>
            <div class="flex justify-between py-1 text-sm border-b border-gray-200 dark:border-gray-700">
                <span><?= $slot['day_of_week'] ?></span>
                <span class="text-blue-600">
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

<script>
feather.replace();
</script>

</body>
</html>