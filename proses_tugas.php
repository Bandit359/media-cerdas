<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

// Cek login guru
if (!isset($_SESSION['guru'])) {
    echo json_encode(["status" => "error", "msg" => "Unauthorized"]);
    exit();
}

$nama = $_SESSION['guru'];

// Ambil id_guru
$stmt = $conn->prepare("SELECT id_guru, foto FROM guru WHERE nama_guru = ?");
$stmt->bind_param("s", $nama);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "error", "msg" => "Guru tidak ditemukan"]);
    exit();
}

$id_guru = $row['id_guru'];

// Ambil data POST
$judul = $_POST["judul"] ?? "";
$deskripsi = $_POST["deskripsi"] ?? "";
$kelas = $_POST["kelas"] ?? "";
$jenis_tugas = $_POST["jenis_tugas"] ?? "";
$deadline = $_POST["deadline"] ?? "";
$waktu = $_POST["waktu"] ?? 0;

// Validasi
if ($judul == "" || $kelas == "" || $jenis_tugas == "" || $deadline == "" || $waktu <= 0) {
    echo json_encode(["status" => "error", "msg" => "Semua field wajib diisi"]);
    exit();
}

// Insert tugas
$stmt = $conn->prepare("
    INSERT INTO tugas (id_guru, judul, deskripsi, kelas, jenis_tugas, deadline, waktu)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("isssssi", $id_guru, $judul, $deskripsi, $kelas, $jenis_tugas, $deadline, $waktu);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "msg" => $stmt->error]);
    exit();
}

$id_tugas = $stmt->insert_id;

// Insert soal
if (isset($_POST['soal']) && is_array($_POST['soal'])) {
    $stmt2 = $conn->prepare("
        INSERT INTO soal (tugas_id, pertanyaan, opsiA, opsiB, opsiC, opsiD, kunci)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($_POST['soal'] as $i => $pertanyaan) {

        $pertanyaan = trim($pertanyaan);
        if ($pertanyaan == "") continue;

        $opsiA = $_POST['opsiA'][$i] ?? "";
        $opsiB = $_POST['opsiB'][$i] ?? "";
        $opsiC = $_POST['opsiC'][$i] ?? "";
        $opsiD = $_POST['opsiD'][$i] ?? "";
        $kunci = $_POST['kunci'][$i] ?? "";

        $stmt2->bind_param("issssss", $id_tugas, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci);
        $stmt2->execute();
    }
}

echo json_encode(["status" => "success"]);
exit();
