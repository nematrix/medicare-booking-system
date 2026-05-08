<?php
session_start();
require "db.php";

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   INIT
========================= */
$message = "";
$messageType = "";

/* =========================
   ADD DOCTOR
========================= */
if (isset($_POST['save'])) {

    // SAFE INPUTS (FIX ALL WARNINGS)
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $passwordRaw = $_POST['password'] ?? '123456';

    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    $fee = floatval($_POST['consultation_fee'] ?? 0);

    if ($name == '' || $email == '' || $license == '') {
        $message = "Name, Email and License Number are required.";
        $messageType = "error";
    } else {

        try {
            $conn->begin_transaction();

            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

            /* =========================
               USER TABLE
            ========================= */
            $u = $conn->prepare("
                INSERT INTO users (name, email, national_identification, password, role)
                VALUES (?, ?, ?, ?, 'doctor')
            ");
            $u->bind_param("ssss", $name, $email, $nid, $password);
            $u->execute();

            $user_id = $u->insert_id;

            /* =========================
               DOCTOR TABLE
            ========================= */
            $d = $conn->prepare("
                INSERT INTO doctors (user_id, specialization, license_number, phone, consultation_fee)
                VALUES (?, ?, ?, ?, ?)
            ");
            $d->bind_param("isssd", $user_id, $specialization, $license, $phone, $fee);
            $d->execute();

            $conn->commit();

            $message = "Doctor added successfully.";
            $messageType = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Doctor</title>

<script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = { darkMode: 'class' };
</script>

<style>
.card{
    background:#e6ebf5;
    border-radius:20px;
    box-shadow:8px 8px 16px #c8ced9,-8px -8px 16px #ffffff;
}
.dark .card{
    background:#111827;
    box-shadow:8px 8px 16px #0b0f1a,-8px -8px 16px #1a2235;
}
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="max-w-3xl mx-auto p-6">

<!-- HEADER -->
<div class="card p-5 flex justify-between items-center">
    <h1 class="text-xl font-bold">➕ Add Doctor</h1>

    <a href="doctor_list.php" class="px-4 py-2 bg-blue-500 text-white rounded-xl">
        Back
    </a>
</div>

<!-- MESSAGE -->
<?php if($message): ?>
<div class="card p-4 mt-4 <?= $messageType=='success'?'text-green-500':'text-red-500' ?>">
    <?= $message ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="card p-6 mt-6">

<form method="POST" class="grid gap-3">

<input name="name" placeholder="Full Name" class="p-3 rounded border">

<input name="email" placeholder="Email" class="p-3 rounded border">

<input name="nid" placeholder="National ID" class="p-3 rounded border">

<input name="phone" placeholder="Phone" class="p-3 rounded border">

<input name="specialization" placeholder="Specialization" class="p-3 rounded border">

<input name="license_number" placeholder="License Number" class="p-3 rounded border">

<input type="number" step="0.01" name="consultation_fee" placeholder="Fee" class="p-3 rounded border">

<input type="password" name="password" placeholder="Default Password" value="123456"
class="p-3 rounded border">

<button name="save" class="bg-blue-500 text-white p-3 rounded-xl">
    Save Doctor
</button>

</form>

</div>

</div>

</body>
</html>