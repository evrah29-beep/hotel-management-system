<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/customers/customers.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Customer ID.");
}

$customer_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer_result = $stmt->get_result();

if ($customer_result->num_rows === 0) die("Guest not found.");
$customer = $customer_result->fetch_assoc();

$history_query = "
    SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.status as booking_status, b.created_at,
           r.room_number, rt.type_name,
           p.status as payment_status
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
";
$hist_stmt = $conn->prepare($history_query);
$hist_stmt->bind_param("i", $customer_id);
$hist_stmt->execute();
$history_result = $hist_stmt->get_result();

$total_spent = 0;
$total_outstanding = 0;
$total_bookings = $history_result->num_rows;

$bookings_data = [];
if ($total_bookings > 0) {
    while($booking = $history_result->fetch_assoc()) {
        $bookings_data[] = $booking;
        if ($booking['booking_status'] !== 'Cancelled') {
            $total_spent += $booking['total_price'];
            if ($booking['payment_status'] !== 'Paid' && $booking['payment_status'] !== 'Completed') {
                $total_outstanding += $booking['total_price'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Dossier - <?php echo htmlspecialchars($customer['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff; color: #1e293b; padding: 20px; }
        
        .brand-icon { color: #10b981; font-size: 2.2rem; margin-right: 10px; }
        .hotel-name { font-size: 2rem; font-weight: 800; color: #0c2340; letter-spacing: -0.5px; margin: 0; line-height: 1; }
        .doc-title { font-size: 1.5rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 2px; text-align: right; margin: 0; }
        .header-divider { height: 4px; background: #10b981; margin: 25px 0; border-radius: 2px; }
        
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; height: 100%; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 5px; display: block; }
        .info-value { font-size: 1rem; font-weight: 600; color: #0c2340; margin-bottom: 15px; }
        
        .finance-box { background: #0c2340; color: #fff; border-radius: 8px; padding: 20px; height: 100%; display: flex; flex-direction: column; justify-content: center;}
        .fin-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .fin-row:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; font-size: 1.2rem; color: #fff; font-weight: 700; }
        .fin-val { font-weight: 700; color: #fff; }
        .text-emerald { color: #10b981 !important; }
        .text-rose { color: #f43f5e !important; }

        .table-title { font-size: 1.1rem; font-weight: 800; color: #0c2340; margin: 40px 0 15px 0; text-transform: uppercase; }
        .table th { background-color: #f1f5f9; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-top: none; border-bottom: 2px solid #cbd5e1; }
        .table td { vertical-align: middle; font-size: 0.9rem; font-weight: 500; border-bottom: 1px solid #e2e8f0; padding: 12px; }
        
        .badge-print { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; border: 1px solid; }
        .bp-paid { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .bp-unpaid { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }
        
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div class="row align-items-center">
    <div class="col-6 d-flex align-items-center">
        <i class="fas fa-bed brand-icon"></i>
        <div>
            <h1 class="hotel-name">Afrik Hotel</h1>
            <span class="text-muted small font-weight-bold">Mogadishu, Somalia</span>
        </div>
    </div>
    <div class="col-6">
        <h2 class="doc-title">Guest Dossier</h2>
        <p class="text-muted small text-right mb-0 mt-1 font-weight-bold">Date: <?php echo date('M d, Y'); ?></p>
    </div>
</div>

<div class="header-divider"></div>

<div class="row mb-4">
    <div class="col-7 mb-3 mb-md-0">
        <div class="info-box">
            <div class="row">
                <div class="col-6">
                    <span class="info-label">Guest Name</span>
                    <div class="info-value"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                    <span class="info-label">Guest ID</span>
                    <div class="info-value">#<?php echo str_pad($customer['id'], 5, '0', STR_PAD_LEFT); ?></div>
                    <span class="info-label">ID / Passport No.</span>
                    <div class="info-value"><?php echo htmlspecialchars($customer['id_number'] ?: 'Not Provided'); ?></div>
                </div>
                <div class="col-6">
                    <span class="info-label">Phone Number</span>
                    <div class="info-value"><?php echo htmlspecialchars($customer['phone']); ?></div>
                    <span class="info-label">Email Address</span>
                    <div class="info-value"><?php echo htmlspecialchars($customer['email'] ?: 'Not Provided'); ?></div>
                    <span class="info-label">Nationality</span>
                    <div class="info-value"><?php echo htmlspecialchars($customer['nationality'] ?: 'Not Provided'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-5">
        <div class="finance-box">
            <span class="info-label text-white opacity-75 mb-3">Account Summary</span>
            <div class="fin-row">
                <span>Total Bookings</span>
                <span class="fin-val"><?php echo $total_bookings; ?></span>
            </div>
            <div class="fin-row">
                <span>Total Billed</span>
                <span class="fin-val">$<?php echo number_format($total_spent, 2); ?></span>
            </div>
            <div class="fin-row mt-2 pt-2 border-top">
                <span>Outstanding Balance</span>
                <span class="fin-val <?php echo ($total_outstanding > 0) ? 'text-rose' : 'text-emerald'; ?>">
                    $<?php echo number_format($total_outstanding, 2); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<h3 class="table-title"><i class="fas fa-list-alt text-muted mr-2"></i> Reservation History</h3>
<div class="table-responsive">
    <table class="table w-100">
        <thead>
            <tr>
                <th>Order</th>
                <th>Dates</th>
                <th>Room</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_bookings > 0): ?>
                <?php foreach($bookings_data as $b): 
                    $pay_status = $b['payment_status'] ?? 'Unpaid';
                    $pay_class = ($pay_status == 'Paid' || $pay_status == 'Completed') ? 'bp-paid' : 'bp-unpaid';
                    $text_muted = ($b['booking_status'] === 'Cancelled') ? 'text-muted' : 'text-dark';
                ?>
                <tr class="<?php echo $text_muted; ?>">
                    <td>
                        <strong>#<?php echo str_pad($b['booking_id'], 5, '0', STR_PAD_LEFT); ?></strong><br>
                        <span style="font-size: 0.75rem;"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></span>
                    </td>
                    <td>
                        <span class="d-block">In: <?php echo date('M d, Y', strtotime($b['check_in'])); ?></span>
                        <span class="d-block">Out: <?php echo date('M d, Y', strtotime($b['check_out'])); ?></span>
                    </td>
                    <td>
                        <strong>Room <?php echo htmlspecialchars($b['room_number']); ?></strong><br>
                        <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($b['type_name']); ?></span>
                    </td>
                    <td><strong>$<?php echo number_format($b['total_price'], 2); ?></strong></td>
                    <td><?php echo htmlspecialchars($b['booking_status']); ?></td>
                    <td>
                        <span class="badge-print <?php echo $pay_class; ?>"><?php echo htmlspecialchars($pay_status); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted font-weight-bold">No reservation history found for this guest.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="text-center mt-5 pt-4 border-top" style="border-color: #f1f5f9 !important;">
    <p class="font-weight-bold text-dark mb-1">End of Guest Dossier</p>
    <p class="small text-muted">Generated by Afrik Hotel Management System</p>
</div>

</body>
</html>