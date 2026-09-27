<?php
session_start();
require_once '../config/database.php';

// Ensure the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

$guest_id = intval($_SESSION['guest_id']);

// Fetch the guest's booking history
$sql = "
    SELECT b.id as booking_id, b.check_in, b.check_out, b.total_price, b.status, b.created_at, 
           r.room_number, rt.type_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE b.customer_id = ? 
    ORDER BY b.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0c2340;
            --emerald: #10b981;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --bg-light: #f8fafc;
        }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: var(--text-dark); padding-top: 80px; }
        
        /* Navbar */
        .guest-navbar { background-color: var(--navy); padding: 15px 0; border-bottom: 3px solid var(--emerald); }
        .navbar-brand { color: #fff !important; font-weight: 800; font-size: 1.4rem; text-transform: uppercase; }
        .navbar-brand i { color: var(--emerald); margin-right: 10px; }
        .nav-link { color: #cbd5e1 !important; font-weight: 600; transition: color 0.3s; }
        .nav-link:hover { color: #fff !important; }
        
        /* Page Header */
        .portal-header { background: #fff; padding: 40px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }
        
        /* Booking Cards */
        .booking-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 20px; overflow: hidden; transition: transform 0.2s; }
        .booking-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .booking-header { background: #f1f5f9; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .booking-id { font-weight: 800; color: var(--navy); font-size: 1.1rem; }
        .booking-date { font-size: 0.85rem; color: var(--text-gray); font-weight: 500; }
        
        .booking-body { padding: 25px; }
        .room-name { font-size: 1.3rem; font-weight: 700; color: var(--navy); margin-bottom: 5px; }
        .room-number { font-size: 0.9rem; color: var(--text-gray); font-weight: 600; display: inline-block; background: var(--bg-light); padding: 4px 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #e2e8f0; }
        
        .stay-dates { display: flex; align-items: center; margin-bottom: 20px; }
        .date-box { flex: 1; }
        .date-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-gray); margin-bottom: 2px; }
        .date-value { font-size: 1rem; font-weight: 600; color: var(--text-dark); }
        .date-arrow { color: var(--emerald); padding: 0 15px; }
        
        .booking-footer { padding: 15px 25px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fafaf9; }
        .total-price { font-size: 1.4rem; font-weight: 800; color: var(--navy); }
        
        /* Badges */
        .status-badge { padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
        .status-pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .status-confirmed { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .status-checked-in { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg guest-navbar fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="rooms.php"><i class="fas fa-bed"></i>Afrik Hotel</a>
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#guestNav">
            <i class="fas fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="guestNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="rooms.php">Book a Room</a></li>
            </ul>
            <div class="ml-auto d-flex align-items-center">
                <span class="text-light mr-4 d-none d-lg-block">Welcome, <span class="text-white font-weight-bold"><?php echo htmlspecialchars($_SESSION['guest_name']); ?></span></span>
                <a href="logout.php" class="nav-link text-danger font-weight-bold"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="portal-header">
    <div class="container">
        <h2 class="font-weight-bold text-dark mb-2" style="font-size: 2.2rem;">My Bookings</h2>
        <p class="text-muted mb-0" style="font-size: 1.1rem;">Manage and view your reservation history.</p>
    </div>
</div>

<div class="container pb-5">
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success font-weight-bold mb-4 border-0 shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i> Your booking has been successfully placed! Our team will confirm it shortly.
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($bookings->num_rows > 0): ?>
            <?php while($row = $bookings->fetch_assoc()): ?>
                <div class="col-lg-6">
                    <div class="booking-card">
                        <div class="booking-header">
                            <span class="booking-id">Order #<?php echo str_pad($row['booking_id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="booking-date">Booked on <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="booking-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="room-name"><?php echo htmlspecialchars($row['type_name']); ?></h4>
                                    <span class="room-number"><i class="fas fa-key text-muted mr-1"></i> Room <?php echo htmlspecialchars($row['room_number']); ?></span>
                                </div>
                                <?php 
                                    $statusClass = 'status-pending';
                                    if(in_array($row['status'], ['Confirmed'])) $statusClass = 'status-confirmed';
                                    if(in_array($row['status'], ['Checked-in', 'Checked-out'])) $statusClass = 'status-checked-in';
                                    if(in_array($row['status'], ['Cancelled'])) $statusClass = 'status-cancelled';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </div>
                            
                            <div class="stay-dates mt-2">
                                <div class="date-box">
                                    <div class="date-label">Check-In</div>
                                    <div class="date-value"><i class="far fa-calendar-alt text-muted mr-1"></i> <?php echo date('D, M d, Y', strtotime($row['check_in'])); ?></div>
                                </div>
                                <div class="date-arrow"><i class="fas fa-long-arrow-alt-right"></i></div>
                                <div class="date-box text-right">
                                    <div class="date-label">Check-Out</div>
                                    <div class="date-value"><i class="far fa-calendar-alt text-muted mr-1"></i> <?php echo date('D, M d, Y', strtotime($row['check_out'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-footer">
                            <span class="text-muted font-weight-bold text-uppercase small">Total Amount</span>
                            <span class="total-price">$<?php echo number_format($row['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 my-5">
                <div style="width: 100px; height: 100px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                    <i class="fas fa-calendar-times fa-3x text-secondary"></i>
                </div>
                <h3 class="font-weight-bold text-dark">No Bookings Yet</h3>
                <p class="text-muted mb-4" style="font-size: 1.1rem;">You haven't made any reservations with us yet.</p>
                <a href="rooms.php" class="btn btn-primary font-weight-bold px-4 py-2" style="background-color: #10b981; border: none; border-radius: 8px;">Browse Available Rooms</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>