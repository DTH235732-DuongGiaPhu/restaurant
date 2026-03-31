<?php
require_once __DIR__ . '/includes/header.php';
// header already starts session and loads functions
if (!isLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $r = $stmt->fetch();
        if ($r && $r['status'] === 'pending') {
            $pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id=?")->execute([$id]);
            setFlash('success', 'Đã huỷ đặt bàn #' . str_pad($id,5,'0',STR_PAD_LEFT));
        } else {
            setFlash('error', 'Không thể huỷ đặt bàn này');
        }
    }
}
header('Location: ' . SITE_URL . '/my-reservations.php'); exit;
