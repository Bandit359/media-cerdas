<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $judul = trim($_POST['judul'] ?? '');
  $deskripsi = trim($_POST['deskripsi'] ?? '');
  $kelas = trim($_POST['kelas'] ?? '');

  if ($judul === '' || $kelas === '') {
    $pesan = ['error', 'Harap isi semua data wajib!'];
  } else {
    $stmt = $conn->prepare("INSERT INTO tugas (judul, deskripsi, kelas) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $deskripsi, $kelas);
    if ($stmt->execute()) {
      $pesan = ['sukses', 'Tugas berhasil disimpan!'];
    } else {
      $pesan = ['error', 'Gagal menyimpan tugas. Coba lagi.'];
    }
  }
} else {
  $pesan = ['error', 'Akses tidak valid.'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Notifikasi Simpan Tugas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

  <?php if (isset($pesan)): ?>
    <div id="notif" class="fixed inset-0 flex items-center justify-center bg-black/40 z-50">
      <div class="bg-white shadow-2xl rounded-2xl p-6 w-[90%] max-w-sm text-center animate-fadeIn">
        <?php if ($pesan[0] === 'sukses'): ?>
          <div class="text-green-500 text-5xl mb-3">✅</div>
        <?php else: ?>
          <div class="text-red-500 text-5xl mb-3">❌</div>
        <?php endif; ?>
        <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($pesan[1]) ?></h2>
        <button id="btnTutup" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
          Kembali
        </button>
      </div>
    </div>
  <?php endif; ?>

  <script>
    document.getElementById('btnTutup')?.addEventListener('click', () => {
      const notif = document.getElementById('notif');
      notif.classList.add('animate-fadeOut');
      setTimeout(() => {
        window.location.href = 'daftar_tugas.php';
      }, 300);
    });
  </script>

  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    @keyframes fadeOut {
      from { opacity: 1; transform: scale(1); }
      to { opacity: 0; transform: scale(0.95); }
    }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
    .animate-fadeOut { animation: fadeOut 0.3s ease-in forwards; }
  </style>

</body>
</html>
