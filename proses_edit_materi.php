<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  $id = intval($_POST['edit_id']);
  $judul = mysqli_real_escape_string($conn, $_POST['edit_judul']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['edit_deskripsi']);
  $jenis = mysqli_real_escape_string($conn, $_POST['edit_jenis']);
  $kelas = mysqli_real_escape_string($conn, $_POST['edit_kelas']);
  $link = mysqli_real_escape_string($conn, $_POST['edit_link']);
  
  $file_path = $_POST['old_file'];
  $video_path = $_POST['old_video'];
  
  // === UPLOAD FILE BARU ===
  if (!empty($_FILES['edit_file']['name'])) {
    $targetDir = "uploads/files/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . "_" . basename($_FILES['edit_file']['name']);
    $targetFile = $targetDir . $fileName;
    
    if (move_uploaded_file($_FILES['edit_file']['tmp_name'], $targetFile)) {
      // Hapus file lama
      if (!empty($file_path) && file_exists($file_path)) {
        unlink($file_path);
      }
      $file_path = $targetFile;
    }
  }
  
  // === UPLOAD VIDEO BARU ===
  if (!empty($_FILES['edit_video']['name'])) {
    $targetDir = "uploads/videos/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . "_" . basename($_FILES['edit_video']['name']);
    $targetFile = $targetDir . $fileName;
    
    if (move_uploaded_file($_FILES['edit_video']['tmp_name'], $targetFile)) {
      // Hapus video lama
      if (!empty($video_path) && file_exists($video_path)) {
        unlink($video_path);
      }
      $video_path = $targetFile;
    }
  }
  
  // === UPDATE DATABASE ===
  $stmt = $conn->prepare("UPDATE materi SET 
                          judul = ?, 
                          deskripsi = ?, 
                          jenis = ?, 
                          kelas = ?, 
                          link = ?, 
                          file_path = ?, 
                          video_path = ? 
                          WHERE id = ?");
  
  $stmt->bind_param("sssssssi", $judul, $deskripsi, $jenis, $kelas, $link, $file_path, $video_path, $id);
  
  if ($stmt->execute()) {
    // === NOTIFIKASI ===
    $nama_guru = $_SESSION['guru'];
    $query_guru = $conn->query("SELECT id FROM guru WHERE nama_guru = '$nama_guru' LIMIT 1");
    $data_guru = $query_guru->fetch_assoc();
    $id_guru = $data_guru['id'] ?? 0;
    
    $pesan = "Materi '$judul' untuk kelas $kelas berhasil diperbarui oleh guru $nama_guru.";
    $conn->query("INSERT INTO notifikasi (id_guru, pesan, tipe) VALUES ($id_guru, '$pesan', 'sukses')");
    
    header("Location: materi.php?alert=updated");
  } else {
    header("Location: edit_materi.php?id=$id&error=1");
  }
  
  $stmt->close();
  exit();
  
} else {
  header("Location: materi.php");
  exit();
}
?>