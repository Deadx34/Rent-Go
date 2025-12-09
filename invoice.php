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
$rental = $conn->query("SELECT r.*, v.make, v.model, v.vehicle_number, v.price_per_day, u.name as user_name, u.email, d.name as driver_name, d.rate_per_day as driver_rate FROM rentals r JOIN vehicles v ON r.vehicle_id = v.id JOIN users u ON r.user_id = u.id LEFT JOIN drivers d ON r.driver_id = d.id WHERE r.id = $rental_id")->fetch_assoc();

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 20px; }
        .invoice-box { max-width: 700px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        h1 { font-size: 2.5em; margin: 0; color: #007bff; }
        .company-info { margin-top: 10px; color: #666; font-size: 0.9em; }
        .invoice-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-section { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .info-section strong { display: block; color: #007bff; margin-bottom: 5px; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .right { text-align: right; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-row { display: flex; justify-content: flex-end; margin: 10px 0; }
        .total-row span:first-child { margin-right: 50px; font-weight: 500; }
        .grand-total { font-size: 1.5em; font-weight: bold; color: #007bff; border-top: 2px solid #007bff; padding-top: 10px; margin-top: 10px; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 0.9em; }
        .btn-group { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; margin: 0 10px; padding: 12px 30px; background: #007bff; color: #fff; border-radius: 5px; text-decoration: none; cursor: pointer; border: none; font-size: 1em; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9em; margin-left: 10px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        @media print { .btn-group { display: none; } }
    </style>
</head>
<body>
    <div class="invoice-box" id="invoice">
        <div class="header">
            <h1>INVOICE</h1>
            <div class="company-info">
                <strong>Hansi Travels - Premium Car Rental</strong><br>
                Email: info@hansitravels.com | Phone: +94 77 123 4567
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="info-section">
                <strong>Invoice Details</strong>
                Invoice #: <?php echo str_pad($rental_id, 6, '0', STR_PAD_LEFT); ?><br>
                Date: <?php echo date('F d, Y', strtotime($rental['created_at'])); ?>
            </div>
            <div class="info-section">
                <strong>Customer Details</strong>
                <?php echo htmlspecialchars($rental['user_name']); ?><br>
                <?php echo htmlspecialchars($rental['email']); ?>
            </div>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong style="color: #007bff;">Payment Information</strong><br>
            Method: <strong><?php echo $rental['payment_method'] === 'pay_at_pickup' ? 'Pay at Pickup' : 'Online Payment (Stripe)'; ?></strong>
            <span class="status-badge <?php echo $rental['payment_status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>">
                <?php echo $rental['payment_status'] === 'paid' ? '✓ PAID' : '⏳ PAYMENT PENDING'; ?>
            </span>
            <?php if($rental['payment_status'] === 'pending'): ?>
                <br><small style="color: #856404; margin-top: 5px; display: block;">⚠️ Please bring payment when collecting the vehicle</small>
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Period</th>
                    <th>Rate</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
                $days = $days > 0 ? $days : 1;
                $vehicle_cost = $days * $rental['price_per_day'];
                ?>
                <tr>
                    <td><strong>Vehicle Rental</strong><br><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?><br><small>Reg: <?php echo htmlspecialchars($rental['vehicle_number']); ?></small></td>
                    <td><?php echo date('M d', strtotime($rental['start_date'])) . ' - ' . date('M d, Y', strtotime($rental['end_date'])); ?><br><small><?php echo $days; ?> day(s)</small></td>
                    <td>LKR <?php echo number_format($rental['price_per_day'], 2); ?>/day</td>
                    <td class="right">LKR <?php echo number_format($vehicle_cost, 2); ?></td>
                </tr>
                <?php if($rental['driver_name']): 
                    $driver_cost = $days * $rental['driver_rate'];
                ?>
                <tr>
                    <td><strong>Driver Service</strong><br><?php echo htmlspecialchars($rental['driver_name']); ?></td>
                    <td><?php echo $days; ?> day(s)</td>
                    <td>LKR <?php echo number_format($rental['driver_rate'], 2); ?>/day</td>
                    <td class="right">LKR <?php echo number_format($driver_cost, 2); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>LKR <?php echo number_format($rental['total_cost'], 2); ?></span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing Hansi Travels!<br>
            For any inquiries, please contact us at info@hansitravels.com</p>
        </div>
    </div>
    
    <div class="btn-group">
        <button onclick="window.print()" class="btn">🖨️ Print Invoice</button>
        <button onclick="downloadPDF()" class="btn btn-success">📥 Download PDF</button>
        <a href="index.php" class="btn" style="background: #6c757d;">← Back to Home</a>
    </div>
    
    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice');
            const opt = {
                margin: 10,
                filename: 'Invoice_<?php echo str_pad($rental_id, 6, '0', STR_PAD_LEFT); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
