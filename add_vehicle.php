<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';


if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: login.php"); exit();
}

$message = "";
$message_type = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        $name          = $_POST['name'] ?? '';
        $title         = $_POST['title'] ?? '';
        $brand         = $_POST['brand'] ?? '';
        $category      = $_POST['category'] ?? '';
        $purpose       = $_POST['purpose'] ?? 'rent';
        $fuel_type     = $_POST['fuel_type'] ?? '';
        $transmission  = $_POST['transmission'] ?? '';
        $mileage       = !empty($_POST['mileage']) ? (int)$_POST['mileage'] : 0;
        $engine_cc     = !empty($_POST['engine_cc']) ? (int)$_POST['engine_cc'] : 0;
        $description   = $_POST['description'] ?? '';
        $status        = $_POST['status'] ?? 'Available';
        $model_year    = !empty($_POST['model_year']) ? (int)$_POST['model_year'] : null;
        $seats         = !empty($_POST['seats']) ? (int)$_POST['seats'] : 4;
        $reg_number    = $_POST['reg_number'] ?? '';
        

        $price_per_day = ($purpose === 'rent' && !empty($_POST['price_per_day'])) ? (float)$_POST['price_per_day'] : 0.00;
        $selling_price = ($purpose === 'sale' && !empty($_POST['selling_price'])) ? (float)$_POST['selling_price'] : 0.00;

        $uploaded_images = [];
        $uploaded_urls = [];

        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $upload_file_dir = './uploads/';
            if(!is_dir($upload_file_dir)){
                mkdir($upload_file_dir, 0777, true);
            }

            foreach ($_FILES['images']['name'] as $key => $val) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['images']['tmp_name'][$key];
                    $file_name = $_FILES['images']['name'][$key];
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    $new_file_name = time() . '_' . md5(rand() . $key) . '.' . $file_extension;
                    $dest_path = $upload_file_dir . $new_file_name;
                    
                    if(move_uploaded_file($file_tmp_path, $dest_path)) {
                        $uploaded_images[] = $new_file_name;
                        $uploaded_urls[]   = 'uploads/' . $new_file_name;
                    }
                }
            }
        }

        $image_name_data = json_encode($uploaded_images);
        $image_url_data  = json_encode($uploaded_urls);


        $sql = "INSERT INTO vehicles (
                    name, title, brand, category, purpose, price_per_day, selling_price, 
                    fuel_type, transmission, mileage, engine_cc, description, image, 
                    image_url, status, model_year, seats, reg_number, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, NOW()
                )";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $title, $brand, $category, $purpose, $price_per_day, $selling_price,
            $fuel_type, $transmission, $mileage, $engine_cc, $description, $image_name_data,
            $image_url_data, $status, $model_year, $seats, $reg_number
        ]);

        $message = "Vehicle with multiple media assets added successfully!";
        $message_type = "success";
    } catch (\PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
}


