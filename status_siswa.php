<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];

// === Ambil ID Absen dari URL ===
if (!isset($_GET['id'])) {
  echo "ID absen tidak ditemukan.";
  exit();
}

$id_absen = intval($_GET['id']);
$absen = $conn->query("SELECT * FROM absen WHERE id = $id_absen")->fetch_assoc();

if (!$absen) {
  echo "Data absen tidak ditemukan.";
  exit();
}

$kelas = $absen['kelas'];

// === Ambil data siswa yang sudah absen ===
$siswa_result = $conn->query("SELECT * FROM absen_siswa WHERE id_absen = '$id_absen' ORDER BY nama_siswa");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Absen Kelas <?= htmlspecialchars($kelas) ?> | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-6xl mx-auto p-6">
  <div class="bg-white p-6 rounded-xl shadow-md mb-6">
    <h1 class="text-2xl font-bold text-blue-700 mb-3">
      📋 Status Kehadiran — Kelas <?= htmlspecialchars($kelas) ?>
    </h1>
    <p class="text-gray-700 text-sm mb-2">
      <b>Tanggal:</b> <?= htmlspecialchars($absen['tanggal']) ?> |
      <b>Deadline:</b> <?= htmlspecialchars($absen['deadline']) ?> |
      <b>Wajib Kamera:</b> <?= $absen['wajib_kamera'] ? 'Ya' : 'Tidak' ?>
    </p>
    <a href="tambah_absen.php" class="text-blue-600 hover:underline text-sm">⬅ Kembali ke daftar absen</a>
  </div>

  <div class="bg-white p-6 rounded-xl shadow-md overflow-x-auto">
    <table class="w-full border-collapse min-w-[700px]">
      <thead>
        <tr class="bg-blue-100 text-blue-700 text-left">
          <th class="py-2 px-3 border-b">No</th>
          <th class="py-2 px-3 border-b">Nama Siswa</th>
          <th class="py-2 px-3 border-b">Status</th>
          <th class="py-2 px-3 border-b">Waktu Absen</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        if ($siswa_result && $siswa_result->num_rows > 0):
          while ($s = $siswa_result->fetch_assoc()):
        ?>
          <tr class="hover:bg-gray-50">
            <td class="py-2 px-3 border-b"><?= $no++ ?></td>
            <td class="py-2 px-3 border-b"><?= htmlspecialchars($s['nama_siswa']) ?></td>
            <td class="py-2 px-3 border-b">
              <?php
              $status = $s['status'];
              echo match ($status) {
                'Hadir' => '✅ Hadir',
                'Izin' => '🟡 Izin',
                'Sakit' => '🔵 Sakit',
                default => '❌ Alfa',
              };
              ?>
            </td>
            <td class="py-2 px-3 border-b">
              <?= $s['waktu_absen'] ? htmlspecialchars($s['waktu_absen']) : '-' ?>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4" class="text-center py-4 text-gray-500">Belum ada data siswa.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
