<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

/*
|--------------------------------------------------------------------------
| User Role
|--------------------------------------------------------------------------
*/

$user_role = $_SESSION['admin_role'] ?? 'user';

/*
|--------------------------------------------------------------------------
| Non-Admin Welcome Dashboard
|--------------------------------------------------------------------------
*/

if ($user_role !== 'admin') {
    include 'includes/header.php';
    include 'includes/sidebar.php';
    include 'includes/navbar.php';
?>

<style>
    .welcome-wrapper {
        padding: 40px 30px;
    }

    .welcome-card {
        background: linear-gradient(135deg, #003f88 0%, #0056b3 100%);
        border-radius: 24px;
        padding: 55px 45px;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 63, 136, 0.15);
    }

    .welcome-card::before {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        right: -80px;
        top: -100px;
    }

    .welcome-card::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        left: -70px;
        bottom: -80px;
    }

    .welcome-content {
        position: relative;
        z-index: 2;
        max-width: 750px;
    }

    .welcome-icon {
        width: 70px;
        height: 70px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        margin-bottom: 25px;
    }

    .welcome-title {
        font-size: 34px;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .welcome-text {
        font-size: 16px;
        line-height: 1.8;
        opacity: 0.9;
        margin-bottom: 0;
    }

    .welcome-date {
        margin-top: 30px;
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.12);
        padding: 10px 18px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .welcome-wrapper {
            padding: 25px 15px;
        }

        .welcome-card {
            padding: 40px 25px;
        }

        .welcome-title {
            font-size: 27px;
        }
    }
</style>

<div class="container-fluid welcome-wrapper">

    <div class="welcome-card">

        <div class="welcome-content">

            <div class="welcome-icon">
                <i class="fas fa-hotel"></i>
            </div>

            <h1 class="welcome-title">
                Welcome to Afrik Hotel
            </h1>

            <p class="welcome-text">
                Welcome to the Afrik Hotel Management System.
                You can use the available sections from the navigation menu
                to perform your assigned tasks.
            </p>

            <div class="welcome-date">
                <i class="fas fa-calendar-alt mr-2"></i>
                <?php echo date('l, M d, Y'); ?>
            </div>

        </div>

    </div>

</div>

<?php
    include 'includes/footer.php';
    exit;
}
?>