<?php
session_start();
include 'database.php';

// Cek login guru
if (!isset($_SESSION['guru'])) {
    header("Location: login.php");
    exit();
}

$nama = $_SESSION['guru'];

// Ambil data guru dan foto
$stmt = $conn->prepare("SELECT id_guru, foto FROM guru WHERE nama_guru = ?");
$stmt->bind_param("s", $nama);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "Guru tidak ditemukan";
    exit();
}

$id_guru = $row['id_guru'];
$foto = $row['foto'];

// Ambil notifikasi
$notif_stmt = $conn->prepare("SELECT * FROM notifikasi WHERE id_guru = ? ORDER BY tanggal DESC LIMIT 10");
$notif_stmt->bind_param("i", $id_guru);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Tugas | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(-10px); }
  100% { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeInUp 0.3s ease-out;
}

.dropdown-anim {
  transition: all 0.2s ease;
  transform: scale(95%);
  opacity: 0;
  pointer-events: none;
}
.dropdown-anim.scale-100 {
  transform: scale(100%);
  opacity: 1;
}
@keyframes pageFadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-enter {
  animation: pageFadeIn 0.6s ease-out;
}

.fade-in {
  opacity: 0;
  transform: translateY(10px);
  animation: fadeInElement 0.8s ease forwards;
}
@keyframes fadeInElement {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-all duration-500 ease-in-out z-50 md:min-h-screen overflow-y-auto md:shadow-xl">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">E-Learning</h1>
      <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
    </div>
    <nav class="space-y-3">
      <a href="dashboard.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🏠 Beranda</a>
      <a href="materi.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-2 px-3 bg-blue-600 rounded transition-all duration-300">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🏫 Kelas Saya</a>
      <a href="Kelola_game.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🎮 Kelola Game</a>
    </nav>
  </aside>

  <!-- Konten Utama -->
  <div class="flex-1 flex flex-col fade-in">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">Kelola Tugas</h2>
      </div>

      <div class="flex items-center space-x-3 relative">
        <button id="notifBtn" class="relative bg-blue-600 hover:bg-blue-800 px-3 py-2 rounded transition duration-300 transform hover:scale-105">
          🔔
          <?php if ($notif_result && $notif_result->num_rows > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1"><?= $notif_result->num_rows ?></span>
          <?php endif; ?>
        </button>

        <div id="notifDropdown" class="dropdown-anim absolute right-0 mt-12 w-72 bg-white text-gray-800 rounded-lg shadow-lg border z-50">
          <div class="p-3 font-semibold border-b bg-blue-50">Notifikasi</div>
          <ul class="max-h-64 overflow-y-auto">
            <?php if ($notif_result && $notif_result->num_rows > 0): ?>
              <?php while ($n = $notif_result->fetch_assoc()): ?>
                <li class="px-3 py-2 border-b text-sm <?= $n['tipe']=='peringatan'?'bg-yellow-50':'bg-blue-50' ?> hover:bg-gray-100 transition-all">
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
          <button id="profileBtn" class="focus:outline-none transform hover:scale-105 transition">
            <?php if ($foto): ?>
              <img src="uploads/<?= htmlspecialchars($foto) ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
            <?php else: ?>
              <div class="bg-white text-blue-700 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                <?= strtoupper(substr($nama, 0, 1)) ?>
              </div>
            <?php endif; ?>
          </button>
          <div id="profileDropdown" class="dropdown-anim absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-50">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Form Tambah Tugas -->
    <main class="p-6">
      <form id="formTugas" class="bg-white p-6 rounded-xl shadow-lg space-y-4 transform transition-all duration-500 hover:shadow-2xl fade-in">
        <h3 class="text-lg font-semibold mb-3 text-blue-700">📝 Tambah Tugas Baru</h3>

        <div class="transition-all hover:scale-[1.01]">
          <label class="block font-semibold mb-1">Judul Tugas</label>
          <input type="text" name="judul" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
          <label class="block font-semibold mb-1">Deskripsi</label>
          <textarea name="deskripsi" rows="3" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold">Kelas</label>
            <select name="kelas" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
              <option value="">-- Pilih Kelas --</option>
              <option>9A</option><option>9B</option><option>9C</option>
              <option>9D</option><option>9E</option><option>9F</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold">Jenis Tugas</label>
            <select name="jenis_tugas" id="jenis_tugas" onchange="ubahJenis()" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
              <option value="">-- Pilih Jenis --</option>
              <option value="esai">Esai</option>
              <option value="pilgan">Pilihan Ganda</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold">Deadline</label>
            <input type="datetime-local" name="deadline" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
          </div>
          <div>
            <label class="block font-semibold">Waktu (menit)</label>
            <input type="number" name="waktu" min="1" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
          </div>
        </div>

        <div class="mt-4 fade-in">
          <label class="block font-semibold mb-2">Daftar Soal</label>
          <div id="soalContainer" class="space-y-3"></div>
          <button type="button" onclick="addSoal()" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg transform hover:scale-105 transition-all">+ Tambah Soal</button>
        </div>

        <div class="pt-4 text-right">
          <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transform hover:scale-105 transition-all duration-300">Simpan Tugas</button>
        </div>
      </form>
    </main>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("-translate-x-full");

  let overlay = document.getElementById("overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "overlay";
    overlay.className = "fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden animate-fadeIn";
    overlay.onclick = toggleSidebar;
    document.body.appendChild(overlay);
  } else {
    overlay.remove();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const notifBtn = document.getElementById("notifBtn");
  const notifDropdown = document.getElementById("notifDropdown");
  const profileBtn = document.getElementById("profileBtn");
  const profileDropdown = document.getElementById("profileDropdown");

  function toggleDropdown(btn, dropdown) {
    dropdown.classList.toggle("scale-100");
    dropdown.classList.toggle("opacity-100");
    dropdown.classList.toggle("pointer-events-auto");
  }

  notifBtn.addEventListener("click", e => {
    e.stopPropagation();
    toggleDropdown(notifBtn, notifDropdown);
  });

  profileBtn.addEventListener("click", e => {
    e.stopPropagation();
    toggleDropdown(profileBtn, profileDropdown);
  });

  document.addEventListener("click", e => {
    [notifDropdown, profileDropdown].forEach(dropdown => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove("scale-100", "opacity-100", "pointer-events-auto");
      }
    });
  });
});

