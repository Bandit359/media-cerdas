<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];

// === Ambil data guru ===
$query_guru = $conn->query("SELECT * FROM guru WHERE nama_guru = '$nama' LIMIT 1");
$data_guru = $query_guru->fetch_assoc();
$id_guru = $data_guru['id'] ?? 0;
$foto = !empty($data_guru['foto']) ? $data_guru['foto'] : null;

// === Filter Jenis ===
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'Semua';

// === Hapus Data ===
if (isset($_GET['hapus']) && isset($_GET['jenis']) && isset($_GET['id'])) {
  $jenis = $_GET['jenis'];
  $id = (int) $_GET['id'];

  if ($jenis == 'Materi') $conn->query("DELETE FROM materi WHERE id = $id");
  elseif ($jenis == 'Tugas') $conn->query("DELETE FROM tugas WHERE id = $id");
  elseif ($jenis == 'Absen') $conn->query("DELETE FROM absen WHERE id = $id");

  $pesan = "Data $jenis berhasil dihapus oleh guru $nama.";
  $conn->query("INSERT INTO notifikasi (id_guru, pesan, tipe) VALUES ($id_guru, '$pesan', 'peringatan')");
  header("Location: dashboard.php?status=deleted");
  exit();
}

// === Statistik ===
$total_materi = $conn->query("SELECT COUNT(*) AS total FROM materi")->fetch_assoc()['total'] ?? 0;
$total_tugas  = $conn->query("SELECT COUNT(*) AS total FROM tugas")->fetch_assoc()['total'] ?? 0;
$total_absen  = $conn->query("SELECT COUNT(*) AS total FROM absen")->fetch_assoc()['total'] ?? 0;

// === Riwayat Aktivitas ===
$history_query = "
  SELECT id AS id_data, 'Materi' AS jenis_data, judul, kelas, tanggal_upload AS tanggal FROM materi
  UNION ALL
  SELECT id AS id_data, 'Tugas' AS jenis_data, judul, kelas, created_at AS tanggal FROM tugas
  UNION ALL
  SELECT id AS id_data, 'Absen' AS jenis_data, CONCAT('Daftar Hadir ', kelas) AS judul, kelas, dibuat_pada AS tanggal FROM absen
";

if ($filter != 'Semua') {
  $history_query = "SELECT * FROM ($history_query) AS h WHERE jenis_data = '$filter'";
}

$history_query .= " ORDER BY tanggal DESC LIMIT 10";
$history_result = $conn->query($history_query);

