<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

include 'koneksi.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $sku = $_POST['sku'];
    $nama = $_POST['nama'];
    $category_id = empty($_POST['category_id']) ? null : (int)$_POST['category_id'];
    $deskripsi = $_POST['deskripsi'];
    $harga_jual = (float)$_POST['harga_jual'];
    $harga_beli = empty($_POST['harga_beli']) ? null : (float)$_POST['harga_beli'];
    $stok = (int)$_POST['stok'];
    $hapus_gambar = isset($_POST['hapus_gambar']) ? true : false;
    
    // Validate inputs
    $errors = [];
    
    if (empty($sku)) {
        $errors[] = "SKU harus diisi.";
    } else {
        // Check if SKU is unique (except for current product when editing)
        $stmt = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $stmt->bind_param("si", $sku, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "SKU sudah digunakan oleh produk lain.";
        }
    }
    
    if (empty($nama)) {
        $errors[] = "Nama produk harus diisi.";
    }
    
    if (empty($harga_jual) || $harga_jual <= 0) {
        $errors[] = "Harga jual harus lebih dari 0.";
    }
    
    if ($stok < 0) {
        $errors[] = "Stok tidak boleh negatif.";
    }
    
    // Handle image upload
    $gambar_path = null;
    if (!empty($_FILES['gambar']['name'])) {
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_type = $_FILES['gambar']['type'];
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Hanya file JPG dan PNG yang diperbolehkan.";
        }
        
        // Check file size (max 2MB)
        if ($file_size > 2097152) {
            $errors[] = "Ukuran file maksimal 2MB.";
        }
        
        if (empty($errors)) {
            // Create uploads directory if not exists
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $gambar_path = $upload_path;
            } else {
                $errors[] = "Gagal mengupload gambar.";
            }
        }
    }
    
    // If no errors, proceed with database operation
    if (empty($errors)) {
        // Get old image path if editing
        $old_image_path = null;
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT gambar_path FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $old_product = $result->fetch_assoc();
                $old_image_path = $old_product['gambar_path'];
            }
        }
        
        if ($id > 0) {
            // Update existing product
            $query = "UPDATE products SET 
                      sku = ?, 
                      nama = ?, 
                      category_id = ?, 
                      deskripsi = ?, 
                      harga_jual = ?, 
                      harga_beli = ?, 
                      stok = ?";
            
            $params = [$sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok];
            $types = "ssiddii";
            
            // Add image path to update if new image uploaded or delete image
            if ($gambar_path !== null || $hapus_gambar) {
                $query .= ", gambar_path = ?";
                if ($hapus_gambar) {
                    $params[] = null;
                } else {
                    $params[] = $gambar_path;
                }
                $types .= "s";
            }
            
            $query .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Delete old image if replaced or deleted
                if (($gambar_path !== null || $hapus_gambar) && !empty($old_image_path) && file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
                
                $_SESSION['success'] = "Produk berhasil diperbarui.";
                header('Location: products.php');
                exit;
            } else {
                $_SESSION['error'] = "Gagal memperbarui produk: " . $conn->error;
                header('Location: product_form.php?id=' . $id);
                exit;
            }
        } else {
            // Insert new product
            $stmt = $conn->prepare("INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok, gambar_path) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiddiis", $sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $gambar_path);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Produk berhasil ditambahkan.";
                header('Location: products.php');
                exit;
            } else {
                $_SESSION['error'] = "Gagal menambahkan produk: " . $conn->error;
                header('Location: product_form.php');
                exit;
            }
        }
    } else {
        // If there are errors, store them in session and redirect back
        $_SESSION['error'] = implode("<br>", $errors);
        if ($id > 0) {
            header('Location: product_form.php?id=' . $id);
        } else {
            header('Location: product_form.php');
        }
        exit;
    }
} else {
    // If not POST request, redirect to products page
    header('Location: products.php');
    exit;
}
?>