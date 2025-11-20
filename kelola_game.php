<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
    header("Location: login.php");
    exit();
}

$nama = $_SESSION['guru'];

// Ambil data guru
$stmt = $conn->prepare("SELECT * FROM guru WHERE nama_guru = ? LIMIT 1");
$stmt->bind_param("s", $nama);
$stmt->execute();
$data_guru = $stmt->get_result()->fetch_assoc();
$foto = $data_guru['foto'] ?? null;

// Ambil notifikasi (terakhir 10)
$notif_result = $conn->query("SELECT * FROM notifikasi ORDER BY tanggal DESC LIMIT 10");

/* ================================
   HANDLER: Tambah Level
================================ */
if (isset($_POST['tambah_level'])) {
    $level_baru = intval($_POST['level']);
    if ($level_baru > 0) {
        $cek = $conn->prepare("SELECT id FROM game_level WHERE level = ? LIMIT 1");
        $cek->bind_param("i", $level_baru);
        $cek->execute();
        $res = $cek->get_result();
        if ($res->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO game_level (level) VALUES (?)");
            $ins->bind_param("i", $level_baru);
            $ins->execute();
        }
    }
    header("Location: kelola_game.php");
    exit();
}

/* ================================
   HANDLER: Tambah Soal
   - jenis: drag_drop, jawab_naik, susun_kata
   Fields per jenis disesuaikan
================================ */
if (isset($_POST['tambah_soal'])) {
    $level = intval($_POST['level']);
    $jenis = $_POST['jenis_game'];

    if ($jenis === 'drag_drop') {
        $drag = $_POST['drag_text'] ?? '';
        $drop = $_POST['drop_text'] ?? '';

        $stmt = $conn->prepare("INSERT INTO soal_game (jenis, level, drag_text, drop_text) VALUES (?,?,?,?)");
        $stmt->bind_param("siss", $jenis, $level, $drag, $drop);
        $stmt->execute();

    } elseif ($jenis === 'jawab_naik') {
        $soal = $_POST['pertanyaan'] ?? '';
        $jawaban = $_POST['jawaban'] ?? '';

        $stmt = $conn->prepare("INSERT INTO soal_game (jenis, level, pertanyaan, jawaban_benar) VALUES (?,?,?,?)");
        $stmt->bind_param("siss", $jenis, $level, $soal, $jawaban);
        $stmt->execute();

    } elseif ($jenis === 'susun_kata') {
        $kata_acak = $_POST['kata_acak'] ?? '';
        $jawaban = $_POST['jawaban'] ?? '';

        $stmt = $conn->prepare("INSERT INTO soal_game (jenis, level, kata_acak, jawaban_benar) VALUES (?,?,?,?)");
        $stmt->bind_param("siss", $jenis, $level, $kata_acak, $jawaban);
        $stmt->execute();
    }

    header("Location: kelola_game.php");
    exit();
}

/* ================================
   Hapus soal
================================ */
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $del = $conn->prepare("DELETE FROM soal_game WHERE id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    header("Location: kelola_game.php");
    exit();
}

/* ================================
   Ambil data level & soal
================================ */
$levels_result = $conn->query("SELECT * FROM game_level ORDER BY level ASC");
$soal_result = $conn->query("SELECT * FROM soal_game ORDER BY level ASC, id ASC");

