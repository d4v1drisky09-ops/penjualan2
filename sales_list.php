<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Invoice', 'Tanggal', 'Pelanggan', 'Metode Pembayaran', 'Total Item', 'Total Amount']);
    
    // Get sales data
    $query = "SELECT s.*, c.nama as customer_nama FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.id 
              ORDER BY s.created_at DESC";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['invoice_no'],
            $row['created_at'],
            $row['customer_nama'] ? $row['customer_nama'] : 'Umum',
            $row['pembayaran_method'],
            $row['total_items'],
            $row['total_amount']
        ]);
    }
    
    fclose($output);
    exit;
}

// Get sales with pagination and filters
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
$types = "";

// Date filter
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where .= " AND DATE(s.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where .= " AND DATE(s.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

// Invoice filter
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $where .= " AND s.invoice_no LIKE ?";
    $invoice_param = "%" . $_GET['invoice'] . "%";
    $params[] = $invoice_param;
    $types .= "s";
}

// Get total sales for pagination
$count_query = "SELECT COUNT(*) as total FROM sales s WHERE 1=1" . $where;
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_sales = $result->fetch_assoc()['total'];
$total_pages = ceil($total_sales / $limit);

// Get sales
$query = "SELECT s.*, c.nama as customer_nama FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE 1=1" . $where . " 
          ORDER BY s.created_at DESC 
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
$sales = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penjualan - Aplikasi Penjualan</title>
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
        .table-responsive {
            font-size: 0.9rem;
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
                            <a class="nav-link active" href="sales_list.php">
                                <i class="bi bi-receipt"></i> Penjualan
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Daftar Penjualan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pos.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus-circle"></i> Transaksi Baru
                            </a>
                        </div>
                        <div class="btn-group">
                            <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download"></i> Export CSV
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="sales_list.php" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="invoice" class="form-label">Invoice</label>
                                <input type="text" class="form-control" id="invoice" name="invoice" 
                                       value="<?php echo isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Filter</button>
                                <a href="sales_list.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Metode Pembayaran</th>
                                <th>Total Item</th>
                                <th>Total Amount</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data penjualan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                                        <td><?php echo $sale['customer_nama'] ? htmlspecialchars($sale['customer_nama']) : 'Umum'; ?></td>
                                        <td><?php echo htmlspecialchars($sale['pembayaran_method']); ?></td>
                                        <td><?php echo $sale['total_items']; ?></td>
                                        <td>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                        <td class="action-buttons">
                                            <a href="sale_detail.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Detail
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                                   tabindex="-1">Sebelumnya</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
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