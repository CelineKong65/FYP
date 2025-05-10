<?php
include 'config.php';
include 'header.php';

// Check login
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch cart & wishlist counts
$cartCount = 0;
$wishlistCount = 0;

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cart WHERE CustID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetch()['total'] ?? 0;

    $wishlistStmt = $conn->prepare("SELECT COUNT(*) AS total FROM wishlist WHERE CustID = ?");
    $wishlistStmt->execute([$_SESSION['user_id']]);
    $wishlistCount = $wishlistStmt->fetch()['total'] ?? 0;
}

// Get search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Redirect if query is empty
if (empty($query)) {
    header("Location: product.php");
    exit();
}

// Search products with active product, brand, and category
$stmt = $conn->prepare("
    SELECT p.* 
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    JOIN category c ON p.CategoryID = c.CategoryID
    WHERE p.ProductName LIKE ?
    AND p.ProductStatus = 'active'
    AND b.BrandStatus = 'active'
    AND c.CategoryStatus = 'active'
");
$searchTerm = '%' . $query . '%';
$stmt->execute([$searchTerm]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Watersport Equipment Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }

        h2 {
            margin-top: 120px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 40px;
        }
        
        .product-card {
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            background-color: white;

        }
        .product-card img {
            width: 100%;
            height: auto;
            max-height: 200px;
            object-fit: contain;
        }
        .product-card h3 {
            margin: 10px 0;
        }
    </style>
</head>
<body>

<main style="padding: 40px;">
    <?php if (count($products) > 0): ?>
        <h2>Search Results for "<?= htmlspecialchars($query) ?>"</h2>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <a href="product_details.php?id=<?= $product['ProductID'] ?>" style="text-decoration: none; color: inherit;">
                        <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                        <img src="image/<?= htmlspecialchars($product['ProductPicture']) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                    </a>
                    <p>Price: RM<?= number_format($product['ProductPrice'], 2) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <h2>No products found matching "<?= htmlspecialchars($query) ?>".</h2>
    <?php endif; ?>
</main>

</body>
</html>

<?php include 'footer.php'; ?>