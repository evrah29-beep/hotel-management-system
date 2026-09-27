<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
// Ensure only allowed staff can access this page
requirePermission('admin/bookings/bookings.php');

$success_msg = '';
$error_msg = '';

// 1. Handle Status Update (Existing Feature)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    $room_id = intval($_POST['room_id']);

    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $success_msg = "Booking #$booking_id successfully updated to $new_status.";
        
        // Auto-manage Room Status based on the booking state
        if ($new_status === 'Cancelled' || $new_status === 'Checked-out') {
            $conn->query("UPDATE rooms SET status = 'Available' WHERE id = $room_id");
        } elseif ($new_status === 'Confirmed' || $new_status === 'Checked-in' || $new_status === 'Pending') {
            $conn->query("UPDATE rooms SET status = 'Booked' WHERE id = $room_id");
        }
    }
}

// 2. Handle New Booking Creation (New Feature)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_booking'])) {
    $customer_id = intval($_POST['customer_id']);
    $room_id = intval($_POST['room_id']);
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $payment_method = $_POST['payment_method'];

    // Calculate the total nights
    $datetime1 = new DateTime($check_in);
    $datetime2 = new DateTime($check_out);
    $interval = $datetime1->diff($datetime2);
    $nights = $interval->days;
    
    if ($nights < 1) $nights = 1; // Minimum 1 night

    // Fetch the price per night for the selected room
    $price_stmt = $conn->query("SELECT rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = $room_id");
    if ($price_stmt && $price_stmt->num_rows > 0) {
        $room_price = $price_stmt->fetch_assoc()['price_per_night'];
        $total_price = $nights * $room_price;

        // Insert the new booking
        $book_stmt = $conn->prepare("INSERT INTO bookings (customer_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, 'Confirmed')");
        $book_stmt->bind_param("iissd", $customer_id, $room_id, $check_in, $check_out, $total_price);
        
        if ($book_stmt->execute()) {
            $new_booking_id = $conn->insert_id;
            
            // Insert the payment record
            $pay_stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, ?, 'Paid')");
            $pay_stmt->bind_param("ids", $new_booking_id, $total_price, $payment_method);
            $pay_stmt->execute();
            
            // Update the room to booked
            $conn->query("UPDATE rooms SET status = 'Booked' WHERE id = $room_id");
            
            $success_msg = "New booking successfully created for Room!";
        } else {
            $error_msg = "Failed to create the booking. Please try again.";
        }
    }
}

// Fetch data for the Add Booking Modal
$available_rooms = $conn->query("SELECT r.id, r.room_number, rt.type_name, rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.status = 'Available'");
$customers = $conn->query("SELECT id, full_name, phone FROM customers ORDER BY full_name ASC");

// Fetch all bookings for the main table
$query = "
    SELECT b.id, b.check_in, b.check_out, b.total_price, b.status, b.created_at, b.room_id,
           c.full_name, c.phone, 
           r.room_number, rt.type_name,
           p.payment_method, p.status as payment_status
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN payments p ON p.booking_id = b.id
    ORDER BY b.created_at DESC
