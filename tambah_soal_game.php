<?php
include 'database.php';
session_start();

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$id_guru = $_SESSION['id_guru'];
$pertanyaan = $_POST['pertanyaan'];
$jenis_game = $_POST['jenis_game'];

// --- Proses upload gambar ---
$gambar = null;
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = time() . "_" . basename($_FILES["gambar"]["name"]);
    $targetFile = $targetDir . $fileName;
    move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile);
    $gambar = $fileName;
}

// --- Simpan ke database ---
$stmt = $conn->prepare("INSERT INTO soal_game (id_guru, pertanyaan, jenis_game, gambar) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $id_guru, $pertanyaan, $jenis_game, $gambar);

if ($stmt->execute()) {
    header("Location: atur_soal.php?status=sukses");
} else {
    header("Location: atur_soal.php?status=gagal");
}
exit();
?>
