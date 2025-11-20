<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

if (!isset($_GET['id'])) {
  echo "<script>alert('ID materi tidak ditemukan!'); window.location='materi.php';</script>";
  exit();
}

$id = (int)$_GET['id'];
$q = $conn->query("SELECT * FROM materi WHERE id = $id");
$data = $q->fetch_assoc();

if (!$data) {
  echo "<script>alert('Data materi tidak ditemukan!'); window.location='materi.php';</script>";
  exit();
}

$nama = $_SESSION['guru'];
$query_guru = $conn->query("SELECT * FROM guru WHERE nama_guru = '$nama' LIMIT 1");
$data_guru = $query_guru->fetch_assoc();
$foto = !empty($data_guru['foto']) ? $data_guru['foto'] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Materi | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.6s ease-out;
    }
    .hover-lift {
      transition: all 0.3s ease;
    }
    .hover-lift:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 md:min-h-screen overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">E-Learning</h1>
      <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
    </div>
    <nav class="space-y-2">
      <a href="dashboard.php" class="block py-3 px-4 hover:bg-blue-600 rounded transition">🏠 Dashboard</a>
      <a href="materi.php" class="block py-3 px-4 bg-blue-600 rounded transition">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-3 px-4 hover:bg-blue-600 rounded transition">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-3 px-4 hover:bg-blue-600 rounded transition">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-3 px-4 hover:bg-blue-600 rounded transition">🏫 Kelas Saya</a>
      <a href="Kelola_game.php" class="block py-3 px-4 hover:bg-blue-600 rounded transition">🎮 Kelola Game</a>
    </nav>
  </aside>

  <!-- Content -->
  <div class="flex-1 flex flex-col">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">✏️ Edit Materi</h2>
      </div>

      <div class="flex items-center space-x-3">
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
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-4 sm:p-6 overflow-y-auto animate-fadeIn">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 hover-lift">
          
          <!-- Header -->
          <div class="flex items-center justify-between mb-6 pb-4 border-b">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">✏️ Edit Materi</h2>
              <p class="text-sm text-gray-600 mt-1">Perbarui informasi materi pembelajaran</p>
            </div>
            <a href="materi.php" class="text-gray-600 hover:text-gray-800 transition">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </a>
          </div>

          <!-- Form -->
          <form action="proses_edit_materi.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="edit_id" value="<?= $data['id'] ?>">
            <input type="hidden" name="old_file" value="<?= $data['file_path'] ?>">
            <input type="hidden" name="old_video" value="<?= $data['video_path'] ?>">

            <!-- Judul -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">
                📌 Judul Materi <span class="text-red-500">*</span>
              </label>
              <input type="text" name="edit_judul" value="<?= htmlspecialchars($data['judul']) ?>" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                     required>
            </div>

            <!-- Deskripsi -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">
                📝 Deskripsi
              </label>
              <textarea name="edit_deskripsi" rows="4" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              
              <!-- Jenis -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  🏷️ Jenis Materi <span class="text-red-500">*</span>
                </label>
                <select name="edit_jenis" id="edit_jenis" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                        required>
                  <option value="">-- Pilih Jenis --</option>
                  <option value="File" <?= $data['jenis']=='File'?'selected':'' ?>>📄 File</option>
                  <option value="Video" <?= $data['jenis']=='Video'?'selected':'' ?>>🎥 Video</option>
                  <option value="Link" <?= $data['jenis']=='Link'?'selected':'' ?>>🔗 Link</option>
                </select>
              </div>

              <!-- Kelas -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  🏫 Kelas <span class="text-red-500">*</span>
                </label>
                <select name="edit_kelas" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                        required>
                  <?php
                  $kelas_options = ['9A', '9B', '9C', '9D', '9E', '9F'];
                  foreach ($kelas_options as $k) {
                    $selected = ($data['kelas'] == $k) ? 'selected' : '';
                    echo "<option value='$k' $selected>$k</option>";
                  }
                  ?>
                </select>
              </div>
            </div>

            <!-- Link -->
            <div id="linkSection" class="<?= $data['jenis']!='Link'?'hidden':'' ?>">
              <label class="block text-gray-700 font-semibold mb-2">
                🔗 Link Materi
              </label>
              <input type="url" name="edit_link" value="<?= htmlspecialchars($data['link']) ?>" 
                     placeholder="https://example.com" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            <!-- File Upload -->
            <div id="fileSection" class="<?= $data['jenis']!='File'?'hidden':'' ?>">
              <label class="block text-gray-700 font-semibold mb-2">
                📄 File Materi (PDF, DOC, PPT, dll)
              </label>
              <?php if (!empty($data['file_path'])): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3 flex items-center justify-between">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-green-800">
                      File saat ini: <strong><?= basename($data['file_path']) ?></strong>
                    </span>
                  </div>
                  <a href="<?= htmlspecialchars($data['file_path']) ?>" target="_blank" 
                     class="text-blue-600 hover:text-blue-800 text-sm underline">
                    Lihat File
                  </a>
                </div>
              <?php endif; ?>
              <input type="file" name="edit_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" 
                     class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
              <p class="text-xs text-gray-500 mt-1">Upload file baru untuk mengganti file lama</p>
            </div>

            <!-- Video Upload -->
            <div id="videoSection" class="<?= $data['jenis']!='Video'?'hidden':'' ?>">
              <label class="block text-gray-700 font-semibold mb-2">
                🎥 Video Materi
              </label>
              <?php if (!empty($data['video_path'])): ?>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-3 flex items-center justify-between">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                    </svg>
                    <span class="text-sm text-purple-800">
                      Video saat ini: <strong><?= basename($data['video_path']) ?></strong>
                    </span>
                  </div>
                  <a href="<?= htmlspecialchars($data['video_path']) ?>" target="_blank" 
                     class="text-blue-600 hover:text-blue-800 text-sm underline">
                    Lihat Video
                  </a>
                </div>
              <?php endif; ?>
              <input type="file" name="edit_video" accept="video/*" 
                     class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition">
              <p class="text-xs text-gray-500 mt-1">Upload video baru untuk mengganti video lama</p>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t">
              <button type="submit" 
                      class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 px-6 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                💾 Simpan Perubahan
              </button>
              <a href="materi.php" 
                 class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-6 rounded-lg font-semibold text-center shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                ❌ Batal
              </a>
            </div>
          </form>

        </div>
      </div>
    </main>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("-translate-x-full");
}

// Toggle sections based on jenis
const jenisSelect = document.getElementById("edit_jenis");
const linkSection = document.getElementById("linkSection");
const fileSection = document.getElementById("fileSection");
const videoSection = document.getElementById("videoSection");

jenisSelect.addEventListener("change", function() {
  linkSection.classList.add("hidden");
  fileSection.classList.add("hidden");
  videoSection.classList.add("hidden");
  
  if (this.value === "Link") linkSection.classList.remove("hidden");
  if (this.value === "File") fileSection.classList.remove("hidden");
  if (this.value === "Video") videoSection.classList.remove("hidden");
});
</script>

</body>
</html>