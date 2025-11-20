<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];
$query = $conn->prepare("SELECT * FROM guru WHERE nama_guru = ?");
$query->bind_param("s", $nama);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
  echo "<script>alert('Data guru tidak ditemukan!'); window.location='dashboard.php';</script>";
  exit();
}

$foto = !empty($data['foto']) ? $data['foto'] : null;
$success = isset($_GET['success']);
$error = isset($_GET['error']);

// === PROSES UBAH PASSWORD ===
if (isset($_POST['ubah_password'])) {
  $lama = $_POST['password_lama'];
  $baru = $_POST['password_baru'];
  $konfirmasi = $_POST['konfirmasi_password'];

  if (!isset($data['password'])) {
    echo "<script>alert('Data password tidak ditemukan di database!'); window.location='profil.php?error=1';</script>";
    exit();
  }

  if (!password_verify($lama, $data['password'])) {
    echo "<script>alert('Password lama salah!'); window.location='profil.php?error=1';</script>";
    exit();
  }

  if ($baru !== $konfirmasi) {
    echo "<script>alert('Konfirmasi password tidak cocok!'); window.location='profil.php?error=1';</script>";
    exit();
  }

  $hash_baru = password_hash($baru, PASSWORD_DEFAULT);
  $update = $conn->prepare("UPDATE guru SET password = ? WHERE nama_guru = ?");
  $update->bind_param("ss", $hash_baru, $nama);

  if ($update->execute()) {
    echo "<script>alert('Password berhasil diubah!'); window.location='profil.php?success=1';</script>";
    exit();
  } else {
    echo "<script>alert('Gagal mengubah password!'); window.location='profil.php?error=1';</script>";
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Guru | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("-translate-x-full");
      const overlay = document.getElementById("overlay");
      if (overlay) {
        overlay.classList.toggle("hidden");
      } else {
        const newOverlay = document.createElement("div");
        newOverlay.id = "overlay";
        newOverlay.className = "fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden";
        newOverlay.onclick = toggleSidebar;
        document.body.appendChild(newOverlay);
      }
    }
  </script>
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 md:min-h-screen overflow-y-auto md:shadow-xl">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl md:text-2xl font-bold">E-Learning</h1>
      <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
    </div>
    <nav class="space-y-3">
      <a href="dashboard.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">🏠 Beranda</a>
      <a href="materi.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">🏫 Kelas Saya</a>
      <a href="profil.php" class="block py-2 px-3 bg-blue-600 rounded transition">👤 Profil</a>
    </nav>
  </aside>

  <!-- Konten utama -->
  <div class="flex-1 flex flex-col md:ml-0">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 shadow-md">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg sm:text-xl font-semibold">Profil Guru</h2>
      </div>
      <div class="flex items-center space-x-2 sm:space-x-4">
        <span class="hidden sm:block font-medium text-sm">👋 <?= htmlspecialchars($nama) ?></span>
        <div class="relative group">
          <?php if ($foto): ?>
            <img src="uploads/<?= htmlspecialchars($foto) ?>" alt="Foto Profil" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 border-white object-cover cursor-pointer">
          <?php else: ?>
            <div class="bg-white text-blue-700 w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold cursor-pointer">
              <?= strtoupper(substr($nama, 0, 1)) ?>
            </div>
          <?php endif; ?>
          <div class="absolute right-0 mt-2 w-36 sm:w-40 bg-white border rounded-lg shadow-lg hidden group-hover:block z-10">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Isi Profil -->
    <main class="flex-1 p-6 sm:p-8 lg:p-10">
      <div class="bg-white shadow-lg rounded-xl p-6 sm:p-8 max-w-3xl mx-auto">
        <h3 class="text-2xl font-bold text-blue-700 mb-6">👤 Profil Guru</h3>

        <?php if ($success): ?>
          <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-5">
            ✅ Password berhasil diperbarui!
          </div>
        <?php elseif ($error): ?>
          <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-5">
            ⚠️ Terjadi kesalahan, silakan coba lagi!
          </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-6 mb-8">
          <img 
            src="<?= $foto ? 'uploads/' . htmlspecialchars($foto) : 'https://ui-avatars.com/api/?name=' . urlencode($data['nama_guru']) . '&background=3b82f6&color=fff&size=128' ?>" 
            alt="Foto Profil"
            class="w-32 h-32 rounded-full border-4 border-blue-500 object-cover mb-4 sm:mb-0"
          >
          <div>
            <h4 class="text-xl font-semibold"><?= htmlspecialchars($data['nama_guru']) ?></h4>
            <p class="text-gray-600"><?= htmlspecialchars($data['email'] ?: '-') ?></p>
            <p class="text-gray-600">📞 <?= htmlspecialchars($data['telepon'] ?: '-') ?></p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-700 font-medium">Nama</span>
            <span><?= htmlspecialchars($data['nama_guru']) ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 font-medium">Email</span>
            <span><?= htmlspecialchars($data['email'] ?: '-') ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 font-medium">Telepon</span>
            <span><?= htmlspecialchars($data['telepon'] ?: '-') ?></span>
          </div>
        </div>

        <div class="mt-6 text-right">
          <a href="edit_profil.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow">
            ✏️ Edit Profil
          </a>
        </div>
      </div>

      <!-- Ubah Password -->
      <div class="bg-white shadow-lg rounded-xl p-6 sm:p-8 max-w-3xl mx-auto mt-8">
        <h3 class="text-xl font-semibold text-blue-700 mb-4">🔒 Ubah Password</h3>
        <form action="" method="POST" class="space-y-4">
          <div>
            <label class="block text-gray-700 font-medium">Password Lama</label>
            <input type="password" name="password_lama" required class="w-full mt-1 border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
          </div>
          <div>
            <label class="block text-gray-700 font-medium">Password Baru</label>
            <input type="password" name="password_baru" required minlength="6" class="w-full mt-1 border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
          </div>
          <div>
            <label class="block text-gray-700 font-medium">Konfirmasi Password Baru</label>
            <input type="password" name="konfirmasi_password" required minlength="6" class="w-full mt-1 border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
          </div>
          <div class="text-right">
            <button type="submit" name="ubah_password" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
              💾 Simpan Password
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

</body>
</html>
