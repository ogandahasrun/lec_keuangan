<?php
session_start();
require_once 'koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        
        $login_ok    = false;

        // Cek tabel 'user' SIMKES Khanza
        $stmt = $koneksi->prepare("SELECT id_user FROM user
                                    WHERE aes_decrypt(id_user, 'nur') = ?
                                    AND aes_decrypt(password, 'windi') = ?
                                    LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $login_ok = true;
            }
            $stmt->close();
        }

        // Cek tabel 'admin'
        if (!$login_ok) {
            $stmt_adm = $koneksi->prepare("SELECT usere FROM admin
                                            WHERE aes_decrypt(usere, 'nur') = ?
                                            AND aes_decrypt(passworde, 'windi') = ?
                                            LIMIT 1");
            if ($stmt_adm) {
                $stmt_adm->bind_param("ss", $username, $password);
                $stmt_adm->execute();
                $res_adm = $stmt_adm->get_result();
                if ($res_adm && $res_adm->num_rows > 0) {
                    $login_ok = true;
                }
                $stmt_adm->close();
            }
        }

        if ($login_ok) {
            $_SESSION['username'] = $username;
            
            // Ambil nama lengkap dari tabel pegawai
            $stmtPeg = $koneksi->prepare("SELECT nama FROM pegawai WHERE nik = ? LIMIT 1");
            if ($stmtPeg) {
                $stmtPeg->bind_param("s", $username);
                $stmtPeg->execute();
                $resPeg = $stmtPeg->get_result();
                if ($resPeg && $rowPeg = $resPeg->fetch_assoc()) {
                    $_SESSION['nama_lengkap'] = $rowPeg['nama'];
                } else {
                    $_SESSION['nama_lengkap'] = strtoupper($username); 
                }
                $stmtPeg->close();
            } else {
                $_SESSION['nama_lengkap'] = strtoupper($username);
            }

            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Harap isi username dan password!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Laporan Keuangan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-message {
            background: rgba(229, 62, 62, 0.1);
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Laporan Keuangan</h1>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username / NIK</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan NIK" required autofocus>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; font-size: 16px;">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
