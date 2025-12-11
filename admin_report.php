<?php
// admin_report.php
// Admin reporting and analytics dashboard

require 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Total Rentals
$total_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals")->fetch_assoc()['count'];
// Total Revenue
$total_revenue = $conn->query("SELECT SUM(total_cost) as revenue FROM rentals WHERE status='active' OR status='returned'")->fetch_assoc()['revenue'] ?? 0;
// Vehicles in Fleet
$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
// Active Rentals
$active_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status='active'")->fetch_assoc()['count'];
// Most Popular Vehicle
$popular_vehicle = $conn->query("SELECT v.make, v.model, COUNT(*) as cnt FROM rentals r JOIN vehicles v ON r.vehicle_id = v.id GROUP BY r.vehicle_id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reporting & Analytics - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Reporting & Analytics</h1>
            <a href="admin.php" class="text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg transition font-medium">Back to Admin</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-xl shadow border">
                <h2 class="font-bold text-lg mb-2">Total Rentals</h2>
                <p class="text-3xl font-black text-blue-600"><?php echo $total_rentals; ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border">
                <h2 class="font-bold text-lg mb-2">Total Revenue</h2>
                <p class="text-3xl font-black text-green-600">LKR<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border">
                <h2 class="font-bold text-lg mb-2">Vehicles in Fleet</h2>
                <p class="text-3xl font-black text-purple-600"><?php echo $total_vehicles; ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border">
                <h2 class="font-bold text-lg mb-2">Active Rentals</h2>
                <p class="text-3xl font-black text-orange-600"><?php echo $active_rentals; ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border mb-8">
            <h2 class="font-bold text-lg mb-2">Most Popular Vehicle</h2>
            <?php if ($popular_vehicle): ?>
                <p class="text-xl font-bold text-gray-700"><?php echo htmlspecialchars($popular_vehicle['make'] . ' ' . $popular_vehicle['model']); ?></p>
                <p class="text-sm text-gray-500">Rented <?php echo $popular_vehicle['cnt']; ?> times</p>
            <?php else: ?>
                <p class="text-gray-500">No rentals yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
