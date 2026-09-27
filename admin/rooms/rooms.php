<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/rooms/rooms.php');

$success_msg = '';
$error_msg = '';

// 1. Handle Add New Room (Moved from add-room.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];

    // Check if room number already exists to prevent duplicates
    $check = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
    $check->bind_param("s", $room_number);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error_msg = "Room number $room_number already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $room_number, $room_type_id, $floor, $status);
        if ($stmt->execute()) {
            $success_msg = "Room $room_number successfully added!";
        } else {
            $error_msg = "Error adding room: " . $conn->error;
        }
    }
}

// 2. Handle Room Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_room'])) {
    $room_id = intval($_POST['room_id']);
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $room_id);
    
    if ($stmt->execute()) {
        $success_msg = "Room successfully updated to $new_status.";
    }
}

// 3. Handle Room Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_room'])) {
    $room_id = intval($_POST['room_id']);
    
    // Check if the room has active bookings before deleting
    $check = $conn->query("SELECT id FROM bookings WHERE room_id = $room_id AND status IN ('Pending', 'Confirmed', 'Checked-in')");
    if($check->num_rows > 0) {
        $error_msg = "Cannot delete this room. It has active bookings.";
    } else {
        $conn->query("DELETE FROM rooms WHERE id = $room_id");
        $success_msg = "Room successfully deleted.";
    }
}

// Fetch all room types into an array to use in both Add and Edit modals
$all_types = [];
$types_query = $conn->query("SELECT id, type_name FROM room_types");
if ($types_query) {
    while ($t = $types_query->fetch_assoc()) {
        $all_types[] = $t;
    }
}

// Fetch all rooms and their types for the table
$query = "
    SELECT r.id, r.room_number, r.floor, r.status, r.room_type_id,
           rt.type_name, rt.price_per_night 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    ORDER BY r.room_number ASC
";
$rooms_result = $conn->query($query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Manage Rooms</h1>
            <p class="text-muted small">View and manage your hotel's physical room inventory.</p>
        </div>
        <div>
            <!-- Button to open the Add Room Modal -->
            <button class="btn btn-green font-weight-bold shadow-sm" data-toggle="modal" data-target="#addRoomModal" style="border-radius: 8px;">
                <i class="fas fa-plus mr-2"></i> Add New Room
            </button>
        </div>
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
                            <th class="pl-4 py-3">Room No.</th>
                            <th>Category</th>
                            <th>Floor</th>
                            <th>Price / Night</th>
                            <th>Status</th>
                            <th class="text-right pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($rooms_result && $rooms_result->num_rows > 0): ?>
                            <?php while($row = $rooms_result->fetch_assoc()): ?>
                            <tr>
                                <td class="pl-4 font-weight-bold text-dark" style="font-size: 1.1rem;">
                                    <i class="fas fa-door-closed text-muted mr-2"></i><?php echo htmlspecialchars($row['room_number']); ?>
                                </td>
                                <td class="font-weight-bold text-dark">
                                    <?php echo htmlspecialchars($row['type_name']); ?>
                                </td>
                                <td class="text-muted font-weight-bold">
                                    Floor <?php echo htmlspecialchars($row['floor']); ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-success">$<?php echo number_format($row['price_per_night'], 2); ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $bg = 'badge-secondary';
                                        if($row['status'] == 'Available') $bg = 'badge-success';
                                        if($row['status'] == 'Booked') $bg = 'badge-info';
                                        if($row['status'] == 'Maintenance') $bg = 'badge-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $bg; ?> px-3 py-2" style="border-radius: 6px;"><?php echo $row['status']; ?></span>
                                </td>
                                <td class="text-right pr-4">
                                    <button class="btn btn-sm btn-light border text-primary font-weight-bold shadow-sm mr-1" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border text-danger font-weight-bold shadow-sm" data-toggle="modal" data-target="#deleteModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Room Modal -->
                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0" style="border-radius: 12px;">
                                        <div class="modal-header bg-light border-0">
                                            <h5 class="modal-title font-weight-bold">Edit Room <?php echo htmlspecialchars($row['room_number']); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                                
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Room Type (Reference)</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['type_name']); ?>" disabled>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Room Status</label>
                                                    <select name="status" class="form-control font-weight-bold">
                                                        <option value="Available" <?php if($row['status'] == 'Available') echo 'selected'; ?>>Available</option>
                                                        <option value="Booked" <?php if($row['status'] == 'Booked') echo 'selected'; ?>>Booked</option>
                                                        <option value="Maintenance" <?php if($row['status'] == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                                                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                                                <button type="submit" name="update_room" class="btn btn-green px-4">Update Room</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Room Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0" style="border-radius: 12px;">
                                        <div class="modal-header bg-danger text-white border-0">
                                            <h5 class="modal-title font-weight-bold">Confirm Deletion</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                                <p class="mb-0">Are you sure you want to delete <strong>Room <?php echo htmlspecialchars($row['room_number']); ?></strong>? This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                                                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_room" class="btn btn-danger px-4">Yes, Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No rooms found in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD NEW ROOM MODAL -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background-color: #0c2340; color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2 text-success"></i> Add New Room</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Room Number</label>
                        <input type="text" name="room_number" class="form-control" required placeholder="e.g. 101">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Room Type</label>
                        <select name="room_type_id" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <?php foreach($all_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Floor Level</label>
                        <input type="number" name="floor" class="form-control" required placeholder="e.g. 1">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Initial Status</label>
                        <select name="status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_room" class="btn btn-success px-4 font-weight-bold" style="background-color: #10b981; border: none;">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>