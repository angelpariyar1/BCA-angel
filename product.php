<?php
require_once("includes/config.php");
require_once("includes/header.php");
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiperPens - Products</title>
    <link rel="stylesheet" href="css/product.css">
</head>
<body>
<main class="storefront">
    <div class="storefront-header">
        <h1>Our Products</h1>
        <p>Explore our wide range of premium stationery, filtered by category.</p>
    </div>

    <!-- Category Filters -->
    <div class="category-filters">
        <button class="filter-btn active" data-filter="all">All Products</button>
        <button class="filter-btn" data-filter="Pens">Pens</button>
        <button class="filter-btn" data-filter="Notebooks">Notebooks</button>
        <button class="filter-btn" data-filter="Art Supplies">Art Supplies</button>
        <button class="filter-btn" data-filter="Desk Accessories">Desk Accessories</button>
        <button class="filter-btn" data-filter="Other">Other</button>
    </div>

    <!-- Product Grid -->
    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $product): ?>
            <div class="product-card" data-category="<?php echo htmlspecialchars($product['category'] ?? 'Other'); ?>">
                <div class="card-image-box">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php if (isset($product['is_exclusive']) && $product['is_exclusive']): ?>
                        <span class="badge exclusive-badge">Exclusive</span>
                    <?php
    endif; ?>
                </div>
                <div class="card-details">
                    <span class="card-cat"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></span>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="price-stock">
                        <span class="card-price">Rs.<?php echo number_format($product['price'], 2); ?></span>
                        <span class="card-stock" id="stock-<?php echo $product['id']; ?>"><?php echo intval($product['stock']); ?> in stock</span>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-outline" onclick="openProductModal(<?php echo $product['id']; ?>)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-purple" onclick="addToCart(<?php echo $product['id']; ?>)" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?> id="btn-add-<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-cart"></i> <span>Add to Cart</span>
                    </button>
                </div>
            </div>
        <?php
endforeach; ?>
        
        <?php if (empty($products)): ?>
            <div class="empty-state">No products found.</div>
        <?php
endif; ?>
    </div>
</main>

<!-- Quick View Modal (AJAX populated) -->
<div id="quickViewModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
        <div class="modal-body" id="modalBody">
            <!-- Loaded via AJAX -->
            <div class="loader"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>
        </div>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="toast-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

<script src="js/product.js"></script>
</body>
</html>
<?php include("includes/footer.php"); ?>
