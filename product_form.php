<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Get product data if editing
$product = null;
$is_edit = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
    $is_edit = true;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan.";
        header('Location: products.php');
        exit;
    }
}

// Get categories
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
    <title><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> Produk - Aplikasi Penjualan</title>
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
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-top: 10px;
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> Produk</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="products.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <form action="process_product.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $is_edit ? $product['id'] : ''; ?>">
                            
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sku" name="sku" required 
                                       value="<?php echo $is_edit ? htmlspecialchars($product['sku']) : ''; ?>">
                                <div class="form-text">SKU harus unik.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" required 
                                       value="<?php echo $is_edit ? htmlspecialchars($product['nama']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($is_edit && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['nama']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo $is_edit ? htmlspecialchars($product['deskripsi']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="harga_jual" class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="harga_jual" name="harga_jual" required min="0" step="0.01"
                                           value="<?php echo $is_edit ? $product['harga_jual'] : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="harga_beli" class="form-label">Harga Beli (Rp)</label>
                                    <input type="number" class="form-control" id="harga_beli" name="harga_beli" min="0" step="0.01"
                                           value="<?php echo $is_edit ? $product['harga_beli'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stok" name="stok" required min="0"
                                       value="<?php echo $is_edit ? $product['stok'] : '0'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept=".jpg,.jpeg,.png">
                                <div class="form-text">Format: JPG/PNG, Maksimal: 2MB</div>
                                <?php if ($is_edit && !empty($product['gambar_path'])): ?>
                                    <div class="mt-2">
                                        <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" 
                                             class="preview-image" alt="Product Image">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="hapus_gambar" name="hapus_gambar" value="1">
                                            <label class="form-check-label" for="hapus_gambar">
                                                Hapus gambar
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="products.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $is_edit ? 'Update' : 'Simpan'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>