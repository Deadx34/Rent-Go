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
        // Note: In production use password_verify($password, $user['password'])
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

// Handle Contact Form (Simulation)
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

// Fetch Feedbacks
$feedbacks_sql = "SELECT f.*, u.name as user_name FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 6";
$feedbacks = $conn->query($feedbacks_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hansi Travels - Luxury Lifestyle Rentals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;900&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #000;
            color: #fff;
        }
        
        .glass-dark {
            background: rgba(20, 20, 20, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-glow {
            text-shadow: 0 0 20px rgba(255,255,255,0.1);
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
    </style>
</head>
<body class="bg-black text-gray-200">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 transition-all duration-300 bg-black/80 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-24">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3 group">
                    <img src="./uploads/rent&go_logo.png" alt="Logo" class="w-12 h-12 object-contain filter brightness-0 invert group-hover:opacity-80 transition"/>
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
        <!-- Background Image/Overlay -->
        <div class="absolute inset-0 z-0">
            <!-- Using a dark abstract background if car image not available, replace url with real one -->
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

    <!-- Fleet Section (Today's Specials) -->
    <div id="fleet" class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 border-b border-white/10 pb-6">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-2 uppercase tracking-wide">Today's Specials</h2>
                    <p class="text-gray-500 text-sm tracking-wider">PREMIUM SELECTION</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-6 mt-6 md:mt-0">
                    <!-- Sort Tabs -->
                    <div class="flex text-sm font-medium text-gray-400 gap-6">
                        <a href="?filter=<?php echo $current_filter; ?>&sort=default#fleet" class="<?php echo $current_sort == 'default' ? 'text-white border-b-2 border-white pb-1' : 'hover:text-white transition'; ?>">Recommended</a>
                        <a href="?filter=<?php echo $current_filter; ?>&sort=asc#fleet" class="<?php echo $current_sort == 'asc' ? 'text-white border-b-2 border-white pb-1' : 'hover:text-white transition'; ?>">Price Low</a>
                        <a href="?filter=<?php echo $current_filter; ?>&sort=desc#fleet" class="<?php echo $current_sort == 'desc' ? 'text-white border-b-2 border-white pb-1' : 'hover:text-white transition'; ?>">Price High</a>
                    </div>

                    <!-- Type Filters as Button-like Tabs -->
                    <div class="flex gap-4">
                        <a href="?filter=all&sort=<?php echo $current_sort; ?>#fleet" class="border border-white/20 px-4 py-1 text-xs uppercase tracking-wider hover:border-white transition <?php echo $current_filter == 'all' ? 'bg-white text-black border-white' : 'text-gray-400'; ?>">View All</a>
                        <a href="?filter=suv&sort=<?php echo $current_sort; ?>#fleet" class="border border-white/20 px-4 py-1 text-xs uppercase tracking-wider hover:border-white transition <?php echo $current_filter == 'suv' ? 'bg-white text-black border-white' : 'text-gray-400'; ?>">SUV</a>
                        <a href="?filter=sedan&sort=<?php echo $current_sort; ?>#fleet" class="border border-white/20 px-4 py-1 text-xs uppercase tracking-wider hover:border-white transition <?php echo $current_filter == 'sedan' ? 'bg-white text-black border-white' : 'text-gray-400'; ?>">Sedan</a>
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
                                    <button onclick="openRentModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="border border-white px-6 py-2 text-xs font-bold tracking-widest bg-white text-black hover:bg-black hover:text-white transition uppercase">
                                        Rent Now
                                    </button>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex justify-between items-end border-b border-gray-800 pb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white mb-1"><?php echo $row['make'] . ' ' . $row['model']; ?></h3>
                                    <div class="flex text-yellow-500 gap-1 text-xs">
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
                        LUXURY CAR <br/>RENTAL Sri Lanka
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
                        <img src="Gemini_Generated_Image_3vfrwe3vfrwe3vfr.jpg" alt="Logo" class="w-10 h-10 object-contain filter brightness-0 invert"/>
                        <span class="font-black text-2xl tracking-widest text-white">Hansi Travels</span>
                    </div>
                    <div class="space-y-6 text-sm text-gray-400">
                        <div>
                            <p class="text-white font-bold uppercase tracking-widest mb-1">Colombo-LK</p>
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
                <p>Copyright © 2024 Hansi Travels</p>
            </div>
        </div>
    </div>

    <!-- Auth Modal (Dark) -->
    <div id="authModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-white/10 p-10 max-w-md w-full relative">
            <button onclick="toggleModal('authModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white"><i data-lucide="x"></i></button>
            <h2 class="text-2xl font-bold mb-8 text-center text-white uppercase tracking-widest">Login</h2>
            <form method="POST" class="space-y-4">
                <input type="email" name="email" required placeholder="Email" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                <input type="password" name="password" required placeholder="Password" class="w-full bg-black border border-white/20 p-4 text-white placeholder-gray-600 focus:border-white outline-none">
                <button type="submit" name="login_user" class="w-full bg-white text-black font-bold py-4 uppercase tracking-widest hover:bg-gray-200 transition">Login</button>
            </form>
        </div>
    </div>

    <!-- Rent Modal (Dark) -->
    <div id="rentModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-[#111] border border-white/10 p-8 max-w-md w-full relative">
            <button onclick="toggleModal('rentModal')" class="absolute top-4 right-4 text-gray-500 hover:text-white"><i data-lucide="x"></i></button>
            <h2 class="text-xl font-bold mb-2 text-white uppercase tracking-wider">Booking</h2>
            <p id="modalCarName" class="text-gray-400 mb-8 text-sm"></p>
            
            <form method="POST" oninput="calculateTotal()" class="space-y-4">
                <input type="hidden" name="vehicle_id" id="modalVehicleId">
                <input type="hidden" name="price_per_day" id="modalPrice">
                <input type="hidden" name="total_cost" id="modalTotalInput">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pick-up</label>
                        <input type="date" name="start_date" id="startDate" required class="w-full bg-black border border-white/20 p-3 text-white focus:border-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Return</label>
                        <input type="date" name="end_date" id="endDate" required class="w-full bg-black border border-white/20 p-3 text-white focus:border-white outline-none">
                    </div>
                </div>
                
                <div class="border-t border-white/10 pt-4 mt-4 flex justify-between items-center">
                    <span class="text-sm text-gray-400 uppercase tracking-widest">Total</span>
                    <span class="font-bold text-2xl text-white" id="displayTotal">$0.00</span>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <button type="submit" name="confirm_rental" class="w-full bg-white text-black font-bold py-4 uppercase tracking-widest hover:bg-gray-200 transition mt-4">Confirm</button>
                <?php else: ?>
                    <button type="button" onclick="toggleModal('rentModal'); toggleModal('authModal');" class="w-full bg-gray-800 text-gray-300 font-bold py-4 uppercase tracking-widest hover:bg-gray-700 transition mt-4">Login Required</button>
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

        function openRentModal(vehicle) {
            document.getElementById('modalCarName').innerText = vehicle.make + ' ' + vehicle.model;
            document.getElementById('modalVehicleId').value = vehicle.id;
            document.getElementById('modalPrice').value = vehicle.price_per_day;
            
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