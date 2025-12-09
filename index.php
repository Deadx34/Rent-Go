<?php
// Add these lines inside the PHP tag
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_user'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email already registered. Please use a different email.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'customer';
            $_SESSION['success_message'] = "Registration successful! Welcome, $name!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_user'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Note: In production use password_verify($password, $user['password'])
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['success_message'] = "Login successful! Welcome back, " . $user['name'] . "!";
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_start();
    $_SESSION = array();
    session_destroy();
    session_start();
    $_SESSION['success_message'] = "You have been logged out successfully.";
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
        $driver_id = isset($_POST['driver_id']) && $_POST['driver_id'] != '' ? intval($_POST['driver_id']) : null;
        
        $stmt = $conn->prepare("INSERT INTO rentals (user_id, vehicle_id, driver_id, start_date, end_date, total_cost) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissd", $_SESSION['user_id'], $vehicle_id, $driver_id, $start_date, $end_date, $total);
        
        if ($stmt->execute()) {
            $conn->query("UPDATE vehicles SET status = 'rented' WHERE id = $vehicle_id");
            if ($driver_id) {
                $conn->query("UPDATE drivers SET status = 'assigned' WHERE id = $driver_id");
            }
            $rental_id = $stmt->insert_id;
            $success = "Payment Successful! Booking confirmed. <a href='invoice.php?id=$rental_id' style='color:#fff;text-decoration:underline;'>View Invoice</a>";
        }
        $stmt->close();
    }
}

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please login to submit feedback.";
    } else {
        $rating = $_POST['rating'];
        $message = $conn->real_escape_string($_POST['message']);
        
        $stmt = $conn->prepare("INSERT INTO feedbacks (user_id, rating, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $_SESSION['user_id'], $rating, $message);
        
        if ($stmt->execute()) {
            $success = "Thank you for your feedback!";
        }
        $stmt->close();
    }
}

// Handle Contact Form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_contact'])) {
    $success = "Message sent! We will contact you shortly.";
}

// --- Filter & Sort Logic ---
$current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

$sql = "SELECT * FROM vehicles WHERE status = 'available'";

if ($current_filter != 'all') {
    $filter_safe = $conn->real_escape_string($current_filter);
    $sql .= " AND type = '$filter_safe'";
}

if ($current_sort == 'asc') {
    $sql .= " ORDER BY price_per_day ASC";
} elseif ($current_sort == 'desc') {
    $sql .= " ORDER BY price_per_day DESC";
} else {
    $sql .= " ORDER BY id DESC"; // Default recommended
}

$vehicles = $conn->query($sql);

// Fetch available drivers
$available_drivers = $conn->query("SELECT * FROM drivers WHERE status = 'available' ORDER BY rate_per_day ASC");

// Fetch Feedbacks
$feedbacks_sql = "SELECT f.*, u.name as user_name FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 6";
$feedbacks = $conn->query($feedbacks_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent & Go - Luxury Lifestyle Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Flatpickr Calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Stripe SDK -->
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;900&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #000;
            color: #fff;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #111;
        }
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .text-glow {
            text-shadow: 0 0 20px rgba(255,255,255,0.1);
        }
    </style>
