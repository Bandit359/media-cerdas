<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];
$alert = "";

// Ambil data guru (prepared)
$stmt = $conn->prepare("SELECT * FROM guru WHERE nama_guru = ? LIMIT 1");
$stmt->bind_param("s", $nama);
$stmt->execute();
$data_guru = $stmt->get_result()->fetch_assoc();
$stmt->close();

$id_guru = $data_guru['id'] ?? 0;
$foto = !empty($data_guru['foto']) ? $data_guru['foto'] : null;

// Ambil notifikasi (prepared)
$stmt = $conn->prepare("SELECT * FROM notifikasi WHERE id_guru = ? ORDER BY tanggal DESC LIMIT 5");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$notif_result = $stmt->get_result();
$stmt->close();


// === HAPUS MATERI ===
if (isset($_GET['hapus'])) {
  $id_hapus = intval($_GET['hapus']);
  
  // Ambil info file untuk dihapus dari server (prepared)
  $stmt = $conn->prepare("SELECT * FROM materi WHERE id = ?");
  $stmt->bind_param("i", $id_hapus);
  $stmt->execute();
  $materi = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if ($materi) {
    // Hapus file fisik jika ada
    if (!empty($materi['file_path']) && file_exists($materi['file_path'])) {
      @unlink($materi['file_path']);
    }
    if (!empty($materi['video_path']) && file_exists($materi['video_path'])) {
      @unlink($materi['video_path']);
    }
    
    // Hapus dari database (prepared)
    $stmt = $conn->prepare("DELETE FROM materi WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      $alert = "deleted";
      
      // Notifikasi (prepared)
      $pesan = "Materi '{$materi['judul']}' berhasil dihapus oleh guru $nama.";
      $stmt = $conn->prepare("INSERT INTO notifikasi (id_guru, pesan, tipe) VALUES (?, ?, 'peringatan')");
      $stmt->bind_param("is", $id_guru, $pesan);
      $stmt->execute();
      $stmt->close();
    }
  }
  
  header("Location: materi.php" . ($alert ? "?alert=$alert" : ""));
  exit();
}

// Proses Tambah Materi (prepared)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Ambil nilai langsung dari POST (validasi tambahan bisa ditambahkan)
  $judul = $_POST['judul'] ?? '';
  $deskripsi = $_POST['deskripsi'] ?? '';
  $link = $_POST['link'] ?? '';
  $jenis = $_POST['jenis'] ?? '';
  $kelas = $_POST['kelas'] ?? '';
  $file_path = "";
  $video_path = "";

  if (!empty($_FILES['file']['name'])) {
    $targetDir = "uploads/files/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $file_path = $targetDir . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
  }

  if (!empty($_FILES['video']['name'])) {
    $targetDir = "uploads/videos/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $video_path = $targetDir . basename($_FILES['video']['name']);
    move_uploaded_file($_FILES['video']['tmp_name'], $video_path);
  }

  $stmt = $conn->prepare("
    INSERT INTO materi (judul, deskripsi, jenis, kelas, file_path, video_path, link, tanggal_upload)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $stmt->bind_param("sssssss", $judul, $deskripsi, $jenis, $kelas, $file_path, $video_path, $link);

  if ($stmt->execute()) {
    $alert = "success";
    // Notifikasi (prepared)
    $pesan = "Materi baru '$judul' untuk kelas $kelas telah ditambahkan oleh guru $nama.";
    $stmt2 = $conn->prepare("INSERT INTO notifikasi (id_guru, pesan, tipe) VALUES (?, ?, 'sukses')");
    $stmt2->bind_param("is", $id_guru, $pesan);
    $stmt2->execute();
    $stmt2->close();
  } else {
    $alert = "error";
  }

  $stmt->close();
}

// Ambil daftar materi (prepared)
$stmt = $conn->prepare("SELECT * FROM materi ORDER BY tanggal_upload DESC");
$stmt->execute();
$materi_result = $stmt->get_result();
$stmt->close();

