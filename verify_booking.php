<?php
session_start();
require 'db_connect.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$booking = null;
$customer = null;
$vehicle = null;
$driver = null;
$search_performed = false;

// Search for booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_booking'])) {
    $search_performed = true;
    $search_type = $_POST['search_type'];
    $search_value = trim($_POST['search_value']);
    
    if ($search_type == 'booking_id') {
        // Search by booking ID
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE r.id = ?");
        $stmt->bind_param("i", $search_value);
    } elseif ($search_type == 'customer_email') {
        // Search by customer email
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE u.email = ?
                                ORDER BY r.id DESC LIMIT 1");
        $stmt->bind_param("s", $search_value);
    } elseif ($search_type == 'vehicle_number') {
        // Search by vehicle registration number
        $stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.email as customer_email, 
                                v.make, v.model, v.vehicle_number, v.type, v.image_url,
                                d.name as driver_name, d.license as driver_license, d.phone as driver_phone
                                FROM rentals r
                                JOIN users u ON r.user_id = u.id
                                JOIN vehicles v ON r.vehicle_id = v.id
                                LEFT JOIN drivers d ON r.driver_id = d.id
                                WHERE v.vehicle_number LIKE ?
                                ORDER BY r.id DESC LIMIT 1");
        $search_pattern = "%{$search_value}%";
        $stmt->bind_param("s", $search_pattern);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    }
    $stmt->close();
}

// Mark as picked up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_picked_up'])) {
    $booking_id = $_POST['booking_id'];
    $stmt = $conn->prepare("UPDATE rentals SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $success = "Booking marked as picked up successfully!";
    }
    $stmt->close();
}