</head>
<body class="bg-black text-gray-200">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 transition-all duration-300 bg-black/80 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-24">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3 group">
                    <img src="./uploads/HansiTravels_logo.svg" alt="Logo" class="w-12 h-12 object-contain filter brightness-0 invert group-hover:opacity-80 transition"/>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-10">
                    <a href="index.php" class="text-sm font-medium text-white hover:text-gray-400 tracking-wider transition">HOME</a>
                    <a href="#fleet" class="text-sm font-medium text-white hover:text-gray-400 tracking-wider transition">GALLERY</a>
                    <a href="#about" class="text-sm font-medium text-white hover:text-gray-400 tracking-wider transition">ABOUT</a>
                    <a href="#contact" class="text-sm font-medium text-white hover:text-gray-400 tracking-wider transition">CONTACT US</a>
                </div>

                <!-- Auth/Actions -->
                <div class="flex items-center gap-6">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest"><?php echo $_SESSION['user_role']; ?></p>
                        </div>
                        <?php if($_SESSION['user_role'] == 'admin'): ?>
                            <a href="admin.php" class="text-xs font-bold text-white border border-white/30 px-3 py-1 rounded hover:bg-white hover:text-black transition">ADMIN</a>
                        <?php endif; ?>
                        <a href="index.php?logout=true" class="text-gray-400 hover:text-white transition">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                        </a>
                    <?php else: ?>
                        <button onclick="toggleModal('authModal')" class="border border-white/30 px-6 py-2 text-xs font-bold tracking-widest hover:bg-white hover:text-black transition uppercase">
                            Login
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative h-screen min-h-[600px] flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1503376763036-066120622c74?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 w-full pt-20">
            <div class="max-w-3xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="h-[1px] w-12 bg-white/50"></div>
                    <span class="text-sm font-medium tracking-[0.2em] text-gray-300">PREMIUM CAR RENTAL</span>
                </div>
                <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-6 text-glow">
                    LUXURY <br/>
                    LIFESTYLE <br/>
                    RENTALS
                </h1>
                <p class="text-lg text-gray-400 mb-10 max-w-xl font-light">
                    Enjoy the most luxurious experience. A small river named Duden flows by their place and supplies it with the necessary regelialia.
                </p>
                <a href="#fleet" class="inline-block border border-white px-10 py-4 text-sm font-bold tracking-widest hover:bg-white hover:text-black transition duration-300">
                    DISCOVER FLEET
                </a>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php 
    // Display session success message
    if(isset($_SESSION['success_message'])): 
        $success = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    endif;
    ?>
    <?php if(isset($success)): ?>
        <div id="success-notification" class="fixed top-24 right-4 z-50 bg-green-500 text-white px-6 py-4 rounded shadow-xl">
            <button onclick="document.getElementById('success-notification').remove()" class="absolute top-2 right-2 text-white hover:text-gray-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <span class="block font-bold pr-8"><?php echo $success; ?></span>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div id="error-notification" class="fixed top-24 right-4 z-50 bg-red-500 text-white px-6 py-4 rounded shadow-xl">
            <button onclick="document.getElementById('error-notification').remove()" class="absolute top-2 right-2 text-white hover:text-gray-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <span class="block font-bold pr-8"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/1234567890?text=Hello%2C%20I%20would%20like%20to%20inquire%20about%20car%20rentals" 
       target="_blank" 
       class="fixed bottom-6 right-6 z-50 bg-green-500 hover:bg-green-600 text-white rounded-full p-4 shadow-2xl transition-all duration-300 hover:scale-110 group"
       title="Chat on WhatsApp">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
    </a>

    <!-- Fleet Section (Today's Specials) -->
    <div id="fleet" class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header & Tabs -->
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 border-b border-white/10 pb-6">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-2 uppercase tracking-wide">Today's Specials</h2>
                    <p class="text-gray-500 text-sm tracking-wider">PREMIUM SELECTION</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-8 mt-6 md:mt-0 items-center">
                    
                    <!-- Sorting Tabs (Text Links) -->
                    <div class="flex items-center gap-6 text-xs uppercase tracking-widest font-bold">
                        <span class="text-gray-600 hidden sm:inline">Sort By:</span>
                        <a href="?filter=<?php echo $current_filter; ?>&sort=default#fleet" 
                           class="transition-colors <?php echo $current_sort == 'default' ? 'text-white border-b-2 border-white pb-1' : 'text-gray-500 hover:text-gray-300'; ?>">
                           Recommended
                        </a>
                        <a href="?filter=<?php echo $current_filter; ?>&sort=asc#fleet" 
                           class="transition-colors <?php echo $current_sort == 'asc' ? 'text-white border-b-2 border-white pb-1' : 'text-gray-500 hover:text-gray-300'; ?>">
                           Price Low
                        </a>
                        <a href="?filter=<?php echo $current_filter; ?>&sort=desc#fleet" 
                           class="transition-colors <?php echo $current_sort == 'desc' ? 'text-white border-b-2 border-white pb-1' : 'text-gray-500 hover:text-gray-300'; ?>">
                           Price High
                        </a>
                    </div>

                    <div class="h-6 w-[1px] bg-white/20 hidden sm:block"></div>

                    <!-- Filter Tabs (Pill Buttons) -->
                    <div class="flex gap-3 overflow-x-auto w-full sm:w-auto no-scrollbar">
                        <?php 
                        $filters = ['all' => 'All', 'sedan' => 'Sedan', 'suv' => 'SUV', 'truck' => 'Truck', 'motorcycle' => 'Moto'];
                        foreach ($filters as $key => $label): 
                            $isActive = $current_filter == $key;
                        ?>
                            <a href="?filter=<?php echo $key; ?>&sort=<?php echo $current_sort; ?>#fleet" 
                               class="border border-white/20 px-5 py-2 text-xs font-bold uppercase tracking-widest transition whitespace-nowrap
                                      <?php echo $isActive ? 'bg-white text-black border-white' : 'text-gray-400 hover:border-white hover:text-white'; ?>">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Car Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-16">
                <?php if ($vehicles->num_rows > 0): ?>
                    <?php while($row = $vehicles->fetch_assoc()): ?>
                        <div class="group relative">
                            <!-- Image Container -->
                            <div class="aspect-[16/9] overflow-hidden bg-[#111] mb-6 relative">
                                <?php if(!empty($row['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['model']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-80 group-hover:opacity-100">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-700">
                                        <i data-lucide="car" class="w-16 h-16"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Hover Overlay Button -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 bg-black/40">
                                    <button onclick="openRentModal(this)" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-make="<?php echo htmlspecialchars($row['make']); ?>"
                                            data-model="<?php echo htmlspecialchars($row['model']); ?>"
                                            data-price="<?php echo $row['price_per_day']; ?>"
                                            class="border border-white px-6 py-2 text-xs font-bold tracking-widest bg-white text-black hover:bg-black hover:text-white transition uppercase">
                                        Rent Now
                                    </button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex justify-between items-end border-b border-gray-800 pb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white mb-1"><?php echo $row['make'] . ' ' . $row['model']; ?></h3>
                                    <?php if(!empty($row['vehicle_number'])): ?>
                                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($row['vehicle_number']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex text-yellow-500 gap-1 text-xs mt-2">
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 uppercase tracking-wider mb-1"><?php echo $row['type']; ?></p>
                                    <p class="text-lg font-bold text-white">$<?php echo number_format($row['price_per_day']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="col-span-3 text-center text-gray-500 py-20">No vehicles available in this category.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- About / Promo Section -->
    <div id="about" class="py-24 bg-[#0a0a0a] relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl md:text-5xl font-black text-white mb-6 leading-tight">
                        LUXURY CAR <br/> RENTAL MIAMI
                    </h2>
                    <p class="text-gray-400 mb-8 leading-relaxed font-light">
                        A small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.
                    </p>
                    <div class="grid grid-cols-3 gap-8 mb-10">
                        <div class="text-center p-4 border border-white/10 hover:border-white/30 transition">
                            <i data-lucide="car" class="w-8 h-8 mx-auto mb-2 text-white"></i>
                            <span class="text-xs uppercase tracking-widest text-gray-400">Motors</span>
                        </div>
                        <div class="text-center p-4 border border-white/10 hover:border-white/30 transition">
                            <i data-lucide="gem" class="w-8 h-8 mx-auto mb-2 text-white"></i>
                            <span class="text-xs uppercase tracking-widest text-gray-400">Luxury</span>
                        </div>
                        <div class="text-center p-4 border border-white/10 hover:border-white/30 transition">
                            <i data-lucide="shield-check" class="w-8 h-8 mx-auto mb-2 text-white"></i>
                            <span class="text-xs uppercase tracking-widest text-gray-400">Safe</span>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <img src="https://images.unsplash.com/photo-1617788138017-80ad40651399?q=80&w=2070&auto=format&fit=crop" class="w-full grayscale hover:grayscale-0 transition duration-700">
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-black border border-white/10 -z-10"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact / Footer Section -->
    <div id="contact" class="bg-black pt-24 pb-12 border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <!-- Brand Info -->
                <div>
                    <div class="flex items-center gap-3 mb-8">
                        <img src="./uploads/HansiTravels_logo.svg" alt="Logo" class="w-10 h-10 object-contain filter brightness-0 invert"/>
                        <span class="font-black text-2xl tracking-widest text-white">Hansi Travels</span>
                    </div>
                    <div class="space-y-6 text-sm text-gray-400">
                        <div>
                            <p class="text-white font-bold uppercase tracking-widest mb-1">LK- Colombo</p>
                            <p>+94712345678</p>
                            <p>info@hansitravels.com</p>
                        </div>
                        <div class="flex gap-4 pt-4">
                            <a href="#" class="hover:text-white transition"><i data-lucide="instagram" class="w-5 h-5"></i></a>
                            <a href="#" class="hover:text-white transition"><i data-lucide="twitter" class="w-5 h-5"></i></a>
                            <a href="#" class="hover:text-white transition"><i data-lucide="facebook" class="w-5 h-5"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div>
                    <form method="POST" class="grid grid-cols-2 gap-4">
                        <input type="text" name="name" placeholder="First Name" class="col-span-1 bg-transparent border border-white/20 p-3 text-white text-sm focus:border-white focus:outline-none transition placeholder-gray-600">
                        <input type="text" name="lastname" placeholder="Last Name" class="col-span-1 bg-transparent border border-white/20 p-3 text-white text-sm focus:border-white focus:outline-none transition placeholder-gray-600">
                        
                        <input type="email" name="email" placeholder="Email" class="col-span-1 bg-transparent border border-white/20 p-3 text-white text-sm focus:border-white focus:outline-none transition placeholder-gray-600">
                        <input type="text" name="phone" placeholder="Phone Number" class="col-span-1 bg-transparent border border-white/20 p-3 text-white text-sm focus:border-white focus:outline-none transition placeholder-gray-600">
                        
                        <textarea name="message" placeholder="Message" rows="1" class="col-span-2 bg-transparent border border-white/20 p-3 text-white text-sm focus:border-white focus:outline-none transition placeholder-gray-600"></textarea>
                        
                        <div class="col-span-2 flex justify-end">
                            <button type="submit" name="send_contact" class="bg-white text-black px-8 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
   
            <div class="mt-20 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-xs text-gray-600">
                <p>Copyright © 2025 hansi travels</p>
            </div>
        </div>
    </div>

    <!-- Auth Modal (Dark) -->
    <div id="authModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-white/10 p-10 max-w-md w-full relative">
            <button onclick="toggleModal('authModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white"><i data-lucide="x"></i></button>
            
            <!-- Login Form -->
            <div id="loginForm">
                <h2 class="text-2xl font-bold mb-8 text-center text-white uppercase tracking-widest">Login</h2>
                <form method="POST" class="space-y-4">
                    <input type="email" name="email" required placeholder="Email" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                    <input type="password" name="password" required placeholder="Password" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                    <button type="submit" name="login_user" class="w-full bg-white text-black font-bold py-4 uppercase tracking-widest hover:bg-gray-200 transition">Login</button>
                </form>
                <p class="text-center text-gray-400 mt-4 text-sm">Don't have an account? <button onclick="toggleAuthForm()" class="text-white underline">Register</button></p>
            </div>
            
            <!-- Register Form -->
            <div id="registerForm" class="hidden">
                <h2 class="text-2xl font-bold mb-8 text-center text-white uppercase tracking-widest">Register</h2>
                <form method="POST" class="space-y-4">
                    <input type="text" name="name" required placeholder="Full Name" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                    <input type="email" name="email" required placeholder="Email" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                    <input type="password" name="password" required placeholder="Password" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                    <button type="submit" name="register_user" class="w-full bg-white text-black font-bold py-4 uppercase tracking-widest hover:bg-gray-200 transition">Register</button>
                </form>
                <p class="text-center text-gray-400 mt-4 text-sm">Already have an account? <button onclick="toggleAuthForm()" class="text-white underline">Login</button></p>
            </div>
        </div>
    </div>

    <!-- Rent Modal (Dark) with Stripe -->
    <div id="rentModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-white/10 p-8 max-w-md w-full relative">
            <button onclick="toggleModal('rentModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white"><i data-lucide="x"></i></button>
            <h2 class="text-xl font-bold mb-2 text-white uppercase tracking-wider">Booking</h2>
            <p id="modalCarName" class="text-gray-400 mb-8 text-sm"></p>
            
            <form id="rentForm" method="POST" oninput="calculateTotal()" class="space-y-4">
                <input type="hidden" name="vehicle_id" id="modalVehicleId">
                <input type="hidden" name="price_per_day" id="modalPrice">
                <input type="hidden" name="total_cost" id="modalTotalInput">
                <input type="hidden" name="transaction_id" id="transactionId">
                <input type="hidden" name="confirm_rental" value="1">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pick-up</label>
                        <input type="text" name="start_date" id="startDate" required class="w-full bg-black border border-white/20 p-3 text-white focus:border-white outline-none" placeholder="Select start date">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Return</label>
                        <input type="text" name="end_date" id="endDate" required class="w-full bg-black border border-white/20 p-3 text-white focus:border-white outline-none" placeholder="Select end date">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 flex items-center gap-2">
                        <input type="checkbox" id="needDriver" onchange="toggleDriverSelection()" class="w-4 h-4">
                        Need a Driver?
                    </label>
                    <select name="driver_id" id="driverSelect" class="w-full bg-black border border-white/20 p-3 text-white focus:border-white outline-none hidden" onchange="calculateTotal()">
                        <option value="">Select a driver</option>
                        <?php while($driver = $available_drivers->fetch_assoc()): ?>
                            <option value="<?php echo $driver['id']; ?>" data-rate="<?php echo $driver['rate_per_day']; ?>">
                                <?php echo htmlspecialchars($driver['name']); ?> - $<?php echo $driver['rate_per_day']; ?>/day (<?php echo $driver['experience_years']; ?> yrs exp)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="border-t border-white/10 pt-4 mt-4 mb-4">
                    <div class="flex justify-between items-center text-sm text-gray-400 mb-2">
                        <span>Vehicle Cost</span>
                        <span id="vehicleCost">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-400 mb-2" id="driverCostRow" style="display:none;">
                        <span>Driver Cost</span>
                        <span id="driverCost">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-white/10">
                        <span class="text-sm text-gray-400 uppercase tracking-widest">Total</span>
                        <span class="font-bold text-2xl text-white" id="displayTotal">$0.00</span>
                    </div>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div id="card-element" class="mt-6 p-3 bg-white border border-gray-300 rounded"></div>
                    <div id="card-errors" class="text-red-500 text-sm mt-2"></div>
                    <button type="button" id="stripe-pay-btn" onclick="handleStripePayment()" class="w-full bg-blue-600 text-white font-bold py-4 uppercase tracking-widest hover:bg-blue-700 transition mt-4">Pay Now</button>
                <?php else: ?>
                    <button type="button" onclick="toggleModal('rentModal'); toggleModal('authModal');" class="w-full bg-gray-800 text-gray-300 font-bold py-4 uppercase tracking-widest hover:bg-gray-700 transition mt-4">Continue</button>
                <?php endif; ?>
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
        
        function toggleAuthForm() {
            document.getElementById('loginForm').classList.toggle('hidden');
            document.getElementById('registerForm').classList.toggle('hidden');
        }

        // Initialize Flatpickr calendars for booking dates
        flatpickr('#startDate', {
            minDate: 'today',
            dateFormat: 'Y-m-d',
            onChange: function(selectedDates, dateStr, instance) {
                // Set minDate for endDate based on startDate
                if (selectedDates.length) {
                    endPicker.set('minDate', dateStr);
                }
                calculateTotal();
            }
        });
        var endPicker = flatpickr('#endDate', {
            minDate: 'today',
            dateFormat: 'Y-m-d',
            onChange: function() {
                calculateTotal();
            }
        });

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

        // Initialize Stripe
        var stripe = Stripe('pk_test_51SaWfrHXfXsWgJRrrake2zZ1q44Z5aKHz7ACO08TXwFYvG62BZ7DgLKvJcV1D5Y2WMWMuesWhhsxn5f4RT86zaNg00UWUyLVMo'); // Replace with your test publishable key
        var elements = stripe.elements();
        var cardElement = elements.create('card');
        var cardMounted = false;
        
        function openRentModal(button) {
            // Using Data Attributes to prevent quote escaping issues
            const id = button.getAttribute('data-id');
            const make = button.getAttribute('data-make');
            const model = button.getAttribute('data-model');
            const price = button.getAttribute('data-price');

            document.getElementById('modalCarName').innerText = make + ' ' + model;
            document.getElementById('modalVehicleId').value = id;
            document.getElementById('modalPrice').value = price;

            // Reset calendars and form fields
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('displayTotal').innerText = '$0.00';
            document.getElementById('modalTotalInput').value = '';
            document.getElementById('needDriver').checked = false;
            document.getElementById('driverSelect').value = '';
            document.getElementById('driverSelect').classList.add('hidden');
            document.getElementById('driverCostRow').style.display = 'none';
            if (document.getElementById('card-errors')) {
                document.getElementById('card-errors').textContent = '';
            }
            endPicker.set('minDate', 'today');

            toggleModal('rentModal');
            
            // Mount Stripe card element after modal is visible
            setTimeout(function() {
                if (!cardMounted && document.getElementById('card-element')) {
                    cardElement.mount('#card-element');
                    cardMounted = true;
                }
            }, 100);
        }

        function calculateTotal() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const price = parseFloat(document.getElementById('modalPrice').value);

            if (start && end) {
                const diff = new Date(end) - new Date(start);
                const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
                const vehicleTotal = (days > 0 ? days : 1) * price;
                
                // Calculate driver cost if selected
                let driverTotal = 0;
                const driverSelect = document.getElementById('driverSelect');
                if (driverSelect && driverSelect.value) {
                    const selectedOption = driverSelect.options[driverSelect.selectedIndex];
                    const driverRate = parseFloat(selectedOption.getAttribute('data-rate'));
                    driverTotal = (days > 0 ? days : 1) * driverRate;
                }
                
                const total = vehicleTotal + driverTotal;
                
                document.getElementById('vehicleCost').innerText = '$' + vehicleTotal.toFixed(2);
                document.getElementById('driverCost').innerText = '$' + driverTotal.toFixed(2);
                document.getElementById('displayTotal').innerText = '$' + total.toFixed(2);
                document.getElementById('modalTotalInput').value = total;
                return total;
            }
            return 0;
        }
        
        function toggleDriverSelection() {
            const checkbox = document.getElementById('needDriver');
            const driverSelect = document.getElementById('driverSelect');
            const driverCostRow = document.getElementById('driverCostRow');
            
            if (checkbox.checked) {
                driverSelect.classList.remove('hidden');
                driverCostRow.style.display = 'flex';
            } else {
                driverSelect.classList.add('hidden');
                driverSelect.value = '';
                driverCostRow.style.display = 'none';
                calculateTotal();
            }
        }

        async function handleStripePayment() {
            const total = document.getElementById('modalTotalInput').value;
            if (!total || total <= 0) {
                alert("Please select valid dates first.");
                return;
            }
            
            document.getElementById('stripe-pay-btn').disabled = true;
            document.getElementById('stripe-pay-btn').innerText = 'Processing...';
            
            const {token, error} = await stripe.createToken(cardElement);
            
            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                document.getElementById('stripe-pay-btn').disabled = false;
                document.getElementById('stripe-pay-btn').innerText = 'Pay Now';
            } else {
                // Payment Success - set transaction ID and submit form
                document.getElementById('transactionId').value = token.id;
                document.getElementById('rentForm').submit();
            }
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