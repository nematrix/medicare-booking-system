<?php
include "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nid = trim($_POST['national_identification'] ?? '');
    $passwordRaw = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    try {

        /* =========================
           BASIC VALIDATION
        ========================= */
        if (
            empty($name) ||
            empty($email) ||
            empty($nid) ||
            empty($passwordRaw) ||
            empty($role)
        ) {
            throw new Exception("Please fill in all required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if (strlen($passwordRaw) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }

        /* =========================
           CHECK DUPLICATES
        ========================= */
        $check = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE email = ? 
            OR national_identification = ?
        ");

        $check->bind_param("ss", $email, $nid);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Email or National ID already exists.");
        }

        $conn->begin_transaction();

        /* =========================
           INSERT USER
        ========================= */
        $hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (
                name,
                email,
                national_identification,
                password,
                role
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssss",
            $name,
            $email,
            $nid,
            $hashedPassword,
            $role
        );

        $stmt->execute();

        $user_id = $conn->insert_id;

        /* =========================
           PATIENT REGISTRATION
        ========================= */
        if ($role === "patient") {

            $phone = trim($_POST['phone'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $dob = trim($_POST['date_of_birth'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $bloodGroup = trim($_POST['blood_group'] ?? '');

            $stmt = $conn->prepare("
                INSERT INTO patients (
                    user_id,
                    phone,
                    gender,
                    date_of_birth,
                    address,
                    blood_group
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssss",
                $user_id,
                $phone,
                $gender,
                $dob,
                $address,
                $bloodGroup
            );

            $stmt->execute();
        }

        /* =========================
           DOCTOR REGISTRATION
        ========================= */
        elseif ($role === "doctor") {

            $specialization = trim($_POST['specialization'] ?? '');
            $licenseNumber = trim($_POST['license_number'] ?? '');
            $doctorPhone = trim($_POST['doctor_phone'] ?? '');
            $consultationFee = floatval($_POST['consultation_fee'] ?? 0);

            if (empty($licenseNumber)) {
                throw new Exception("Doctor license number is required.");
            }

            $stmt = $conn->prepare("
                INSERT INTO doctors (
                    user_id,
                    specialization,
                    license_number,
                    phone,
                    consultation_fee
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssd",
                $user_id,
                $specialization,
                $licenseNumber,
                $doctorPhone,
                $consultationFee
            );

            $stmt->execute();
        }

        $conn->commit();
        $success = "Registration successful. You can now login.";

    } catch (Exception $e) {

        if ($conn->errno === 0) {
            $conn->rollback();
        }

        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Registration</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        body{
            background:#e6ebf5;
        }

        .card{
            background:#e6ebf5;
            box-shadow:
                8px 8px 16px #c8ced9,
                -8px -8px 16px #ffffff;
            border-radius:20px;
        }

        .input{
            background:#e6ebf5;
            box-shadow:
                inset 4px 4px 8px #c8ced9,
                inset -4px -4px 8px #ffffff;
        }

        .input:focus{
            outline:none;
            box-shadow:0 0 0 3px rgba(59,130,246,0.2);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">

<div class="card w-full max-w-4xl p-8">

    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-feather="user-plus"></i>
        </div>

        <h1 class="text-2xl font-bold">
            Hospital Registration
        </h1>

        <p class="text-gray-500">
            Create your account
        </p>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-600 p-3 rounded-xl mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 text-green-600 p-3 rounded-xl mb-4">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">

        <!-- Basic Details -->
        <div class="grid md:grid-cols-2 gap-4">

            <input
                type="text"
                name="name"
                placeholder="Full Name"
                class="input p-3 rounded-xl w-full"
                required
            >

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                class="input p-3 rounded-xl w-full"
                required
            >

            <input
                type="text"
                name="national_identification"
                placeholder="National ID"
                class="input p-3 rounded-xl w-full"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                class="input p-3 rounded-xl w-full"
                required
            >
        </div>

        <!-- Role -->
        <select
            name="role"
            id="role"
            class="input w-full p-3 rounded-xl"
            required
        >
            <option value="">Select Role</option>
            <option value="patient">Patient</option>
            <option value="doctor">Doctor</option>
            <option value="admin">Admin</option>
        </select>

        <!-- Patient Fields -->
        <div id="patientFields" class="hidden grid md:grid-cols-2 gap-4">

            <input
                type="text"
                name="phone"
                placeholder="Phone Number"
                class="input p-3 rounded-xl"
            >

            <select name="gender" class="input p-3 rounded-xl">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>

            <input
                type="date"
                name="date_of_birth"
                class="input p-3 rounded-xl"
            >

            <input
                type="text"
                name="blood_group"
                placeholder="Blood Group"
                class="input p-3 rounded-xl"
            >

            <textarea
                name="address"
                placeholder="Address"
                class="input p-3 rounded-xl md:col-span-2"
            ></textarea>
        </div>

        <!-- Doctor Fields -->
        <div id="doctorFields" class="hidden grid md:grid-cols-2 gap-4">

            <input
                type="text"
                name="specialization"
                placeholder="Specialization"
                class="input p-3 rounded-xl"
            >

            <input
                type="text"
                name="license_number"
                placeholder="License Number"
                class="input p-3 rounded-xl"
            >

            <input
                type="text"
                name="doctor_phone"
                placeholder="Phone Number"
                class="input p-3 rounded-xl"
            >

            <input
                type="number"
                step="0.01"
                name="consultation_fee"
                placeholder="Consultation Fee"
                class="input p-3 rounded-xl"
            >
        </div>

        <!-- Submit -->
        <button
            type="submit"
            name="register"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl flex justify-center items-center gap-2"
        >
            <i data-feather="check-circle"></i>
            Register Account
        </button>

        <a
            href="login.php"
            class="block text-center text-blue-600"
        >
            Already have an account? Login
        </a>

    </form>
</div>

<script>
feather.replace();

const roleSelect = document.getElementById("role");
const patientFields = document.getElementById("patientFields");
const doctorFields = document.getElementById("doctorFields");

roleSelect.addEventListener("change", function () {

    patientFields.classList.add("hidden");
    doctorFields.classList.add("hidden");

    if (this.value === "patient") {
        patientFields.classList.remove("hidden");
    }

    if (this.value === "doctor") {
        doctorFields.classList.remove("hidden");
    }
});
</script>

</body>
</html>