// === Handle Form Submission ===
document.getElementById('formTugas').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  
  fetch('proses_tugas.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: data.msg || 'Tugas berhasil ditambahkan',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        window.location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: data.msg || 'Terjadi kesalahan'
      });
    }
  })
  .catch(error => {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Terjadi kesalahan saat mengirim data'
    });
    console.error('Error:', error);
  });
});

// === Fungsi Tambah & Hapus Soal ===
let soalIndex = 0;

function addSoal() {
  const container = document.getElementById("soalContainer");
  const jenis = document.getElementById("jenis_tugas").value;

  if (!jenis) {
    Swal.fire({
      icon: "warning",
      title: "Pilih Jenis Tugas Dulu!",
      text: "Silakan pilih jenis tugas sebelum menambah soal.",
      timer: 2000,
      showConfirmButton: false
    });
    return;
  }

  soalIndex++;
  const soalDiv = document.createElement("div");
  soalDiv.className = "border rounded-lg p-4 bg-gray-50 space-y-2 shadow-sm relative fade-in soal-item";

  soalDiv.innerHTML = `
    <button type="button" onclick="hapusSoal(this)" 
      class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-sm font-bold">✕</button>

    <label class="block font-semibold nomor-soal">Soal ${soalIndex}</label>
    <textarea name="soal[]" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400" rows="2"></textarea>

    ${
      jenis === "pilgan"
        ? `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <input type="text" name="opsiA[]" placeholder="Opsi A" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
          <input type="text" name="opsiB[]" placeholder="Opsi B" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
          <input type="text" name="opsiC[]" placeholder="Opsi C" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
          <input type="text" name="opsiD[]" placeholder="Opsi D" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
          <label class="font-semibold">Kunci Jawaban</label>
          <select name="kunci[]" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
            <option value="">-- Pilih Kunci --</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
          </select>
        </div>
        `
        : `
        <label class="block font-semibold">Jawaban Esai</label>
        <textarea name="kunci[]" rows="2" placeholder="Tulis jawaban esai..." class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400"></textarea>
        `
    }
  `;
  container.appendChild(soalDiv);
  resetNomorSoal();
}

function hapusSoal(btn) {
  btn.parentElement.remove();
  resetNomorSoal();
}

function resetNomorSoal() {
  const soalList = document.querySelectorAll(".soal-item");
  soalList.forEach((soal, index) => {
    const label = soal.querySelector(".nomor-soal");
    if (label) {
      label.textContent = `Soal ${index + 1}`;
    }
  });
  soalIndex = soalList.length;
}

function ubahJenis() {
  document.getElementById("soalContainer").innerHTML = "";
  soalIndex = 0;
}
</script>

</body>
</html>