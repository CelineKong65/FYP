<?php
ob_start();
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$custID = $_SESSION['user_id'];
$eWalletBalance = 0;
$requiredAmount = $_SESSION['required_amount'] ?? 0;
$topupAmount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

$query = "SELECT EWalletBalance FROM customer WHERE CustID = :custID";
$stmt = $conn->prepare($query);
$stmt->bindParam(':custID', $custID, PDO::PARAM_INT);
$stmt->execute();
$eWalletBalance = $stmt->fetchColumn() ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) {
    $topupAmount = (float)$_POST['topup_amount'];
    
    if ($topupAmount <= 0) {
        $error = "The top-up amount must be greater than 0.";
    } else {
        $newBalance = $eWalletBalance + $topupAmount;
        $updateQuery = "UPDATE customer SET EWalletBalance = :newBalance WHERE CustID = :custID";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':newBalance', $newBalance, PDO::PARAM_STR);
        $updateStmt->bindParam(':custID', $custID, PDO::PARAM_INT);
        
        if ($updateStmt->execute()) {
            $_SESSION['topup_success'] = true;
            header('Location: payment.php');
            exit();
        } else {
            $error = "An error occurred during the top-up process. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-wallet Top Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        .topup-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 180px;
            margin-bottom: 60px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .balance-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .balance-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .balance-amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .amount-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .amount-btn {
            flex: 1;
            min-width: 100px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        .amount-btn:hover {
            background-color: #e0e0e0;
        }
        .amount-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #218838;
        }
        .error {
            color: #dc3545;
            margin-top: 5px;
        }
        .required-amount {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="topup-container">
        <h2>E-wallet Top Up</h2>
        
        <div class="balance-info">
            <p>Current Balance</p>
            <p class="balance-amount">RM <?= number_format($eWalletBalance, 2) ?></p>
            <?php if ($requiredAmount > 0): ?>
                <p>You need <span class="required-amount">RM <?= number_format($requiredAmount - $eWalletBalance, 2) ?></span> more to complete your purchase.</p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="topup.php">
            <div class="form-group">
                <label for="topup_amount">Top Up Amount (RM)</label>
                <input type="number" id="topup_amount" name="topup_amount" min="10" step="10" value="<?= $topupAmount > 0 ? $topupAmount : '' ?>" required>
            </div>
            
            <div class="amount-buttons">
                <div class="amount-btn" data-amount="50">RM 50</div>
                <div class="amount-btn" data-amount="100">RM 100</div>
                <div class="amount-btn" data-amount="200">RM 200</div>
                <div class="amount-btn" data-amount="500">RM 500</div>
            </div>
            
            <button type="submit" class="btn">Top Up Now</button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.amount-btn').forEach(b => {
                    b.classList.remove('active');
                });
             
                this.classList.add('active');
                
                document.getElementById('topup_amount').value = this.getAttribute('data-amount');
            });
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        const amountParam = urlParams.get('amount');
        if (amountParam) {
            const matchingBtn = document.querySelector(`.amount-btn[data-amount="${amountParam}"]`);
            if (matchingBtn) {
                matchingBtn.classList.add('active');
            }
        }
    </script>
</body>
</html>

<?php include 'footer.php'; ?>