// Mark as returned
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_returned'])) {
    $booking_id = $_POST['booking_id'];
    $stmt = $conn->prepare("UPDATE rentals SET status = 'returned' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $success = "Vehicle marked as returned successfully!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Booking - Hansi Travels Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black text-white p-4 sticky top-0 z-40 border-b border-white/10 no-print">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-black tracking-wider">HANSI<span class="text-blue-500">TRAVELS</span> - ADMIN</h1>
            <div class="flex gap-4">
                <a href="admin.php" class="px-4 py-2 bg-white/10 rounded hover:bg-white/20 transition">Dashboard</a>
                <a href="index.php?logout=1" class="px-4 py-2 bg-red-600 rounded hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-8">
        <!-- Success Message -->
        <?php if(isset($success)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-6 relative">
                <button onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-green-700 hover:text-green-900">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <span class="block pr-8"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8 no-print">
            <div class="flex items-center gap-3 mb-6">
                <i data-lucide="search" class="w-8 h-8 text-blue-600"></i>
                <h2 class="text-3xl font-black text-gray-800">VERIFY BOOKING</h2>
            </div>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Search By</label>
                        <select name="search_type" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="booking_id">Booking ID</option>
                            <option value="customer_email">Customer Email</option>
                            <option value="vehicle_number">Vehicle Registration Number</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Search Value</label>
                        <input type="text" name="search_value" required placeholder="Enter search value..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
                <button type="submit" name="search_booking" class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <i data-lucide="search" class="w-5 h-5"></i>
                    Search Booking
                </button>
            </form>
        </div>

        <?php if ($search_performed && !$booking): ?>
            <!-- No Results -->
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-8 h-8 text-yellow-600"></i>
                    <div>
                        <h3 class="font-bold text-lg text-yellow-800">No Booking Found</h3>
                        <p class="text-yellow-700">No booking matches your search criteria. Please verify the details and try again.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($booking): ?>
            <!-- Booking Details Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-3xl font-black mb-2">BOOKING VERIFIED ✓</h2>
                            <p class="text-blue-100">Booking ID: #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php 
                                echo $booking['status'] === 'active' ? 'bg-green-500' : 
                                    ($booking['status'] === 'returned' ? 'bg-gray-500' : 'bg-yellow-500'); 
                            ?>">
                                <?php echo strtoupper($booking['status']); ?>
                            </span>
                            <div class="mt-2">
                                <span class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php 
                                    echo $booking['payment_status'] === 'paid' ? 'bg-green-500' : 'bg-orange-500'; 
                                ?>">
                                    <?php echo $booking['payment_status'] === 'paid' ? '✓ PAID' : '⏳ PAYMENT PENDING'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Customer Information -->
                        <div class="border-l-4 border-blue-600 pl-6">
                            <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="user" class="w-6 h-6 text-blue-600"></i>
                                CUSTOMER DETAILS
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Full Name</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Email Address</p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['customer_email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Customer ID</p>
                                    <p class="text-lg font-semibold text-gray-900">#<?php echo str_pad($booking['user_id'], 5, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Information -->
                        <div class="border-l-4 border-green-600 pl-6">
                            <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="car" class="w-6 h-6 text-green-600"></i>
                                VEHICLE DETAILS
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Vehicle</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Registration Number</p>
                                    <p class="text-lg font-semibold text-gray-900 bg-yellow-100 px-3 py-1 inline-block rounded"><?php echo htmlspecialchars($booking['vehicle_number']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 uppercase">Vehicle Type</p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['type']); ?></p>
                                </div>
                                <?php if (!empty($booking['image_url'])): ?>
                                <div class="mt-4">
                                    <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="Vehicle" class="w-full h-40 object-cover rounded-lg border-2 border-gray-200">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Period & Pricing -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
                                RENTAL PERIOD
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600 uppercase mb-1">Pick-up Date</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 uppercase mb-1">Return Date</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo date('M d, Y', strtotime($booking['end_date'])); ?></p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-blue-200">
                                <p class="text-sm text-gray-600 uppercase mb-1">Total Duration</p>
                                <?php 
                                    $days = (strtotime($booking['end_date']) - strtotime($booking['start_date'])) / (60 * 60 * 24);
                                    $days = $days > 0 ? $days : 1;
                                ?>
                                <p class="text-2xl font-black text-blue-600"><?php echo $days; ?> Day<?php echo $days > 1 ? 's' : ''; ?></p>
                            </div>
                        </div>

                        <div class="bg-green-50 p-6 rounded-lg">
                            <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="dollar-sign" class="w-6 h-6 text-green-600"></i>
                                PRICING DETAILS
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">Total Amount</span>
                                    <span class="text-2xl font-black text-green-600">LKR <?php echo number_format($booking['total_cost'], 2); ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-green-200">
                                    <span class="text-gray-700">Payment Method</span>
                                    <span class="font-bold text-gray-900"><?php echo $booking['payment_method'] === 'pay_at_pickup' ? 'Pay at Pickup' : 'Online Payment'; ?></span>
                                </div>
                                <?php if ($booking['transaction_id']): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">Transaction ID</span>
                                    <span class="font-mono text-sm text-gray-900"><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($booking['driver_name']): ?>
                    <!-- Driver Information -->
                    <div class="bg-purple-50 p-6 rounded-lg mb-8">
                        <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="user-check" class="w-6 h-6 text-purple-600"></i>
                            ASSIGNED DRIVER
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 uppercase mb-1">Driver Name</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($booking['driver_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 uppercase mb-1">License Number</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['driver_license'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 uppercase mb-1">Contact</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['driver_phone'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-4 pt-6 border-t border-gray-200 no-print">
                        <?php if ($booking['status'] === 'pending'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <button type="submit" name="mark_picked_up" class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                Mark as Picked Up
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($booking['status'] === 'active'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <button type="submit" name="mark_returned" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <i data-lucide="check-square" class="w-5 h-5"></i>
                                Mark as Returned
                            </button>
                        </form>
                        <?php endif; ?>

                        <a href="invoice.php?id=<?php echo $booking['id']; ?>" target="_blank" class="px-6 py-3 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                            View Invoice
                        </a>

                        <button onclick="window.print()" class="px-6 py-3 bg-gray-600 text-white font-bold rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                            Print Details
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        // Auto-fade notifications
        setTimeout(() => {
            const notifications = document.querySelectorAll('.bg-green-100');
            notifications.forEach(notification => {
                notification.style.transition = 'opacity 0.5s ease-out';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>
