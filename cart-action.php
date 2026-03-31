<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');

$data = ['success' => false];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $id    = (int)($_POST['id'] ?? 0);
        $name  = clean($_POST['name'] ?? '');
        $price = (int)($_POST['price'] ?? 0);
        $img   = clean($_POST['img'] ?? '');

        if ($id > 0 && $name && $price > 0) {
            if (!isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] = ['id'=>$id,'name'=>$name,'price'=>$price,'img'=>$img,'quantity'=>0];
            }
            $_SESSION['cart'][$id]['quantity']++;
            $data = ['success' => true, 'count' => getCartCount(), 'total' => getCartTotal()];
        }
        break;

    case 'increase':
        $id = (int)($_POST['id'] ?? 0);
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
            $data = ['success' => true, 'count' => getCartCount()];
        }
        break;

    case 'decrease':
        $id = (int)($_POST['id'] ?? 0);
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']--;
            if ($_SESSION['cart'][$id]['quantity'] <= 0) unset($_SESSION['cart'][$id]);
            $data = ['success' => true, 'count' => getCartCount()];
        }
        break;

    case 'remove':
        $id = (int)($_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        $data = ['success' => true, 'count' => getCartCount()];
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        $data = ['success' => true, 'count' => 0];
        break;
}

echo json_encode($data);
?>
