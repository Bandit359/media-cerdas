<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$guru = $_SESSION['guru'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_guru = mysqli_real_escape_string($conn, $_POST['nama_guru']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon   = mysqli_real_escape_string($conn, $_POST['telepon']);

    // Upload foto jika ada
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fileName = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowed)) {
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile);
            $fotoSql = ", foto='$targetFile'";
        } else {
            echo "<script>alert('Format gambar tidak valid!'); window.location='profil.php';</script>";
            exit();
        }
    } else {
        $fotoSql = "";
    }

    $conn->query("UPDATE guru SET nama_guru='$nama_guru', email='$email', telepon='$telepon' $fotoSql WHERE nama_guru='$guru'");

    $_SESSION['guru'] = $nama_guru;
    echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil.php';</script>";
    exit();
}
?>
