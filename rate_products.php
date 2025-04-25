<?php 
include 'config.php'; 
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $orderId = (int)($_POST['order_id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');

    // Validate input
    if ($productId <= 0 || $orderId <= 0) {
        $error = 'Invalid product or order reference.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5 stars.';
    }

    if (!$error) {
        // Verify the user actually purchased this product in this size
        $verificationSql = "
            SELECT od.OrderDetailID
            FROM orderdetails od
            JOIN orderpayment op ON od.OrderID = op.OrderID
            JOIN product p ON od.ProductName = p.ProductName
            WHERE op.OrderID = ?
              AND op.CustID = ?
              AND p.ProductID = ?
              AND od.Size = ?
              AND NOT EXISTS (
                  SELECT 1 FROM product_feedback pf
                  WHERE pf.OrderID = op.OrderID
                    AND pf.ProductID = p.ProductID
                    AND pf.Size = od.Size
                    AND pf.CustID = op.CustID
              )
        ";
        
        $stmt = $conn->prepare($verificationSql);
        $stmt->execute([$orderId, $user_id, $productId, $size]);
        
        if ($stmt->rowCount() === 0) {
            $error = 'You can only review products you\'ve purchased in this specific size.';
        } else {
            // Insert new feedback
            $insertSql = "
                INSERT INTO product_feedback
                  (OrderID, ProductID, Size, CustID, Rating, Feedback, FeedbackDate)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $conn->prepare($insertSql);
            if ($stmt->execute([$orderId, $productId, $size, $user_id, $rating, $feedback])) {
                $success = 'Thank you for your feedback!';
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
        }
    }
}

// Fetch all purchased products not yet reviewed, grouped by size
$listSql = "
    SELECT 
      op.OrderID,
      op.OrderDate,
      p.ProductID,
      p.ProductName,
      p.ProductPicture,
      od.Size,
      od.ProductPrice
    FROM orderpayment AS op
    JOIN orderdetails AS od ON op.OrderID = od.OrderID
    JOIN product AS p ON od.ProductName = p.ProductName
    LEFT JOIN product_feedback AS pf ON pf.OrderID = op.OrderID
      AND pf.ProductID = p.ProductID
      AND pf.Size = od.Size
      AND pf.CustID = op.CustID
    WHERE op.CustID = ?
      AND pf.ProductFeedbackID IS NULL
    ORDER BY op.OrderDate DESC, p.ProductName, od.Size
";

$stmt = $conn->prepare($listSql);
$stmt->execute([$user_id]);
$ordersResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rate Your Purchases</title>
  <link rel="stylesheet" href="rate_products.css">
</head>
<body>
  <h2>Rate Your Purchases</h2>

  <?php if ($success): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (empty($ordersResult)): ?>
    <div class="alert info">
      You don't have any products to review at this time.
    </div>
  <?php else: ?>
    <div class="product-grid">
      <?php 
      $currentProduct = null;
      foreach ($ordersResult as $order): 
        $productKey = $order['ProductID'].'-'.$order['Size'];
        $uniquePrefix = 'rating_' . $order['ProductID'] . '_' . md5($order['Size']);
        
        if ($currentProduct !== $productKey):
          $currentProduct = $productKey;
      ?>

      <div class="product-item">
        <?php if (!empty($order['ProductPicture'])): ?>
            <img src="image/<?= htmlspecialchars($order['ProductPicture']) ?>" 
                alt="<?= htmlspecialchars($order['ProductName']) ?>"
                style="max-width: 100px; max-height: 100px; object-fit: contain; margin-bottom: 10px;">
          <?php endif; ?>
          <div class="product-title"><?= htmlspecialchars($order['ProductName']) ?></div>
          <div class="product-details">
            <strong>Size:</strong> <?= htmlspecialchars($order['Size']) ?><br>
            Order #<?= htmlspecialchars($order['OrderID']) ?> - <?= date('M d, Y', strtotime($order['OrderDate'])) ?>
          </div>

          <form method="post" action="rate_products.php">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($order['ProductID']) ?>">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['OrderID']) ?>">
            <input type="hidden" name="size" value="<?= htmlspecialchars($order['Size']) ?>">

            <label class="rating-label">Your Rating:</label>
            <div class="rating-stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" name="rating" class="d-none" id="<?= $uniquePrefix . '_star' . $i ?>" value="<?= $i ?>">
                <label for="<?= $uniquePrefix . '_star' . $i ?>" class="rating-star">★</label>
              <?php endfor; ?>
            </div>

            <label class="feedback-label">Your Feedback (optional):</label>
            <textarea name="feedback" placeholder="Share your experience..."></textarea>

            <button type="submit" class="submit-btn">Submit Review</button>
          </form>
      </div>
        <?php 
          endif;
        endforeach; 
        ?>
      <?php endif; ?>
    </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // Handle star rating interactions
    document.querySelectorAll('.rating-star').forEach(star => {
      star.addEventListener('click', function() {
        // Get all stars in this group
        const stars = this.parentElement.querySelectorAll('.rating-star');
        const inputId = this.getAttribute('for');
        const input = document.getElementById(inputId);
        const ratingValue = parseInt(input.value);
        
        // Update visual state
        stars.forEach((s, index) => {
          const starInputId = s.getAttribute('for');
          const starInput = document.getElementById(starInputId);
          const starValue = parseInt(starInput.value);
          
          if (starValue <= ratingValue) {
            s.classList.add('active');
          } else {
            s.classList.remove('active');
          }
        });
      });
    });

    // Highlight stars on hover
    document.querySelectorAll('.rating-star').forEach(star => {
      star.addEventListener('mouseover', function() {
        const stars = this.parentElement.querySelectorAll('.rating-star');
        const inputId = this.getAttribute('for');
        const input = document.getElementById(inputId);
        const hoverValue = parseInt(input.value);
        
        stars.forEach((s, index) => {
          const starInputId = s.getAttribute('for');
          const starInput = document.getElementById(starInputId);
          const starValue = parseInt(starInput.value);
          
          if (starValue <= hoverValue) {
            s.style.color = '#f1c40f';
          } else {
            s.style.color = '#ddd';
          }
        });
      });

      // Reset to selected state when mouse leaves
      star.parentElement.addEventListener('mouseleave', function() {
        const stars = this.querySelectorAll('.rating-star');
        const checkedInput = this.querySelector('input:checked');
        
        if (checkedInput) {
          const checkedValue = parseInt(checkedInput.value);
          stars.forEach((s, index) => {
            const starInputId = s.getAttribute('for');
            const starInput = document.getElementById(starInputId);
            const starValue = parseInt(starInput.value);
            
            if (starValue <= checkedValue) {
              s.style.color = '#f1c40f';
            } else {
              s.style.color = '#ddd';
            }
          });
        } else {
          stars.forEach(s => {
            s.style.color = '#ddd';
          });
        }
      });
    });
  });
  </script>
</body>
</html>

<?php include 'footer.php'; ?>