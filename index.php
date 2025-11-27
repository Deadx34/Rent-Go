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
    <title>Hansi Travels - Premium Vehicle Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        html { scroll-behavior: smooth; }
        /* Hide scrollbar for horizontal scroll areas */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-40 backdrop-blur-md bg-white/90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3 hover:opacity-80 transition">
                    <div class="w-10 h-10 bg-white rounded-lg p-1">
                        <!-- Ensure this image is in your folder -->
                        <img src="./uploads/rent&go_logo.png" alt="Logo" class="w-full h-full object-contain"/>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tighter text-gray-900">Hansi Travels</span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#fleet" class="text-sm font-medium text-gray-500 hover:text-gray-900">Fleet</a>
                    <a href="#about" class="text-sm font-medium text-gray-500 hover:text-gray-900">About</a>
                    <a href="#contact" class="text-sm font-medium text-gray-500 hover:text-gray-900">Contact</a>
                </div>

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
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative max-w-7xl mx-auto mt-4 z-50" role="alert">
            <span class="block sm:inline"><?php echo $success; ?></span>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative max-w-7xl mx-auto mt-4 z-50" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
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
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#about" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                    Learn More
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

    <!-- About Section -->
    <div id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-blue-600 font-bold tracking-wide uppercase">Who We Are</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    About Hansi Travels
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                    Founded in 2024, Hansi Travels has revolutionized the car rental industry by combining technology with exceptional customer service. We believe in making mobility accessible, affordable, and hassle-free.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-10 rounded-3xl border border-blue-100 flex flex-col items-center text-center relative overflow-hidden">
                    <div class="bg-white p-4 rounded-full shadow-md mb-6 z-10">
                        <i data-lucide="eye" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 z-10">Our Vision</h3>
                    <p class="text-gray-600 leading-relaxed z-10">
                        To be the world's most customer-centric mobility company, where customers can find and rent any vehicle they might need for their journey.
                    </p>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-10 rounded-3xl border border-blue-100 flex flex-col items-center text-center relative overflow-hidden">
                    <div class="bg-white p-4 rounded-full shadow-md mb-6 z-10">
                        <i data-lucide="target" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 z-10">Our Mission</h3>
                    <p class="text-gray-600 leading-relaxed z-10">
                        We strive to offer the best rental experience by maintaining a premium fleet, offering transparent pricing, and ensuring customer safety.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fleet Section -->
    <div id="fleet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24 pt-12">
        <div class="flex flex-col lg:flex-row justify-between items-end mb-10 gap-6">
            <div class="w-full lg:w-auto">
                <h2 class="text-3xl font-extrabold text-gray-900">Premium Fleet</h2>
                <p class="text-gray-500 mt-2 text-lg">Choose from our wide range of vehicles</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto items-start sm:items-center">
                
                <!-- Sort Tabs -->
                <div class="flex bg-gray-100 p-1 rounded-lg self-start sm:self-auto">
                    <a href="?filter=<?php echo $current_filter; ?>&sort=default#fleet" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $current_sort == 'default' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                        Recommended
                    </a>
                    <a href="?filter=<?php echo $current_filter; ?>&sort=asc#fleet" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $current_sort == 'asc' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                        Price ↑
                    </a>
                    <a href="?filter=<?php echo $current_filter; ?>&sort=desc#fleet" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $current_sort == 'desc' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                        Price ↓
                    </a>
                </div>

                <!-- Filter Tabs -->
                <div class="flex bg-gray-100 p-1.5 rounded-xl overflow-x-auto w-full sm:w-auto no-scrollbar">
                    <?php 
                    $filters = ['all' => 'All', 'sedan' => 'Sedan', 'suv' => 'SUV', 'truck' => 'Truck', 'motorcycle' => 'Motorcycle'];
                    foreach ($filters as $key => $label): 
                    ?>
                        <a href="?filter=<?php echo $key; ?>&sort=<?php echo $current_sort; ?>#fleet" 
                           class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 whitespace-nowrap <?php echo $current_filter == $key ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($vehicles->num_rows > 0): ?>
                <?php while($row = $vehicles->fetch_assoc()): ?>
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
                        
                        <!-- VEHICLE IMAGE LOGIC START -->
                        <div class="h-56 bg-gray-50 flex items-center justify-center text-gray-300 relative group-hover:bg-blue-50/30 transition-colors duration-300 overflow-hidden">
                            <?php if(!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['model']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <i data-lucide="car" class="w-24 h-24"></i>
                            <?php endif; ?>
                            
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-green-100 text-green-800">Available</span>
                            </div>
                        </div>
                        <!-- VEHICLE IMAGE LOGIC END -->

                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900"><?php echo $row['make'] . ' ' . $row['model']; ?></h3>
                                    <?php if(!empty($row['vehicle_number'])): ?>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($row['vehicle_number']); ?></p>
                                    <?php endif; ?>
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

    <!-- Feedback & Testimonials Section -->
    <div class="py-20 bg-gray-50 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-blue-600 font-bold tracking-wide uppercase">Testimonials</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    What our customers say
                </p>
            </div>

            <!-- Display Feedbacks -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                <?php if ($feedbacks->num_rows > 0): ?>
                    <?php while($fb = $feedbacks->fetch_assoc()): ?>
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 relative">
                            <div class="flex gap-1 text-yellow-400 mb-4">
                                <?php for($i=0; $i<5; $i++): ?>
                                    <i data-lucide="star" class="w-4 h-4 <?php echo $i < $fb['rating'] ? 'fill-current' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-gray-600 mb-6 italic leading-relaxed">"<?php echo htmlspecialchars($fb['message']); ?>"</p>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                    <?php echo strtoupper(substr($fb['user_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($fb['user_name']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center text-gray-400">No feedback yet. Be the first!</div>
                <?php endif; ?>
            </div>

            <!-- Feedback Form -->
            <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="bg-blue-600 p-8 text-white text-center">
                    <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-4"></i>
                    <h3 class="text-2xl font-bold">Share your experience</h3>
                    <p class="text-blue-100 opacity-90">Your feedback helps us improve.</p>
                </div>
                <div class="p-8">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <select name="rating" class="w-full p-3 border rounded-xl bg-white">
                                <option value="5">5 Stars - Excellent</option>
                                <option value="4">4 Stars - Good</option>
                                <option value="3">3 Stars - Average</option>
                                <option value="2">2 Stars - Poor</option>
                                <option value="1">1 Star - Terrible</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Message</label>
                            <textarea name="message" rows="4" required class="w-full px-4 py-3 rounded-xl border border-gray-300" placeholder="Tell us about your trip..."></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition flex items-center justify-center gap-2">
                            Submit Feedback <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="text-center text-gray-500 py-4">
                            Please <button onclick="toggleModal('authModal')" class="text-blue-600 font-bold hover:underline">login</button> to leave feedback.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Contact Section -->
    <div id="contact" class="py-20 bg-white scroll-mt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-base text-blue-600 font-bold tracking-wide uppercase">Get in Touch</h2>
          <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
            Contact Us
          </p>
          <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
            Have questions? We're here to help 24/7.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
          <!-- Contact Form -->
          <div class="bg-gray-50 p-8 rounded-3xl border border-gray-100 shadow-sm">
            <form method="POST" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" name="name" required placeholder="John Doe" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" required placeholder="john@example.com" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea name="message" rows="4" required placeholder="How can we help you today?" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white"></textarea>
              </div>
              <button type="submit" name="send_contact" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg">
                Send Message
              </button>
            </form>
          </div>

          <!-- Info & WhatsApp -->
          <div class="flex flex-col justify-between gap-8">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-8 rounded-3xl text-white shadow-xl relative overflow-hidden">
              <h3 class="text-2xl font-bold mb-6 relative z-10">Contact Information</h3>
              <div class="space-y-6 relative z-10">
                <div class="flex items-start gap-4">
                  <i data-lucide="map-pin" class="w-6 h-6"></i>
                  <div>
                    <p class="font-semibold text-blue-100 text-sm uppercase tracking-wide mb-1">Address</p>
                    <p class="text-lg">123 Rental Avenue, Suite 456<br />Metropolis, NY 10012</p>
                  </div>
                </div>
                
                <div class="flex items-start gap-4">
                   <i data-lucide="phone" class="w-6 h-6"></i>
                  <div>
                    <p class="font-semibold text-blue-100 text-sm uppercase tracking-wide mb-1">Phone</p>
                    <p class="text-lg">+1 (555) 123-4567</p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                   <i data-lucide="mail" class="w-6 h-6"></i>
                  <div>
                    <p class="font-semibold text-blue-100 text-sm uppercase tracking-wide mb-1">Email</p>
                    <p class="text-lg">support@rentandgo.com</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-green-50 p-8 rounded-3xl border border-green-100 text-center flex-grow flex flex-col justify-center items-center shadow-sm hover:shadow-md transition-shadow">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 text-green-600">
                 <i data-lucide="message-circle" class="w-8 h-8"></i>
              </div>
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Chat with us</h3>
              <p class="text-gray-600 mb-6 max-w-xs mx-auto">Need immediate assistance? Our support team is available on WhatsApp.</p>
              <a href="https://wa.me/1234567890" target="_blank" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-full transition transform hover:scale-105 shadow-lg shadow-green-200">
                 <i data-lucide="message-circle" class="w-6 h-6"></i> Chat on WhatsApp
              </a>
            </div>
          </div>
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
            <p class="text-gray-500">&copy; 2024 Hansi Travels Inc. All rights reserved.</p>
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