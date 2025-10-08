<?php
// Get tracking ID from URL parameter
$tracking_id = $_GET['tracking_id'] ?? '';

if (empty($tracking_id)) {
    header('Location: report_emergency.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Submitted Successfully - iMSafe Disaster Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/icon2.png">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .success-page {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px 40px 20px;
            text-align: center;
        }
        
        .tracking-info {
            color: white;
            max-width: 600px;
            width: 100%;
        }
        
        .success-icon {
            background: white;
            color: #10b981;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            margin: 0 auto 30px auto;
        }
        
        .tracking-info h3 {
            color: white;
            font-size: 2.5em;
            margin-bottom: 40px;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .tracking-id {
            background: white;
            color: #6b7280;
            padding: 18px 28px;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            letter-spacing: 1px;
            margin: 35px auto;
            display: block;
            max-width: 380px;
            font-family: 'Courier New', monospace;
        }
        
        .tracking-info p {
            color: white;
            line-height: 1.6;
            margin: 25px auto;
            font-size: 1.1rem;
            max-width: 500px;
        }
        
        .tracking-info p strong {
            font-weight: 700;
        }
        
        .btn-group {
            margin-top: 60px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 16px 32px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 160px;
            justify-content: center;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.25);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn:nth-child(3) {
            background: #10b981;
        }
        
        .btn:nth-child(3):hover {
            background: #059669;
        }
        
        /* Show navbar and footer */
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .tracking-info h3 {
                font-size: 2em;
            }
            
            .tracking-id {
                font-size: 1em;
                padding: 18px 25px;
                margin: 30px 20px;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/includes/public_nav.php'; ?>

    <!-- Success Page -->
    <section class="success-page">
        <div class="tracking-info">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h3>Report Submitted Successfully!</h3>
            <div class="tracking-id"><?php echo htmlspecialchars($tracking_id); ?></div>
            <p>Your emergency report has been submitted and assigned to the appropriate LGU. You will receive acknowledgment within 24-48 hours. Save your tracking ID for reference.</p>
            <p><strong>Next Steps:</strong> The LGU will contact you at the provided phone number. Keep your phone accessible.</p>
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    Return to Home
                </a>
                <a href="track_report.php?tracking_id=<?php echo urlencode($tracking_id); ?>" class="btn btn-primary">
                    Track This Report
                </a>
                <a href="report_emergency.php" class="btn btn-primary">
                    Submit Another Report
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php require_once __DIR__ . '/includes/public_footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
</body>
</html>