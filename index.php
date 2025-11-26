ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
<?php
session_start();
require 'db_connect.php';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_user'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // In production use password_verify($password, $user['password'])
        // For demo, we assume direct login or simple check
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle Renting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_rental'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please login first";
    } else {
        $vehicle_id = $_POST['vehicle_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $total = $_POST['total_cost'];
        
        $stmt = $conn->prepare("INSERT INTO rentals (user_id, vehicle_id, start_date, end_date, total_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissd", $_SESSION['user_id'], $vehicle_id, $start_date, $end_date, $total);
        
        if ($stmt->execute()) {
            $conn->query("UPDATE vehicles SET status = 'rented' WHERE id = $vehicle_id");
            $success = "Booking confirmed!";
        }
        $stmt->close();
    }
}

// Fetch Vehicles
$sql = "SELECT * FROM vehicles WHERE status = 'available'";
if (isset($_GET['filter']) && $_GET['filter'] != 'all') {
    $filter = $conn->real_escape_string($_GET['filter']);
    $sql .= " AND type = '$filter'";
}
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'asc') $sql .= " ORDER BY price_per_day ASC";
    if ($_GET['sort'] == 'desc') $sql .= " ORDER BY price_per_day DESC";
}
$vehicles = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent & Go - Premium Vehicle Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-40 backdrop-blur-md bg-white/90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3 hover:opacity-80 transition">
                    <div class="w-10 h-10 bg-white rounded-lg p-1">
                        <img src="Gemini_Generated_Image_3vfrwe3vfrwe3vfr.jpg" alt="Logo" class="w-full h-full object-contain"/>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tighter text-gray-900">Rent & Go</span>
                </a>
                
                <div class="flex items-center gap-6">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="text-xs text-blue-600 font-medium uppercase tracking-wide"><?php echo $_SESSION['user_role']; ?></p>
                        </div>
                        <?php if($_SESSION['user_role'] == 'admin'): ?>
                            <a href="admin.php" class="text-sm font-bold text-blue-600">Admin Panel</a>
                        <?php endif; ?>
                        <a href="index.php?logout=true" class="p-2.5 text-gray-400 hover:text-red-500 transition-all rounded-full hover:bg-red-50">
                            <i data-lucide="log-out"></i>
                        </a>
                    <?php else: ?>
                        <button onclick="toggleModal('authModal')" class="flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                            <i data-lucide="log-in" class="w-4 h-4"></i> Login / Register
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Messages -->
    <?php if(isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative max-w-7xl mx-auto mt-4" role="alert">
            <span class="block sm:inline"><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="relative bg-white overflow-hidden mb-16">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32 pt-20 px-4 sm:px-6 lg:px-8">
                <main class="mt-10 mx-auto max-w-7xl sm:mt-12 md:mt-16 lg:mt-20 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">Drive your dreams</span>
                            <span class="block text-blue-600 xl:inline">without limits</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Premium car rental services for your daily needs. Whether it's a weekend getaway or a business trip, we have the perfect vehicle waiting for you.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="#fleet" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                    Browse Fleet
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-100 flex items-center justify-center text-gray-200">
             <i data-lucide="car" class="w-96 h-96 opacity-20"></i>
        </div>
    </div>

    <!-- Fleet Section -->
    <div id="fleet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24 pt-12">
        <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
            <div class="w-full md:w-auto">
                <h2 class="text-3xl font-extrabold text-gray-900">Premium Fleet</h2>
                <p class="text-gray-500 mt-2 text-lg">Choose from our wide range of vehicles</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                <form method="GET" class="flex gap-2">
                    <select name="sort" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 py-2.5 px-4 rounded-lg font-semibold">
                        <option value="">Sort by Price</option>
                        <option value="asc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'asc') echo 'selected'; ?>>Low to High</option>
                        <option value="desc" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'desc') echo 'selected'; ?>>High to Low</option>
                    </select>
                    <select name="filter" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 py-2.5 px-4 rounded-lg font-semibold">
                        <option value="all">All Types</option>
                        <option value="sedan" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'sedan') echo 'selected'; ?>>Sedan</option>
                        <option value="suv" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'suv') echo 'selected'; ?>>SUV</option>
                        <option value="truck" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'truck') echo 'selected'; ?>>Truck</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($vehicles->num_rows > 0): ?>
                <?php while($row = $vehicles->fetch_assoc()): ?>
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
                        <div class="h-56 bg-gray-50 flex items-center justify-center text-gray-300 relative group-hover:bg-blue-50/30 transition-colors duration-300">
                            <i data-lucide="car" class="w-24 h-24"></i> <!-- Static icon for now -->
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-green-100 text-green-800">Available</span>
                            </div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900"><?php echo $row['make'] . ' ' . $row['model']; ?></h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-blue-600">$<?php echo $row['price_per_day']; ?></p>
                                    <p class="text-xs text-gray-400 font-medium">per day</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-6 text-sm text-gray-600">
                                <span class="flex items-center gap-2"><i data-lucide="settings" class="w-4 h-4"></i> <?php echo $row['transmission']; ?></span>
                                <span class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> <?php echo $row['seats']; ?> Seats</span>
                            </div>
                            <button onclick="openRentModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="w-full py-3.5 px-6 rounded-xl font-bold flex items-center justify-center gap-2 bg-blue-600 text-white hover:bg-blue-700 transition shadow-md">
                                Rent Now <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-3 text-center text-gray-500">No vehicles found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Contact Us</h2>
            <div class="flex justify-center gap-4">
                <a href="https://wa.me/1234567890" target="_blank" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-full shadow-lg transition">
                    <i data-lucide="message-circle"></i> Chat on WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Auth Modal -->
    <div id="authModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-md relative">
            <button onclick="toggleModal('authModal')" class="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-full"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg">
                </div>
                <button type="submit" name="login_user" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg">Login</button>
            </form>
        </div>
    </div>

    <!-- Rent Modal -->
    <div id="rentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md relative">
            <button onclick="toggleModal('rentModal')" class="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-full"><i data-lucide="x"></i></button>
            <h2 class="text-xl font-bold mb-1">Complete Booking</h2>
            <p id="modalCarName" class="text-blue-600 mb-6"></p>
            
            <form method="POST" oninput="calculateTotal()">
                <input type="hidden" name="vehicle_id" id="modalVehicleId">
                <input type="hidden" name="price_per_day" id="modalPrice">
                <input type="hidden" name="total_cost" id="modalTotalInput">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pick-up</label>
                        <input type="date" name="start_date" id="startDate" required class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Return</label>
                        <input type="date" name="end_date" id="endDate" required class="w-full p-2 border rounded-lg">
                    </div>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-xl mb-6 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Cost</span>
                    <span class="font-bold text-xl text-blue-900" id="displayTotal">$0.00</span>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <button type="submit" name="confirm_rental" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl">Confirm Reservation</button>
                <?php else: ?>
                    <button type="button" onclick="toggleModal('rentModal'); toggleModal('authModal');" class="w-full bg-gray-800 text-white font-bold py-3 rounded-xl">Login to Rent</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500">&copy; 2024 Rent & Go Inc. All rights reserved.</p>
            <div class="flex justify-center gap-4 mt-4 text-sm text-gray-400">
                <a href="#" class="hover:text-white">Terms</a>
                <a href="#" class="hover:text-white">Privacy</a>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Icons
        lucide.createIcons();

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

        function openRentModal(vehicle) {
            document.getElementById('modalCarName').innerText = vehicle.make + ' ' + vehicle.model;
            document.getElementById('modalVehicleId').value = vehicle.id;
            document.getElementById('modalPrice').value = vehicle.price_per_day;
            
            // Set min dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').min = today;
            document.getElementById('endDate').min = today;
            
            toggleModal('rentModal');
        }

        function calculateTotal() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const price = parseFloat(document.getElementById('modalPrice').value);
            
            if (start && end) {
                const diff = new Date(end) - new Date(start);
                const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
                const total = (days > 0 ? days : 1) * price;
                
                document.getElementById('displayTotal').innerText = '$' + total.toFixed(2);
                document.getElementById('modalTotalInput').value = total;
            }
        }
    </script>
</body>
</html>