$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub - Add New Fleet Asset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg-dark: #0f172a; 
            --panel-bg: #1e293b; 
            --accent-teal: #0d9488; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; 
            --border: #334155;
            --success: #10b981;
            --error: #ef4444;
            --sidebar-width: 260px;
        }
        
        * { margin:0; padding:0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-dark); 
            color: var(--text-main); 
            display: flex; 
            min-height: 100vh; 
        }
        
    
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--panel-bg); 
            border-right: 1px solid rgba(255, 255, 255, 0.05); 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 35px; 
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar .logo { 
            font-size: 22px; 
            font-weight: 700; 
            color: #fff; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding-left: 12px;
        }
        
        .sidebar .logo i { color: var(--accent-teal); }
        
        .menu-list { 
            list-style: none; 
            padding: 0;
            margin: 0;
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }
        
        .menu-list li a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            color: var(--text-muted); 
            text-decoration: none; 
            padding: 12px 15px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 500; 
            transition: all 0.2s ease; 
        }
        
        .menu-list li a:hover { 
            background: rgba(255, 255, 255, 0.03); 
            color: #fff; 
        }
        
        .menu-list li a.active { 
            background: var(--accent-teal); 
            color: #fff; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3); 
        }
        
        .menu-list li a i { font-size: 16px; width: 20px; text-align: center; }
        

        .main-content { 
            flex: 1; 
            padding: 40px; 
            margin-left: var(--sidebar-width);
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        
        .btn-primary { background: var(--accent-teal); color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; border: none; cursor: pointer; }
        .btn-primary:hover { background: #0f766e; box-shadow: 0 0 15px rgba(13, 148, 136, 0.4); }
        .btn-secondary { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; cursor: pointer; }
        .btn-secondary:hover { background: rgba(255,255,255,0.05); color: #fff; }

        .card { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .card-title { font-size: 20px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; color: var(--accent-teal); border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 14px; font-weight: 600; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { padding: 12px; background: var(--bg-dark); border: 1px solid var(--border); color: #fff; border-radius: 8px; font-size: 15px; outline: none; transition: 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent-teal); box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2); }
        
 
        .alert { padding: 16px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid var(--success); }
        .alert.error { background: rgba(239, 68, 68, 0.15); color: var(--error); border: 1px solid var(--error); }

        .form-actions { display: flex; justify-content: flex-end; gap: 14px; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; }
    </style>
</head>
<body>

   
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-gauge-high"></i> DriveHub</div>
        
        
        <div>
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 12px;">Admin Controls</p>
            <ul class="menu-list">
                <li>
                    <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Control Panel
                    </a>
                </li>
                <li>
                    <a href="manage_payments.php" class="<?php echo ($current_page == 'manage_payments.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-credit-card"></i> Manage Payments
                    </a>
                </li>

                <li>
                    <a href="manage_rent_requests.php" class="<?php echo ($current_page == 'manage_rent_requests.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-check"></i> Rent Requests
                    </a>
                </li>
                <li>
                    <a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-plus"></i> Add New Vehicle
                    </a>
                </li>
                <li>
                    <a href="feedback.php" class="<?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-star"></i> Testimonial Control
                    </a>
                </li>
                <li>
                    <a href="admin_message.php" class="<?php echo ($current_page == 'admin_message.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-envelope-open-text"></i> Inbound Messages
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 12px;">Main Navigation</p>
            <ul class="menu-list">
                <li><a href="index.php"><i class="fa-solid fa-house"></i> Showroom</a></li>
                <li><a href="vehicle_list.php"><i class="fa-solid fa-car"></i> Explore Fleet</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About Us</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact</a></li>
                <li><a href="logout.php" style="color: #f87171; margin-top: 15px;"><i class="fa-solid fa-right-from-bracket"></i> System Exit</a></li>
            </ul>
        </div>  
    </div>

   
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1 style="font-size: 28px; font-weight: 800; margin: 0;">Add New Fleet Vehicle</h1>
                <p style="color: var(--text-muted); margin-top: 5px; margin-bottom: 0;">Register a new rental asset or commercial sales listing.</p>
            </div>
            <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title"><i class="fa-solid fa-car-rear"></i> Vehicle Specifications Sheet</div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    
                    
                    <div class="form-group">
                        <label for="purpose">Listing Purpose </label>
                        <select name="purpose" id="purpose" onchange="togglePriceFields()" required>
                            <option value="rent">For Rent </option>
                            <option value="sale">For Sale </option>
                        </select>
                    </div>

                  
                    <div class="form-group">
                        <label for="name">Vehicle Name ( Civic, Axio)</label>
                        <input type="text" name="name" id="name" placeholder="e.g. Prius S Grade" required>
                    </div>

                  
                    <div class="form-group">
                        <label for="title">Display Title </label>
                        <input type="text" name="title" id="title" placeholder="e.g. Luxury Toyota Prius For Rent" required>
                    </div>

                 
                    <div class="form-group">
                        <label for="brand">Brand / Manufacturer </label>
                        <input type="text" name="brand" id="brand" placeholder="e.g. Toyota, Honda, Nissan" required>
                    </div>

                    
                    <div class="form-group">
                        <label for="category">Category </label>
                        <select name="category" id="category">
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                            <option value="jeep">Jeep</option>
                            <option value="Three-Wheeler">Three-Wheeler</option>
                            <option value="motorbike">Motoebike</option>
                        </select>
                    </div>

                   
                    <div class="form-group">
                        <label for="reg_number">Registration Number </label>
                        <input type="text" name="reg_number" id="reg_number" placeholder="e.g. WP CAS-1234">
                    </div>

                   
                    <div class="form-group" id="rent_price_group">
                        <label for="price_per_day">Rental Price Per Day (LKR)</label>
                        <input type="number" step="0.01" name="price_per_day" id="price_per_day" placeholder="0.00">
                    </div>

                    
                    <div class="form-group" id="sale_price_group" style="display: none;">
                        <label for="selling_price">Selling Price (LKR)</label>
                        <input type="number" step="0.01" name="selling_price" id="selling_price" placeholder="0.00">
                    </div>

                    
                    <div class="form-group">
                        <label for="model_year">Model Year </label>
                        <input type="number" name="model_year" id="model_year" placeholder="e.g. 2022">
                    </div>

                    
                    <div class="form-group">
                        <label for="fuel_type">Fuel Type </label>
                        <select name="fuel_type" id="fuel_type">
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>

                   
                    <div class="form-group">
                        <label for="transmission">Transmission </label>
                        <select name="transmission" id="transmission">
                            <option value="Auto">Automatic</option>
                            <option value="Manual">Manual</option>
                            <option value="Tiptronic">Tiptronic</option>
                        </select>
                    </div>

                   
                    <div class="form-group">
                        <label for="mileage">Mileage </label>
                        <input type="number" name="mileage" id="mileage" placeholder="e.g. 45000">
                    </div>

                    
                    <div class="form-group">
                        <label style="font-weight: 600;" for="engine_cc">Engine Capacity (CC)</label>
                        <input type="number" name="engine_cc" id="engine_cc" placeholder="e.g. 1500">
                    </div>

                    
                    <div class="form-group">
                        <label for="seats">Number of Seats </label>
                        <input type="number" name="seats" id="seats" value="5">
                    </div>

                    <div class="form-group">
                        <label for="status">Initial Status</label>
                        <select name="status" id="status">
                            <option value="Available">Available / In Stock</option>
                            <option value="Rented">Rented</option>
                            <option value="Sold">Sold</option>
                        </select>
                    </div>

                   
                    <div class="form-group full-width">
                        <label for="images">Vehicle Media Display </label>
                        <input type="file" name="images[]" id="images" accept="image/*" multiple required>
                    </div>

                    
                    <div class="form-group full-width">
                        <label for="description">Comprehensive Description </label>
                        <textarea name="description" id="description" rows="5" placeholder="Enter vehicle conditions, features, and extra specifications..."></textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="reset" class="btn-secondary">Clear Fields</button>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Save Asset to Fleet</button>
                </div>
            </form>
        </div>
    </div>

   
    <script>
        function togglePriceFields() {
            const purpose = document.getElementById('purpose').value;
            const rentGroup = document.getElementById('rent_price_group');
            const saleGroup = document.getElementById('sale_price_group');

            if (purpose === 'rent') {
                rentGroup.style.display = 'block'; 
                saleGroup.style.display = 'none';
                document.getElementById('selling_price').value = ''; 
            } else {
                rentGroup.style.display = 'none';
                saleGroup.style.display = 'block';
                document.getElementById('price_per_day').value = ''; 
            }
        }
    </script>
</body>
</html>