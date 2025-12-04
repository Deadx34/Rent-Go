<?php
// invoice.php
// Generates a simple invoice for a rental

require 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get rental ID from query
$rental_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rental = $conn->query("SELECT r.*, v.make, v.model, v.vehicle_number, u.name as user_name, u.email FROM rentals r JOIN vehicles v ON r.vehicle_id = v.id JOIN users u ON r.user_id = u.id WHERE r.id = $rental_id")->fetch_assoc();

if (!$rental || $rental['user_id'] != $_SESSION['user_id']) {
    echo "Invoice not found or access denied.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $rental_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; }
        .invoice-box { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        h1 { font-size: 2em; margin-bottom: 0.5em; }
        table { width: 100%; margin-top: 1em; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #eee; }
        .total { font-weight: bold; font-size: 1.2em; }
        .right { text-align: right; }
        .center { text-align: center; }
        .btn { display: inline-block; margin-top: 2em; padding: 10px 20px; background: #007bff; color: #fff; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <h1>Invoice</h1>
        <p><strong>Invoice #: </strong><?php echo $rental_id; ?><br>
           <strong>Date: </strong><?php echo date('Y-m-d', strtotime($rental['created_at'])); ?><br>
           <strong>Customer: </strong><?php echo htmlspecialchars($rental['user_name']); ?><br>
           <strong>Email: </strong><?php echo htmlspecialchars($rental['email']); ?></p>
        <table>
            <tr><th>Vehicle</th><th>Number</th><th>Period</th><th class="right">Total</th></tr>
            <tr>
                <td><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></td>
                <td><?php echo htmlspecialchars($rental['vehicle_number']); ?></td>
                <td><?php echo htmlspecialchars($rental['start_date'] . ' to ' . $rental['end_date']); ?></td>
                <td class="right">$<?php echo number_format($rental['total_cost'], 2); ?></td>
            </tr>
        </table>
        <p class="center total">Amount Due: $<?php echo number_format($rental['total_cost'], 2); ?></p>
        <a href="#" onclick="window.print()" class="btn">Print Invoice</a>
    </div>
</body>
</html>