";
$bookings_result = $conn->query($query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Manage Bookings</h1>
            <p class="text-muted small">View incoming reservations and update their statuses.</p>
        </div>
        <!-- Add New Booking Button -->
        <button class="btn btn-success shadow-sm font-weight-bold" data-toggle="modal" data-target="#addBookingModal" style="border-radius: 8px; background-color: #10b981; border: none;">
            <i class="fas fa-plus mr-2"></i> Add New Booking
        </button>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm border-0" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div class="alert alert-danger font-weight-bold shadow-sm border-0" style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="pl-4 py-3">Order ID</th>
                            <th>Guest Info</th>
                            <th>Room</th>
                            <th>Stay Dates</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-right pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($bookings_result && $bookings_result->num_rows > 0): ?>
                            <?php while($row = $bookings_result->fetch_assoc()): ?>
                            <tr>
                                <td class="pl-4 font-weight-bold text-dark">
                                    #<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?><br>
                                    <small class="text-muted"><?php echo date('M d, g:i A', strtotime($row['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></div>
                                    <div class="small text-muted"><i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-light border text-dark px-2 py-1 mb-1">Room <?php echo $row['room_number']; ?></span><br>
                                    <small class="text-muted font-weight-bold"><?php echo htmlspecialchars($row['type_name']); ?></small>
                                </td>
                                <td class="small font-weight-bold">
                                    <div class="text-success">IN: <?php echo date('M d, Y', strtotime($row['check_in'])); ?></div>
                                    <div class="text-danger">OUT: <?php echo date('M d, Y', strtotime($row['check_out'])); ?></div>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark">$<?php echo number_format($row['total_price'], 2); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['payment_method']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $bg = 'badge-secondary';
                                        if($row['status'] == 'Pending') $bg = 'badge-warning text-dark';
                                        if($row['status'] == 'Confirmed') $bg = 'badge-success';
                                        if($row['status'] == 'Checked-in') $bg = 'badge-info';
                                        if($row['status'] == 'Checked-out') $bg = 'badge-dark';
                                        if($row['status'] == 'Cancelled') $bg = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $bg; ?> px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;"><?php echo $row['status']; ?></span>
                                </td>
                                <td class="text-right pr-4">
                                    <button class="btn btn-sm btn-light border text-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#updateModal<?php echo $row['id']; ?>">
                                        Update
                                    </button>
                                </td>
                            </tr>

                            <!-- Update Status Modal for this specific booking -->
                            <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0" style="border-radius: 12px;">
                                        <div class="modal-header bg-light border-0">
                                            <h5 class="modal-title font-weight-bold">Update Order #<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="room_id" value="<?php echo $row['room_id']; ?>">
                                                
                                                <div class="alert alert-info small mb-4">
                                                    <i class="fas fa-info-circle mr-1"></i> Marking this as <strong>Cancelled</strong> or <strong>Checked-out</strong> will automatically return Room <?php echo $row['room_number']; ?> to Available status on the website.
                                                </div>

                                                <div class="form-group">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Booking Status</label>
                                                    <select name="status" class="form-control font-weight-bold">
                                                        <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                        <option value="Confirmed" <?php if($row['status'] == 'Confirmed') echo 'selected'; ?>>Confirmed</option>
                                                        <option value="Checked-in" <?php if($row['status'] == 'Checked-in') echo 'selected'; ?>>Checked-in</option>
                                                        <option value="Checked-out" <?php if($row['status'] == 'Checked-out') echo 'selected'; ?>>Checked-out</option>
                                                        <option value="Cancelled" <?php if($row['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-light">
                                                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                                                <button type="submit" name="update_status" class="btn btn-primary px-4" style="background-color: #10b981; border: none;">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No bookings found in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD NEW BOOKING MODAL -->
<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background-color: #0c2340; color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-calendar-plus mr-2 text-success"></i> Create New Booking</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Guest Selection -->
                        <div class="col-md-6 form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Select Registered Guest</label>
                            <select name="customer_id" class="form-control" required>
                                <option value="">-- Choose Guest --</option>
                                <?php if($customers && $customers->num_rows > 0): ?>
                                    <?php while($c = $customers->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name'] . ' (' . $c['phone'] . ')'); ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>No guests found! Please register a guest first.</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Room Selection -->
                        <div class="col-md-6 form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Select Available Room</label>
                            <select name="room_id" class="form-control" required>
                                <option value="">-- Choose Room --</option>
                                <?php if($available_rooms && $available_rooms->num_rows > 0): ?>
                                    <?php while($r = $available_rooms->fetch_assoc()): ?>
                                        <option value="<?php echo $r['id']; ?>">Room <?php echo $r['room_number']; ?> - <?php echo $r['type_name']; ?> ($<?php echo $r['price_per_night']; ?>/night)</option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>No rooms currently available!</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Stay Dates -->
                        <div class="col-md-6 form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Check-In Date</label>
                            <input type="date" name="check_in" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Check-Out Date</label>
                            <input type="date" name="check_out" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-12 form-group mb-2">
                            <label class="font-weight-bold text-muted small text-uppercase">Payment Method (Auto-marked as Paid)</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash at Reception">Cash at Reception</option>
                                <option value="EVC Plus">EVC Plus (Manual Verify)</option>
                                <option value="Credit Card">Credit / Debit Card</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_booking" class="btn btn-success px-4 font-weight-bold" style="background-color: #10b981; border: none;">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>