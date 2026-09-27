<?php

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
| Only users with the Admin role can access User Management.
| We support both session names in case your login currently uses
| either admin_role or role.
|--------------------------------------------------------------------------
*/

$user_role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? '';

if ($user_role !== 'Admin') {

    echo '
    <div style="
        text-align:center;
        margin-top:80px;
        font-family:Arial, sans-serif;
        padding:30px;
    ">

        <h2 style="
            color:#dc2626;
            font-size:32px;
            margin-bottom:15px;
        ">
            Access Denied
        </h2>

        <p style="
            color:#475569;
            font-size:16px;
            margin-bottom:25px;
        ">
            Ma leedahay awood aad ku gasho boggan.
            Fadlan la xiriir maamulka.
        </p>

        <a
            href="../../logout.php"
            style="
                display:inline-block;
                padding:10px 20px;
                background:#003f88;
                color:#fff;
                text-decoration:none;
                border-radius:6px;
                font-weight:bold;
            "
        >
            Ka bax (Logout)
        </a>

    </div>
    ';

    exit();
}


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$success_msg = '';
$error_msg = '';


/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {

    $full_name = trim($_POST['full_name'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    $password  = $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if ($full_name === '' || $name === '' || $password === '' || $role === '') {

        $error_msg = "Please fill in all required fields.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Login Name
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM admin
            WHERE name = ?
            LIMIT 1
        ");

        if ($check) {

            $check->bind_param("s", $name);
            $check->execute();

            $result = $check->get_result();

            if ($result && $result->num_rows > 0) {

                $error_msg = "Login Name already exists. Choose a different one.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Hash Password
                |--------------------------------------------------------------------------
                */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | Insert User
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    CALL InsertAdmin(?, ?, ?, ?, ?)
                ");

                if ($stmt) {

                    $stmt->bind_param(
                        "sssss",
                        $full_name,
                        $name,
                        $email,
                        $hashed_password,
                        $role
                    );


                    if ($stmt->execute()) {

                        $success_msg = "Staff member added successfully!";

                    } else {

                        $error_msg = "Error adding staff: " . $stmt->error;
                    }

                    $stmt->close();

                } else {

                    $error_msg = "Unable to prepare user creation query.";
                }
            }

            $check->close();

        } else {

            $error_msg = "Unable to check Login Name.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $delete_id = (int) $_GET['delete'];


    /*
    |--------------------------------------------------------------------------
    | Protect Primary Admin and Current User
    |--------------------------------------------------------------------------
    */

    if (
        $delete_id === 1 ||
        $delete_id === (int) ($_SESSION['admin_id'] ?? 0)
    ) {

        $error_msg = "You cannot delete the primary administrator or your own account.";

    } else {

        $stmt = $conn->prepare("
            CALL DeleteAdmin(?)
        ");

        if ($stmt) {

            $stmt->bind_param("i", $delete_id);

            if ($stmt->execute()) {

                header("Location: users.php");
                exit();

            } else {

                $error_msg = "Unable to delete this staff member.";
            }

            $stmt->close();

        } else {

            $error_msg = "Unable to prepare delete request.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$admins = $conn->query("
    SELECT
        id,
        full_name,
        name,
        email,
        role,
        status
    FROM admin
    ORDER BY id ASC
");


/*
|--------------------------------------------------------------------------
| Check Query
|--------------------------------------------------------------------------
*/

if (!$admins) {

    $error_msg = "Unable to load staff members: " . $conn->error;

    $total_staff = 0;

} else {

    $total_staff = $admins->num_rows;
}


/*
|--------------------------------------------------------------------------
| TOTAL ADMINS
|--------------------------------------------------------------------------
*/

$managers = 0;

$manager_query = $conn->query("
    SELECT COUNT(*) AS cnt
    FROM admin
    WHERE role = 'Admin'
");

if ($manager_query) {

    $manager_result = $manager_query->fetch_assoc();

    $managers = (int) ($manager_result['cnt'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| PAGE INCLUDES
|--------------------------------------------------------------------------
*/

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';

?>


<!-- =========================================================
     DATATABLES CSS
========================================================= -->

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css"
>


<style>

    /*
    |--------------------------------------------------------------------------
    | PAGE HEADER
    |--------------------------------------------------------------------------
    */

    .page-header {
        margin-bottom: 30px;
    }


    /*
    |--------------------------------------------------------------------------
    | STAT CARD
    |--------------------------------------------------------------------------
    */

    .stat-card {

        border: 1px solid #edf2f7;

        border-radius: 12px;

        background: #fff;

        padding: 20px;

    }


    .stat-title {

        color: #64748b;

        font-size: 0.9rem;

        margin-bottom: 8px;

    }


    .stat-value {

        color: #0f172a;

        font-size: 2rem;

        font-weight: 700;

        margin: 0;

        line-height: 1;

    }


    /*
    |--------------------------------------------------------------------------
    | LIST CARD
    |--------------------------------------------------------------------------
    */

    .list-card {

        border: 1px solid #edf2f7;

        border-radius: 12px;

        background: #fff;

    }


    .list-header {

        border-bottom: 1px solid #edf2f7;

        padding: 20px;

        display: flex;

        justify-content: space-between;

        align-items: center;

    }


    /*
    |--------------------------------------------------------------------------
    | AVATAR
    |--------------------------------------------------------------------------
    */

    .avatar-circle {

        width: 40px;

        height: 40px;

        background-color: #f1f5f9;

        color: #3b82f6;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

        font-weight: bold;

        margin-right: 15px;

    }


    /*
    |--------------------------------------------------------------------------
    | ROLE BADGES
    |--------------------------------------------------------------------------
    */

    .role-badge {

        display: inline-block;

        padding: 6px 10px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 700;

    }


    .role-admin {

        background: rgba(16, 185, 129, 0.12);

        color: #059669;

    }


    .role-reception {

        background: rgba(59, 130, 246, 0.12);

        color: #2563eb;

    }


    .role-finance {

        background: rgba(245, 158, 11, 0.12);

        color: #d97706;

    }


    .role-hrm {

        background: rgba(139, 92, 246, 0.12);

        color: #7c3aed;

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    .status-text {

        font-size: 12px;

        color: #64748b;

        margin-top: 4px;

    }

</style>


<div class="container-fluid p-0">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header d-flex justify-content-between align-items-center">

        <div>

            <h1 class="h3 font-weight-bold text-dark mb-1">
                Staff Management
            </h1>

            <p class="text-muted small mb-0">
                Manage system users, roles, and access.
            </p>

        </div>


        <button
            class="btn btn-green shadow-sm"
            data-toggle="modal"
            data-target="#addUserModal"
        >

            <i class="fas fa-user-plus"></i>

            Add Staff

        </button>

    </div>


    <!-- =====================================================
         ALERTS
    ====================================================== -->

    <?php if ($success_msg): ?>

        <div class="alert alert-success">

            <?php echo htmlspecialchars(
                $success_msg,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    <?php endif; ?>


    <?php if ($error_msg): ?>

        <div class="alert alert-danger">

            <?php echo htmlspecialchars(
                $error_msg,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="row mb-4">


        <!-- TOTAL STAFF -->

        <div class="col-md-6">

            <div class="stat-card shadow-sm">

                <div class="stat-title">
                    Total Active Staff
                </div>

                <div class="stat-value text-primary">

                    <?php echo number_format($total_staff); ?>

                </div>

            </div>

        </div>


        <!-- SYSTEM ADMINS -->

        <div class="col-md-6">

            <div class="stat-card shadow-sm">

                <div class="stat-title">
                    System Admins
                </div>

                <div class="stat-value text-success">

                    <?php echo number_format($managers); ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         USER DIRECTORY
    ====================================================== -->

    <div class="list-card shadow-sm mb-4">


        <div class="list-header">

            <h5 class="font-weight-bold text-dark mb-0">

                User Directory

            </h5>

        </div>


        <div class="card-body">

            <div class="table-responsive">


                <table
                    class="table table-hover align-middle"
                    id="staffTable"
                    width="100%"
                >


                    <thead class="bg-light text-muted small text-uppercase">

                        <tr>

                            <th>
                                Staff Member
                            </th>

                            <th>
                                Login Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Role & Status
                            </th>

                            <th class="text-right">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if ($admins && $admins->num_rows > 0): ?>


                            <?php while ($row = $admins->fetch_assoc()): ?>


                                <?php

                                $display_name = !empty($row['full_name'])
                                    ? $row['full_name']
                                    : $row['name'];

                                $initial = strtoupper(
                                    substr($display_name, 0, 1)
                                );


                                $role_class = 'role-reception';

                                if ($row['role'] === 'Admin') {

                                    $role_class = 'role-admin';

                                } elseif ($row['role'] === 'Finance') {

                                    $role_class = 'role-finance';

                                } elseif ($row['role'] === 'HRM') {

                                    $role_class = 'role-hrm';

                                }

                                ?>


                                <tr>


                                    <!-- STAFF -->

                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div class="avatar-circle">

                                                <?php echo htmlspecialchars(
                                                    $initial,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </div>


                                            <strong class="text-dark">

                                                <?php echo htmlspecialchars(
                                                    $display_name,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </strong>

                                        </div>

                                    </td>


                                    <!-- LOGIN NAME -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $row['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $row['email'] ?: 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        <span
                                            class="role-badge <?php echo $role_class; ?>"
                                        >

                                            <?php echo htmlspecialchars(
                                                $row['role'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>


                                        <div class="status-text">

                                            <?php echo htmlspecialchars(
                                                $row['status'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </div>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td class="text-right">


                                        <?php

                                        $current_admin_id =
                                            (int) ($_SESSION['admin_id'] ?? 0);

                                        ?>


                                        <?php if (
                                            (int) $row['id'] !== 1 &&
                                            (int) $row['id'] !== $current_admin_id
                                        ): ?>


                                            <a
                                                href="users.php?delete=<?php echo (int) $row['id']; ?>"
                                                class="btn btn-light btn-sm text-danger font-weight-bold"
                                                onclick="return confirm('Remove this staff member?');"
                                            >

                                                Revoke Access

                                            </a>


                                        <?php else: ?>


                                            <span class="text-muted small">

                                                Protected

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-5"
                                >

                                    <i class="fas fa-users fa-2x mb-3"></i>

                                    <br>

                                    No staff members found.

                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>


                </table>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     ADD USER MODAL
========================================================= -->

<div
    class="modal fade"
    id="addUserModal"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>


    <div class="modal-dialog">

        <div
            class="modal-content border-0"
            style="border-radius:12px;"
        >


            <!-- HEADER -->

            <div class="modal-header bg-light border-0">

                <h5 class="modal-title font-weight-bold">

                    Create Staff Account

                </h5>


                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                >

                    <span>&times;</span>

                </button>

            </div>


            <!-- FORM -->

            <form method="POST" action="">


                <div class="modal-body p-4">


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label class="font-weight-bold text-muted small text-uppercase">

                            Full Name

                        </label>


                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label class="font-weight-bold text-muted small text-uppercase">

                            Email

                        </label>


                        <input
                            type="email"
                            name="email"
                            class="form-control"
                        >

                    </div>


                    <!-- LOGIN NAME -->

                    <div class="form-group">

                        <label class="font-weight-bold text-muted small text-uppercase">

                            Login Name

                        </label>


                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label class="font-weight-bold text-muted small text-uppercase">

                            Password

                        </label>


                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="form-group">

                        <label class="font-weight-bold text-muted small text-uppercase">

                            System Role

                        </label>


                        <select
                            name="role"
                            class="form-control"
                            required
                        >

                            <option value="Reception">
                                Reception
                            </option>

                            <option value="Finance">
                                Finance
                            </option>

                            <option value="HRM">
                                HRM
                            </option>

                            <option value="Admin">
                                Admin
                            </option>

                        </select>

                    </div>


                </div>


                <!-- FOOTER -->

                <div class="modal-footer border-0">

                    <button
                        type="button"
                        class="btn btn-light border"
                        data-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        name="add_user"
                        class="btn btn-green"
                    >

                        Create Account

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<?php include '../../includes/footer.php'; ?>


<!-- =========================================================
     DATATABLES JS
========================================================= -->

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>


<script>

$(document).ready(function() {

    $('#staffTable').DataTable({

        pageLength: 10,

        language: {

            search: "",

            searchPlaceholder: "Search staff..."

        }

    });

});

</script>