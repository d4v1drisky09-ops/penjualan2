<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Get sale ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID penjualan tidak ditemukan.";
    header('Location: sales_list.php');
    exit;
}

$sale_id = (int)$_GET['id'];

// Get sale data
$stmt = $conn->prepare("SELECT s.*, c.nama as customer_nama, c.email, c.telepon, c.alamat 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Data penjualan tidak ditemukan.";
    header('Location: sales_list.php');
    exit;
}

$sale = $result->fetch_assoc();

// Get sale items
$stmt = $conn->prepare("SELECT si.*, p.nama as product_nama, p.sku 
                        FROM sale_items si 
                        JOIN products p ON si.product_id = p.id 
                        WHERE si.sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();
$sale_items = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sale_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penjualan - Aplikasi Penjualan</title>
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
        .invoice-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .invoice-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .invoice-detail {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
        }
        .table-responsive {
            font-size: 0.9rem;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        @media print {
            .no-print {
                display: none;
            }
            .invoice-detail {
                border: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
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
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse no-print">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                    <h1 class="h2">Detail Penjualan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Cetak
                            </button>
                        </div>
                        <div class="btn-group">
                            <a href="sales_list.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="invoice-detail">
                    <div class="invoice-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h2 class="invoice-title">INVOICE</h2>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($sale['invoice_no']); ?></strong></p>
                                <p class="mb-0">Tanggal: <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h4>Aplikasi Penjualan</h4>
                                <p class="mb-0">Alamat: Jl. Contoh No. 123</p>
                                <p class="mb-0">Telepon: (021) 1234567</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informasi Pelanggan</h5>
                            <?php if ($sale['customer_nama']): ?>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($sale['customer_nama']); ?></strong></p>
                                <?php if ($sale['email']): ?>
                                    <p class="mb-1">Email: <?php echo htmlspecialchars($sale['email']); ?></p>
                                <?php endif; ?>
                                <?php if ($sale['telepon']): ?>
                                    <p class="mb-1">Telepon: <?php echo htmlspecialchars($sale['telepon']); ?></p>
                                <?php endif; ?>
                                <?php if ($sale['alamat']): ?>
                                    <p class="mb-0">Alamat: <?php echo nl2br(htmlspecialchars($sale['alamat'])); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="mb-0">Pelanggan Umum</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Pembayaran</h5>
                            <p class="mb-1"><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($sale['pembayaran_method']); ?></p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Lunas</span></p>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>SKU</th>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($sale_items as $item): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_nama']); ?></td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                        <td><?php echo $item['qty']; ?></td>
                                        <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" rowspan="3" class="text-start">
                                        <h5>Catatan:</h5>
                                        <p>Terima kasih telah berbelanja di toko kami!</p>
                                    </td>
                                    <td><strong>Total Item</strong></td>
                                    <td><strong><?php echo $sale['total_items']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Harga</strong></td>
                                    <td><strong>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-center no-print">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Cetak Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>