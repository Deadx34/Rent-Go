<?php
// admin_driver.php
// Admin panel for driver management

require 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle Add Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_driver'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $license = $conn->real_escape_string($_POST['license']);
    $conn->query("INSERT INTO drivers (name, phone, license) VALUES ('$name', '$phone', '$license')");
}

// Handle Delete Driver
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM drivers WHERE id = $id");
}

// Fetch Drivers
$drivers = $conn->query("SELECT * FROM drivers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Management - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-3xl mx-auto mt-10 p-8 bg-white rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Driver Management</h1>
        <form method="POST" class="mb-8 grid grid-cols-1 gap-4">
            <input type="text" name="name" placeholder="Driver Name" required class="border p-2 rounded">
            <input type="text" name="phone" placeholder="Phone Number" required class="border p-2 rounded">
            <input type="text" name="license" placeholder="License Number" required class="border p-2 rounded">
            <button type="submit" name="add_driver" class="bg-blue-600 text-white px-4 py-2 rounded">Add Driver</button>
        </form>
        <h2 class="text-xl font-semibold mb-4">Driver List</h2>
        <table class="w-full border">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Name</th>
                    <th class="p-2">Phone</th>
                    <th class="p-2">License</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $drivers->fetch_assoc()): ?>
                <tr class="border-b">
                    <td class="p-2"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($row['license']); ?></td>
                    <td class="p-2">
                        <a href="?delete=<?php echo $row['id']; ?>" class="text-red-600">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
