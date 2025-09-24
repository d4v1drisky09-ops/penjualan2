<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Get customers for dropdown
$customers = [];
$result = $conn->query("SELECT * FROM customers ORDER BY nama");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Get products for search
$products = [];
$result = $conn->query("SELECT p.*, c.nama as category_nama FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        ORDER BY p.nama");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get payment methods
$payment_methods = ['Tunai', 'Transfer Bank', 'Kartu Kredit', 'E-Wallet'];

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart
if (isset($_GET['add_to_cart']) && !empty($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
        
        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product_id) {
                // Check if stock is sufficient
                if ($item['qty'] + 1 <= $product['stok']) {
                    $item['qty'] += 1;
                    $item['subtotal'] = $item['qty'] * $item['price'];
                } else {
                    $_SESSION['error'] = "Stok tidak mencukupi untuk " . htmlspecialchars($product['nama']);
                }
                $found = true;
                break;
            }
        }
        
        // If not found, add to cart
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'sku' => $product['sku'],
                'nama' => $product['nama'],
                'price' => $product['harga_jual'],
                'qty' => 1,
                'subtotal' => $product['harga_jual']
            ];
        }
    }
    
    header('Location: pos.php');
    exit;
}

// Remove item from cart
if (isset($_GET['remove_from_cart']) && !empty($_GET['remove_from_cart'])) {
    $key = (int)$_GET['remove_from_cart'];
    
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        // Reindex array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    
    header('Location: pos.php');
    exit;
}

// Update cart quantity
if (isset($_POST['update_cart']) && isset($_POST['qty'])) {
    $qtys = $_POST['qty'];
    
    foreach ($qtys as $key => $qty) {
        $qty = (int)$qty;
        
        if ($qty <= 0) {
            // Remove item if quantity is 0 or negative
            unset($_SESSION['cart'][$key]);
        } else {
            // Check if stock is sufficient
            $product_id = $_SESSION['cart'][$key]['id'];
            $stmt = $conn->prepare("SELECT stok FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($qty <= $product['stok']) {
                $_SESSION['cart'][$key]['qty'] = $qty;
                $_SESSION['cart'][$key]['subtotal'] = $qty * $_SESSION['cart'][$key]['price'];
            } else {
                $_SESSION['error'] = "Stok tidak mencukupi untuk " . htmlspecialchars($_SESSION['cart'][$key]['nama']);
            }
        }
    }
    
    // Reindex array
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    header('Location: pos.php');
    exit;
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    header('Location: pos.php');
    exit;
}

