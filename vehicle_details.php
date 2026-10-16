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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: " . BASE_URL . "vehicle_list.php");
    exit();
}

$vehicle_id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$v = $stmt->fetch();

if (!$v) {
    header("Location: " . BASE_URL . "vehicle_list.php");
    exit();
}

$purpose = strtolower($v['purpose'] ?? ''); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($v['brand'] . ' ' . $v['name']); ?> | DriveHub Showroom</title>
    <meta name="description" content="View full specs and book the <?php echo htmlspecialchars($v['brand'] . ' ' . $v['name']); ?> on DriveHub.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css?v=<?php echo time(); ?>">
    

    <style>
        .gallery-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
        }
        .main-image-holder {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            background: #1e293b;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .main-image-holder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: zoom-in; 
            transition: opacity 0.3s ease-in-out;
        }
        
        .thumbnail-wrapper {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }
        .thumbnail-row {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            white-space: nowrap;
            width: 100%;
            padding: 5px 0;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .thumbnail-row::-webkit-scrollbar { display: none; }
        .thumbnail-row { scrollbar-width: none; -ms-overflow-style: none; }
        
        .thumbnail-item {
            flex: 0 0 80px;
            width: 80px;
            height: 60px;
            border-radius: 6px;
            cursor: pointer;
            object-fit: cover;
            border: 2px solid transparent;
            transition: 0.2s;
            opacity: 0.6;
        }
        .thumbnail-item:hover, .thumbnail-item.active {
            border-color: #0d9488;
            opacity: 1;
            transform: scale(1.05);
        }
        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.6);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            z-index: 5;
        }
        .gallery-nav:hover { background: rgba(15, 23, 42, 0.9); }
        .nav-prev { left: 15px; }
        .nav-next { right: 15px; }

        .fullscreen-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.95);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }
        .lightbox-content {
            max-width: 90%;
            max-height: 85%;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.5);
            object-fit: contain;
            animation: zoomEffect 0.3s ease;
        }
        .close-lightbox {
            position: absolute;
            top: 20px;
            right: 25px;
            color: #fff;
            font-size: 35px;
            cursor: pointer;
            transition: 0.3s;
        }
        .close-lightbox:hover { color: #f43f5e; }
        
        .lightbox-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .lightbox-btn:hover { background: rgba(255,255,255,0.25); }
        .lightbox-prev { left: 20px; }
        .lightbox-next { right: 20px; }

        @keyframes zoomEffect {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

<?php include_once ROOT_DIR . '/header.php'; ?>

<div class="details-container">
    <a href="<?php echo BASE_URL; ?>vehicle_list.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Fleet Showroom
    </a>

    <div class="details-layout">
        
       
        <div class="details-image-panel">
            <div class="gallery-container">
                <?php 
                $images = [];

                
                $images_array = json_decode($v['image_url'] ?? '', true);

                if (!empty($images_array) && is_array($images_array)) {
                    foreach ($images_array as $img) {
                        $img = trim($img);
                        if (!empty($img)) {
                            if (strpos($img, 'uploads/') !== false || strpos($img, 'images/') !== false || strpos($img, 'http') !== false) {
                                $images[] = $img;
                            } else {
                                $images[] = 'uploads/' . $img;
                            }
                        }
                    }
                } 
         
                else {
                    $raw_img = trim($v['image'] ?? '');
                    if (!empty($raw_img)) {
                        $raw_images_array = explode(',', $raw_img);
                        foreach ($raw_images_array as $img) {
                            $img = trim($img);
                            if (!empty($img)) {
                                if (strpos($img, 'uploads/') !== false || strpos($img, 'images/') !== false || strpos($img, 'http') !== false) {
                                    $images[] = $img;
                                } else {
                                    $images[] = 'uploads/' . $img;
                                }
                            }
                        }
                    }
                }

                if (empty($images)) {
                    $images[] = 'uploads/default.jpg';
                }
                ?>

           
                <div class="main-image-holder">
                    <button class="gallery-nav nav-prev" onclick="moveSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                    <img src="<?php echo (strpos($images[0], 'http') === 0) ? htmlspecialchars($images[0]) : BASE_URL . htmlspecialchars($images[0]); ?>" id="active-showroom-img" alt="Vehicle Image" onclick="openLightbox()">
                    <button class="gallery-nav nav-next" onclick="moveSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
                </div>

                <div class="thumbnail-wrapper">
                    <div class="thumbnail-row" id="thumbRow">
                        <?php foreach ($images as $index => $img_url): ?>
                            <img 
                                src="<?php echo (strpos($img_url, 'http') === 0) ? htmlspecialchars($img_url) : BASE_URL . htmlspecialchars($img_url); ?>" 
                                class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                onclick="switchShowcaseImage(this, <?php echo $index; ?>)"
                                alt="Preview Thumb">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

      
        <div class="details-info-panel">
            <span class="details-brand"><?php echo htmlspecialchars($v['brand']); ?> Matrix</span>
            <h2><?php echo htmlspecialchars($v['name']); ?></h2>

            <?php if ($purpose === 'sale'): ?>
                <span class="purpose-tag badge-sale">AVAILABLE FOR ACQUISITION / SALE</span>
            <?php else: ?>
                <span class="purpose-tag badge-rent">AVAILABLE FOR DEPLOYMENT / RENT</span>
            <?php endif; ?>

            <div class="specs-grid" style="margin-top: 20px;">
                <div class="spec-item">
                    <i class="fa-solid fa-calendar-days"></i>
                    <div><span>Model Year</span><strong><?php echo htmlspecialchars($v['model_year']); ?></strong></div>
                </div>
                <div class="spec-item">
                    <i class="fa-solid fa-gas-pump"></i>
                    <div><span>Fuel Architecture</span><strong><?php echo htmlspecialchars($v['fuel_type']); ?></strong></div>
                </div>
                <div class="spec-item">
                    <i class="fa-solid fa-gear"></i>
                    <div><span>Transmission</span><strong><?php echo htmlspecialchars($v['transmission']); ?></strong></div>
                </div>
                <div class="spec-item">
                    <i class="fa-solid fa-users"></i>
                    <div><span>Seating Capacity</span><strong><?php echo htmlspecialchars($v['seats']); ?> Persons</strong></div>
                </div>
                <div class="spec-item" style="grid-column: 1 / -1;">
                    <i class="fa-solid fa-id-card"></i>
                    <div>
                        <span>Registration Plate</span>
                        <strong style="color: var(--accent-teal);"><?php echo htmlspecialchars($v['reg_number']); ?></strong>
                    </div>
                </div>
            </div>

            <div class="price-booking-box">
                <div class="detail-price">
                    <?php if ($purpose === 'sale'): ?>
                        LKR <?php echo number_format($v['selling_price'] ?? 0); ?> <span>(Total Price)</span>
                    <?php else: ?>
                        LKR <?php echo number_format($v['price_per_day'] ?? 0); ?> <span>/ day</span>
                    <?php endif; ?>
                </div>

                <div class="action-buttons" style="margin-top: 15px;">
                    <?php if ($purpose === 'sale'): ?>
                        <a href="<?php echo BASE_URL; ?>payment.php?id=<?php echo $v['id']; ?>" class="btn-action buy-now" style="display: block; text-align: center; background: #8b5cf6; color: #fff; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s;">
                            <i class="fa-solid fa-credit-card"></i> Buy Now (Pay)
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>rent_booking.php?id=<?php echo $v['id']; ?>" class="btn-action rent-now" style="display: block; text-align: center; background: #0d9488; color: #fff; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s;">
                            <i class="fa-solid fa-key"></i> Rent Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="lightboxOverlay" class="fullscreen-lightbox">
    <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
    <button class="lightbox-btn lightbox-prev" onclick="moveSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
    <img id="lightboxImg" class="lightbox-content" src="" alt="Fullscreen View">
    <button class="lightbox-btn lightbox-next" onclick="moveSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
</div>


<script>
const imageList = <?php 
    $resolved_images = [];
    foreach ($images as $img) {
        $resolved_images[] = (strpos($img, 'http') === 0) ? $img : BASE_URL . $img;
    }
    echo json_encode($resolved_images); 
?>;
let currentImgIndex = 0;

function updateImageDisplays() {
    
    document.getElementById('active-showroom-img').src = imageList[currentImgIndex];
   
    document.getElementById('lightboxImg').src = imageList[currentImgIndex];
    
    
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    
    if(thumbnails[currentImgIndex]) {
        thumbnails[currentImgIndex].classList.add('active');
        thumbnails[currentImgIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
}

function switchShowcaseImage(element, index) {
    currentImgIndex = index;
    updateImageDisplays();
}

function moveSlide(direction) {
    currentImgIndex += direction;
    
    if (currentImgIndex >= imageList.length) {
        currentImgIndex = 0;
    } else if (currentImgIndex < 0) {
        currentImgIndex = imageList.length - 1;
    }
    updateImageDisplays();
}


function openLightbox() {
    const lightbox = document.getElementById('lightboxOverlay');
    document.getElementById('lightboxImg').src = imageList[currentImgIndex];
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightboxOverlay').style.display = 'none';
    document.body.style.overflow = 'auto'; 
}


document.getElementById('lightboxOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});


document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('lightboxOverlay');
    if (lightbox.style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') moveSlide(1);
        if (e.key === 'ArrowLeft') moveSlide(-1);
    }
});
</script>

<?php require_once ROOT_DIR . '/footer.php'; ?>
</body>
</html>