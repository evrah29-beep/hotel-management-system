<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/customers/customers.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']);
    $address = trim($_POST['address']); 
    $nationality = trim($_POST['nationality']);

    // Feature 2: Duplicate Guest Check
    // Prevent matching on empty strings if a field (like email) is left blank
    $check_stmt = $conn->prepare("SELECT id, full_name FROM customers WHERE (id_number = ? AND id_number != '') OR (phone = ? AND phone != '') OR (email = ? AND email != '')");
    $check_stmt->bind_param("sss", $id_number, $phone, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Duplicate found: Stop execution and inform the user
        $existing = $check_result->fetch_assoc();
        $error = "A guest already exists with matching details (<strong>" . htmlspecialchars($existing['full_name']) . "</strong>). <a href='../bookings/add-booking.php' class='alert-link'>Return to bookings to select them.</a>";
    } else {
        // No duplicate found: Proceed with the Stored Procedure
        $stmt = $conn->prepare("CALL InsertCustomer(?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            $error = "SQL Prepare Error: " . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $full_name, $email, $phone, $id_number, $address, $nationality);
            
            if ($stmt->execute()) {
              header("Location: customers.php");
                exit();
            } else {
                $error = "Error adding customer: " . $conn->error;
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Customer</h1>
    
    <?php if($error): ?>
        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4" style="max-width: 800px;">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Nationality</label>
                        <input type="text" name="nationality" class="form-control" placeholder="e.g. Somali, Kenyan">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Passport / ID Number</label>
                        <input type="text" name="id_number" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Home Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save Customer</button>
                <a href="customers.php" class="btn btn-secondary mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>