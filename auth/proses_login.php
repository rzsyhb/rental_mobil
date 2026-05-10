<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$usernameDb = mysqli_real_escape_string($conn, $username);

$query_admin = mysqli_query($conn, "SELECT * FROM admin WHERE username='$usernameDb' LIMIT 1");
$dataAdmin = $query_admin ? mysqli_fetch_assoc($query_admin) : null;
if ($dataAdmin && app_password_matches($password, $dataAdmin['password'])) {
    if (!app_password_is_hashed($dataAdmin['password'])) {
        $newHash = mysqli_real_escape_string($conn, app_hash_password($password));
        mysqli_query($conn, "UPDATE admin SET password='$newHash' WHERE id_admin='" . (int) $dataAdmin['id_admin'] . "'");
    }

    $_SESSION['login'] = true;
    $_SESSION['role'] = "admin";
    $_SESSION['id_user'] = $dataAdmin['id_admin'];
    $_SESSION['nama'] = $dataAdmin['nama_admin'];

    header("Location: ../admin/dashboard.php");
    exit;
}

$query_customer = mysqli_query($conn, "SELECT * FROM customer WHERE username='$usernameDb' LIMIT 1");
$dataCustomer = $query_customer ? mysqli_fetch_assoc($query_customer) : null;
if ($dataCustomer && app_password_matches($password, $dataCustomer['password'])) {
    if (!app_password_is_hashed($dataCustomer['password'])) {
        $newHash = mysqli_real_escape_string($conn, app_hash_password($password));
        mysqli_query($conn, "UPDATE customer SET password='$newHash' WHERE id_customer='" . (int) $dataCustomer['id_customer'] . "'");
    }

    $_SESSION['login'] = true;
    $_SESSION['role'] = "customer";
    $_SESSION['id_user'] = $dataCustomer['id_customer'];
    $_SESSION['nama'] = $dataCustomer['nama'];

    header("Location: ../customer/dashboard.php");
    exit;
}

echo "<script>
        alert('Username atau Password salah!');
        window.location='login.php';
      </script>";
?>
