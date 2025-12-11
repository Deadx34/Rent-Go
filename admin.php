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
    $image_url = '';
    $upload_error = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            } else {
                $upload_error = 'Image upload failed. Please try again.';
            }
        } else {
            $upload_error = 'Invalid image type. Allowed: jpg, jpeg, png, webp.';
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != 4) {
        $upload_error = 'Image upload error. Please select a valid file.';
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

// Delete Vehicle
if (isset($_GET['delete_vehicle'])) {
    $id = intval($_GET['delete_vehicle']);
    $conn->query("DELETE FROM vehicles WHERE id=$id");
    $_SESSION['success_message'] = "Vehicle deleted successfully.";
    header("Location: admin.php");
    exit();
}

// Add Driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_driver'])) {
    $name = $_POST['driver_name'];
    $phone = $_POST['driver_phone'];
    $license = $_POST['driver_license'];
    $rate_per_day = floatval($_POST['driver_rate']);
    $experience_years = intval($_POST['driver_experience']);
    
    // Handle Photo Upload
    $photo_url = '';
    if (isset($_FILES['driver_photo']) && $_FILES['driver_photo']['error'] == 0) {
        $target_dir = "uploads/drivers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["driver_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["driver_photo"]["tmp_name"], $target_file)) {
                $photo_url = $target_file;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO drivers (name, phone, license, rate_per_day, experience_years, photo_url, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
    $stmt->bind_param("ssssis", $name, $phone, $license, $rate_per_day, $experience_years, $photo_url);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Driver added successfully.";
        header("Location: admin.php");
        exit();
    } else {
        $error = "Error adding driver: " . $conn->error;
    }
    $stmt->close();
}

// Edit Driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_driver'])) {
    $id = intval($_POST['driver_id']);
    $name = $_POST['driver_name'];
    $phone = $_POST['driver_phone'];
    $license = $_POST['driver_license'];
    $rate_per_day = floatval($_POST['driver_rate']);
    $experience_years = intval($_POST['driver_experience']);
    
    // Handle Photo Upload
    $photo_url = $_POST['existing_photo'];
    if (isset($_FILES['driver_photo']) && $_FILES['driver_photo']['error'] == 0) {
        $target_dir = "uploads/drivers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["driver_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["driver_photo"]["tmp_name"], $target_file)) {
                $photo_url = $target_file;
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE drivers SET name=?, phone=?, license=?, rate_per_day=?, experience_years=?, photo_url=? WHERE id=?");
    $stmt->bind_param("ssssdsi", $name, $phone, $license, $rate_per_day, $experience_years, $photo_url, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Driver updated successfully.";
        header("Location: admin.php");
        exit();
    } else {
        $error = "Error updating driver: " . $conn->error;
    }
    $stmt->close();
}

// Delete Driver
if (isset($_GET['delete_driver'])) {
    $id = intval($_GET['delete_driver']);
    $conn->query("DELETE FROM drivers WHERE id=$id");
    $_SESSION['success_message'] = "Driver deleted successfully.";
    header("Location: admin.php");
    exit();
}

