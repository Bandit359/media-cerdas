<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];

// Ambil data guru
$query = $conn->prepare("SELECT * FROM guru WHERE nama_guru = ?");
$query->bind_param("s", $nama);
$query->execute();
$result = $query->get_result();
$guru = $result->fetch_assoc();

// === Update profil ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nama_guru = mysqli_real_escape_string($conn, $_POST['nama_guru']);
  $email     = mysqli_real_escape_string($conn, $_POST['email']);
  $telepon   = mysqli_real_escape_string($conn, $_POST['telepon']);
  $foto      = $guru['foto']; // default pakai foto lama

  // Jika ada upload foto baru
  if (!empty($_FILES['foto']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Validasi file gambar
    $allowedTypes = ['jpg', 'jpeg', 'png'];
    if (in_array($imageFileType, $allowedTypes)) {
      if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile)) {
        $foto = $fileName;
      }
    }
  }

  // Update data guru
  $update = $conn->prepare("UPDATE guru SET nama_guru=?, email=?, telepon=?, foto=? WHERE nama_guru=?");
  $update->bind_param("sssss", $nama_guru, $email, $telepon, $foto, $nama);
  if ($update->execute()) {
    $_SESSION['guru'] = $nama_guru; // update session jika nama berubah
    header("Location: profil.php?success=1");
    exit();
  } else {
    $error = "Gagal memperbarui profil.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profil | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
        <h2 class="text-lg sm:text-xl font-semibold">Edit Profil</h2>
      </div>
      <div class="flex items-center space-x-2 sm:space-x-4">
        <span class="hidden sm:block font-medium text-sm">👋 <?= htmlspecialchars($nama) ?></span>
        <div class="relative group">
          <button class="bg-white text-blue-700 w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold">
            <?= strtoupper(substr($nama, 0, 1)) ?>
          </button>
          <div class="absolute right-0 mt-2 w-36 sm:w-40 bg-white border rounded-lg shadow-lg hidden group-hover:block z-10">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm sm:text-base">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm sm:text-base">Logout</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Form Edit -->
    <main class="flex-1 p-6 sm:p-8 lg:p-10">
      <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 max-w-3xl mx-auto">
        <h3 class="text-2xl font-bold text-blue-700 mb-6">✏️ Edit Profil Guru</h3>

        <?php if (isset($error)): ?>
          <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Nama Guru</label>
            <input type="text" name="nama_guru" value="<?= htmlspecialchars($guru['nama_guru']) ?>" required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none">
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($guru['email'] ?? '') ?>"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none">
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-2">Telepon</label>
            <input type="text" name="telepon" value="<?= htmlspecialchars($guru['telepon'] ?? '') ?>"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none">
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-2">Foto Profil</label>
            <div class="flex items-center gap-4">
              <?php if (!empty($guru['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($guru['foto']) ?>" alt="Foto" class="w-20 h-20 rounded-full object-cover border">
              <?php else: ?>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($guru['nama_guru']) ?>&background=3b82f6&color=fff&size=100" class="w-20 h-20 rounded-full object-cover border">
              <?php endif; ?>
              <input type="file" name="foto" accept="image/*" class="block w-full text-gray-600">
            </div>
          </div>

          <div class="pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow transition">
              💾 Simpan Perubahan
            </button>
            <a href="profil.php" class="ml-3 text-gray-600 hover:text-blue-700">Batal</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

</body>
</html>