// Bantu buat array levels untuk JS
$levels = [];
if ($levels_result) {
    while ($l = $levels_result->fetch_assoc()) {
        $levels[] = (int)$l['level'];
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🎮 Kelola Game | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(20px);} to { opacity:1; transform: translateY(0);} }
    .page-enter { animation: fadeSlideUp 0.6s ease-out; }
    .fade-in { animation: fadeSlideUp 0.6s ease-out; }
  </style>
  <script>
    // Data levels dari PHP
    const LEVELS = <?php echo json_encode($levels); ?>;

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('-translate-x-full');
    }

    function toggleDropdown(id) {
      document.getElementById(id).classList.toggle('hidden');
    }

    function onGameTypeChange() {
      const jenis = document.getElementById('jenis_game').value;
      // Hide all
      document.querySelectorAll('.form-type').forEach(el => el.classList.add('hidden'));
      if (jenis === 'drag_drop') document.getElementById('form-drag').classList.remove('hidden');
      if (jenis === 'jawab_naik') document.getElementById('form-jawab').classList.remove('hidden');
      if (jenis === 'susun_kata') document.getElementById('form-susun').classList.remove('hidden');
    }

    function filterSoal() {
      const level = document.getElementById('filter_level').value;
      const jenis = document.getElementById('filter_jenis').value;
      document.querySelectorAll('#tabel-soal tbody tr').forEach(tr => {
        const rLevel = tr.dataset.level;
        const rJenis = tr.dataset.jenis;
        let show = true;
        if (level && rLevel !== level) show = false;
        if (jenis && rJenis !== jenis) show = false;
        tr.style.display = show ? '' : 'none';
      });
    }

    document.addEventListener('DOMContentLoaded', function(){
      onGameTypeChange();
    });
  </script>
