<?php
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['contact_person'];
        $_SESSION['user_company'] = $user['company_name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Update last login
        mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = '{$user['id']}'");
        
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
    .login-container {
        max-width: 450px;
        margin: 60px auto;
        padding: 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-header i {
        font-size: 4rem;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .login-header h2 {
        color: #0a3147;
        margin-bottom: 10px;
    }
    
    .login-header p {
        color: #7f8c8d;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 500;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus {
        border-color: #00d4ff;
        outline: none;
        box-shadow: 0 0 10px rgba(0,212,255,0.2);
    }
    
    .btn-login {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .register-link {
        text-align: center;
        margin-top: 20px;
        color: #7f8c8d;
    }
    
    .register-link a {
        color: #00d4ff;
        text-decoration: none;
    }
    
    .forgot-password {
        text-align: right;
        margin-bottom: 15px;
    }
    
    .forgot-password a {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-decoration: none;
    }
    
    .forgot-password a:hover {
        color: #00d4ff;
    }
</style>

<div class="login-container">
    <div class="login-header">
        <i class="fas fa-user-circle"></i>
        <h2>Welcome Back</h2>
        <p>Login to your buyer account</p>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #2ecc71; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div style="background: #ff4757; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" data-validate>
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>
        
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>