<?php
session_start();
include 'database.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['guru'])) {
  header("Location: dashboard.php");
  exit();
}

if (isset($_POST['login'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Ambil data user berdasarkan username
  $stmt = $conn->prepare("SELECT * FROM guru WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $db_password = $data['password'];
    $password_valid = false;

    // === VERIFIKASI PASSWORD (SUPPORT MD5 & BCRYPT) ===
    
    // 1. Coba verifikasi dengan bcrypt (format baru - paling aman)
    if (password_verify($password, $db_password)) {
      $password_valid = true;
    }
    
    // 2. Jika gagal, coba dengan MD5 (format lama)
    elseif ($db_password === md5($password)) {
      $password_valid = true;
      
      // AUTO-UPGRADE: Ubah password MD5 ke bcrypt untuk keamanan
      $new_hash = password_hash($password, PASSWORD_DEFAULT);
      $update_stmt = $conn->prepare("UPDATE guru SET password = ? WHERE id = ?");
      $update_stmt->bind_param("si", $new_hash, $data['id']);
      $update_stmt->execute();
      $update_stmt->close();
    }
    
    // 3. Jika masih gagal, coba plain text (untuk testing/emergency)
    elseif ($db_password === $password) {
      $password_valid = true;
      
      // AUTO-UPGRADE: Hash password plain text ke bcrypt
      $new_hash = password_hash($password, PASSWORD_DEFAULT);
      $update_stmt = $conn->prepare("UPDATE guru SET password = ? WHERE id = ?");
      $update_stmt->bind_param("si", $new_hash, $data['id']);
      $update_stmt->execute();
      $update_stmt->close();
    }

    // === JIKA PASSWORD VALID ===
    if ($password_valid) {
      // Set session dengan penanganan id_guru yang lebih robust
      $_SESSION['guru'] = $data['nama_guru'];
      
      // Prioritas: gunakan id_guru jika ada dan tidak null, jika tidak pakai id
      if (!empty($data['id_guru'])) {
        $_SESSION['id_guru'] = $data['id_guru'];
      } else {
        $_SESSION['id_guru'] = $data['id'];
        
        // Auto-update id_guru jika kosong
        $conn->query("UPDATE guru SET id_guru = {$data['id']} WHERE id = {$data['id']}");
      }
      
      header("Location: dashboard.php");
      exit();
    } else {
      $error = "Password salah!";
    }

  } else {
    $error = "Username tidak ditemukan!";
  }
  
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Guru | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media (max-width: 640px) {
      .login-container {
        width: 90%;
        padding: 2rem;
      }
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .animate-fadeIn {
      animation: fadeIn 0.5s ease-out;
    }
    
    .error-shake {
      animation: shake 0.5s;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-100 via-blue-50 to-indigo-100 flex justify-center items-center min-h-screen p-4">
  <div class="bg-white shadow-2xl p-5 sm:p-10 rounded-2xl w-full max-w-md login-container animate-fadeIn">
    
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
      <div class="bg-blue-600 w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Login Guru</h1>
      <p class="text-gray-600 text-sm mt-2">SMPN 14 E-Learning System</p>
    </div>
    
    <!-- Error Message -->
    <?php if (isset($error)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg error-shake animate-fadeIn">
        <div class="flex items-center">
          <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <span class="font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
      </div>
    <?php endif; ?>
    
    <!-- Login Form -->
    <form method="POST" class="space-y-5">
      
      <!-- Username -->
      <div class="relative">
        <label class="block mb-2 text-sm font-semibold text-gray-700">Username</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </span>
          <input 
            type="text" 
            name="username" 
            placeholder="Masukkan username" 
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
            required
            autofocus
          >
        </div>
      </div>

      <!-- Password -->
      <div class="relative">
        <label class="block mb-2 text-sm font-semibold text-gray-700">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </span>
          <input 
            type="password" 
            name="password" 
            id="password"
            placeholder="Masukkan password" 
            class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
            required
          >
          <button 
            type="button" 
            onclick="togglePassword()" 
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
          >
            <svg id="eye-icon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        name="login" 
        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 font-medium text-base sm:text-lg mt-6 shadow-lg hover:shadow-xl transform hover:scale-[1.02]"
      >
        <span class="flex items-center justify-center">
          <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
          </svg>
          Masuk
        </span>
      </button>
    </form>
    
    <!-- Footer -->
    <div class="mt-6 text-center">
      <p class="text-xs sm:text-sm text-gray-500">
        Lupa password? Hubungi administrator
      </p>
      <div class="mt-4 pt-4 border-t border-gray-200">
        <p class="text-xs text-gray-400">
          🔒 Sistem login aman dengan auto-upgrade encryption
        </p>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Toggle password visibility
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eye-icon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        `;
      } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    }

    // Auto focus on username field
    document.addEventListener('DOMContentLoaded', function() {
      const usernameInput = document.querySelector('input[name="username"]');
      if (usernameInput) {
        usernameInput.focus();
      }
    });
  </script>
</body>
</html>