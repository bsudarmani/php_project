<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Here you would typically send an email
    // mail($to, $subject, $message, $headers);
    
    $_SESSION['success'] = "Thank you for contacting us. We'll get back to you soon!";
    header('Location: contact.php');
    exit();
}
?>

<style>
    .contact-page {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .contact-header {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 60px 40px;
        border-radius: 20px;
        text-align: center;
        margin-bottom: 60px;
    }
    
    .contact-header h1 {
        font-size: 3rem;
        margin-bottom: 20px;
    }
    
    .contact-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    
    .contact-info {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .info-item {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        transform: translateX(10px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .info-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #00d4ff, #0077be);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    
    .info-content h3 {
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .info-content p {
        color: #7f8c8d;
        line-height: 1.6;
    }
    
    .contact-form {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #00d4ff;
        outline: none;
        box-shadow: 0 0 10px rgba(0,212,255,0.2);
    }
    
    .btn-submit {
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
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .map-container {
        margin-top: 60px;
        height: 400px;
        background: #ecf0f1;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .map-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #7f8c8d;
    }
    
    .map-placeholder i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .business-hours {
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .hours-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .hours-item:last-child {
        border-bottom: none;
    }
    
    .day {
        color: #0a3147;
        font-weight: 500;
    }
    
    .time {
        color: #00d4ff;
    }
    
    @media (max-width: 768px) {
        .contact-container {
            grid-template-columns: 1fr;
        }
        
        .contact-header h1 {
            font-size: 2rem;
        }
    }
</style>

<div class="contact-page">
    <div class="contact-header">
        <h1>Get in Touch</h1>
        <p>We're here to help with all your seafood export needs</p>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #2ecc71; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="contact-container">
        <!-- Contact Information -->
        <div class="contact-info">
            <h2 style="color: #0a3147; margin-bottom: 30px;">Contact Information</h2>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="info-content">
                    <h3>Head Office</h3>
                    <p>123 Fishing Harbor,<br>Sassoon Dock, Mumbai<br>Maharashtra - 400005, India</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="info-content">
                    <h3>Phone Numbers</h3>
                    <p>Sales: +91 98765 43210<br>Support: +91 98765 43211<br>Export: +91 98765 43212</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-content">
                    <h3>Email Addresses</h3>
                    <p>sales@seafoodexport.com<br>support@seafoodexport.com<br>export@seafoodexport.com</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="info-content">
                    <h3>Follow Us</h3>
                    <p style="font-size: 1.5rem;">
                        <a href="#" style="color: #00d4ff; margin-right: 15px;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: #00d4ff; margin-right: 15px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: #00d4ff; margin-right: 15px;"><i class="fab fa-linkedin"></i></a>
                        <a href="#" style="color: #00d4ff;"><i class="fab fa-instagram"></i></a>
                    </p>
                </div>
            </div>
            
            <div class="business-hours">
                <h3 style="color: #0a3147; margin-bottom: 15px;">Business Hours</h3>
                <div class="hours-item">
                    <span class="day">Monday - Friday</span>
                    <span class="time">9:00 AM - 8:00 PM</span>
                </div>
                <div class="hours-item">
                    <span class="day">Saturday</span>
                    <span class="time">10:00 AM - 6:00 PM</span>
                </div>
                <div class="hours-item">
                    <span class="day">Sunday</span>
                    <span class="time">Closed</span>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="contact-form">
            <h2 style="color: #0a3147; margin-bottom: 30px;">Send us a Message</h2>
            
            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Your Name *</label>
                    <input type="text" name="name" required placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Subject *</label>
                    <input type="text" name="subject" required placeholder="Enter subject">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Message *</label>
                    <textarea name="message" required rows="6" placeholder="Type your message here..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
    
    <!-- Map -->
    <div class="map-container">
        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <p>Google Maps integration would be here</p>
            <p style="font-size: 0.9rem;">123 Fishing Harbor, Mumbai - 400005</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>