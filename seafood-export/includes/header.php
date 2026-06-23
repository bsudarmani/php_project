<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require_once dirname(__DIR__) . '/includes/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

// Get cart count for user
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count = getCartTotalKg($_SESSION['user_id']);
}

// Get unread notifications count
$notification_count = 0;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '{$_SESSION['user_id']}' AND is_read = 0";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $notification_count = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sea Food Processing & Auto Export System</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="Premium quality seafood processing and export system">
    <meta name="keywords" content="seafood, fish, prawns, export, processing, frozen seafood">
    <meta name="author" content="SeaFood Export">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
        }
        
        /* Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #0a3147, #1b4b6c);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand a {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .brand-icon {
            font-size: 2rem;
            color: #00d4ff;
            animation: pulse 2s infinite;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 5px;
            align-items: center;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateY(-2px);
        }
        
        .cart-badge, .notification-badge {
            background: #ff4757;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }
        
        .hamburger span {
            width: 25px;
            height: 3px;
            background: white;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 992px) {
            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                background: #1b4b6c;
                flex-direction: column;
                padding: 40px;
                transition: left 0.3s ease;
            }
            
            .nav-menu.active {
                left: 0;
            }
            
            .hamburger {
                display: flex;
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Alert Messages */
        .alert-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.5s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .alert-success { background: linear-gradient(135deg, #00b09b, #96c93d); }
        .alert-error { background: linear-gradient(135deg, #ff416c, #ff4b2b); }
        .alert-info { background: linear-gradient(135deg, #2193b0, #6dd5ed); }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Alert Container -->
    <div class="alert-container" id="alertContainer"></div>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo SITE_URL; ?>index.php">
                    <i class="fas fa-fish brand-icon"></i>
                    <span>SeaFood Export</span>
                </a>
            </div>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo SITE_URL; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="<?php echo USER_URL; ?>products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>"><i class="fas fa-fish"></i> Products</a></li>
                <li><a href="<?php echo SITE_URL; ?>about.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="<?php echo SITE_URL; ?>contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo USER_URL; ?>cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i> Cart 
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?>kg</span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="<?php echo USER_URL; ?>my_orders.php" class="nav-link"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="<?php echo USER_URL; ?>index.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?php echo USER_URL; ?>logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <li><a href="<?php echo ADMIN_URL; ?>dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Admin</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo USER_URL; ?>login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo USER_URL; ?>register.php" class="nav-link"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    
    <main></main>