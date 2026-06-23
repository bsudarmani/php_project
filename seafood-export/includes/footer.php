<?php
$current_year = date('Y');
?>
    </main>
    
    <!-- Footer -->
    <footer style="background: #0a3147; color: white; padding: 60px 0 20px; margin-top: 60px;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div>
                    <h3 style="color: #00d4ff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-fish"></i> SeaFood Export
                    </h3>
                    <p style="color: #b0c4de; line-height: 1.6;">Premium quality seafood processing and export system. HACCP certified, globally recognized.</p>
                    <div style="margin-top: 20px; display: flex; gap: 15px;">
                        <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                        <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div>
                    <h4 style="color: #00d4ff; margin-bottom: 20px;">Quick Links</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>index.php" style="color: #b0c4de; text-decoration: none;"><i class="fas fa-chevron-right" style="margin-right: 8px;"></i> Home</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo USER_URL; ?>products.php" style="color: #b0c4de; text-decoration: none;"><i class="fas fa-chevron-right" style="margin-right: 8px;"></i> Products</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>about.php" style="color: #b0c4de; text-decoration: none;"><i class="fas fa-chevron-right" style="margin-right: 8px;"></i> About Us</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>contact.php" style="color: #b0c4de; text-decoration: none;"><i class="fas fa-chevron-right" style="margin-right: 8px;"></i> Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #00d4ff; margin-bottom: 20px;">Products</h4>
                    <ul style="list-style: none;">
                        <?php
                        $query = "SELECT * FROM fish_species WHERE status = 1 LIMIT 5";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)):
                        ?>
                        <li style="margin-bottom: 10px;"><a href="<?php echo USER_URL; ?>products.php?species=<?php echo $row['id']; ?>" style="color: #b0c4de; text-decoration: none;"><i class="fas fa-chevron-right" style="margin-right: 8px;"></i> <?php echo $row['name']; ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #00d4ff; margin-bottom: 20px;">Contact Info</h4>
                    <p style="color: #b0c4de; margin-bottom: 10px;"><i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i> 123 Fishing Harbor, Mumbai - 400001</p>
                    <p style="color: #b0c4de; margin-bottom: 10px;"><i class="fas fa-phone" style="margin-right: 10px;"></i> +91 98765 43210</p>
                    <p style="color: #b0c4de; margin-bottom: 10px;"><i class="fas fa-envelope" style="margin-right: 10px;"></i> info@seafoodexport.com</p>
                </div>
            </div>
            
            <hr style="border: 1px solid #1b4b6c; margin: 20px 0;">
            
            <div style="text-align: center; color: #b0c4de;">
                <p>&copy; <?php echo $current_year; ?> SeaFood Export System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
    
    <script>
        // Mobile Menu Toggle
        document.getElementById('hamburger').addEventListener('click', function() {
            document.getElementById('navMenu').classList.toggle('active');
            this.classList.toggle('active');
        });
        
        // Show notification function
        function showNotification(message, type = 'success') {
            const container = document.getElementById('alertContainer');
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            
            let icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            notification.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
        
        <?php if (isset($_SESSION['success'])): ?>
            showNotification('<?php echo $_SESSION['success']; ?>', 'success');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            showNotification('<?php echo $_SESSION['error']; ?>', 'error');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
<?php if (isset($conn)) mysqli_close($conn); ?>