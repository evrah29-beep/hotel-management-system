<?php
session_start();
require_once '../config/database.php';

// Ensure the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

$guest_id = intval($_SESSION['guest_id']);
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id === 0) {
    die("Invalid Invoice ID.");
}

// Fetch complete booking, room, payment, and customer info
$query = "
    SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.created_at,
           r.room_number, rt.type_name, rt.price_per_night,
           c.full_name, c.email, c.phone,
           p.payment_method, p.status as payment_status
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.customer_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invoice not found or access denied.");
}

$invoice = $result->fetch_assoc();

// Calculate Total Nights
$date1 = new DateTime($invoice['check_in']);
$date2 = new DateTime($invoice['check_out']);
$nights = $date1->diff($date2)->days;
if ($nights == 0) $nights = 1; // Fallback for day-use
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($invoice['booking_id'], 5, '0', STR_PAD_LEFT); ?> - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; padding: 40px 0; }
        .invoice-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 50px; max-width: 800px; margin: 0 auto; }
        .brand-icon { color: #10b981; font-size: 2rem; margin-right: 10px; }
        .invoice-title { font-size: 2.5rem; font-weight: 800; color: #0c2340; text-transform: uppercase; letter-spacing: 1px; }
        .text-emerald { color: #10b981 !important; }
        .table th { background-color: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; border-top: none; border-bottom: 2px solid #e2e8f0; }
        .table td { border-bottom: 1px solid #e2e8f0; vertical-align: middle; padding: 15px 12px; font-weight: 600; }
        .total-row td { font-size: 1.25rem; font-weight: 800; color: #0c2340; border-bottom: none; border-top: 2px solid #0c2340; }
        
        /* Print Styles - Hides backgrounds and buttons for perfect paper printing */
        @media print {
            body { background-color: #fff; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Buttons (Hidden when printing) -->
    <div class="d-flex justify-content-between mb-4 max-w-800 mx-auto no-print" style="max-width: 800px;">
        <a href="my-bookings.php" class="btn btn-light border font-weight-bold shadow-sm"><i class="fas fa-arrow-left mr-2"></i> Back to Bookings</a>
        <button onclick="window.print()" class="btn btn-primary font-weight-bold shadow-sm" style="background-color: #0c2340; border: none;"><i class="fas fa-print mr-2"></i> Print Invoice</button>
    </div>

    <!-- Printable Invoice Box -->
    <div class="invoice-card">
        <div class="row mb-5">
            <div class="col-sm-6">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-bed brand-icon"></i>
                    <h2 class="font-weight-bold mb-0 text-dark">Afrik Hotel</h2>
                </div>
                <p class="text-muted small mb-0">Maka Al-Mukarama Road</p>
                <p class="text-muted small mb-0">Mogadishu, Banadir, Somalia</p>
                <p class="text-muted small mb-0">+252 61X XXX XXX | info@afrikhotel.com</p>
            </div>
            <div class="col-sm-6 text-sm-right mt-4 mt-sm-0">
                <h1 class="invoice-title">Invoice</h1>
                <p class="font-weight-bold text-muted mb-0">#INV-<?php echo str_pad($invoice['booking_id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p class="small text-muted mb-0">Date: <?php echo date('F d, Y'); ?></p>
                
                <div class="mt-3">
                    <?php if($invoice['payment_status'] == 'Paid' || $invoice['payment_status'] == 'Completed'): ?>
                        <span class="badge badge-success px-3 py-2" style="font-size: 1rem;"><i class="fas fa-check-circle mr-1"></i> PAID IN FULL</span>
                    <?php else: ?>
                        <span class="badge badge-warning text-dark px-3 py-2" style="font-size: 1rem;"><i class="fas fa-clock mr-1"></i> PAYMENT PENDING</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-sm-6 mb-4 mb-sm-0">
                <h6 class="text-uppercase text-muted font-weight-bold small mb-2">Billed To:</h6>
                <h5 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($invoice['full_name']); ?></h5>
                <p class="text-muted small mb-0"><i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($invoice['email'] ?? 'N/A'); ?></p>
                <p class="text-muted small mb-0"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($invoice['phone']); ?></p>
            </div>
            <div class="col-sm-6 text-sm-right">
                <h6 class="text-uppercase text-muted font-weight-bold small mb-2">Reservation Details:</h6>
                <p class="text-dark small font-weight-bold mb-1">Check-in: <span class="text-muted"><?php echo date('M d, Y', strtotime($invoice['check_in'])); ?></span></p>
                <p class="text-dark small font-weight-bold mb-1">Check-out: <span class="text-muted"><?php echo date('M d, Y', strtotime($invoice['check_out'])); ?></span></p>
                <p class="text-dark small font-weight-bold mb-0">Payment Method: <span class="text-muted"><?php echo htmlspecialchars($invoice['payment_method']); ?></span></p>
            </div>
        </div>

        <div class="table-responsive mb-5">
            <table class="table w-100">
                <thead>
                    <tr>
                        <th class="w-50">Accommodation</th>
                        <th class="text-center">Nights</th>
                        <th class="text-right">Price/Night</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($invoice['type_name']); ?></div>
                            <div class="small text-muted">Room Number: <?php echo htmlspecialchars($invoice['room_number']); ?></div>
                        </td>
                        <td class="text-center"><?php echo $nights; ?></td>
                        <td class="text-right">$<?php echo number_format($invoice['price_per_night'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($nights * $invoice['price_per_night'], 2); ?></td>
                    </tr>
                    
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Total Amount Due:</td>
                        <td class="text-right text-emerald">$<?php echo number_format($invoice['total_price'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5 pt-4 border-top" style="border-color: #f1f5f9 !important;">
            <p class="font-weight-bold text-dark mb-1">Thank you for choosing Afrik Hotel.</p>
            <p class="small text-muted">If you have any questions concerning this invoice, contact our reception at info@afrikhotel.com.</p>
        </div>
    </div>
</div>

</body>
</html>