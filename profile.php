<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get user's rentals with invoices
$stmt = $conn->prepare("SELECT r.*, v.make, v.model, v.vehicle_number, v.image_url, d.name as driver_name 
    FROM rentals r 
    JOIN vehicles v ON r.vehicle_id = v.id 
    LEFT JOIN drivers d ON r.driver_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals_result = $stmt->get_result();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['user_name'] = $name;
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $error = "Failed to update profile.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($user['password'] === $current_password) {
        if ($new_password === $confirm_password) {
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $new_password, $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = "Password changed successfully!";
            header("Location: profile.php");
            exit();
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hansi Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-black text-white p-4 sticky top-0 z-40 border-b border-white/10">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-black tracking-wider">HANSI<span class="text-blue-500">TRAVELS</span></a>
            <div class="flex gap-4 items-center">
                <span class="text-gray-400">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="index.php" class="px-4 py-2 bg-white/10 rounded hover:bg-white/20 transition">Home</a>
                <a href="index.php?logout=1" class="px-4 py-2 bg-red-600 rounded hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-8">
        <?php 
        if(isset($_SESSION['success_message'])): 
            $success = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        endif;
        ?>
        <?php if(isset($success)): ?>
            <div id="success-notification" class="bg-green-100 text-green-700 p-4 rounded mb-6 relative">
                <button onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-green-700 hover:text-green-900">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <span class="block pr-8"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Information -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-blue-600 rounded-full mx-auto flex items-center justify-center text-white text-3xl font-bold mb-4">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex items-center gap-3 mb-3">
                            <i data-lucide="calendar" class="w-5 h-5 text-gray-400"></i>
                            <span class="text-sm text-gray-600">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="car" class="w-5 h-5 text-gray-400"></i>
                            <span class="text-sm text-gray-600"><?php echo $rentals_result->num_rows; ?> Total Bookings</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <button onclick="toggleModal('editProfileModal')" class="w-full text-left px-4 py-3 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition flex items-center gap-3">
                            <i data-lucide="edit" class="w-5 h-5"></i>
                            Edit Profile
                        </button>
                        <button onclick="toggleModal('changePasswordModal')" class="w-full text-left px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition flex items-center gap-3">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                            Change Password
                        </button>
                        <a href="index.php#fleet" class="block w-full text-left px-4 py-3 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition flex items-center gap-3">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                            New Booking
                        </a>
                    </div>
                </div>
            </div>

            <!-- Rental History & Invoices -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="file-text" class="text-blue-600"></i>
                        My Bookings & Invoices
                    </h2>

                    <?php if($rentals_result->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($rental = $rentals_result->fetch_assoc()): 
                                $days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
                                $days = $days > 0 ? $days : 1;
                            ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex gap-4">
                                    <!-- Vehicle Image -->
                                    <div class="w-24 h-24 bg-gray-200 rounded-lg flex-shrink-0 overflow-hidden">
                                        <?php if(!empty($rental['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($rental['image_url']); ?>" alt="Vehicle" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i data-lucide="car" class="w-8 h-8"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Booking Details -->
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></h3>
                                                <p class="text-sm text-gray-500">Reg: <?php echo htmlspecialchars($rental['vehicle_number']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-2xl font-bold text-blue-600">LKR <?php echo number_format($rental['total_cost'], 2); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $days; ?> day(s)</p>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 mb-3">
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase">Pick-up</p>
                                                <p class="font-semibold text-sm"><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase">Return</p>
                                                <p class="font-semibold text-sm"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></p>
                                            </div>
                                        </div>

                                        <?php if($rental['driver_name']): ?>
                                        <div class="mb-3">
                                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                                <i data-lucide="user-check" class="w-3 h-3"></i>
                                                Driver: <?php echo htmlspecialchars($rental['driver_name']); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>

                                        <div class="flex gap-2 items-center">
                                            <span class="inline-block text-xs px-3 py-1 rounded-full <?php 
                                                echo $rental['status'] === 'active' ? 'bg-green-100 text-green-700' : 
                                                    ($rental['status'] === 'returned' ? 'bg-gray-100 text-gray-700' : 'bg-blue-100 text-blue-700'); 
                                            ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                            <span class="inline-block text-xs px-3 py-1 rounded-full <?php 
                                                echo $rental['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; 
                                            ?>">
                                                <?php echo $rental['payment_status'] === 'paid' ? '✓ Paid' : '⏳ Payment Pending'; ?>
                                            </span>
                                            <a href="invoice.php?id=<?php echo $rental['id']; ?>" target="_blank" class="ml-auto inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                                Download Invoice
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i data-lucide="inbox" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                            <p class="text-gray-500 mb-4">No bookings yet</p>
                            <a href="index.php#fleet" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Browse Vehicles
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-8 max-w-md w-full relative">
            <button onclick="toggleModal('editProfileModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <h2 class="text-2xl font-bold mb-6">Edit Profile</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full p-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full p-3 border rounded-lg">
                </div>
                <button type="submit" name="update_profile" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-8 max-w-md w-full relative">
            <button onclick="toggleModal('changePasswordModal')" class="absolute top-4 right-4 text-gray-500 hover:text-black">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <h2 class="text-2xl font-bold mb-6">Change Password</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full p-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                    <input type="password" name="new_password" required class="w-full p-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full p-3 border rounded-lg">
                </div>
                <button type="submit" name="change_password" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
            lucide.createIcons();
        }

        // Auto-fade notifications
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
