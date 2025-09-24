<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Keranjang belanja kosong.";
    header('Location: pos.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = empty($_POST['customer_id']) ? null : (int)$_POST['customer_id'];
    $pembayaran_method = $_POST['pembayaran_method'];
    $bayar = (float)$_POST['bayar'];
    $total_amount = (float)$_POST['total_amount'];
    $total_items = (int)$_POST['total_items'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($pembayaran_method)) {
        $errors[] = "Metode pembayaran harus dipilih.";
    }
    
    if ($bayar < $total_amount) {
        $errors[] = "Jumlah bayar tidak mencukupi.";
    }
    
    // If no errors, proceed with transaction
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Generate invoice number
            $invoice_no = "INV-" . date('Ymd') . "-" . rand(1000, 9999);
            
            // Check if invoice number is unique
            $stmt = $conn->prepare("SELECT id FROM sales WHERE invoice_no = ?");
            $stmt->bind_param("s", $invoice_no);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // If not unique, generate again
            while ($result->num_rows > 0) {
                $invoice_no = "INV-" . date('Ymd') . "-" . rand(1000, 9999);
                $stmt = $conn->prepare("SELECT id FROM sales WHERE invoice_no = ?");
                $stmt->bind_param("s", $invoice_no);
                $stmt->execute();
                $result = $stmt->get_result();
            }
            
            // Insert sales record
            $stmt = $conn->prepare("INSERT INTO sales (invoice_no, customer_id, total_amount, total_items, pembayaran_method) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sidis", $invoice_no, $customer_id, $total_amount, $total_items, $pembayaran_method);
            $stmt->execute();
            
            // Get the inserted sale ID
            $sale_id = $conn->insert_id;
            
            // Insert sale items and update product stock
            foreach ($_SESSION['cart'] as $item) {
                $product_id = $item['id'];
                $qty = $item['qty'];
                $price = $item['price'];
                $subtotal = $item['subtotal'];
                
                // Insert sale item
                $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $sale_id, $product_id, $qty, $price, $subtotal);
                $stmt->execute();
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
                $stmt->bind_param("ii", $qty, $product_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Set success message
            $_SESSION['success'] = "Transaksi berhasil dengan invoice #" . $invoice_no;
            
            // Redirect to sales list
            header('Location: sales_list.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            $_SESSION['error'] = "Gagal memproses transaksi: " . $e->getMessage();
            header('Location: pos.php');
            exit;
        }
    } else {
        // If there are errors, store them in session and redirect back
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: pos.php');
        exit;
    }
} else {
    // If not POST request, redirect to POS page
    header('Location: pos.php');
    exit;
}
?>