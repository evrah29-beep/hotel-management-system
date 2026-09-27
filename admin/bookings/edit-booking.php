<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/bookings/bookings.php');
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$id = $_GET['id'];

// Fetch the existing booking data
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: bookings.php");
    exit();
}

// Fetch all customers (for the dropdown)
$customers = $conn->query("SELECT id, full_name, phone FROM customers ORDER BY full_name ASC");

// Fetch rooms with prices
$rooms = $conn->query("
    SELECT r.id, r.room_number, rt.type_name, rt.price_per_night 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    ORDER BY r.room_number ASC
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];
    $num_guests = $_POST['num_guests'];
    $total_amount = $_POST['total_amount'];

    // Simple date check
    if (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Check-out date must be after check-in date.";
    } else {
        // USING DIRECT SQL INSTEAD OF STORED PROCEDURE TO GUARANTEE IT WORKS
        $sql = "UPDATE bookings SET room_id=?, check_in=?, check_out=?, status=?, total_amount=?, num_guests=? WHERE id=?";
        $update_stmt = $conn->prepare($sql);
        
        if (!$update_stmt) {
            $error = "SQL Prepare Error: " . $conn->error;
        } else {
            // Bind params: integer, string, string, string, double, integer, integer (isssdii)
            $update_stmt->bind_param("isssdii", $room_id, $check_in, $check_out, $status, $total_amount, $num_guests, $id);
            
            if ($update_stmt->execute()) {
                // Update the room's physical status
                $room_status = 'Reserved';
                if ($status == 'Checked-in') $room_status = 'Occupied';
                if ($status == 'Checked-out' || $status == 'Cancelled') $room_status = 'Available';
                
                $update_room = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
                $update_room->bind_param("si", $room_status, $room_id);
                $update_room->execute();

                // If room was swapped, free up the old one
                if ($room_id != $booking['room_id']) {
                    $free_old_room = $conn->prepare("UPDATE rooms SET status = 'Available' WHERE id = ?");
                    $free_old_room->bind_param("i", $booking['room_id']);
                    $free_old_room->execute();
                }
                
                header("Location: bookings.php");
                exit();
            } else {
                $error = "Update Failed: " . $update_stmt->error;
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
        <h1 class="h3 text-gray-800">Edit Booking #<?php echo $id; ?></h1>
        <a href="bookings.php" class="btn btn-light border shadow-sm"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 800px; border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" action="">
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Guest (Cannot be changed)</label>
                        <select class="form-control" disabled>
                            <?php while($c = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php if($booking['customer_id'] == $c['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($c['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Room</label>
                        <select name="room_id" id="room_select" class="form-control" required>
                            <?php while($r = $rooms->fetch_assoc()): ?>
                                <option value="<?php echo $r['id']; ?>" data-price="<?php echo $r['price_per_night']; ?>" <?php if($booking['room_id'] == $r['id']) echo 'selected'; ?>>
                                    Room <?php echo $r['room_number']; ?> ($<?php echo $r['price_per_night']; ?>/night)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Check-In Date</label>
                        <input type="date" name="check_in" id="check_in" class="form-control" value="<?php echo $booking['check_in']; ?>" required>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Check-Out Date</label>
                        <input type="date" name="check_out" id="check_out" class="form-control" value="<?php echo $booking['check_out']; ?>" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Number of Guests</label>
                        <input type="number" name="num_guests" class="form-control" value="<?php echo $booking['num_guests']; ?>" min="1" required>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Pending" <?php if($booking['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Confirmed" <?php if($booking['status'] == 'Confirmed') echo 'selected'; ?>>Confirmed</option>
                            <option value="Checked-in" <?php if($booking['status'] == 'Checked-in') echo 'selected'; ?>>Checked-in</option>
                            <option value="Checked-out" <?php if($booking['status'] == 'Checked-out') echo 'selected'; ?>>Checked-out</option>
                            <option value="Cancelled" <?php if($booking['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase text-success">Total Amount ($)</label>
                        <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control font-weight-bold" value="<?php echo $booking['total_amount']; ?>" required readonly style="background-color: #f8fafc;">
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-warning px-4 py-2 font-weight-bold">Update Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_select');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const totalAmountInput = document.getElementById('total_amount');

    function calculateTotal() {
        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = parseFloat(selectedRoom.getAttribute('data-price')) || 0;

        if (!isNaN(checkIn) && !isNaN(checkOut) && checkOut > checkIn && pricePerNight > 0) {
            const timeDiff = checkOut.getTime() - checkIn.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            const total = daysDiff * pricePerNight;
            totalAmountInput.value = total.toFixed(2);
        }
    }

    roomSelect.addEventListener('change', calculateTotal);
    checkInInput.addEventListener('change', calculateTotal);
    checkOutInput.addEventListener('change', calculateTotal);
});
</script>