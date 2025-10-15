<?php
// Custom 404 Not Found Page
$page_title = 'Page Not Found - iMSafe';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
    <style>
        :root {
            --gradient-start: #4f46e5;
            --gradient-end: #7c3aed;
            --button-primary: #2563eb;
            --button-primary-hover: #1d4ed8;
            --button-secondary: rgba(255, 255, 255, 0.16);
            --button-secondary-border: rgba(255, 255, 255, 0.3);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: #fff;
            text-align: center;
            padding: 40px 16px;
        }

        .error-wrapper {
            max-width: 520px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .error-icon {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.6rem;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(8px);
        }

        .error-code {
            font-size: clamp(3.2rem, 8vw, 4.6rem);
            font-weight: 800;
            margin: 6px 0 0;
        }

        .error-title {
            font-size: clamp(1.6rem, 4vw, 2.1rem);
            margin: 4px 0 0;
            font-weight: 600;
        }

        .error-message {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            max-width: 440px;
            color: rgba(255, 255, 255, 0.85);
        }

        .error-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .error-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background 0.25s ease, transform 0.25s ease;
        }

        .error-button.secondary {
            background: var(--button-secondary);
            border: 1px solid var(--button-secondary-border);
            color: #f9fafb;
        }

        .error-button.secondary:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.22);
        }

        .error-button.primary {
            background: var(--button-primary);
            color: #fff;
        }

        .error-button.primary:hover {
            transform: translateY(-2px);
            background: var(--button-primary-hover);
        }

        .error-footer {
            width: 100%;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
        }

        @media (max-width: 480px) {
            .error-actions {
                flex-direction: column;
            }

            .error-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Page Not Found</h2>
        <p class="error-message">
            Sorry, the page you are looking for doesn’t exist or has been moved. This might be due to a broken link or incorrect URL.
        </p>

        <div class="error-actions">
            <a href="index.php" class="error-button secondary">
                <i class="fas fa-home"></i>
                Go Home
            </a>
            <a href="report_emergency.php" class="error-button primary">
                <i class="fas fa-triangle-exclamation"></i>
                Report Emergency
            </a>
        </div>

        <div class="error-footer">
            IMSafe Disaster Monitoring System · Emergency Response • Community Safety
        </div>
    </div>
</body>
</html>
