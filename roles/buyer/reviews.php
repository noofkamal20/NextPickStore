<?php
include_once __DIR__ . '/../../includes/auth_guard.php';
include_once __DIR__ . '/../../includes/config.php';
requireRole(['Buyer']);

$conn = getConnection();
$buyerId = (int) $_SESSION['user_id'];
$buyerName = $_SESSION['full_name'] ?? 'Buyer';
$errors = [];
$successMessage = '';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS nps_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviews_product_id (product_id),
    INDEX idx_reviews_buyer_id (buyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$selectedProductId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$selectedProductName = trim($_GET['product_name'] ?? '');
$productName = $selectedProductName;
$rating = 0;
$reviewText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedProductId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $productId = $postedProductId ?: null;
    $productName = sanitize($_POST['product_name'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);
    $reviewText = sanitize($_POST['review_text'] ?? '');

    if ($productName === '') {
        $errors['product_name'] = 'Product name is required.';
    } elseif (mb_strlen($productName) > 150) {
        $errors['product_name'] = 'Product name must be 150 characters or fewer.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Please choose a rating from 1 to 5 stars.';
    }

    if ($reviewText === '') {
        $errors['review_text'] = 'Review is required.';
    } elseif (mb_strlen($reviewText) > 1000) {
        $errors['review_text'] = 'Review must be 1000 characters or fewer.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO nps_reviews (buyer_id, product_id, product_name, rating, review_text)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisis", $buyerId, $productId, $productName, $rating, $reviewText);

            if (mysqli_stmt_execute($stmt)) {
                $successMessage = 'Your review was submitted successfully.';
                $selectedProductId = $productId;
                $selectedProductName = $productName;
                $rating = 0;
                $reviewText = '';
            } else {
                $errors['general'] = 'Could not save your review. Please try again.';
            }

            mysqli_stmt_close($stmt);
        } else {
            $errors['general'] = 'Could not prepare the review. Please try again.';
        }
    }
}

$reviewWhere = '';
$reviewParams = [];
$reviewTypes = '';

if ($selectedProductId) {
    $reviewWhere = 'WHERE product_id = ?';
    $reviewParams[] = $selectedProductId;
    $reviewTypes = 'i';
} elseif ($selectedProductName !== '') {
    $reviewWhere = 'WHERE product_name = ?';
    $reviewParams[] = $selectedProductName;
    $reviewTypes = 's';
}

$reviews = [];
$reviewSql = "SELECT r.product_name, r.rating, r.review_text, r.created_at, u.full_name
              FROM nps_reviews r
              LEFT JOIN nps_users u ON r.buyer_id = u.user_id
              $reviewWhere
              ORDER BY r.created_at DESC
              LIMIT 20";
$reviewStmt = mysqli_prepare($conn, $reviewSql);

if ($reviewStmt) {
    if (!empty($reviewParams)) {
        mysqli_stmt_bind_param($reviewStmt, $reviewTypes, ...$reviewParams);
    }

    mysqli_stmt_execute($reviewStmt);
    $reviewResult = mysqli_stmt_get_result($reviewStmt);

    while ($row = mysqli_fetch_assoc($reviewResult)) {
        $reviews[] = $row;
    }

    mysqli_stmt_close($reviewStmt);
}

