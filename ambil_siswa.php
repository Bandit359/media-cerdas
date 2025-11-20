<?php
include 'database.php';

if (isset($_GET['kelas'])) {
  $kelas = $conn->real_escape_string($_GET['kelas']);
  $result = $conn->query("SELECT nama, nis FROM siswa WHERE kelas = '$kelas'");

  echo "<div class='modal-content bg-white rounded-t-2xl md:rounded-xl shadow-2xl w-full md:w-96 max-h-[85vh] overflow-y-auto animate-slide-in md:animate-fade-in transition-all duration-300 ease-out relative'>";
  echo "<div class='p-6'>";
  echo "<h2 class='text-lg font-semibold mb-3 text-gray-700 border-b pb-2 text-center md:text-left'>Daftar Siswa Kelas " . htmlspecialchars($kelas) . "</h2>";

  if ($result && $result->num_rows > 0) {
    echo "<ul class='divide-y divide-gray-200'>";
    while ($row = $result->fetch_assoc()) {
      echo "<li class='py-2 px-3 hover:bg-blue-50 transition transform hover:scale-[1.02]'>
              👩‍🎓 " . htmlspecialchars($row['nama']) . "
              <span class='text-gray-500 text-sm'>(NIS: " . htmlspecialchars($row['nis']) . ")</span>
            </li>";
    }
    echo "</ul>";
  } else {
    echo "<p class='text-center text-gray-500 mt-4'>Belum ada siswa di kelas ini.</p>";
  }

  echo "</div></div>";
}
?>


<style>
/* Animasi masuk */
@keyframes fade-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
@keyframes fade-out {
  from { opacity: 1; transform: scale(1); }
  to { opacity: 0; transform: scale(0.95); }
}
@keyframes slide-in {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
@keyframes slide-out {
  from { transform: translateY(0); opacity: 1; }
  to { transform: translateY(100%); opacity: 0; }
}

.animate-fade-in { animation: fade-in 0.3s ease-out; }
.animate-fade-out { animation: fade-out 0.25s ease-in forwards; }
.animate-slide-in { animation: slide-in 0.35s ease-out; }
.animate-slide-out { animation: slide-out 0.25s ease-in forwards; }
</style>

<script>
// Tutup modal dengan klik luar area
document.addEventListener("click", function(e) {
  const overlay = document.getElementById("daftarSiswa");
  const box = overlay ? overlay.querySelector(".modal-content") : null;

  if (overlay && box && !box.contains(e.target)) {
    const isMobile = window.innerWidth < 768;
    box.classList.add(isMobile ? "animate-slide-out" : "animate-fade-out");
    setTimeout(() => overlay.remove(), 300);
  }
});
</script>