</head>
<body class="bg-gray-100 font-sans page-enter">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-all duration-500 z-50 md:min-h-screen overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">E-Learning</h1>
        <button class="md:hidden text-white text-2xl" onclick="toggleSidebar()">✕</button>
      </div>
      <nav class="space-y-2 text-sm">
        <a href="dashboard.php" class="block py-2 px-3 hover:bg-blue-600 rounded">🏠 Dashboard</a>
        <a href="materi.php" class="block py-2 px-3 hover:bg-blue-600 rounded">📚 Kelola Materi</a>
        <a href="tambah_tugas.php" class="block py-2 px-3 hover:bg-blue-600 rounded">📝 Kelola Tugas</a>
        <a href="tambah_absen.php" class="block py-2 px-3 hover:bg-blue-600 rounded">📅 Kelola Absen</a>
        <a href="kelola_game.php" class="block py-2 px-3 bg-blue-600 rounded">🎮 Kelola Game</a>
        <a href="hasil_game.php" class="block py-2 px-3 hover:bg-blue-600 rounded">📊 Lihat Hasil Siswa</a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 ml-0 md:ml-64">
      <!-- Topbar -->
      <header class="bg-white border-b sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <button class="md:hidden text-2xl" onclick="toggleSidebar()">☰</button>
            <h2 class="text-lg font-semibold">Kelola Game</h2>
            <span class="text-sm text-gray-500">(Atur level & soal untuk game siswa)</span>
          </div>

          <div class="flex items-center space-x-3">
            <button onclick="toggleDropdown('notifDropdown')" id="notifBtn" class="relative bg-blue-600 text-white px-3 py-2 rounded">🔔
              <?php if ($notif_result && $notif_result->num_rows > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1"><?php echo $notif_result->num_rows; ?></span>
              <?php endif; ?>
            </button>
            <div id="notifDropdown" class="hidden absolute right-5 mt-12 w-72 bg-white text-gray-800 rounded-lg shadow-lg border z-50">
              <div class="p-3 font-semibold border-b bg-blue-50">Notifikasi</div>
              <ul class="max-h-64 overflow-y-auto">
                <?php if ($notif_result && $notif_result->num_rows > 0): ?>
                  <?php while ($n = $notif_result->fetch_assoc()): ?>
                    <li class="px-3 py-2 border-b text-sm <?php echo ($n['tipe']=='peringatan')?'bg-yellow-50':'bg-blue-50' ?>">
                      <?php echo htmlspecialchars($n['pesan']); ?><br>
                      <span class="text-xs text-gray-500"><?php echo date('d M H:i', strtotime($n['tanggal'])); ?></span>
                    </li>
                  <?php endwhile; ?>
                <?php else: ?>
                  <li class="px-3 py-2 text-sm text-gray-500">Belum ada notifikasi.</li>
                <?php endif; ?>
              </ul>
            </div>

            <div class="flex items-center space-x-3 relative">
              <span class="hidden sm:block font-semibold"><?php echo htmlspecialchars($nama); ?></span>
              <button onclick="toggleDropdown('profileDropdown')" id="profileBtn" class="focus:outline-none">
                <?php if ($foto): ?>
                  <img src="uploads/<?php echo htmlspecialchars($foto); ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                <?php else: ?>
                  <div class="bg-white text-blue-700 w-10 h-10 rounded-full flex items-center justify-center font-bold"><?php echo strtoupper(substr($nama,0,1)); ?></div>
                <?php endif; ?>
              </button>
              <div id="profileDropdown" class="hidden absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-50">
                <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
              </div>
            </div>

          </div>
        </div>
      </header>

      <!-- Content -->
      <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid md:grid-cols-3 gap-6">

          <!-- Panel: Atur Level -->
          <div class="bg-white p-5 rounded-xl shadow">
            <h3 class="font-semibold text-lg mb-3">Atur Level Game</h3>
            <p class="text-sm text-gray-600 mb-3">Buat level baru untuk game. Level akan muncul di pilihan saat menambah soal.</p>
            <form method="POST" class="space-y-3">
              <label class="block text-sm font-medium">Tambahkan Level (angka)</label>
              <input type="number" name="level" min="1" class="w-full border p-2 rounded" required>
              <button type="submit" name="tambah_level" class="w-full bg-blue-600 text-white py-2 rounded">Tambah Level</button>
            </form>

            <div class="mt-4">
              <h4 class="font-medium">Daftar Level</h4>
              <div class="mt-2 text-sm text-gray-700">
                <?php if (count($levels) > 0): ?>
                  <?php echo implode(', ', $levels); ?>
                <?php else: ?>
                  Belum ada level. Tambahkan level terlebih dahulu.
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Panel: Form Tambah Soal -->
          <div class="bg-white p-5 rounded-xl shadow md:col-span-2">
            <h3 class="font-semibold text-lg mb-3">Tambah Soal</h3>
            <form method="POST" class="space-y-4">
              <input type="hidden" name="tambah_soal" value="1">

              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                  <label class="block text-sm font-medium">Pilih Level</label>
                  <select name="level" class="w-full border p-2 rounded" required>
                    <?php if (count($levels) > 0): ?>
                      <?php foreach ($levels as $lv): ?>
                        <option value="<?php echo $lv; ?>"><?php echo $lv; ?></option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="">-- Tambahkan level dulu --</option>
                    <?php endif; ?>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium">Jenis Game</label>
                  <select name="jenis_game" id="jenis_game" onchange="onGameTypeChange()" class="w-full border p-2 rounded" required>
                    <option value="drag_drop">Drag & Drop</option>
                    <option value="jawab_naik">Jawab Untuk Naik</option>
                    <option value="susun_kata">Susun Kata</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium">(Opsional) Preview</label>
                  <div class="text-xs text-gray-500">Pilih jenis untuk melihat input yang sesuai.</div>
                </div>
              </div>

              <!-- Form untuk Drag & Drop -->
              <div id="form-drag" class="form-type mt-3 hidden">
                <label class="block font-medium">Drag & Drop (masukkan pasangan)</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                  <input type="text" name="drag_text" placeholder="Teks yang di-drag" class="border p-2 rounded">
                  <input type="text" name="drop_text" placeholder="Teks target drop" class="border p-2 rounded">
                </div>
                <div class="text-sm text-gray-500 mt-2">Setiap pasangan drag-drop disimpan sebagai satu entri soal.</div>
              </div>

              <!-- Form untuk Jawab Untuk Naik -->
              <div id="form-jawab" class="form-type mt-3 hidden">
                <label class="block font-medium">Pertanyaan</label>
                <textarea name="pertanyaan" class="w-full border p-2 rounded" placeholder="Tulis pertanyaan singkat..."></textarea>
                <label class="block font-medium mt-2">Jawaban Benar</label>
                <input type="text" name="jawaban" class="w-full border p-2 rounded" placeholder="Jawaban singkat (tanpa huruf besar sensitif)">
              </div>

              <!-- Form untuk Susun Kata -->
              <div id="form-susun" class="form-type mt-3 hidden">
                <label class="block font-medium">Kata (acak atau pemisah)</label>
                <input type="text" name="kata_acak" class="w-full border p-2 rounded" placeholder="Misal: A N T A R (atau masukkan huruf acak)">
                <label class="block font-medium mt-2">Jawaban Benar</label>
                <input type="text" name="jawaban" class="w-full border p-2 rounded" placeholder="Jawaban yang benar (contoh: ANTAR)">
                <div class="text-sm text-gray-500 mt-2">Siswa akan menyusun kata dari huruf/fragmen yang disediakan.</div>
              </div>

              <div class="flex items-center space-x-3 mt-3">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan Soal</button>
                <a href="kelola_game.php" class="text-sm text-gray-600">Batal</a>
              </div>
            </form>

            <!-- Filter & Tabel Soal -->
            <div class="mt-6">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-center gap-3">
                  <label class="text-sm">Filter Level</label>
                  <select id="filter_level" onchange="filterSoal()" class="border p-2 rounded text-sm">
                    <option value="">Semua</option>
                    <?php foreach ($levels as $lv): ?>
                      <option value="<?php echo $lv; ?>"><?php echo $lv; ?></option>
                    <?php endforeach; ?>
                  </select>

                  <label class="text-sm">Filter Jenis</label>
                  <select id="filter_jenis" onchange="filterSoal()" class="border p-2 rounded text-sm">
                    <option value="">Semua</option>
                    <option value="drag_drop">Drag & Drop</option>
                    <option value="jawab_naik">Jawab Untuk Naik</option>
                    <option value="susun_kata">Susun Kata</option>
                  </select>
                </div>

                <div class="text-sm text-gray-600">Total soal: <?php echo $soal_result ? $soal_result->num_rows : 0; ?></div>
              </div>

              <div class="mt-3 overflow-x-auto">
                <table id="tabel-soal" class="min-w-full bg-white rounded-lg">
                  <thead class="bg-gray-50 text-sm">
                    <tr>
                      <th class="p-2 border">#</th>
                      <th class="p-2 border">Level</th>
                      <th class="p-2 border">Jenis</th>
                      <th class="p-2 border">Isi Soal</th>
                      <th class="p-2 border">Jawaban</th>
                      <th class="p-2 border">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="text-sm">
                    <?php if ($soal_result && $soal_result->num_rows > 0): $i=1; ?>
                      <?php while ($r = $soal_result->fetch_assoc()): ?>
                        <tr data-level="<?php echo $r['level']; ?>" data-jenis="<?php echo $r['jenis']; ?>">
                          <td class="p-2 border text-center"><?php echo $i++; ?></td>
                          <td class="p-2 border text-center"><?php echo (int)$r['level']; ?></td>
                          <td class="p-2 border text-center"><?php echo htmlspecialchars($r['jenis']); ?></td>
                          <td class="p-2 border">
                            <?php
                              if ($r['jenis'] === 'drag_drop') {
                                echo '<strong>Drag:</strong> ' . htmlspecialchars($r['drag_text']) . '<br><strong>Drop:</strong> ' . htmlspecialchars($r['drop_text']);
                              } elseif ($r['jenis'] === 'jawab_naik') {
                                echo htmlspecialchars($r['pertanyaan']);
                              } elseif ($r['jenis'] === 'susun_kata') {
                                echo htmlspecialchars($r['kata_acak']);
                              } else {
                                echo htmlspecialchars($r['pertanyaan'] ?? '-');
                              }
                            ?>
                          </td>
                          <td class="p-2 border text-center"><?php echo htmlspecialchars($r['jawaban_benar'] ?? '-'); ?></td>
                          <td class="p-2 border text-center">
                            <a href="?hapus=<?php echo $r['id']; ?>" onclick="return confirm('Hapus soal ini?')" class="text-red-600 hover:underline">Hapus</a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="6" class="p-3 text-center text-gray-500">Belum ada soal.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div>

          </div>

        </div>

      </div>
    </main>
  </div>

  <script>
    // Tutup dropdown bila klik di luar
    document.addEventListener('click', function(e){
      if (!e.target.closest('#notifBtn') && !e.target.closest('#notifDropdown')) {
        document.getElementById('notifDropdown').classList.add('hidden');
      }
      if (!e.target.closest('#profileBtn') && !e.target.closest('#profileDropdown')) {
        document.getElementById('profileDropdown').classList.add('hidden');
      }
    });
  </script>
</body>
</html>
