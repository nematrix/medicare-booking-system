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
   FILTERS
========================= */
$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';

/* =========================
   PAGINATION
========================= */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

/* =========================
   QUERY BUILD
========================= */
$sql = "SELECT id, name, email, role FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($role)) {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

/* =========================
   TOTAL USERS
========================= */
$total = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users Management</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
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
    transform: translateY(-4px);
}

.table-row:hover {
    background: rgba(59,130,246,0.08);
    transform: translateX(4px);
}
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white p-10">

<div class="max-w-7xl mx-auto">

<!-- HEADER -->
<div class="flex justify-between items-center mb-8">

    <div>
        <h2 class="text-3xl font-bold flex items-center gap-2">
            <i data-feather="users"></i>
            Users Management
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            Manage system users and roles
        </p>
    </div>

    <a href="admin.php"
       class="card bg-blue-500 text-white px-4 py-2 rounded-xl flex items-center gap-2">
        <i data-feather="arrow-left"></i>
        Dashboard
    </a>

</div>

<!-- FILTERS -->
<div class="card p-6 mb-8">

<form method="GET" class="flex flex-col md:flex-row gap-4">

    <input type="text"
           name="search"
           value="<?= htmlspecialchars($search) ?>"
           placeholder="Search user by name or email..."
           class="p-3 rounded-xl w-full text-black">

    <select name="role"
            class="p-3 rounded-xl text-black">

        <option value="">All Roles</option>
        <option value="admin" <?= $role=='admin'?'selected':'' ?>>Admin</option>
        <option value="doctor" <?= $role=='doctor'?'selected':'' ?>>Doctor</option>
        <option value="patient" <?= $role=='patient'?'selected':'' ?>>Patient</option>

    </select>

    <button class="bg-blue-500 text-white px-6 py-3 rounded-xl flex items-center gap-2">
        <i data-feather="filter"></i>
        Filter
    </button>

</form>

</div>

<!-- TABLE -->
<div class="card p-6 overflow-x-auto">

<table class="w-full text-sm">

<thead class="text-gray-500 dark:text-gray-400">
<tr>
<th class="p-3 text-left">User</th>
<th class="p-3 text-left">Email</th>
<th class="p-3 text-left">Role</th>
<th class="p-3 text-left">Actions</th>
</tr>
</thead>

<tbody>

<?php if ($users->num_rows > 0): ?>

<?php while($u = $users->fetch_assoc()): 

$nameParts = explode(" ", $u['name']);
$initials = strtoupper(
    $nameParts[0][0] .
    (isset($nameParts[1]) ? $nameParts[1][0] : '')
);

$roleColor = match($u['role']) {
    'admin'  => 'bg-purple-500',
    'doctor' => 'bg-blue-500',
    default  => 'bg-green-500'
};
?>

<tr class="table-row border-b dark:border-gray-700">

<td class="p-3 flex items-center gap-3">

    <div class="w-10 h-10 flex items-center justify-center text-white font-bold rounded-xl <?= $roleColor ?>">
        <?= $initials ?>
    </div>

    <div>
        <div class="font-medium"><?= htmlspecialchars($u['name']) ?></div>
        <div class="text-xs text-gray-500">ID: <?= $u['id'] ?></div>
    </div>

</td>

<td class="p-3 text-gray-600 dark:text-gray-300">
    <?= htmlspecialchars($u['email']) ?>
</td>

<td class="p-3">
    <span class="px-3 py-1 text-xs rounded-full text-white <?= $roleColor ?>">
        <?= ucfirst($u['role']) ?>
    </span>
</td>

<td class="p-3 flex gap-3">

    <a href="edit_user.php?id=<?= $u['id'] ?>"
       class="text-blue-500 hover:scale-110 transition">
        <i data-feather="edit-2"></i>
    </a>

    <a href="delete_user.php?id=<?= $u['id'] ?>"
       onclick="return confirm('Delete this user?')"
       class="text-red-500 hover:scale-110 transition">
        <i data-feather="trash-2"></i>
    </a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="4" class="text-center py-10 text-gray-500">
No users found
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<!-- PAGINATION -->
<div class="flex justify-center mt-6 gap-2">

<?php for ($i = 1; $i <= $totalPages; $i++): ?>

<a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>"
   class="px-4 py-2 rounded-xl <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-800' ?>">
    <?= $i ?>
</a>

<?php endfor; ?>

</div>

</div>

<script>
feather.replace();

/* Dark mode sync */
if (localStorage.getItem("theme") === "dark") {
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>