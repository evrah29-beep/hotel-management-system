<?php
session_start();
require_once '../config/database.php';

// Ensure the guest is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

$guest_id = intval($_SESSION['guest_id']);
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$error = '';

// Fetch Room Details
$stmt = $conn->prepare("SELECT r.room_number, rt.type_name, rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ? AND r.status = 'Available'");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room_result = $stmt->get_result();

if ($room_result->num_rows === 0) {
    // Room doesn't exist or is already booked
    header("Location: rooms.php");
    exit();
}
$room = $room_result->fetch_assoc();
$price_per_night = $room['price_per_night'];

// Handle the Booking Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $payment_method = $_POST['payment_method'];
    $payment_number = trim($_POST['payment_number']); // For EVC Plus / Mobile Money
    
    // Calculate Total Price securely on the server
    $datetime1 = new DateTime($check_in);
    $datetime2 = new DateTime($check_out);
    $interval = $datetime1->diff($datetime2);
    $nights = $interval->days;
    
    if ($nights < 1) {
        $error = "Check-out date must be after check-in date.";
    } else {
        $total_price = $nights * $price_per_night;
        
        // 1. Insert into bookings table
        $book_stmt = $conn->prepare("INSERT INTO bookings (customer_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $book_stmt->bind_param("iissd", $guest_id, $room_id, $check_in, $check_out, $total_price);
        
        if ($book_stmt->execute()) {
            $booking_id = $conn->insert_id;
            
            // 2. Insert into payments table
            $pay_stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, ?, 'Pending')");
            $pay_stmt->bind_param("ids", $booking_id, $total_price, $payment_method);
            $pay_stmt->execute();
            
            // 3. Update room status so no one else can book it
            $conn->query("UPDATE rooms SET status = 'Booked' WHERE id = $room_id");
            
            // Success! Redirect to their booking history
            header("Location: my-bookings.php?success=1");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1e293b; padding-top: 80px; }
        .guest-navbar { background-color: #0c2340; padding: 15px 0; border-bottom: 3px solid #10b981; }
        .navbar-brand { color: #fff !important; font-weight: 800; font-size: 1.4rem; text-transform: uppercase; }
        .navbar-brand i { color: #10b981; margin-right: 10px; }
        .nav-link { color: #cbd5e1 !important; font-weight: 600; }
        
        .checkout-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0; }
        .section-title { font-size: 1.2rem; font-weight: 800; color: #0c2340; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        
        .form-control, .custom-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; height: auto; font-weight: 500; }
        .form-control:focus, .custom-select:focus { border-color: #10b981; box-shadow: 0 0 0 0.2rem rgba(16,185,129,0.25); }
        
        .summary-box { background: #0c2340; color: #fff; border-radius: 12px; padding: 30px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; color: #cbd5e1; }
        .summary-row.total { border-top: 1px solid rgba(255,255,255,0.1); margin-top: 15px; padding-top: 15px; font-size: 1.4rem; font-weight: 800; color: #fff; }
        
        .btn-emerald { background-color: #10b981; color: #fff; border-radius: 8px; font-weight: 700; padding: 14px; width: 100%; border: none; font-size: 1.1rem; transition: all 0.3s; }
        .btn-emerald:hover { background-color: #059669; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(16,185,129,0.3); }
        
        .payment-option { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
        .payment-option:hover { border-color: #10b981; background: #f0fdf4; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg guest-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand" href="rooms.php"><i class="fas fa-bed"></i>Afrik Hotel</a>
        <div class="ml-auto d-flex align-items-center">
            <a href="rooms.php" class="nav-link"><i class="fas fa-arrow-left mr-1"></i> Back to Rooms</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="font-weight-bold text-dark mb-4">Complete Your Booking</h2>
    
    <?php if($error): ?>
        <div class="alert alert-danger font-weight-bold"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <!-- Left Column: Forms -->
            <div class="col-lg-7 mb-4">
                <div class="checkout-card mb-4">
                    <h4 class="section-title">1. Stay Details</h4>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="text-muted font-weight-bold small text-uppercase">Check-In Date</label>
                            <input type="date" name="check_in" id="check_in" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required onchange="calculateTotal()">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-muted font-weight-bold small text-uppercase">Check-Out Date</label>
                            <input type="date" name="check_out" id="check_out" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required onchange="calculateTotal()">
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <h4 class="section-title">2. Payment Method</h4>
                    
                    <div class="custom-control custom-radio payment-option">
                        <input type="radio" id="pay_evc" name="payment_method" class="custom-control-input" value="EVC Plus" checked onchange="togglePhoneInput()">
                        <label class="custom-control-label font-weight-bold w-100" style="cursor:pointer;" for="pay_evc">
                            EVC Plus / Mobile Money <i class="fas fa-mobile-alt float-right text-success mt-1"></i>
                        </label>
                    </div>
                    
                    <div class="custom-control custom-radio payment-option">
                        <input type="radio" id="pay_card" name="payment_method" class="custom-control-input" value="Credit Card" onchange="togglePhoneInput()">
                        <label class="custom-control-label font-weight-bold w-100" style="cursor:pointer;" for="pay_card">
                            Credit / Debit Card <i class="fas fa-credit-card float-right text-primary mt-1"></i>
                        </label>
                    </div>

                    <div id="phone_input_group" class="form-group mt-3">
                        <label class="text-muted font-weight-bold small text-uppercase">Mobile Money Number</label>
                        <input type="text" name="payment_number" class="form-control" placeholder="e.g. 061XXXXXXX">
                        <small class="text-muted">A payment prompt will be sent to this number.</small>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="col-lg-5">
                <div class="summary-box sticky-top" style="top: 100px;">
                    <h4 class="font-weight-bold text-white mb-4">Reservation Summary</h4>
                    
                    <div class="summary-row">
                        <span class="font-weight-bold text-white"><?php echo htmlspecialchars($room['type_name']); ?></span>
                        <span class="badge badge-light text-dark">Room <?php echo $room['room_number']; ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Price per night</span>
                        <span>$<?php echo number_format($price_per_night, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Total Nights</span>
                        <span id="display_nights">1</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total Due</span>
                        <span id="display_total">$<?php echo number_format($price_per_night, 2); ?></span>
                    </div>
                    
                    <button type="submit" name="confirm_booking" class="btn btn-emerald mt-4">Confirm & Pay</button>
                    
                    <p class="text-center mt-3 mb-0" style="font-size: 0.8rem; color: #94a3b8;"><i class="fas fa-lock mr-1"></i> Secure Encrypted Checkout</p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const pricePerNight = <?php echo $price_per_night; ?>;
    
    function calculateTotal() {
        const checkIn = new Date(document.getElementById('check_in').value);
        const checkOut = new Date(document.getElementById('check_out').value);
        
        if (checkOut > checkIn) {
            const diffTime = Math.abs(checkOut - checkIn);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            
            document.getElementById('display_nights').innerText = diffDays;
            document.getElementById('display_total').innerText = '$' + (diffDays * pricePerNight).toFixed(2);
        } else {
            document.getElementById('display_nights').innerText = '0';
            document.getElementById('display_total').innerText = '$0.00';
        }
    }

    function togglePhoneInput() {
        const evcSelected = document.getElementById('pay_evc').checked;
        const phoneGroup = document.getElementById('phone_input_group');
        if(evcSelected) {
            phoneGroup.style.display = 'block';
        } else {
            phoneGroup.style.display = 'none';
        }
    }
</script>
</body>
</html>