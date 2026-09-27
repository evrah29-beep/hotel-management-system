<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/bookings/bookings.php');$error = '';

// Fetch all customers for the dropdown
$customers = $conn->query("SELECT id, full_name, phone FROM customers ORDER BY full_name ASC");

// Fetch available rooms with their daily prices
$rooms = $conn->query("
    SELECT r.id, r.room_number, rt.type_name, rt.price_per_night 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    ORDER BY r.room_number ASC
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];
    $num_guests = $_POST['num_guests'];
    $total_amount = $_POST['total_amount'];

    // Validation: Check-out must be after check-in
    if (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Check-out date must be after check-in date.";
    } else {
        // Check if room is already booked for these dates to prevent double-booking
        $overlap = $conn->prepare("
            SELECT id FROM bookings 
            WHERE room_id = ? AND status != 'Cancelled' AND status != 'Checked-out'
            AND ((check_in <= ? AND check_out >= ?) OR (check_in <= ? AND check_out >= ?))
        ");
        $overlap->bind_param("issss", $room_id, $check_out, $check_in, $check_in, $check_out);
        $overlap->execute();
        
        if ($overlap->get_result()->num_rows > 0) {
            $error = "This room is already booked for the selected dates!";
        } else {
            // Call the InsertBooking Stored Procedure (iisssdi)
            // i=customer_id, i=room_id, s=check_in, s=check_out, s=status, d=total_amount, i=num_guests
            $stmt = $conn->prepare("CALL InsertBooking(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssdi", $customer_id, $room_id, $check_in, $check_out, $status, $total_amount, $num_guests);
            
            if ($stmt->execute()) {
                // Update room status to Reserved or Occupied based on booking status
                $room_status = ($status == 'Checked-in') ? 'Occupied' : 'Reserved';
                $update_room = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
                $update_room->bind_param("si", $room_status, $room_id);
                $update_room->execute();
                
                header("Location: bookings.php");
                exit();
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Add New Booking</h1>
        <a href="bookings.php" class="btn btn-light border shadow-sm"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 800px; border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" action="">
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Select Guest</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">-- Choose a Guest --</option>
                            <?php while($c = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['full_name']) . ' (' . htmlspecialchars($c['phone']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="form-text text-muted"><a href="../customers/add-customer.php">Create new guest</a></small>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Select Room</label>
                        <select name="room_id" id="room_select" class="form-control" required>
                            <option value="" data-price="0">-- Choose a Room --</option>
                            <?php while($r = $rooms->fetch_assoc()): ?>
                                <!-- Inject data-price attribute for Javascript calculation -->
                                <option value="<?php echo $r['id']; ?>" data-price="<?php echo $r['price_per_night']; ?>">
                                    Room <?php echo $r['room_number']; ?> - <?php echo $r['type_name']; ?> ($<?php echo $r['price_per_night']; ?>/night)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Check-In Date</label>
                        <input type="date" name="check_in" id="check_in" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Check-Out Date</label>
                        <input type="date" name="check_out" id="check_out" class="form-control" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Number of Guests</label>
                        <input type="number" name="num_guests" class="form-control" value="1" min="1" required>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Booking Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Checked-in">Checked-in</option>
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase text-success">Total Amount ($)</label>
                        <!-- Readonly input; populated by Javascript -->
                        <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control font-weight-bold" required readonly style="background-color: #f8fafc;">
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-green px-4 py-2 font-weight-bold">Save Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Javascript to Auto-Calculate Total Price -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_select');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const totalAmountInput = document.getElementById('total_amount');

    function calculateTotal() {
        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);
        
        // Get the price from the selected option's data-price attribute
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = parseFloat(selectedRoom.getAttribute('data-price')) || 0;

        // Ensure dates are valid and checkout is after checkin
        if (!isNaN(checkIn) && !isNaN(checkOut) && checkOut > checkIn && pricePerNight > 0) {
            // Calculate time difference in days
            const timeDiff = checkOut.getTime() - checkIn.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // Set total
            const total = daysDiff * pricePerNight;
            totalAmountInput.value = total.toFixed(2);
        } else {
            totalAmountInput.value = '';
        }
    }

    // Run calculation whenever these inputs change
    roomSelect.addEventListener('change', calculateTotal);
    checkInInput.addEventListener('change', calculateTotal);
    checkOutInput.addEventListener('change', calculateTotal);
});
</script>