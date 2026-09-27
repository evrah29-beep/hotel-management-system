<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/rooms/rooms.php');
$error = '';
// Fetch room types for the dropdown menu
$types = $conn->query("SELECT id, type_name FROM room_types");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];

    // Check if room number already exists to prevent duplicates
    $check = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
    $check->bind_param("s", $room_number);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Room number already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $room_number, $room_type_id, $floor, $status);
        if ($stmt->execute()) {
            header("Location: rooms.php");
            exit();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Room</h1>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" class="form-control" required placeholder="e.g. 101">
                </div>
                <div class="form-group">
                    <label>Room Type</label>
                    <select name="room_type_id" class="form-control" required>
                        <option value="">Select Type</option>
                        <?php while($t = $types->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo $t['type_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" class="form-control" required placeholder="e.g. 1">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Room</button>
                <a href="rooms.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>