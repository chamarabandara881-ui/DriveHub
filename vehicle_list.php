<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
require_once ROOT_DIR . '/db.php';

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $rootPath = str_replace('\\', '/', ROOT_DIR);
    $webPath = str_replace($docRoot, '', $rootPath);
    $cleanWebPath = '/' . ltrim($webPath, '/');
    $cleanWebPath = rtrim($cleanWebPath, '/');
    define('BASE_URL', $protocol . $host . ($cleanWebPath ? $cleanWebPath . '/' : '/'));
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
   
    $sql = "SELECT * FROM vehicles WHERE 
            brand LIKE ? OR 
            name LIKE ? OR 
            fuel_type LIKE ? OR 
            transmission LIKE ? OR 
            purpose LIKE ? 
            ORDER BY id DESC";
            
    $stmt = $pdo->prepare($sql);
    
    $search_term = '%' . $search_query . '%';
    $stmt->execute([$search_term, $search_term, $search_term, $search_term, $search_term]);
} else {
    $sql = "SELECT * FROM vehicles ORDER BY id DESC";
    $stmt = $pdo->query($sql);
}

$vehicles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub | Explore Fleet — Premium Showroom</title>
    <meta name="description" content="Browse DriveHub's premium vehicle fleet. Filter by rental or purchase, explore specs, and book your next drive.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo time(); ?>">

    <style>
        .vehicle-card.unavailable {
            opacity: 0.65;
            position: relative;
            cursor: not-allowed;
            filter: grayscale(20%);
        }
        .badge-unavailable {
            background: #ef4444 !important; 
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
        }
        .disabled-box {
            background: #334155 !important;
            color: #94a3b8 !important;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<?php include_once ROOT_DIR . '/header.php'; ?>

<div class="showroom-container">


    <div class="showroom-title">
        <span style="display:inline-block; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.2); color:var(--accent-teal); padding:5px 14px; border-radius:30px; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-bottom:16px;">ANTIGRAVITY FLEET</span>
        <h1>Explore Our Elite Fleet</h1>
        <p>Select a vehicle to analyze full operational specifications and unlock deployment clearance.</p>
    </div>


    <div class="vehicle-grid">
        <?php if (!empty($vehicles)): ?>
            <?php foreach ($vehicles as $v): ?>
                <?php 
                   
                    $purpose = strtolower($v['purpose'] ?? ''); 
                    
           
                    $is_available = true;
                    if (isset($v['status']) && strcasecmp($v['status'], 'Not Available') === 0) {
                        $is_available = false;
                    }
                ?>
                
            
                <div class="vehicle-card <?php echo !$is_available ? 'unavailable' : ''; ?>">
                    
                
                    <div class="card-img-wrapper" <?php echo $is_available ? "onclick=\"window.location.href='".BASE_URL."vehicle_details.php?id=".$v['id']."'\"" : ""; ?>>
                        <?php 
                       
                        $images_array = json_decode($v['image_url'] ?? '', true);
                        $first_image = '';

                        if (!empty($images_array) && is_array($images_array)) {
                            $first_image = trim($images_array[0]);
                        } else {
                            $first_image = trim($v['image'] ?? '');
                        }

                     
                        $img_path = '';
                        if (!empty($first_image)) {
                            if (strpos($first_image, 'uploads/') !== false || strpos($first_image, 'images/') !== false || strpos($first_image, 'http') !== false) {
                                $img_path = $first_image;
                            } else {
                                $img_path = 'uploads/' . $first_image;
                            }
                        } else {
                            $img_path = 'uploads/default.jpg';
                        }
                        ?>
                        
                        <img src="<?php echo (strpos($img_path, 'http') === 0) ? htmlspecialchars($img_path) : BASE_URL . htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($v['brand'] . ' ' . $v['name']); ?>">
                        
                        
                        <?php if (!$is_available): ?>
                            <span class="purpose-badge badge-unavailable">
                                <i class="fa-solid fa-circle-xmark"></i> Not Available
                            </span>
                        <?php else: ?>
                            <span class="purpose-badge <?php echo ($purpose === 'sale') ? 'badge-sale' : 'badge-rent'; ?>">
                                For <?php echo ($purpose === 'sale') ? 'Sale' : 'Rent'; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-content">
                        <p class="card-brand"><?php echo htmlspecialchars($v['brand']); ?></p>
                        
                        <?php if ($is_available): ?>
                            <a href="<?php echo BASE_URL; ?>vehicle_details.php?id=<?php echo $v['id']; ?>" class="card-title">
                                <?php echo htmlspecialchars($v['name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="card-title" style="cursor: not-allowed; color: var(--text-muted);">
                                <?php echo htmlspecialchars($v['name']); ?> (Reserved)
                            </span>
                        <?php endif; ?>

                        <div class="card-specs-mini">
                            <span><i class="fa-solid fa-gauge"></i><?php echo htmlspecialchars($v['transmission']); ?></span>
                            <span><i class="fa-solid fa-gas-pump"></i><?php echo htmlspecialchars($v['fuel_type']); ?></span>
                            <span><i class="fa-solid fa-calendar"></i><?php echo htmlspecialchars($v['model_year']); ?></span>
                        </div>

                        <div class="card-footer" style="margin-bottom: 12px;">
                            <div class="card-price">
                                <?php if ($purpose === 'sale'): ?>
                                    LKR <?php echo number_format($v['selling_price'] ?? 0); ?> <span>(Total)</span>
                                <?php else: ?>
                                    LKR <?php echo number_format($v['price_per_day'] ?? 0); ?> <span>/day</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_available): ?>
                                <a href="<?php echo BASE_URL; ?>vehicle_details.php?id=<?php echo $v['id']; ?>" class="btn-view-details">
                                    <i class="fa-solid fa-arrow-right" style="margin-right:5px; font-size:11px;"></i>Specs
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="card-action-box" style="padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <?php if (!$is_available): ?>
                                <button class="disabled-box" style="width: 100%; text-align: center; padding: 10px; border-radius: 8px; border: none; font-weight: 700; font-size: 13px;">
                                    <i class="fa-solid fa-ban"></i> Out of Stock / Rented
                                </button>
                            <?php elseif ($purpose === 'sale'): ?>
                                <a href="<?php echo BASE_URL; ?>payment.php?id=<?php echo $v['id']; ?>" style="display: block; text-align: center; background: #8b5cf6; color: #fff; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; transition: 0.2s;">
                                    <i class="fa-solid fa-credit-card"></i> Buy Now (Pay)
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>rent_booking.php?id=<?php echo $v['id']; ?>" style="display: block; text-align: center; background: #0d9488; color: #fff; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; transition: 0.2s;">
                                    <i class="fa-solid fa-key"></i> Rent Now
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
                <i class="fa-solid fa-car" style="font-size: 50px; color: rgba(6,182,212,0.2); display:block; margin-bottom:20px;"></i>
                <p style="color: var(--text-muted); font-size: 16px;">No vehicles available in the showroom right now.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once ROOT_DIR . '/footer.php'; ?>
</body>
</html>