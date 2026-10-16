<?php 

require_once 'db.php'; 
require_once 'header.php'; 

try {
    
    $sql = "SELECT * FROM vehicles WHERE status = 'rent' LIMIT 3";
    $stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'Available' ORDER BY id DESC LIMIT 3");
    $featured_vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $featured_vehicles = []; 
}


if (empty($featured_vehicles)) {
    $featured_vehicles = [
        [
            'id' => '#',
            'name' => 'Mercedes-Benz S-Class',
            'category' => 'Executive Sedan',
            'image' => 'https://images.unsplash.com/photo-1617788138017-80ad40651399?q=80&w=600',
            'fuel_type' => 'Hybrid',
            'transmission' => 'Auto',
            'price_per_day' => 65000
        ],
        [
            'id' => '#',
            'name' => 'Range Rover Vogue',
            'category' => 'Terrain SUV',
            'image' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600',
            'fuel_type' => 'Diesel',
            'transmission' => '4x4',
            'price_per_day' => 85000
        ],
        [
            'id' => '#',
            'name' => 'Porsche 911 Carrera S',
            'category' => 'High Performance',
            'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=600',
            'fuel_type' => 'Petrol',
            'transmission' => 'Tiptronic',
            'price_per_day' => 120000
        ]
    ];
}


try {
    $review_stmt = $pdo->query("SELECT * FROM reviews ORDER BY id DESC");
    $db_reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $db_reviews = [];
}

?>


<section class="hero-matrix">
    <div class="hero-matrix-overlay">
        <span class="hero-tagline"><i class="fa-solid fa-bolt-lightning"></i> Ultimate Automotive Pipeline</span>
        <h1>Drive Corporate Luxury. <br>Command The Terrain.</h1>
        <p>Instantly secure high-performance fleets and luxury sedan matrices with full military-grade verification protocols.</p>
        
   
        <div class="hero-search-console">
            <form action="vehicle_list.php" method="GET" class="search-matrix-form">
                <div class="search-node">
                    <label><i class="fa-solid fa-car-side"></i> Vehicle Category</label>
                    <select name="category">
                        <option value="">All Fleets Selected</option>
                        <option value="luxury">Executive Luxury</option>
                        <option value="suv">Terrain SUVs</option>
                        <option value="sports">High Performance</option>
                    </select>
                </div>
                <div class="search-node">
                    <label><i class="fa-solid fa-calendar-check"></i> Pickup Date</label>
                 
                    <input type="date" name="pickup_date" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn-search-trigger">Execute Search</button>
            </form>
        </div>
    </div>
</section>



<section class="home-section container-custom">
    <div class="section-header-center">
        <span class="section-tag">System Protocols</span>
        <h2>How DriveHub Operates</h2>
        <p>Three streamlined cryptographic nodes to deploy your premium vehicle platform.</p>
    </div>
    
    <div class="protocol-grid">
        <div class="protocol-card">
            <div class="protocol-num">01</div>
            <h3>Identity Verification</h3>
            <p>Establish your profile matrix by deploying your NIC, license markers, and verification tokens.</p>
        </div>
        <div class="protocol-card">
            <div class="protocol-num">02</div>
            <h3>Select Vector Fleet</h3>
            <p>Browse through our real-time verified showroom and lock down your chronological scheduling.</p>
        </div>
        <div class="protocol-card">
            <div class="protocol-num">03</div>
            <h3>Instant Checkout</h3>
            <p>Authorize your session parameters and clear keys at our strategic deployment centers.</p>
        </div>
    </div>
</section>


<section class="home-section container-custom">
    <div class="section-header-center">
        <span class="section-tag">Consumer Testimonials</span>
        <h2>DriveHub Network Trust Node</h2>
        <p>Verified executive accounts and legal entities share their mobility experiences.</p>
    </div>
    
    <div class="feedback-grid">
        <?php if (!empty($db_reviews)): ?>
            <?php foreach ($db_reviews as $review): ?>
                <div class="feedback-card">
                    <div class="stars-row">
                        <?php 
                      
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $review['rating']) {
                                echo '<i class="fa-solid fa-star"></i>';
                            } else {
                                echo '<i class="fa-regular fa-star" style="color: #475569;"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="fb-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                    <div class="fb-user-row">
                        <img src="<?php echo htmlspecialchars($review['user_image'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=150'); ?>" alt="User">
                        <div>
                            <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                            <span><?php echo htmlspecialchars($review['designation']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
       
            <p style="text-align: center; color: var(--text-muted); width: 100%;">No testimonials available at the moment.</p>
        <?php endif; ?>
    </div>
</section>



<section class="newsletter-matrix-section">
    <div class="newsletter-box-container">
        <h3>Subscribe to Fleet Protocols</h3>
        <p>Receive real-time automated telemetry updates when new high-performance vehicles intercept our showroom vectors.</p>
        <form action="#" class="newsletter-form" onsubmit="event.preventDefault(); alert('Subscribed to DriveHub Telemetry Sync.');">
            <input type="email" placeholder="Enter security email route..." required>
            <button type="submit">Establish Sync</button>
        </form>
    </div>
</section>

<?php require_once 'footer.php'; ?>