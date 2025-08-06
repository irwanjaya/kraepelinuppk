<?php
session_start();
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['participant_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                
                if (empty($username) || empty($password)) {
                    $error = 'Username dan password harus diisi.';
                } else {
                    $result = loginParticipant($username, $password);
                    if ($result['success']) {
                        $_SESSION['participant_logged_in'] = true;
                        $_SESSION['participant_info'] = [
                            'session_id' => $result['participant']['id'],
                            'name' => $result['participant']['participant_name'],
                            'unit_kerja' => $result['participant']['participant_unit_kerja'],
                            'username' => $result['participant']['participant_username']
                        ];
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'register':
                $name = trim($_POST['name']);
                $unitKerja = trim($_POST['unit_kerja']);
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if (empty($name) || empty($unitKerja) || empty($username) || empty($password)) {
                    $error = 'Semua field harus diisi.';
                } elseif (strlen($username) < 3) {
                    $error = 'Username minimal 3 karakter.';
                } elseif (strlen($password) < 6) {
                    $error = 'Password minimal 6 karakter.';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Konfirmasi password tidak cocok.';
                } else {
                    $result = registerParticipant($name, $unitKerja, $username, $password);
                    if ($result['success']) {
                        $success = 'Registrasi berhasil! Silakan login dengan username dan password Anda.';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tes Kraepelin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Tes Kraepelin</h1>
            <p class="text-gray-600">Silakan login atau daftar untuk memulai tes</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Login/Register Tabs -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex">
                <button id="loginTab" class="flex-1 py-3 px-4 text-center font-medium bg-blue-600 text-white">
                    Login
                </button>
                <button id="registerTab" class="flex-1 py-3 px-4 text-center font-medium bg-gray-200 text-gray-700 hover:bg-gray-300">
                    Daftar
                </button>
            </div>

            <!-- Login Form -->
            <div id="loginForm" class="p-6">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="mb-4">
                        <label for="login_username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input
                            id="login_username"
                            name="username"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Masukkan username"
                        />
                    </div>

                    <div class="mb-6">
                        <label for="login_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input
                            id="login_password"
                            name="password"
                            type="password"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Masukkan password"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Login
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="registerForm" class="p-6 hidden">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="mb-4">
                        <label for="register_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Lengkap
                        </label>
                        <input
                            id="register_name"
                            name="name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Masukkan nama lengkap"
                        />
                    </div>

                    <div class="mb-4">
                        <label for="register_unit_kerja" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit Kerja
                        </label>
                        <input
                            id="register_unit_kerja"
                            name="unit_kerja"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Masukkan unit kerja"
                        />
                    </div>

                    <div class="mb-4">
                        <label for="register_username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input
                            id="register_username"
                            name="username"
                            type="text"
                            required
                            minlength="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Minimal 3 karakter"
                        />
                    </div>

                    <div class="mb-4">
                        <label for="register_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input
                            id="register_password"
                            name="password"
                            type="password"
                            required
                            minlength="6"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Minimal 6 karakter"
                        />
                    </div>

                    <div class="mb-6">
                        <label for="register_confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Konfirmasi Password
                        </label>
                        <input
                            id="register_confirm_password"
                            name="confirm_password"
                            type="password"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Ulangi password"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium"
                    >
                        Daftar
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500">
            <p>Aplikasi Tes Kraepelin - Sistem Penilaian Digital</p>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        loginTab.addEventListener('click', function() {
            loginTab.className = 'flex-1 py-3 px-4 text-center font-medium bg-blue-600 text-white';
            registerTab.className = 'flex-1 py-3 px-4 text-center font-medium bg-gray-200 text-gray-700 hover:bg-gray-300';
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });

        registerTab.addEventListener('click', function() {
            registerTab.className = 'flex-1 py-3 px-4 text-center font-medium bg-green-600 text-white';
            loginTab.className = 'flex-1 py-3 px-4 text-center font-medium bg-gray-200 text-gray-700 hover:bg-gray-300';
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });

        // Password confirmation validation
        const registerPassword = document.getElementById('register_password');
        const confirmPassword = document.getElementById('register_confirm_password');

        function validatePassword() {
            if (registerPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Password tidak cocok');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        registerPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    </script>
</body>
</html>