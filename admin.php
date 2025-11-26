<?php
// Add these lines inside the PHP tag
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Add Vehicle Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vehicle'])) {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $vehicle_number = $_POST['vehicle_number'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    
    // Handle Image Upload
    $image_url = ''; // Default empty
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        // Create dir if not exists
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_extension, $allowed_types)) {
            // Generate unique name to prevent overwriting
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }
    }

    // Use Prepared Statement for security
    $stmt = $conn->prepare("INSERT INTO vehicles (make, model, vehicle_number, type, price_per_day, seats, image_url) VALUES (?, ?, ?, ?, ?, 4, ?)");
    $stmt->bind_param("ssssds", $make, $model, $vehicle_number, $type, $price, $image_url);
    
    if ($stmt->execute()) {
        header("Location: admin.php?msg=added");
        exit();
    } else {
        $error = "Error adding vehicle: " . $conn->error;
    }
    $stmt->close();
}

// Return Vehicle (Simple toggle)
if (isset($_GET['return'])) {
    $id = intval($_GET['return']); // Sanitize
    $conn->query("UPDATE rentals SET status='returned' WHERE id=$id");
    $conn->query("UPDATE vehicles SET status='available' WHERE id=(SELECT vehicle_id FROM rentals WHERE id=$id)");
    header("Location: admin.php");
    exit();
}

$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
$rentals = $conn->query("SELECT r.*, u.name as user_name, v.make, v.model, v.vehicle_number FROM rentals r JOIN users u ON r.user_id = u.id JOIN vehicles v ON r.vehicle_id = v.id ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                    <i data-lucide="layout-dashboard"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            </div>
            <a href="index.php" class="flex items-center gap-2 text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg transition font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
            </a>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Vehicle Form -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit border border-gray-100">
                <h2 class="font-bold text-xl mb-6 flex items-center gap-2">
                    <i data-lucide="plus-circle" class="text-blue-600"></i> Add New Vehicle
                </h2>
                
                <!-- Note the enctype for file upload -->
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Make</label>
                            <input type="text" name="make" placeholder="Toyota" required class="w-full p-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Model</label>
                            <input type="text" name="model" placeholder="Camry" required class="w-full p-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vehicle Number</label>
                        <input type="text" name="vehicle_number" placeholder="ABC-1234" required class="w-full p-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label>
                            <select name="type" class="w-full p-2 border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="truck">Truck</option>
                                <option value="motorcycle">Motorcycle</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Price/Day</label>
                            <input type="number" name="price" placeholder="50.00" step="0.01" required class="w-full p-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vehicle Image</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition text-center cursor-pointer relative">
                            <input type="file" name="image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <i data-lucide="image" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-500">Click to upload image</p>
                        </div>
                    </div>

                    <button type="submit" name="add_vehicle" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                        Add to Fleet
                    </button>
                </form>
            </div>

            <!-- Data Lists -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Active Rentals -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-bold text-xl flex items-center gap-2">
                            <i data-lucide="clock" class="text-blue-600"></i> Recent Rentals
                        </h2>
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                            <?php echo $rentals->num_rows; ?> Total
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                                <tr>
                                    <th class="pb-3 pl-2">User</th>
                                    <th class="pb-3">Vehicle Info</th>
                                    <th class="pb-3">Status</th>
                                    <th class="pb-3 text-right pr-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php while($row = $rentals->fetch_assoc()): ?>
                                <tr class="group hover:bg-gray-50 transition">
                                    <td class="py-3 pl-2 font-medium"><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td class="py-3">
                                        <div class="font-bold text-gray-900"><?php echo htmlspecialchars($row['make'] . ' ' . $row['model']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['vehicle_number']); ?></div>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $row['status']=='active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-right pr-2">
                                        <?php if($row['status'] == 'active'): ?>
                                            <a href="admin.php?return=<?php echo $row['id']; ?>" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded border border-blue-200 transition">Mark Returned</a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vehicle Inventory Preview -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="font-bold text-xl mb-6 flex items-center gap-2">
                        <i data-lucide="car" class="text-blue-600"></i> Fleet Inventory
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php while($v = $vehicles->fetch_assoc()): ?>
                            <div class="flex items-center p-3 border rounded-lg gap-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-md flex-shrink-0 overflow-hidden">
                                    <?php if(!empty($v['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($v['image_url']); ?>" alt="Car" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i data-lucide="image" class="w-6 h-6"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($v['vehicle_number']); ?></p>
                                    <p class="text-xs font-bold text-blue-600 mt-1">$<?php echo $v['price_per_day']; ?>/day</p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>