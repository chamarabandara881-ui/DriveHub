<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
?>

<footer class="master-footer">
    <div class="footer-container">
        <div class="footer-col branding-col">
            <a href="index.php" class="footer-logo">
                <i class="fa-solid fa-car-side"></i> Drive<span>Hub</span>
            </a>
            <p class="footer-desc">
                Sri Lanka's premier automotive matrix. Elevating your driving experience with absolute security, verified luxury rentals, and seamless direct vehicle acquisitions.
            </p>
        </div>

        <div class="footer-col">
            <h3>Quick Navigation</h3>
            <ul class="footer-links">
                <li><a href="index.php"><i class="fa-solid fa-chevron-right"></i> Virtual Showroom</a></li>
                <li><a href="vehicle_list.php"><i class="fa-solid fa-chevron-right"></i> Explore Our Fleet</a></li>
                <li><a href="about.php"><i class="fa-solid fa-chevron-right"></i> Executive About</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-chevron-right"></i> Help Center / Support</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Operations Hub</h3>
            <ul class="footer-meta">
                <li><i class="fa-solid fa-clock"></i> 24/7 Digital Concierge</li>
                <li><i class="fa-solid fa-warehouse"></i> Showroom: 8:00 AM - 7:00 PM</li>
                <li><i class="fa-solid fa-location-dot"></i> Colombo, Western Province</li>
            </ul>
        </div>

        <div class="footer-col social-col">
            <h3>Connect Ecosystem</h3>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 15px;">Follow our official digital handles:</p>
            <div class="social-grid">
                <a href="https://facebook.com" target="_blank" class="social-icon facebook" title="Facebook Connect">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="https://instagram.com" target="_blank" class="social-icon instagram" title="Instagram Feed">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="https://youtube.com" target="_blank" class="social-icon youtube" title="YouTube Channel">
                    <i class="fa-brands fa-youtube"></i>
                </a>
                <a href="https://tiktok.com" target="_blank" class="social-icon tiktok" title="TikTok Hub">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> DriveHub Premium Platform. All Rights Reserved. Engineered for Academic Excellence.</p>
    </div>
</footer>
</body>
</html>