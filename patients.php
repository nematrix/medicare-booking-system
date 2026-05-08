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
   SEARCH PATIENTS
========================= */
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT * 
    FROM users 
    WHERE role='patient'
";

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);

    $sql .= "
        AND (
            name LIKE '%$searchEscaped%'
            OR email LIKE '%$searchEscaped%'
        )
    ";
}

$sql .= " ORDER BY id DESC";

$patients = $conn->query($sql);

/* =========================
   STATS
========================= */
$totalPatients = $conn->query("
    SELECT COUNT(*) total 
    FROM users 
    WHERE role='patient'
")->fetch_assoc()['total'];

$newPatientsToday = $conn->query("
    SELECT COUNT(*) total 
    FROM users
    WHERE role='patient'
    AND DATE(created_at)=CURDATE()
")->fetch_assoc()['total'];

$totalAppointments = $conn->query("
    SELECT COUNT(*) total
    FROM appointments
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patients Management</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = {
    darkMode: 'class'
}
</script>

<style>
.card{
    background:#e6ebf5;
    border-radius:20px;
    box-shadow:
        8px 8px 16px #c8ced9,
        -8px -8px 16px #ffffff;
    transition:0.3s ease;
}

.dark .card{
    background:#111827;
    box-shadow:
        8px 8px 16px #0b0f1a,
        -8px -8px 16px #1a2235;
}

.card:hover{
    transform:translateY(-4px);
}

.sidebar-link:hover{
    transform:translateX(5px);
}

.avatar{
    background:linear-gradient(135deg,#3B82F6,#1D4ED8);
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
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl">
                <i data-feather="user-check"></i>
                Doctors
            </a>

            <a href="patients.php"
               class="sidebar-link flex items-center gap-3 p-3 rounded-xl bg-blue-500 text-white">
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
                    <i data-feather="users"></i>
                    Patients Management
                </h2>

                <p class="text-gray-500 dark:text-gray-400">
                    Manage all registered patients
                </p>
            </div>

            <button onclick="toggleTheme()"
                class="card px-5 py-3 rounded-xl flex items-center gap-2">
                <i data-feather="moon"></i>
                Theme
            </button>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="card p-6">
                <i data-feather="users" class="text-blue-500 mb-3"></i>
                <p class="text-gray-500">Total Patients</p>
                <h3 class="text-3xl font-bold"><?= $totalPatients ?></h3>
            </div>

            <div class="card p-6">
                <i data-feather="user-plus" class="text-green-500 mb-3"></i>
                <p class="text-gray-500">New Today</p>
                <h3 class="text-3xl font-bold text-green-500">
                    <?= $newPatientsToday ?>
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
                    placeholder="Search patient by name or email..."
                    class="w-full p-3 rounded-xl text-black"
                >

                <button class="bg-blue-500 text-white px-6 py-3 rounded-xl flex items-center gap-2">
                    <i data-feather="search"></i>
                    Search
                </button>

            </form>

        </div>

        <!-- PATIENT TABLE -->
        <div class="card p-6 overflow-x-auto">

            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                <i data-feather="clipboard"></i>
                All Patients
            </h3>
            

            <table class="w-full text-left">

                <thead>
                    <tr class="border-b">
                        <th class="p-3">ID</th>
                        <th class="p-3">Patient</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Joined</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if($patients->num_rows > 0): ?>
                    <?php while($p = $patients->fetch_assoc()): 

                    $nameParts = explode(" ", $p['name']);
                    $initials = strtoupper(
                        $nameParts[0][0] .
                        (isset($nameParts[1]) ? $nameParts[1][0] : '')
                    );
                    ?>

                    <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-800">

                        <td class="p-3"><?= $p['id'] ?></td>

                        <td class="p-3 flex items-center gap-3">

                            <div class="avatar w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold">
                                <?= $initials ?>
                            </div>

                            <span class="font-medium">
                                <?= htmlspecialchars($p['name']) ?>
                            </span>
                        </td>

                        <td class="p-3">
                            <?= htmlspecialchars($p['email']) ?>
                        </td>

                        <td class="p-3">
                            <?= date("M d, Y", strtotime($p['created_at'])) ?>
                        </td>

                        <td class="p-3 flex gap-2">

                            <a href="edit_user.php?id=<?= $p['id'] ?>"
                               class="bg-blue-500 text-white px-3 py-2 rounded-lg">
                                <i data-feather="edit"></i>
                            </a>

                            <a href="delete_user.php?id=<?= $p['id'] ?>"
                               onclick="return confirm('Delete this patient?')"
                               class="bg-red-500 text-white px-3 py-2 rounded-lg">
                                <i data-feather="trash-2"></i>
                            </a>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="text-center p-8 text-gray-500">
                            <i data-feather="alert-circle"></i>
                            No patients found
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

function toggleTheme(){
    document.documentElement.classList.toggle("dark");

    if(document.documentElement.classList.contains("dark")){
        localStorage.setItem("theme","dark");
    } else {
        localStorage.setItem("theme","light");
    }
}

if(localStorage.getItem("theme")==="dark"){
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>