function renderStars($count) {
    $count = (int) $count;
    $stars = '';

    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $count ? '&#9733;' : '&#9734;';
    }

    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reviews - NextPick</title>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }

            body {
                background: #d9d9d9;
                min-height: 100vh;
                padding: 30px;
            }

            .page-wrapper {
                width: 100%;
                max-width: 1200px;
                min-height: 720px;
                margin: 0 auto;
                background: #f8f8f8;
                border-radius: 6px;
                padding: 28px;
            }

            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin-bottom: 28px;
            }

            .logo img {
                max-width: 140px;
                height: auto;
                display: block;
            }

            .nav-actions {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .nav-link,
            .logout-btn {
                min-height: 40px;
                padding: 11px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .nav-link {
                border: 1px solid #2155f5;
                color: #2155f5;
                background: #fff;
            }

            .logout-btn {
                border: none;
                background: #2155f5;
                color: #fff;
            }

            .heading {
                margin-bottom: 24px;
            }

            h1 {
                font-size: 30px;
                color: #1b1b1b;
                margin-bottom: 10px;
            }

            .desc {
                color: #666;
                font-size: 14px;
                line-height: 1.6;
            }

            .content-grid {
                display: grid;
                grid-template-columns: minmax(280px, 420px) 1fr;
                gap: 22px;
                align-items: start;
            }

            .card {
                background: #fff;
                border: 1px solid #d8d8d8;
                border-radius: 8px;
                padding: 24px;
            }

            h2 {
                font-size: 22px;
                color: #1b1b1b;
                margin-bottom: 16px;
            }

            .field-group {
                margin-bottom: 16px;
            }

            label {
                display: block;
                color: #333;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 7px;
            }

            input,
            textarea {
                width: 100%;
                border: 1px solid #3b5cff;
                border-radius: 6px;
                padding: 12px 14px;
                outline: none;
                font-size: 14px;
                background: #fff;
            }

            input {
                height: 44px;
            }

            textarea {
                min-height: 130px;
                resize: vertical;
                line-height: 1.5;
            }

            .error-input {
                border-color: #ff4d4f;
                background: #fffafa;
            }

            .field-error {
                color: #ff4d4f;
                font-size: 12px;
                margin-top: 6px;
            }

            .message {
                padding: 10px 12px;
                border-radius: 6px;
                margin-bottom: 16px;
                font-size: 13px;
            }

            .message.error {
                background: #fff1f0;
                border: 1px solid #ffb3b3;
                color: #d93025;
            }

            .message.success {
                background: #f0fff4;
                border: 1px solid #9ed9ad;
                color: #187437;
            }

            .star-rating {
                display: flex;
                flex-direction: row-reverse;
                justify-content: flex-end;
                gap: 4px;
            }

            .star-rating input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .star-rating label {
                margin: 0;
                color: #c9c9c9;
                cursor: pointer;
                font-size: 32px;
                line-height: 1;
                transition: color 0.15s;
            }

            .star-rating label:hover,
            .star-rating label:hover ~ label,
            .star-rating input:checked ~ label {
                color: #f5b301;
            }

            .btn {
                width: 100%;
                height: 44px;
                border: none;
                border-radius: 6px;
                background: #2155f5;
                color: #fff;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
            }

            .review-list {
                display: grid;
                gap: 14px;
            }

            .review-item {
                border: 1px solid #d8d8d8;
                border-radius: 8px;
                padding: 16px;
                background: #fff;
            }

            .review-meta {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 8px;
                color: #666;
                font-size: 12px;
                flex-wrap: wrap;
            }

            .review-product {
                color: #1b1b1b;
                font-size: 15px;
                font-weight: 700;
                margin-bottom: 5px;
            }

            .review-stars {
                color: #f5b301;
                font-size: 18px;
                letter-spacing: 1px;
                margin-bottom: 10px;
            }

            .review-text {
                color: #333;
                font-size: 14px;
                line-height: 1.6;
            }

            .empty-state {
                color: #666;
                font-size: 14px;
                line-height: 1.6;
                background: #fff;
                border: 1px dashed #9fb2ff;
                border-radius: 8px;
                padding: 18px;
            }

            @media (max-width: 820px) {
                .content-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 640px) {
                body {
                    padding: 16px;
                }

                .page-wrapper {
                    padding: 20px 14px;
                }

                .topbar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .nav-actions,
                .nav-link,
                .logout-btn {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <header class="topbar">
                <a class="logo" href="/NextPickStore/roles/buyer/dashboard.php">
                    <img src="../../assets/images/Logos/nextpickstore-logo.png" alt="NextPickStore Logo">
                </a>
                <nav class="nav-actions">
                    <a class="nav-link" href="/NextPickStore/roles/buyer/dashboard.php">Dashboard</a>
                    <a class="logout-btn" href="/NextPickStore/auth/logout.php">Logout</a>
                </nav>
            </header>

            <section class="heading">
                <h1>Product Reviews</h1>
                <p class="desc">
                    Hi <?php echo htmlspecialchars($buyerName, ENT_QUOTES, 'UTF-8'); ?>, rate a product and write a short review to help other buyers choose with confidence.
                </p>
            </section>

            <main class="content-grid">
                <section class="card">
                    <h2>Write a Review</h2>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="message error"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>

                    <?php if ($successMessage !== ''): ?>
                        <div class="message success"><?php echo $successMessage; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="reviews.php<?php echo $selectedProductId ? '?product_id=' . (int) $selectedProductId : ''; ?>" novalidate>
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string) ($selectedProductId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="field-group">
                            <label for="product_name">Product</label>
                            <input
                                type="text"
                                id="product_name"
                                name="product_name"
                                placeholder="Enter product name"
                                value="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>"
                                class="<?php echo !empty($errors['product_name']) ? 'error-input' : ''; ?>"
                            >
                            <?php if (!empty($errors['product_name'])): ?>
                                <div class="field-error"><?php echo $errors['product_name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="field-group">
                            <label>Rating</label>
                            <div class="star-rating" aria-label="Choose a rating from 1 to 5 stars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input
                                        type="radio"
                                        id="star<?php echo $i; ?>"
                                        name="rating"
                                        value="<?php echo $i; ?>"
                                        <?php echo $rating === $i ? 'checked' : ''; ?>
                                    >
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">&#9733;</label>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($errors['rating'])): ?>
                                <div class="field-error"><?php echo $errors['rating']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="field-group">
                            <label for="review_text">Review</label>
                            <textarea
                                id="review_text"
                                name="review_text"
                                placeholder="Share what you liked, what could be better, and whether you recommend it."
                                class="<?php echo !empty($errors['review_text']) ? 'error-input' : ''; ?>"
                            ><?php echo htmlspecialchars($reviewText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <?php if (!empty($errors['review_text'])): ?>
                                <div class="field-error"><?php echo $errors['review_text']; ?></div>
                            <?php endif; ?>
                        </div>

                        <button class="btn" type="submit">Submit Review</button>
                    </form>
                </section>

                <section class="card">
                    <h2>Recent Reviews</h2>

                    <?php if (empty($reviews)): ?>
                        <div class="empty-state">
                            No reviews yet. Be the first buyer to rate this product.
                        </div>
                    <?php else: ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $review): ?>
                                <article class="review-item">
                                    <div class="review-meta">
                                        <span><?php echo htmlspecialchars($review['full_name'] ?? 'Buyer', ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span><?php echo htmlspecialchars(date('M j, Y', strtotime($review['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="review-product"><?php echo htmlspecialchars($review['product_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="review-stars" aria-label="<?php echo (int) $review['rating']; ?> out of 5 stars">
                                        <?php echo renderStars($review['rating']); ?>
                                    </div>
                                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </body>
</html>
