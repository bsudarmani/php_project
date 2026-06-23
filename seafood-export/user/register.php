<?php
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = sanitize($_POST['company_name']);
    $contact_person = sanitize($_POST['contact_person']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $phone_secondary = sanitize($_POST['phone_secondary'] ?? '');
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $state = sanitize($_POST['state']);
    $country = sanitize($_POST['country']);
    $postal_code = sanitize($_POST['postal_code']);
    $gst_number = sanitize($_POST['gst_number'] ?? '');
    $import_license = sanitize($_POST['import_license'] ?? '');
    $business_type = sanitize($_POST['business_type']);
    $password = md5($_POST['password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already registered!";
        } else {
            $query = "INSERT INTO users (company_name, contact_person, email, phone, phone_secondary, 
                      address, city, state, country, postal_code, gst_number, import_license, 
                      business_type, password) 
                      VALUES ('$company_name', '$contact_person', '$email', '$phone', '$phone_secondary',
                      '$address', '$city', '$state', '$country', '$postal_code', '$gst_number',
                      '$import_license', '$business_type', '$password')";
            
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header('Location: login.php');
                exit();
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
    .register-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .register-header i {
        font-size: 4rem;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .register-header h2 {
        color: #0a3147;
        margin-bottom: 10px;
    }
    
    .register-header p {
        color: #7f8c8d;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
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
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #00d4ff;
        outline: none;
        box-shadow: 0 0 10px rgba(0,212,255,0.2);
    }
    
    .btn-register {
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
    
    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .login-link {
        text-align: center;
        margin-top: 20px;
        color: #7f8c8d;
    }
    
    .login-link a {
        color: #00d4ff;
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="register-container">
    <div class="register-header">
        <i class="fas fa-user-plus"></i>
        <h2>Create Buyer Account</h2>
        <p>Register as an importer, distributor or wholesaler</p>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: #ff4757; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" data-validate>
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-building"></i> Company Name *</label>
                <input type="text" name="company_name" required placeholder="Enter company name">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Contact Person *</label>
                <input type="text" name="contact_person" required placeholder="Full name">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" name="email" required placeholder="Enter email">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone *</label>
                <input type="tel" name="phone" required placeholder="Enter phone number">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-phone-alt"></i> Secondary Phone</label>
                <input type="tel" name="phone_secondary" placeholder="Alternate number">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-globe"></i> Business Type *</label>
                <select name="business_type" required>
                    <option value="">Select Business Type</option>
                    <option value="Importer">Importer</option>
                    <option value="Distributor">Distributor</option>
                    <option value="Wholesaler">Wholesaler</option>
                    <option value="Retailer">Retailer</option>
                    <option value="Processor">Processor</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-map-marker-alt"></i> Address *</label>
            <textarea name="address" required rows="3" placeholder="Enter complete address"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" required placeholder="City">
            </div>
            
            <div class="form-group">
                <label>State *</label>
                <input type="text" name="state" required placeholder="State">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Country *</label>
                <input type="text" name="country" required placeholder="Country" value="India">
            </div>
            
            <div class="form-group">
                <label>Postal Code *</label>
                <input type="text" name="postal_code" required placeholder="Postal code">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-file-invoice"></i> GST Number</label>
                <input type="text" name="gst_number" placeholder="GST number (if applicable)">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-passport"></i> Import License</label>
                <input type="text" name="import_license" placeholder="Import license number">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password *</label>
                <input type="password" name="password" required placeholder="Create password" minlength="6">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Confirm password" minlength="6">
            </div>
        </div>
        
        <button type="submit" class="btn-register">
            <i class="fas fa-user-plus"></i> Register
        </button>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>