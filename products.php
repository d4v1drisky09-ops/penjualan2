<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Process delete product
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Check if product exists
    $stmt = $conn->prepare("SELECT gambar_path FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
        
        // Delete product image if exists
        if (!empty($product['gambar_path']) && file_exists($product['gambar_path'])) {
            unlink($product['gambar_path']);
        }
        
        // Delete product from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus produk.";
        }
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan.";
    }
    
    header('Location: products.php');
    exit;
}

// Get products with pagination and search
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

// Get categories for filter
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY nama");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Aplikasi Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .sidebar .nav-link {
            color: #495057;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .content {
            padding: 20px;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .stock-badge {
            font-size: 0.8rem;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop"></i> Aplikasi Penjualan
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Lihat Katalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="products.php">
                                <i class="bi bi-box-seam"></i> Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales_list.php">
                                <i class="bi bi-receipt"></i> Penjualan
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Produk</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="product_form.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus-circle"></i> Tambah Produk
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form action="products.php" method="get" class="d-flex">
                            <input type="hidden" name="category" value="<?php echo isset($_GET['category']) ? $_GET['category'] : ''; ?>">
                            <input class="form-control me-2" type="search" name="search" placeholder="Cari produk..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-outline-primary" type="submit">Cari</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="products.php" method="get">
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

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada produk yang ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['gambar_path'])): ?>
                                                <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" 
                                                     class="product-image" alt="<?php echo htmlspecialchars($product['nama']); ?>">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/50x50?text=No+Image" 
                                                     class="product-image" alt="No Image">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($product['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_nama']); ?></td>
                                        <td>Rp <?php echo number_format($product['harga_jual'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($product['stok'] > 10): ?>
                                                <span class="badge bg-success stock-badge"><?php echo $product['stok']; ?></span>
                                            <?php elseif ($product['stok'] > 0): ?>
                                                <span class="badge bg-warning stock-badge"><?php echo $product['stok']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger stock-badge">Habis</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>