// Calculate total
$total_amount = 0;
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += $item['subtotal'];
    $total_items += $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Aplikasi Penjualan</title>
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
        .product-search {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ced4da;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .search-results.show {
            display: block;
        }
        .search-result-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
        }
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .cart-table {
            font-size: 0.9rem;
        }
        .cart-table th, .cart-table td {
            padding: 0.5rem;
        }
        .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }
        .qty-input {
            width: 60px;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
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
                        <a class="nav-link active" href="pos.php">Kasir</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin_dashboard.php">Dashboard Admin</a></li>
                            <li><hr class="dropdown-divider"></li>
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
                            <a class="nav-link" href="products.php">
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
                    <h1 class="h2">Kasir</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pos.php?clear_cart=1" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Apakah Anda yakin ingin mengosongkan keranjang?')">
                                <i class="bi bi-trash"></i> Kosongkan Keranjang
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

                <div class="row">
                    <div class="col-md-5">
                        <!-- Product Search -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Cari Produk</h5>
                            </div>
                            <div class="card-body">
                                <div class="product-search">
                                    <input type="text" class="form-control" id="productSearch" placeholder="Ketik nama atau SKU produk...">
                                    <div class="search-results" id="searchResults"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Informasi Transaksi</h5>
                            </div>
                            <div class="card-body">
                                <form action="process_sale.php" method="post" id="checkoutForm">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Pelanggan</label>
                                        <select class="form-select" id="customer_id" name="customer_id">
                                            <option value="">-- Umum --</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?php echo $customer['id']; ?>">
                                                    <?php echo htmlspecialchars($customer['nama']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pembayaran_method" class="form-label">Metode Pembayaran</label>
                                        <select class="form-select" id="pembayaran_method" name="pembayaran_method" required>
                                            <?php foreach ($payment_methods as $method): ?>
                                                <option value="<?php echo $method; ?>"><?php echo $method; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bayar" class="form-label">Jumlah Bayar</label>
                                        <input type="number" class="form-control" id="bayar" name="bayar" min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="total-section mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Item:</span>
                                            <span id="totalItems"><?php echo $total_items; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Harga:</span>
                                            <span id="totalAmount">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Kembalian:</span>
                                            <span id="kembalian">Rp 0</span>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                                    <input type="hidden" name="total_items" value="<?php echo $total_items; ?>">
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                            <i class="bi bi-check-circle"></i> Proses Transaksi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <!-- Cart -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Keranjang Belanja</h5>
                                <span class="badge bg-primary"><?php echo count($_SESSION['cart']); ?> Item</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($_SESSION['cart'])): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                                        <p class="mt-2">Keranjang belanja kosong</p>
                                        <p>Silakan tambahkan produk dari hasil pencarian</p>
                                    </div>
                                <?php else: ?>
                                    <form action="pos.php" method="post" id="cartForm">
                                        <input type="hidden" name="update_cart" value="1">
                                        <div class="table-responsive">
                                            <table class="table table-hover cart-table">
                                                <thead>
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th>Harga</th>
                                                        <th>Qty</th>
                                                        <th>Subtotal</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php 
                                                                    // Find product image
                                                                    $product_image = null;
                                                                    foreach ($products as $product) {
                                                                        if ($product['id'] == $item['id']) {
                                                                            $product_image = $product['gambar_path'];
                                                                            break;
                                                                        }
                                                                    }
                                                                    
                                                                    if (!empty($product_image)): ?>
                                                                        <img src="download_image.php?path=<?php echo urlencode($product_image); ?>" 
                                                                             class="product-image me-2" alt="<?php echo htmlspecialchars($item['nama']); ?>">
                                                                    <?php else: ?>
                                                                        <img src="https://via.placeholder.com/40x40?text=No+Image" 
                                                                             class="product-image me-2" alt="No Image">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <div><?php echo htmlspecialchars($item['nama']); ?></div>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($item['sku']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                                            <td>
                                                                <input type="number" class="form-control qty-input" name="qty[<?php echo $key; ?>]" 
                                                                       value="<?php echo $item['qty']; ?>" min="1">
                                                            </td>
                                                            <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                                            <td>
                                                                <a href="pos.php?remove_from_cart=<?php echo $key; ?>" 
                                                                   class="btn btn-sm btn-danger">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-arrow-clockwise"></i> Update Keranjang
                                            </button>
                                            <a href="pos.php?clear_cart=1" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin mengosongkan keranjang?')">
                                                <i class="bi bi-trash"></i> Kosongkan Keranjang
                                            </a>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Product search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            const searchResults = document.getElementById('searchResults');
            
            // Product data from PHP
            const products = <?php echo json_encode($products); ?>;
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length < 2) {
                    searchResults.classList.remove('show');
                    return;
                }
                
                // Filter products based on search term
                const filteredProducts = products.filter(product => {
                    return product.nama.toLowerCase().includes(searchTerm) || 
                           product.sku.toLowerCase().includes(searchTerm);
                });
                
                // Display search results
                if (filteredProducts.length > 0) {
                    let html = '';
                    filteredProducts.forEach(product => {
                        html += `
                            <div class="search-result-item" data-id="${product.id}">
                                <div class="d-flex align-items-center">
                                    ${product.gambar_path ? 
                                        `<img src="download_image.php?path=${encodeURIComponent(product.gambar_path)}" class="product-image me-2" alt="${product.nama}">` : 
                                        `<img src="https://via.placeholder.com/40x40?text=No+Image" class="product-image me-2" alt="No Image">`
                                    }
                                    <div>
                                        <div>${product.nama}</div>
                                        <small class="text-muted">${product.sku} | Stok: ${product.stok} | Rp ${new Intl.NumberFormat('id-ID').format(product.harga_jual)}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    searchResults.innerHTML = html;
                    searchResults.classList.add('show');
                } else {
                    searchResults.innerHTML = '<div class="search-result-item">Tidak ada produk yang ditemukan</div>';
                    searchResults.classList.add('show');
                }
            });
            
            // Handle click on search result
            searchResults.addEventListener('click', function(e) {
                const resultItem = e.target.closest('.search-result-item');
                if (resultItem && resultItem.dataset.id) {
                    window.location.href = 'pos.php?add_to_cart=' + resultItem.dataset.id;
                }
            });
            
            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('show');
                }
            });
            
            // Calculate change
            const bayarInput = document.getElementById('bayar');
            const kembalianSpan = document.getElementById('kembalian');
            const totalAmount = <?php echo $total_amount; ?>;
            
            bayarInput.addEventListener('input', function() {
                const bayar = parseFloat(this.value) || 0;
                const kembalian = bayar - totalAmount;
                
                if (kembalian >= 0) {
                    kembalianSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(kembalian);
                    kembalianSpan.classList.remove('text-danger');
                } else {
                    kembalianSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(kembalian)) + ' (Kurang)';
                    kembalianSpan.classList.add('text-danger');
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>