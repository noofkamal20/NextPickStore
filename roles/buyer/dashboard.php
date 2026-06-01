<?php
include_once __DIR__ . '/../../includes/auth_guard.php';
requireRole(['Buyer']);

$buyerName = $_SESSION['full_name'] ?? 'Buyer';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Buyer Dashboard - NextPick</title>
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
                margin-bottom: 34px;
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

            .welcome-card {
                width: 100%;
                max-width: 620px;
                background: #fff;
                border: 1px solid #d8d8d8;
                border-radius: 8px;
                padding: 28px 24px;
            }

            h1 {
                font-size: 30px;
                color: #1b1b1b;
                margin-bottom: 12px;
            }

            .desc {
                color: #666;
                font-size: 14px;
                line-height: 1.6;
                margin-bottom: 22px;
            }

            .primary-btn {
                height: 44px;
                padding: 0 18px;
                border-radius: 6px;
                background: #2155f5;
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                font-size: 14px;
                font-weight: 600;
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
                .logout-btn,
                .primary-btn {
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
                    <a class="nav-link" href="/NextPickStore/roles/buyer/reviews.php">Reviews</a>
                    <a class="logout-btn" href="/NextPickStore/auth/logout.php">Logout</a>
                </nav>
            </header>

            <main class="welcome-card">
                <h1>Buyer Dashboard</h1>
                <p class="desc">
                    Welcome, <?php echo htmlspecialchars($buyerName, ENT_QUOTES, 'UTF-8'); ?>. You can rate products and share your experience from the Reviews page.
                </p>
                <a class="primary-btn" href="/NextPickStore/roles/buyer/reviews.php">Write a Review</a>
            </main>
        </div>
    </body>
</html>
