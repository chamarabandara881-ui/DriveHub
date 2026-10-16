<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    die("Review node not found.");
}

$message = '';


if (isset($_POST['update_review'])) {
    $user_name = $_POST['user_name'];
    $designation = $_POST['designation'];
    $rating = (int)$_POST['rating'];
    $review_text = $_POST['review_text'];
    $user_image = $_POST['user_image'];

    if (!empty($user_name) && !empty($review_text)) {
        $update_stmt = $pdo->prepare("UPDATE reviews SET user_name = ?, designation = ?, rating = ?, review_text = ?, user_image = ? WHERE id = ?");
        $update_stmt->execute([$user_name, $designation, $rating, $review_text, $user_image, $id]);
        header('Location: feedback.php');
        exit;
    } else {
        $message = "<p style='color: #f87171;'>Please fill required fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Testimonial</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 4px; box-sizing: border-box;}
        button { background: #38bdf8; color: #0f172a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;}
        .back-link { color: #94a3b8; text-decoration: none; margin-left: 15px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Testimonial Node</h2>
    <?php echo $message; ?>
    
    <form action="" method="POST">
        <div class="form-group">
            <label>Customer Name *</label>
            <input type="text" name="user_name" value="<?php echo htmlspecialchars($review['user_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Designation</label>
            <input type="text" name="designation" value="<?php echo htmlspecialchars($review['designation']); ?>">
        </div>
        <div class="form-group">
            <label>Rating</label>
            <select name="rating">
                <option value="5" <?php echo $review['rating'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                <option value="4" <?php echo $review['rating'] == 4 ? 'selected' : ''; ?>>4 Stars</option>
                <option value="3" <?php echo $review['rating'] == 3 ? 'selected' : ''; ?>>3 Stars</option>
                <option value="2" <?php echo $review['rating'] == 2 ? 'selected' : ''; ?>>2 Stars</option>
                <option value="1" <?php echo $review['rating'] == 1 ? 'selected' : ''; ?>>1 Star</option>
            </select>
        </div>
        <div class="form-group">
            <label>Review Text *</label>
            <textarea name="review_text" rows="5" required><?php echo htmlspecialchars($review['review_text']); ?></textarea>
        </div>
        <div class="form-group">
            <label>User Avatar URL</label>
            <input type="url" name="user_image" value="<?php echo htmlspecialchars($review['user_image']); ?>">
        </div>
        <button type="submit" name="update_review">Update Node</button>
        <a href="feedback.php" class="back-link">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>