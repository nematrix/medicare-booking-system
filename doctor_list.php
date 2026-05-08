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
   DELETE DOCTOR
========================= */
if (isset($_GET['delete'])) {
    $doctorId = intval($_GET['delete']);

    $stmt = $conn->prepare("
        DELETE FROM users 
        WHERE id = ? AND role = 'doctor'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();

    header("Location: doctor_list.php?success=Doctor deleted successfully");
    exit();
}

/* =========================
   SEARCH INPUT
========================= */
$search = trim($_GET['search'] ?? '');
$searchTerm = "%{$search}%";

/* =========================
   DOCTOR LIST (JOINED QUERY)
========================= */
$sql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.status,
        u.created_at,
        d.phone,
        d.specialization
    FROM users u
    LEFT JOIN doctors d ON u.id = d.user_id
    WHERE u.role = 'doctor'
";

if (!empty($search)) {
    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR d.phone LIKE ?
            OR d.specialization LIKE ?
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$doctors = $stmt->get_result();

/* =========================
   STATS (SAFE INITIALIZATION)
========================= */
$totalDoctors = 0;
$activeDoctors = 0;
$inactiveDoctors = 0;
$totalAppointments = 0;

/* TOTAL DOCTORS */
$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'doctor'
");
$totalDoctors = $res->fetch_assoc()['total'] ?? 0;

/* ACTIVE DOCTORS */
$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'doctor' 
    AND status = 'active'
");
$activeDoctors = $res->fetch_assoc()['total'] ?? 0;

/* INACTIVE DOCTORS */
$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE role = 'doctor' 
    AND status = 'inactive'
");
$inactiveDoctors = $res->fetch_assoc()['total'] ?? 0;

/* APPOINTMENTS */
$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM appointments
");
$totalAppointments = $res->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctors Management</title>
    <link rel="shortcut icon" href="favicon_io/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <style>
        .card {
            background: #e6ebf5;
            border-radius: 20px;
            box-shadow: 8px 8px 16px #c8ced9,
                        -8px -8px 16px #ffffff;
            transition: 0.3s ease;
        }

        .dark .card {
            background: #111827;
            box-shadow: 8px 8px 16px #0b0f1a,
                        -8px -8px 16px #1a2235;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .sidebar-link:hover {
            transform: translateX(5px);
        }
    </style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 card m-4 p-6">

        <h1 class="text-2xl font-bold mb-8 flex items-center gap-2">
            <i data-feather="activity"></i>
            MediCare
        </h1>

        <nav class="space-y-3">

            <a href="admin.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="grid"></i>
                Dashboard
            </a>

            <a href="doctors.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl bg-blue-500 text-white">
                <i data-feather="user-check"></i>
                Doctors
            </a>

            <a href="patients.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="users"></i>
                Patients
            </a>

            <a href="appointments.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="calendar"></i>
                Appointments
            </a>

            <a href="analytics.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="bar-chart-2"></i>
                Analytics
            </a>

            <a href="reports.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="file-text"></i>
                Reports
            </a>

        </nav>

        <a href="logout.php"
           class="mt-8 flex items-center justify-center gap-2 bg-red-500 text-white p-3 rounded-xl">
            <i data-feather="log-out"></i>
            Logout
        </a>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-8">

            <div>
                <h2 class="text-3xl font-bold flex items-center gap-2">
                    <i data-feather="briefcase"></i>
                    Doctors Management
                </h2>

                <p class="text-gray-500 dark:text-gray-400">
                    Manage all hospital doctors
                </p>
            </div>

            <button onclick="toggleTheme()"
                    class="card px-5 py-3 flex items-center gap-2 rounded-xl">
                <i data-feather="moon"></i>
                Theme
            </button>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="card p-6">
                <i data-feather="users" class="text-blue-500 mb-3"></i>
                <p class="text-gray-500">Total Doctors</p>
                <h3 class="text-3xl font-bold"><?= $totalDoctors ?></h3>
            </div>

            <div class="card p-6">
                <i data-feather="check-circle" class="text-green-500 mb-3"></i>
                <p class="text-gray-500">Active Doctors</p>
                <h3 class="text-3xl font-bold text-green-500">
                    <?= $activeDoctors ?>
                </h3>
            </div>

            <div class="card p-6">
                <i data-feather="x-circle" class="text-red-500 mb-3"></i>
                <p class="text-gray-500">Inactive Doctors</p>
                <h3 class="text-3xl font-bold text-red-500">
                    <?= $inactiveDoctors ?>
                </h3>
            </div>

            <div class="card p-6">
                <i data-feather="calendar" class="text-yellow-500 mb-3"></i>
                <p class="text-gray-500">Appointments</p>
                <h3 class="text-3xl font-bold text-yellow-500">
                    <?= $totalAppointments ?>
                </h3>
            </div>

        </div>

        <!-- SEARCH -->
        <div class="card p-6 mb-8">

            <form method="GET" class="flex flex-col md:flex-row gap-4">

                <input 
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search doctor name, email or specialization..."
                    class="w-full p-3 rounded-xl text-black"
                >

                <button class="bg-blue-500 text-white px-6 py-3 rounded-xl flex items-center gap-2">
                    <i data-feather="search"></i>
                    Search
                </button>

            </form>

        </div>

        <!-- SUCCESS MESSAGE -->
        <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-500 text-white p-4 rounded-xl mb-8 flex items-center gap-2">
                <i data-feather="check-circle"></i>
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <!-- DOCTORS TABLE -->
        <div class="card p-6 overflow-x-auto">

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i data-feather="clipboard"></i>
                    All Doctors
                </h3>

                <a href="add_doctor.php"
                   class="bg-blue-500 text-white px-4 py-2 rounded-xl flex items-center gap-2">
                    <i data-feather="plus"></i>
                    Add Doctor
                </a>
            </div>

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b">
                        <th class="p-3">ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Specialization</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Joined</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if($doctors->num_rows > 0): ?>
                    <?php while($doctor = $doctors->fetch_assoc()): ?>

                        <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-800">

                            <td class="p-3"><?= $doctor['id'] ?></td>

                            <td class="p-3 font-semibold">
                                <?= htmlspecialchars($doctor['name']) ?>
                            </td>

                            <td class="p-3">
                                <?= htmlspecialchars($doctor['email']) ?>
                            </td>

                            <td class="p-3">
                                <?= $doctor['phone'] ?? 'N/A' ?>
                            </td>

                            <td class="p-3">
                                <?= $doctor['specialization'] ?? 'General' ?>
                            </td>

                            <td class="p-3">
                                <?php if($doctor['status'] == 'active'): ?>
                                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3">
                                <?= date("M d, Y", strtotime($doctor['created_at'])) ?>
                            </td>

                            <td class="p-3 flex gap-2">

                                <a href="edit_user.php?id=<?= $doctor['id'] ?>"
                                   class="bg-blue-500 text-white px-3 py-2 rounded-lg flex items-center gap-1">
                                    <i data-feather="edit"></i>
                                </a>

                                <a href="?delete=<?= $doctor['id'] ?>"
                                   onclick="return confirm('Delete this doctor?')"
                                   class="bg-red-500 text-white px-3 py-2 rounded-lg flex items-center gap-1">
                                    <i data-feather="trash-2"></i>
                                </a>

                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="text-center p-8 text-gray-500">
                            <i data-feather="alert-circle"></i>
                            No doctors found
                        </td>
                    </tr>

                <?php endif; ?>
                </tbody>

            </table>

        </div>

    </main>
</div>

<script>
feather.replace();

/* Persistent Theme */
function toggleTheme() {
    document.documentElement.classList.toggle("dark");

    if(document.documentElement.classList.contains("dark")){
        localStorage.setItem("theme", "dark");
    } else {
        localStorage.setItem("theme", "light");
    }
}

if(localStorage.getItem("theme") === "dark"){
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>