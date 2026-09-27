<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afrik Hotel Management System</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
    
    <style>
    /* 1. Stop the whole page from scrolling */
    body {
        margin: 0;
        padding: 0;
        overflow: hidden !important; 
        height: 100vh;
    }

    /* 2. The main flex container */
    .wrapper {
        display: flex;
        height: 100vh;
        width: 100%;
        overflow: hidden;
    }

    /* 3. Independent Sidebar Scroll */
    #sidebar {
        min-width: 260px;
        max-width: 260px;
        height: 100vh;
        overflow-y: auto;
        flex-shrink: 0;
        /* Hides default ugly scrollbar for a cleaner look */
        scrollbar-width: thin; 
        background-color: #0f172a;
    }

    /* =========================================
       XALLINTA MIDABKA IYO PADDING-KA SIDEBAR-KA
       ========================================= */
    #sidebar ul { padding-left: 0; list-style: none; }
    #sidebar a {
        padding: 12px 25px !important;
        color: #cbd5e1 !important;
        text-decoration: none !important;
        transition: all 0.2s ease;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
    }
    #sidebar a:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.05);
    }
    #sidebar li.active > a {
        color: #ffffff !important;
        background-color: #10b981 !important;
        border-radius: 8px !important;
        margin: 5px 15px !important;
        padding: 12px 10px !important;
    }
    #sidebar li.active > a i { color: #ffffff !important; }
    #sidebar ul.collapse a { padding-left: 60px !important; font-size: 0.85rem; }
    #sidebar .logout-container a:hover { color: #ef4444 !important; background-color: rgba(239, 68, 68, 0.1); border-radius: 8px; margin: 0 15px; }
    #sidebar .fas.fa-folder { color: #94a3b8 !important; }

    /* 4. Independent Main Content Scroll */
    #content {
        flex-grow: 1;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        transition: all 0.3s;
    }

    /* Modern scrollbar styling for webkit browsers (Chrome/Edge/Safari) */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <style>
    /* Dark Mode Global Overrides */
    body.dark-mode {
        background-color: #0f172a !important; /* Deep navy background */
        color: #f8fafc !important;
    }
    
    /* Convert Cards, Navbar, and Modals to Dark Slate */
    body.dark-mode .bg-white,
    body.dark-mode .card,
    body.dark-mode .list-card,
    body.dark-mode .stat-card,
    body.dark-mode .settings-card,
    body.dark-mode .top-navbar,
    body.dark-mode .navbar,
    body.dark-mode .dropdown-menu,
    body.dark-mode .modal-content {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    /* Text Adjustments */
    body.dark-mode .text-dark { color: #f8fafc !important; }
    body.dark-mode .text-muted { color: #94a3b8 !important; }
    body.dark-mode .text-primary { color: #38bdf8 !important; }
    
    /* Borders and Tables */
    body.dark-mode .border,
    body.dark-mode .border-bottom,
    body.dark-mode .border-top {
        border-color: #334155 !important;
    }
    body.dark-mode .table,
    body.dark-mode .table th,
    body.dark-mode .table td {
        color: #f8fafc !important;
        border-color: #334155 !important;
    }
    body.dark-mode .table-hover tbody tr:hover {
        background-color: #334155 !important;
        color: #fff !important;
    }

    /* Buttons and Inputs */
    body.dark-mode .btn-light {
        background-color: #334155 !important;
        border-color: #475569 !important;
        color: #f8fafc !important;
    }
    body.dark-mode .form-control,
    body.dark-mode select.form-control,
    body.dark-mode .input-group-text {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }
    </style>
</head>
<body>
    <div class="wrapper">