<?php
session_start();
require 'db_connect.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Add Vehicle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vehicle'])) {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    
    $conn->query("INSERT INTO vehicles (make, model, type, price_per_day, seats) VALUES ('$make', '$model', '$type', '$price', 4)");
    header("Location: admin.php");
}

// Return Vehicle (Simple toggle)
if (isset($_GET['return'])) {
    $id = $_GET['return'];
    $conn->query("UPDATE rentals SET status='returned' WHERE id=$id");
    $conn->query("UPDATE vehicles SET status='available' WHERE id=(SELECT vehicle_id FROM rentals WHERE id=$id)");
    header("Location: admin.php");
}

$vehicles = $conn->query("SELECT * FROM vehicles");
$rentals = $conn->query("SELECT r.*, u.name as user_name, v.make, v.model FROM rentals r JOIN users u ON r.user_id = u.id JOIN vehicles v ON r.vehicle_id = v.id ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
            <a href="index.php" class="text-blue-600 hover:underline">Back to Home</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Add Vehicle Form -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit">
                <h2 class="font-bold text-xl mb-4">Add Vehicle</h2>
                <form method="POST" class="space-y-4">
                    <input type="text" name="make" placeholder="Make" required class="w-full p-2 border rounded">
                    <input type="text" name="model" placeholder="Model" required class="w-full p-2 border rounded">
                    <select name="type" class="w-full p-2 border rounded">
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV</option>
                        <option value="truck">Truck</option>
                    </select>
                    <input type="number" name="price" placeholder="Price/Day" required class="w-full p-2 border rounded">
                    <button type="submit" name="add_vehicle" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Add Vehicle</button>
                </form>
            </div>

            <!-- Inventory List -->
            <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-sm">
                <h2 class="font-bold text-xl mb-4">Active Rentals</h2>
                <table class="w-full text-left">
                    <thead class="border-b">
                        <tr>
                            <th class="pb-2">User</th>
                            <th class="pb-2">Vehicle</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $rentals->fetch_assoc()): ?>
                        <tr class="border-b last:border-0">
                            <td class="py-3"><?php echo $row['user_name']; ?></td>
                            <td class="py-3"><?php echo $row['make'] . ' ' . $row['model']; ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-xs font-bold <?php echo $row['status']=='active' ? 'bg-green-100 text-green-800' : 'bg-gray-100'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <?php if($row['status'] == 'active'): ?>
                                    <a href="admin.php?return=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline text-sm">Mark Returned</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>