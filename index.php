<?php
session_start();
include 'koneksi.php';

// Get categories for filter
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY nama");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get products with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
$types = "";

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND (p.nama LIKE ? OR p.sku LIKE ? OR p.deskripsi LIKE ?)";
    $search_param = "%" . $_GET['search'] . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where .= " AND p.category_id = ?";
    $params[] = $_GET['category'];
    $types .= "i";
}

// Get total products for pagination
$count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1" . $where;
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_products = $result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$query = "SELECT p.*, c.nama as category_nama FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1" . $where . " 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

// Combine all parameters including limit and offset
$all_params = $params;
$all_params[] = $limit;
$all_params[] = $offset;
$all_types = $types . "ii";

// Bind parameters
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KATALOG PRODUK - Aplikasi Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i>APLIKASI PENJUALAN
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="mb-4">Katalog Produk</h1>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="index.php" method="get" class="d-flex">
                    <input type="hidden" name="category" value="<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>">
                    <input class="form-control me-2" type="search" name="search" placeholder="Cari produk..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-success" type="submit">Cari</button>
                </form>
            </div>
            <div class="col-md-6">
                <form action="index.php" method="get">
                    <input type="hidden" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <select class="form-select" name="category" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tidak ada produk yang ditemukan.</div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card product-card">
                            <?php if (!empty($product['gambar_path'])): ?>
                                <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" 
                                     class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['nama']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200?text=No+Image" 
                                     class="card-img-top product-image" alt="No Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['nama']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small><br>
                                    <small class="text-muted">Kategori: <?php echo htmlspecialchars($product['category_nama']); ?></small><br>
                                    <?php if ($product['stok'] > 0): ?>
                                        <span class="badge bg-success">Stok: <?php echo $product['stok']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Habis</span>
                                    <?php endif; ?>
                                </p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($product['deskripsi'], 0, 100))); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Rp <?php echo number_format($product['harga_jual'], 0, ',', '.'); ?></h5>
                                    <a href="pos.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i> Beli
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>&category=<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>" 
                           tabindex="-1">Sebelumnya</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>&category=<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>&category=<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>">
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3">
        <div class="container">
            <span class="text-muted">© <?php echo date('Y'); ?> Aplikasi Penjualan. All rights reserved.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>