// Edit Vehicle Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_vehicle'])) {
    $id = intval($_POST['vehicle_id']);
    $make = $_POST['make'];
    $model = $_POST['model'];
    $vehicle_number = $_POST['vehicle_number'];
    $type = $_POST['type'];
    $price = $_POST['price'];

    // Handle Image Upload for edit
    $image_url = $_POST['existing_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE vehicles SET make=?, model=?, vehicle_number=?, type=?, price_per_day=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssssdsi", $make, $model, $vehicle_number, $type, $price, $image_url, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Vehicle updated successfully.";
        header("Location: admin.php");
        exit();
    } else {
        $error = "Error updating vehicle: " . $conn->error;
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
$drivers = $conn->query("SELECT * FROM drivers ORDER BY id DESC");
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
            <div class="flex gap-2">
                <a href="index.php" class="flex items-center gap-2 text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg transition font-medium">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                </a>
                <a href="verify_booking.php" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-orange-700 transition">
                    <i data-lucide="search-check" class="inline w-5 h-5 mr-2"></i> Verify Booking
                </a>
                <button onclick="toggleModal('driverModal')" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-green-700 transition">
                    <i data-lucide="user-check" class="inline w-5 h-5 mr-2"></i> Manage Drivers
                </button>
                <button onclick="toggleModal('customerModal')" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-indigo-700 transition">
                    <i data-lucide="user-plus" class="inline w-5 h-5 mr-2"></i> Register Customer
                </button>
            </div>
        </div>

        <?php 
        if(isset($_SESSION['success_message'])): 
            $success = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        endif;
        ?>
        <?php if(isset($success)): ?>
            <div id="success-notification" class="bg-green-100 text-green-700 p-4 rounded mb-4 relative">
                <button onclick="document.getElementById('success-notification').remove()" class="absolute top-2 right-2 text-green-700 hover:text-green-900">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <span class="block pr-8"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div id="error-notification" class="bg-red-100 text-red-700 p-4 rounded mb-4 relative">
                <button onclick="document.getElementById('error-notification').remove()" class="absolute top-2 right-2 text-red-700 hover:text-red-900">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <span class="block pr-8"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <?php if(isset($upload_error) && $upload_error): ?>
            <div id="upload-error-notification" class="bg-red-100 text-red-700 p-4 rounded mb-4 relative">
                <button onclick="document.getElementById('upload-error-notification').remove()" class="absolute top-2 right-2 text-red-700 hover:text-red-900">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <span class="block pr-8"><?php echo $upload_error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Reporting & Analytics Summary -->
        <?php
        // Reporting & Analytics quick stats
        $total_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals")->fetch_assoc()['count'];
        $total_revenue = $conn->query("SELECT SUM(total_cost) as revenue FROM rentals WHERE status='active' OR status='returned'")->fetch_assoc()['revenue'] ?? 0;
        $total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
        $active_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status='active'")->fetch_assoc()['count'];
        $popular_vehicle = $conn->query("SELECT v.make, v.model, COUNT(*) as cnt FROM rentals r JOIN vehicles v ON r.vehicle_id = v.id GROUP BY r.vehicle_id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
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
        <?php if ($popular_vehicle): ?>
        <div class="bg-white p-6 rounded-xl shadow border mb-8">
            <h2 class="font-bold text-lg mb-2">Most Popular Vehicle</h2>
            <p class="text-xl font-bold text-gray-700"><?php echo htmlspecialchars($popular_vehicle['make'] . ' ' . $popular_vehicle['model']); ?></p>
            <p class="text-sm text-gray-500">Rented <?php echo $popular_vehicle['cnt']; ?> times</p>
        </div>
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
                        Add Vehicle
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
                            <div class="flex items-center p-3 border rounded-lg gap-4 relative">
                                <div class="w-16 h-16 bg-gray-100 rounded-md flex-shrink-0 overflow-hidden">
                                    <?php if(!empty($v['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($v['image_url']); ?>" alt="Car" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i data-lucide="image" class="w-6 h-6"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($v['vehicle_number'] ?? ''); ?></p>
                                    <p class="text-xs font-bold text-blue-600 mt-1">LKR <?php echo number_format($v['price_per_day'], 2); ?>/day</p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button onclick="openEditVehicleModal(<?php echo htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8'); ?>)" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-1 rounded border border-blue-200 transition">
                                        <i data-lucide="edit" class="w-3 h-3 inline"></i>
                                    </button>
                                    <a href="admin.php?delete_vehicle=<?php echo $v['id']; ?>" onclick="return confirm('Are you sure you want to delete this vehicle?')" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-2 py-1 rounded border border-red-200 transition text-center">
                                        <i data-lucide="trash-2" class="w-3 h-3 inline"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Driver Management Modal -->
    <div id="driverModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white border border-gray-200 p-8 max-w-4xl w-full rounded-xl relative max-h-[90vh] overflow-y-auto">
            <button onclick="toggleModal('driverModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Driver Management</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Add Driver Form -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5 text-green-600"></i>
                        Add New Driver
                    </h3>
                    <form method="POST" enctype="multipart/form-data" class="space-y-3" id="addDriverForm">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Profile Photo</label>
                            <input type="file" name="driver_photo" accept="image/*" class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                            <input type="text" name="driver_name" required class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                            <input type="text" name="driver_phone" required class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">License Number</label>
                            <input type="text" name="driver_license" required class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Rate per Day (LKR)</label>
                            <input type="number" name="driver_rate" step="0.01" required class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Experience (Years)</label>
                            <input type="number" name="driver_experience" required class="w-full p-2 border border-gray-200 rounded-lg">
                        </div>
                        <button type="submit" name="add_driver" class="w-full bg-green-600 text-white font-bold py-2 rounded-lg hover:bg-green-700 transition">
                            Add Driver
                        </button>
                    </form>
                </div>
                
                <!-- Driver List -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                        Current Drivers
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php 
                        $drivers->data_seek(0);
                        while($d = $drivers->fetch_assoc()): 
                        ?>
                        <div class="border rounded-lg p-3 bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex gap-3 flex-1">
                                    <div class="w-16 h-16 bg-gray-200 rounded-full flex-shrink-0 overflow-hidden">
                                        <?php if(!empty($d['photo_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($d['photo_url']); ?>" alt="Driver" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i data-lucide="user" class="w-8 h-8"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($d['name']); ?></h4>
                                        <p class="text-xs text-gray-500">📞 <?php echo htmlspecialchars($d['phone']); ?></p>
                                        <p class="text-xs text-gray-500">🪪 <?php echo htmlspecialchars($d['license']); ?></p>
                                        <p class="text-xs font-bold text-green-600 mt-1">LKR <?php echo number_format($d['rate_per_day'], 2); ?>/day | <?php echo $d['experience_years']; ?> yrs exp</p>
                                        <span class="inline-block text-xs px-2 py-1 rounded mt-1 <?php echo $d['status'] == 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-300 text-gray-700'; ?>">
                                            <?php echo ucfirst($d['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="openEditDriverModal(<?php echo htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:bg-blue-50 p-2 rounded">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <a href="admin.php?delete_driver=<?php echo $d['id']; ?>" onclick="return confirm('Delete this driver?')" class="text-red-600 hover:bg-red-50 p-2 rounded">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Driver Modal -->
    <div id="editDriverModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white border border-gray-200 p-8 max-w-md w-full rounded-xl relative">
            <button onclick="toggleModal('editDriverModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Edit Driver</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="driver_id" id="edit_driver_id">
                <input type="hidden" name="existing_photo" id="edit_existing_photo">
                <div id="current_photo_container" class="hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Photo</label>
                    <img id="current_photo_preview" src="" class="w-24 h-24 rounded-full object-cover mx-auto mb-2">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Change Photo (optional)</label>
                    <input type="file" name="driver_photo" accept="image/*" class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                    <input type="text" name="driver_name" id="edit_driver_name" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                    <input type="text" name="driver_phone" id="edit_driver_phone" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">License Number</label>
                    <input type="text" name="driver_license" id="edit_driver_license" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Rate per Day (LKR)</label>
                    <input type="number" name="driver_rate" id="edit_driver_rate" step="0.01" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Experience (Years)</label>
                    <input type="number" name="driver_experience" id="edit_driver_experience" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <button type="submit" name="edit_driver" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Update Driver</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Vehicle Modal -->
    <div id="editVehicleModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white border border-gray-200 p-8 max-w-md w-full rounded-xl relative">
            <button onclick="toggleModal('editVehicleModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Edit Vehicle</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Make</label>
                        <input type="text" name="make" id="edit_make" required class="w-full p-2 border border-gray-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Model</label>
                        <input type="text" name="model" id="edit_model" required class="w-full p-2 border border-gray-200 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vehicle Number</label>
                    <input type="text" name="vehicle_number" id="edit_vehicle_number" required class="w-full p-2 border border-gray-200 rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label>
                        <select name="type" id="edit_type" class="w-full p-2 border border-gray-200 rounded-lg bg-white">
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="truck">Truck</option>
                            <option value="motorcycle">Motorcycle</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Price/Day (LKR)</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required class="w-full p-2 border border-gray-200 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vehicle Image (optional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full border p-2 rounded">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing image</p>
                </div>
                <button type="submit" name="edit_vehicle" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Update Vehicle</button>
            </form>
        </div>
    </div>
    <!-- Customer Registration Modal -->
    <div id="customerModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white border border-gray-200 p-8 max-w-md w-full rounded-xl relative">
            <button onclick="toggleModal('customerModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Customer Registration</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="first_name" placeholder="First Name" required class="border p-2 rounded">
                    <input type="text" name="last_name" placeholder="Last Name" required class="border p-2 rounded">
                </div>
                <input type="text" name="phone" placeholder="Phone Number" required class="w-full border p-2 rounded">
                <input type="email" name="email" placeholder="Email" required class="w-full border p-2 rounded">
                <input type="text" name="nic_passport" placeholder="NIC/Passport Number" required class="w-full border p-2 rounded">
                <input type="text" name="driver_license_number" placeholder="Driver License Number" required class="w-full border p-2 rounded">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Driver License Front Image</label>
                    <input type="file" name="license_front" accept="image/*" required class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Driver License Back Image</label>
                    <input type="file" name="license_back" accept="image/*" required class="w-full border p-2 rounded">
                </div>
                <div class="flex justify-end">
                    <button type="submit" name="register_customer" class="bg-indigo-600 text-white px-6 py-2 rounded font-bold hover:bg-indigo-700 transition">Register</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        lucide.createIcons();
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }
        
        function openEditVehicleModal(vehicle) {
            document.getElementById('edit_vehicle_id').value = vehicle.id;
            document.getElementById('edit_make').value = vehicle.make;
            document.getElementById('edit_model').value = vehicle.model;
            document.getElementById('edit_vehicle_number').value = vehicle.vehicle_number;
            document.getElementById('edit_type').value = vehicle.type;
            document.getElementById('edit_price').value = vehicle.price_per_day;
            document.getElementById('edit_existing_image').value = vehicle.image_url || '';
            toggleModal('editVehicleModal');
            lucide.createIcons();
        }
        
        function openEditDriverModal(driver) {
            document.getElementById('edit_driver_id').value = driver.id;
            document.getElementById('edit_driver_name').value = driver.name;
            document.getElementById('edit_driver_phone').value = driver.phone;
            document.getElementById('edit_driver_license').value = driver.license;
            document.getElementById('edit_driver_rate').value = driver.rate_per_day;
            document.getElementById('edit_driver_experience').value = driver.experience_years;
            document.getElementById('edit_existing_photo').value = driver.photo_url || '';
            
            // Show current photo if exists
            if (driver.photo_url) {
                document.getElementById('current_photo_preview').src = driver.photo_url;
                document.getElementById('current_photo_container').classList.remove('hidden');
            } else {
                document.getElementById('current_photo_container').classList.add('hidden');
            }
            
            toggleModal('driverModal');
            toggleModal('editDriverModal');
            lucide.createIcons();
        }
        
        // Auto-fade notifications after 3 seconds
        setTimeout(() => {
            const notifications = document.querySelectorAll('[id$="-notification"]');
            notifications.forEach(notification => {
                notification.style.transition = 'opacity 0.5s ease-out';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>