// Handle alert dari URL
if (isset($_GET['alert'])) {
  $alert = $_GET['alert'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Materi | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    @keyframes fadeUp { from {opacity:0;transform:translateY(25px);} to {opacity:1;transform:translateY(0);} }
    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    .animate-fadeUp { animation: fadeUp 0.6s ease-out forwards; }
    .animate-fadeIn { animation: fadeIn 0.8s ease-in forwards; }

    .hover-lift { transition: all 0.3s ease; }
    .hover-lift:hover { transform: translateY(-6px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .btn-animate { transition: all 0.25s ease; }
    .btn-animate:hover { transform: scale(1.03); }

    input, textarea, select { transition: all 0.25s ease; }
    input:focus, textarea:focus, select:focus { transform: scale(1.01); }
  </style>
</head>
<body class="bg-gray-100">

<?php if ($alert == "success"): ?>
<script>
Swal.fire({
  title: 'Berhasil!',
  text: 'Materi berhasil disimpan 🎉',
  icon: 'success',
  confirmButtonColor: '#2563eb',
  timer: 2000,
  showConfirmButton: false
});
</script>
<?php elseif ($alert == "error"): ?>
<script>
Swal.fire({
  title: 'Gagal!',
  text: 'Terjadi kesalahan saat menyimpan materi 😢',
  icon: 'error',
  confirmButtonColor: '#dc2626'
});
</script>
<?php elseif ($alert == "deleted"): ?>
<script>
Swal.fire({
  title: 'Terhapus!',
  text: 'Materi berhasil dihapus 🗑️',
  icon: 'success',
  confirmButtonColor: '#2563eb',
  timer: 2000,
  showConfirmButton: false
});
</script>
<?php elseif ($alert == "updated"): ?>
<script>
Swal.fire({
  title: 'Diperbarui!',
  text: 'Materi berhasil diperbarui ✅',
  icon: 'success',
  confirmButtonColor: '#2563eb',
  timer: 2000,
  showConfirmButton: false
});
</script>
<?php endif; ?>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("-translate-x-full");
  document.body.classList.toggle("overflow-hidden");

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

function konfirmasiHapus(id, judul) {
  Swal.fire({
    title: 'Hapus Materi?',
    html: `Materi "<b>${judul}</b>" akan dihapus permanen.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `materi.php?hapus=${id}`;
    }
  });
}
</script>

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 md:min-h-screen overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">E-Learning</h1>
      <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
    </div>
    <nav class="space-y-2">
      <a href="dashboard.php" class="block py-3 px-4 hover:bg-blue-600 rounded">🏠 Dashboard</a>
      <a href="materi.php" class="block py-3 px-4 bg-blue-600 rounded">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-3 px-4 hover:bg-blue-600 rounded">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-3 px-4 hover:bg-blue-600 rounded">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-3 px-4 hover:bg-blue-600 rounded">🏫 Kelas Saya</a>
      <a href="Kelola_game.php" class="block py-3 px-4 hover:bg-blue-600 rounded">🎮 Kelola Game</a>
    </nav>
  </aside>

  <!-- Konten Utama -->
  <div class="flex-1 flex flex-col">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md animate-fadeIn relative z-40">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">Kelola Materi</h2>
      </div>

      <div class="flex items-center space-x-3 relative">
        <button id="notifBtn" class="relative bg-blue-600 hover:bg-blue-800 px-3 py-2 rounded btn-animate">
          🔔
          <?php if ($notif_result && $notif_result->num_rows > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1"><?= $notif_result->num_rows ?></span>
          <?php endif; ?>
        </button>

        <!-- Dropdown Notifikasi -->
        <div id="notifDropdown" class="hidden absolute right-0 mt-12 w-72 bg-white text-gray-800 rounded-lg shadow-lg border z-50">
          <div class="p-3 font-semibold border-b bg-blue-50">Notifikasi</div>
          <ul class="max-h-64 overflow-y-auto">
            <?php if ($notif_result && $notif_result->num_rows > 0): ?>
              <?php while ($n = $notif_result->fetch_assoc()): ?>
                <li class="px-3 py-2 border-b text-sm <?= $n['tipe']=='peringatan'?'bg-yellow-50':'bg-blue-50' ?> animate-fadeUp">
                  <?= htmlspecialchars($n['pesan']) ?><br>
                  <span class="text-xs text-gray-500"><?= date('d M H:i', strtotime($n['tanggal'])) ?></span>
                </li>
              <?php endwhile; ?>
            <?php else: ?>
              <li class="px-3 py-2 text-sm text-gray-500">Belum ada notifikasi.</li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Profil -->
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
          <div id="profileDropdown" class="hidden absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-50 animate-fadeUp">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-6 animate-fadeUp relative z-10 overflow-y-auto">
      
      <!-- Form Tambah Materi -->
      <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-lg hover-lift mb-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 text-center">➕ Tambah Materi Baru</h1>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <div>
            <label class="block text-gray-700 font-semibold">Judul Materi</label>
            <input type="text" name="judul" required class="w-full mt-2 p-2 border rounded-lg focus:ring focus:ring-blue-300">
          </div>

          <div>
            <label class="block text-gray-700 font-semibold">Deskripsi</label>
            <textarea name="deskripsi" rows="3" required class="w-full mt-2 p-2 border rounded-lg focus:ring focus:ring-blue-300"></textarea>
          </div>

          <div>
            <label class="block text-gray-700 font-semibold">Jenis Materi</label>
            <select name="jenis" id="jenis" required class="w-full mt-2 p-2 border rounded-lg focus:ring focus:ring-blue-300">
              <option value="">-- Pilih Jenis --</option>
              <option value="File">File</option>
              <option value="Video">Video</option>
              <option value="Link">Link</option>
            </select>
          </div>

          <div id="fileInput" class="hidden">
            <label class="block text-gray-700 font-semibold">Upload File</label>
            <input type="file" name="file" class="w-full mt-2 p-2 border rounded-lg">
          </div>

          <div id="videoInput" class="hidden">
            <label class="block text-gray-700 font-semibold">Upload Video</label>
            <input type="file" name="video" class="w-full mt-2 p-2 border rounded-lg">
          </div>

          <div id="linkInput" class="hidden">
            <label class="block text-gray-700 font-semibold">Link Materi</label>
            <input type="url" name="link" placeholder="https://..." class="w-full mt-2 p-2 border rounded-lg">
          </div>

          <!-- Dropdown Kelas -->
          <div class="relative" id="kelasDropdownContainer">
            <label class="block text-gray-700 font-semibold mb-2">Kelas</label>
            <button type="button" id="kelasDropdownBtn"
              class="w-full border rounded-lg p-2 bg-white flex justify-between items-center focus:ring focus:ring-blue-300">
              <span id="kelasSelected">-- Pilih Kelas --</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            <div id="kelasDropdownList"
              class="hidden absolute z-50 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto animate-fadeIn">
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9A">9A</div>
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9B">9B</div>
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9C">9C</div>
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9D">9D</div>
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9E">9E</div>
              <div class="px-4 py-2 hover:bg-blue-100 cursor-pointer" data-value="9F">9F</div>
            </div>
            <input type="hidden" name="kelas" id="kelasInput" required>
          </div>

          <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-semibold btn-animate">
            💾 Simpan Materi
          </button>
        </form>
      </div>

      <!-- Daftar Materi -->
      <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-6 hover-lift">
          <h2 class="text-2xl font-bold mb-6 text-gray-800">📋 Daftar Materi</h2>

          <?php if ($materi_result && $materi_result->num_rows > 0): ?>
            
            <!-- Desktop View -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full border-collapse">
                <thead>
                  <tr class="bg-blue-100 text-blue-700 text-left">
                    <th class="py-3 px-4 border-b">No</th>
                    <th class="py-3 px-4 border-b">Judul</th>
                    <th class="py-3 px-4 border-b">Jenis</th>
                    <th class="py-3 px-4 border-b">Kelas</th>
                    <th class="py-3 px-4 border-b">Tanggal</th>
                    <th class="py-3 px-4 border-b text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $no = 1;
                  while ($m = $materi_result->fetch_assoc()): 
                  ?>
                    <tr class="hover:bg-gray-50 animate-fadeUp">
                      <td class="py-3 px-4 border-b"><?= $no++ ?></td>
                      <td class="py-3 px-4 border-b font-semibold"><?= htmlspecialchars($m['judul']) ?></td>
                      <td class="py-3 px-4 border-b">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                          <?= $m['jenis'] == 'File' ? 'bg-green-100 text-green-700' : 
                              ($m['jenis'] == 'Video' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700') ?>">
                          <?= htmlspecialchars($m['jenis']) ?>
                        </span>
                      </td>
                      <td class="py-3 px-4 border-b"><?= htmlspecialchars($m['kelas']) ?></td>
                      <td class="py-3 px-4 border-b text-sm text-gray-600">
                        <?= date('d M Y', strtotime($m['tanggal_upload'])) ?>
                      </td>
                      <td class="py-3 px-4 border-b text-center">
                        <div class="flex justify-center gap-2">
                          <a href="edit_materi.php?id=<?= $m['id'] ?>" 
                             class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg text-sm btn-animate inline-flex items-center">
                            ✏️ Edit
                          </a>
                          <button onclick="konfirmasiHapus(<?= $m['id'] ?>, '<?= addslashes($m['judul']) ?>')" 
                                  class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-sm btn-animate">
                            🗑️ Hapus
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile View -->
            <div class="md:hidden space-y-4">
              <?php 
              $materi_result->data_seek(0);
              while ($m = $materi_result->fetch_assoc()): 
              ?>
                <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition animate-fadeUp">
                  <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($m['judul']) ?></h3>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                      <?= $m['jenis'] == 'File' ? 'bg-green-100 text-green-700' : 
                          ($m['jenis'] == 'Video' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700') ?>">
                      <?= htmlspecialchars($m['jenis']) ?>
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mb-2">Kelas: <strong><?= htmlspecialchars($m['kelas']) ?></strong></p>
                  <p class="text-xs text-gray-500 mb-3"><?= date('d M Y, H:i', strtotime($m['tanggal_upload'])) ?></p>
                  <div class="flex gap-2">
                    <a href="edit_materi.php?id=<?= $m['id'] ?>" 
                       class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded-lg text-sm text-center btn-animate">
                      ✏️ Edit
                    </a>
                    <button onclick="konfirmasiHapus(<?= $m['id'] ?>, '<?= addslashes($m['judul']) ?>')" 
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm btn-animate">
                      🗑️ Hapus
                    </button>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>

          <?php else: ?>
            <div class="text-center py-12">
              <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-gray-500 mt-4 text-lg">Belum ada materi yang ditambahkan</p>
              <p class="text-gray-400 text-sm">Tambahkan materi pertama Anda menggunakan form di atas</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
const jenisSelect = document.getElementById("jenis");
const fileInput = document.getElementById("fileInput");
const videoInput = document.getElementById("videoInput");
const linkInput = document.getElementById("linkInput");

jenisSelect.addEventListener("change", () => {
  [fileInput, videoInput, linkInput].forEach(el => el.classList.add("hidden"));
  if (jenisSelect.value === "File") fileInput.classList.remove("hidden");
  if (jenisSelect.value === "Video") videoInput.classList.remove("hidden");
  if (jenisSelect.value === "Link") linkInput.classList.remove("hidden");
});

const btn = document.getElementById("kelasDropdownBtn");
const list = document.getElementById("kelasDropdownList");
const selected = document.getElementById("kelasSelected");
const input = document.getElementById("kelasInput");

btn.addEventListener("click", (e) => {
  e.stopPropagation();
  list.classList.toggle("hidden");
});

document.querySelectorAll("#kelasDropdownList div").forEach(item => {
  item.addEventListener("click", () => {
    selected.textContent = item.textContent;
    input.value = item.dataset.value;
    list.classList.add("hidden");
  });
});

document.addEventListener("click", e => {
  if (!btn.contains(e.target) && !list.contains(e.target)) list.classList.add("hidden");
});
</script>
</body>
</html>