// === Ambil notifikasi terbaru ===
$notif_result = $conn->query("SELECT * FROM notifikasi WHERE id_guru = $id_guru ORDER BY tanggal DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">

<!-- JS Sidebar dan Dropdown -->
<script>
  function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("-translate-x-full");
    document.body.classList.toggle("overflow-hidden");

    let overlay = document.getElementById("overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "overlay";
      overlay.className = "fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden";
      overlay.onclick = toggleSidebar;
      document.body.appendChild(overlay);
    } else {
      overlay.remove();
    }
  }

  function konfirmasiHapus(id, jenis, nama) {
    Swal.fire({
      title: 'Hapus Data?',
      html: `<b>${jenis}</b> "<i>${nama}</i>" akan dihapus.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e11d48',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((r) => { if (r.isConfirmed) window.location.href = `dashboard.php?hapus=1&jenis=${jenis}&id=${id}`; });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    notifBtn.addEventListener("click", e => {
      e.stopPropagation();
      notifDropdown.classList.toggle("hidden");
    });
    document.addEventListener("click", e => {
      if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target))
        notifDropdown.classList.add("hidden");
    });

    const profileBtn = document.getElementById("profileBtn");
    const profileDropdown = document.getElementById("profileDropdown");
    profileBtn.addEventListener("click", e => {
      e.stopPropagation();
      profileDropdown.classList.toggle("hidden");
    });
    document.addEventListener("click", e => {
      if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target))
        profileDropdown.classList.add("hidden");
    });
  });

  function gantiFilter(select) {
    const filter = select.value;
    window.location.href = "dashboard.php?filter=" + filter;
  }
</script>

<div class="flex min-h-screen flex-col md:flex-row">
 <!-- Sidebar -->
<aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 md:min-h-screen overflow-y-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">E-Learning</h1>
    <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
  </div>
  <nav class="space-y-2">
    <a href="dashboard.php" class="block py-3 px-4 bg-blue-600 rounded">🏠 Dashboard</a>
    <a href="materi.php" class="block py-3 px-4 hover:bg-blue-600 rounded">📚 Kelola Materi</a>
    <a href="tambah_tugas.php" class="block py-3 px-4 hover:bg-blue-600 rounded">📝 Kelola Tugas</a>
    <a href="tambah_absen.php" class="block py-3 px-4 hover:bg-blue-600 rounded">📅 Kelola Absen</a>

    <!-- Tambahan Menu Baru -->
    <a href="kelas_saya.php" class="block py-3 px-4 hover:bg-blue-600 rounded">🏫 Kelas Saya</a>

    <a href="Kelola_game.php" class="block py-3 px-4 hover:bg-blue-600 rounded">🎮 Kelola Game</a>
  </nav>
</aside>

  <!-- Konten utama -->
  <div class="flex-1 flex flex-col">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">Dashboard Guru</h2>
      </div>

      <div class="flex items-center space-x-3 relative">
        <button id="notifBtn" class="relative bg-blue-600 hover:bg-blue-800 px-3 py-2 rounded transition">
          🔔
          <?php if ($notif_result && $notif_result->num_rows > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1"><?= $notif_result->num_rows ?></span>
          <?php endif; ?>
        </button>

        <div id="notifDropdown" class="hidden absolute right-0 mt-12 w-72 bg-white text-gray-800 rounded-lg shadow-lg border z-50">
          <div class="p-3 font-semibold border-b bg-blue-50">Notifikasi</div>
          <ul class="max-h-64 overflow-y-auto">
            <?php if ($notif_result && $notif_result->num_rows > 0): ?>
              <?php while ($n = $notif_result->fetch_assoc()): ?>
                <li class="px-3 py-2 border-b text-sm <?= $n['tipe']=='peringatan'?'bg-yellow-50':'bg-blue-50' ?>">
                  <?= htmlspecialchars($n['pesan']) ?><br>
                  <span class="text-xs text-gray-500"><?= date('d M H:i', strtotime($n['tanggal'])) ?></span>
                </li>
              <?php endwhile; ?>
            <?php else: ?>
              <li class="px-3 py-2 text-sm text-gray-500">Belum ada notifikasi.</li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="flex items-center space-x-3 relative">
          <span class="hidden sm:block font-semibold"><?= htmlspecialchars($nama) ?></span>
          <button id="profileBtn" class="focus:outline-none">
            <?php if ($foto): ?>
              <img src="uploads/<?= htmlspecialchars($foto) ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
            <?php else: ?>
              <div class="bg-white text-blue-700 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                <?= strtoupper(substr($nama, 0, 1)) ?>
              </div>
            <?php endif; ?>
          </button>
          <div id="profileDropdown" class="hidden absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-50">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
          </div>
        </div>
      </div>
    </header>

        <!-- Konten -->
    <main class="flex-1 p-4 animate-fadeIn">
      <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px);} to { opacity: 1; transform: translateY(0);} }
        .animate-fadeIn { animation: fadeIn 0.6s ease-in-out; }
        .animate-card { animation: fadeIn 0.7s ease-in-out; }
        .transition-smooth { transition: all 0.3s ease-in-out; }
      </style>

      <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white p-6 rounded-xl shadow mb-6 transform hover:scale-[1.01] transition-smooth">
        <h2 class="text-xl font-bold">Selamat Datang, <?= htmlspecialchars($nama) ?>! 👨‍🏫</h2>
        <p class="text-sm mt-1">Semangat mengajar hari ini! 💪</p>
      </div>

      <!-- Statistik -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-6 rounded-xl shadow text-center border-t-4 border-blue-500 hover:shadow-xl hover:-translate-y-1 transition-smooth animate-card">
          <h3 class="font-semibold">Materi</h3>
          <p class="text-3xl text-blue-700 font-bold mt-2"><?= $total_materi ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow text-center border-t-4 border-yellow-500 hover:shadow-xl hover:-translate-y-1 transition-smooth animate-card">
          <h3 class="font-semibold">Tugas</h3>
          <p class="text-3xl text-yellow-600 font-bold mt-2"><?= $total_tugas ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow text-center border-t-4 border-green-500 hover:shadow-xl hover:-translate-y-1 transition-smooth animate-card">
          <h3 class="font-semibold">Absen</h3>
          <p class="text-3xl text-green-600 font-bold mt-2"><?= $total_absen ?></p>
        </div>
      </div>

      <!-- Riwayat Aktivitas -->
      <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-smooth animate-card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
          <h3 class="text-lg font-bold text-blue-700">📊 Riwayat Aktivitas Terbaru</h3>
          <select onchange="gantiFilter(this)" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 w-full sm:w-auto mt-2 sm:mt-0">
            <option value="Semua" <?= $filter=='Semua'?'selected':'' ?>>Semua</option>
            <option value="Materi" <?= $filter=='Materi'?'selected':'' ?>>Materi</option>
            <option value="Tugas" <?= $filter=='Tugas'?'selected':'' ?>>Tugas</option>
            <option value="Absen" <?= $filter=='Absen'?'selected':'' ?>>Absen</option>
          </select>
        </div>

        <!-- Desktop -->
        <div class="hidden md:block overflow-x-auto transition-smooth">
          <table class="w-full border-collapse min-w-[600px]">
            <thead>
              <tr class="bg-blue-100 text-blue-700 text-left">
                <th class="py-2 px-3 border-b">Jenis</th>
                <th class="py-2 px-3 border-b">Judul</th>
                <th class="py-2 px-3 border-b">Kelas</th>
                <th class="py-2 px-3 border-b">Tanggal</th>
                <th class="py-2 px-3 border-b text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($history_result && $history_result->num_rows > 0): ?>
                <?php while ($row = $history_result->fetch_assoc()): ?>
                  <tr class="hover:bg-gray-50 text-sm transition-smooth hover:scale-[1.01]">
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['jenis_data']) ?></td>
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['judul']) ?></td>
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['kelas'] ?: '-') ?></td>
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td class="py-2 px-3 border-b text-center">
                      <button onclick="konfirmasiHapus('<?= $row['id_data'] ?>', '<?= $row['jenis_data'] ?>', '<?= addslashes($row['judul']) ?>')" class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50 transition-smooth">🗑️</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-4 text-gray-500">Belum ada aktivitas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile -->
        <div class="md:hidden space-y-3 mt-2">
          <?php if ($history_result && $history_result->num_rows > 0): ?>
            <?php $history_result->data_seek(0); while ($row = $history_result->fetch_assoc()): ?>
              <div class="border rounded-lg p-4 shadow-sm border-l-4 hover:shadow-md hover:-translate-y-1 transition-smooth animate-card <?= $row['jenis_data'] == 'Materi' ? 'border-blue-500' : ($row['jenis_data'] == 'Tugas' ? 'border-yellow-500' : 'border-green-500') ?>">
                <div class="flex justify-between items-start mb-2">
                  <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded"><?= htmlspecialchars($row['jenis_data']) ?></span>
                  <button onclick="konfirmasiHapus('<?= $row['id_data'] ?>', '<?= $row['jenis_data'] ?>', '<?= addslashes($row['judul']) ?>')" class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-smooth">🗑️</button>
                </div>
                <h4 class="font-semibold"><?= htmlspecialchars($row['judul']) ?></h4>
                <p class="text-sm text-gray-600 mt-1">Kelas: <?= htmlspecialchars($row['kelas'] ?: '-') ?></p>
                <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($row['tanggal']) ?></p>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-4 text-gray-500">Belum ada aktivitas.</div>
          <?php endif; ?>
        </div>
      </div>
    </main>

  </div>
</div>
</body>
</html>
