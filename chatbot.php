<?php
/**
 * Chatbot API - Tích hợp Claude AI
 * Nhận JSON POST, trả về JSON response
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Phương thức không hợp lệ.']);
    exit;
}

$body    = file_get_contents('php://input');
$payload = json_decode($body, true);
$userMsg = trim($payload['message'] ?? '');

if (!$userMsg) {
    echo json_encode(['reply' => 'Vui lòng nhập câu hỏi!']);
    exit;
}

// ─── Build context từ database ────────────────────────────────
$menuItems   = $pdo->query("SELECT d.name, d.price, c.name as cat FROM dishes d JOIN categories c ON d.category_id=c.id WHERE d.status='active' ORDER BY c.sort_order, d.name")->fetchAll();
$menuContext = '';
foreach ($menuItems as $m) {
    $menuContext .= "- {$m['name']} ({$m['cat']}): " . number_format($m['price'],0,',','.') . "đ\n";
}

$systemPrompt = <<<PROMPT
Bạn là trợ lý AI thân thiện của nhà hàng **Saigon Bistro** — nhà hàng ẩm thực Việt Nam cao cấp tại TP.HCM.

**Thông tin nhà hàng:**
- Tên: Saigon Bistro
- Địa chỉ: 168 Hai Bà Trưng, Quận 1, TP.HCM
- Điện thoại: 1800 6868
- Email: info@saigonbistro.com
- Giờ mở cửa: 10:00 – 22:00, tất cả các ngày trong tuần
- Website: saigonbistro.com

**Thực đơn hiện tại:**
$menuContext

**Vai trò của bạn:**
1. Tư vấn món ăn phù hợp với khẩu vị, ngân sách, dịp ăn
2. Gợi ý các combo, món nổi bật, món mới
3. Trả lời về giờ mở cửa, địa chỉ, liên hệ
4. Hướng dẫn đặt hàng qua website
5. Giải đáp thắc mắc về giao hàng, thanh toán

**Phong cách:**
- Thân thiện, nhiệt tình, chuyên nghiệp
- Trả lời bằng tiếng Việt ngắn gọn (2-4 câu)
- Thỉnh thoảng dùng emoji phù hợp
- Không bịa thông tin ngoài dữ liệu đã cho
PROMPT;

// ─── Gọi Claude API ────────────────────────────────────────────
$apiKey = CLAUDE_API_KEY;

// Fallback nếu chưa có API key thực
if (!$apiKey || $apiKey === 'YOUR_CLAUDE_API_KEY_HERE') {
    $reply = generateLocalReply($userMsg, $menuItems);
    echo json_encode(['reply' => $reply]);
    exit;
}

$requestBody = json_encode([
    'model'      => 'claude-sonnet-4-20250514',
    'max_tokens' => 512,
    'system'     => $systemPrompt,
    'messages'   => [
        ['role' => 'user', 'content' => $userMsg]
    ]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $requestBody,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $httpCode === 200) {
    $data  = json_decode($response, true);
    $reply = $data['content'][0]['text'] ?? 'Xin lỗi, tôi không thể trả lời lúc này.';
} else {
    // Fallback khi API lỗi
    $reply = generateLocalReply($userMsg, $menuItems);
}

echo json_encode(['reply' => $reply]);

// ─── Local fallback khi chưa có API key ───────────────────────
function generateLocalReply($msg, $menu) {
    $msg = mb_strtolower($msg, 'UTF-8');

    $greetings = ['xin chào', 'hello', 'hi', 'chào'];
    foreach ($greetings as $g) {
        if (str_contains($msg, $g)) {
            return "Xin chào! Tôi là trợ lý AI của Saigon Bistro 🍜 Tôi có thể giúp bạn tư vấn món ăn, giờ mở cửa và đặt hàng. Bạn cần hỗ trợ gì?";
        }
    }
    if (str_contains($msg, 'giờ') || str_contains($msg, 'mở cửa') || str_contains($msg, 'thời gian')) {
        return "Saigon Bistro mở cửa từ **10:00 – 22:00** tất cả các ngày trong tuần, kể cả ngày lễ. Chúng tôi cũng nhận đặt hàng online và giao hàng tận nơi! ⏰";
    }
    if (str_contains($msg, 'địa chỉ') || str_contains($msg, 'ở đâu') || str_contains($msg, 'chi nhánh')) {
        return "Saigon Bistro tọa lạc tại **168 Hai Bà Trưng, Quận 1, TP.HCM** 📍 Bạn có thể gọi **1800 6868** để được hỗ trợ thêm hoặc xem bản đồ trên trang liên hệ!";
    }
    if (str_contains($msg, 'ngon') || str_contains($msg, 'gợi ý') || str_contains($msg, 'recommend') || str_contains($msg, 'nên ăn')) {
        $featured = array_filter($menu, fn($m) => true);
        $picks = array_slice($featured, 0, 3);
        $list = implode(', ', array_column($picks, 'name'));
        return "Những món được yêu thích nhất tại Saigon Bistro: **$list** 🌟 Đây đều là món đặc trưng với nguyên liệu tươi ngon mỗi ngày!";
    }
    if (str_contains($msg, 'rẻ') || str_contains($msg, 'giá') || str_contains($msg, 'tiết kiệm') || str_contains($msg, 'budget')) {
        $cheap = array_filter($menu, fn($m) => $m['price'] <= 80000);
        $names = implode(', ', array_slice(array_column($cheap, 'name'), 0, 3));
        return "Những món ngon giá phải chăng dưới 80.000đ: **$names** 💰 Chất lượng đảm bảo, vị ngon chuẩn!";
    }
    if (str_contains($msg, 'lẩu') || str_contains($msg, 'nướng')) {
        return "Mục Lẩu & Nướng của chúng tôi rất được yêu thích! 🔥 Nổi bật là **Lẩu Thái hải sản** và **Nướng than hoa tổng hợp** cho nhóm 2 người. Bạn muốn xem giá cụ thể không?";
    }
    if (str_contains($msg, 'hải sản') || str_contains($msg, 'tôm') || str_contains($msg, 'cua') || str_contains($msg, 'mực')) {
        return "Mục Hải sản chúng tôi có nhiều món tươi ngon như **Tôm hùm nướng bơ tỏi**, **Cua rang me**, **Mực nhồi thịt hấp** 🦞 Hải sản tươi nhập mỗi ngày, đảm bảo chất lượng!";
    }
    if (str_contains($msg, 'giao hàng') || str_contains($msg, 'ship') || str_contains($msg, 'delivery')) {
        return "Saigon Bistro giao hàng trong vòng **45 phút** và **miễn phí phí giao hàng** 🚀 Bạn chỉ cần đặt hàng trên website, điền địa chỉ và chúng tôi sẽ lo phần còn lại!";
    }
    if (str_contains($msg, 'thanh toán') || str_contains($msg, 'payment') || str_contains($msg, 'tiền mặt') || str_contains($msg, 'chuyển khoản')) {
        return "Saigon Bistro chấp nhận **tiền mặt khi nhận hàng** và **chuyển khoản ngân hàng** 💳 Bạn có thể chọn phương thức thanh toán khi đặt hàng!";
    }
    if (str_contains($msg, 'tráng miệng') || str_contains($msg, 'dessert')) {
        return "Tráng miệng của chúng tôi cực ngon! 🍮 Có **Bánh flan caramel** tự làm, **Chè thập cẩm** đặc biệt và **Kem dừa Thái** nguyên trái. Điểm tuyệt vời để kết thúc bữa ăn!";
    }
    if (str_contains($msg, 'đặt hàng') || str_contains($msg, 'order') || str_contains($msg, 'đặt món')) {
        return "Đặt hàng rất đơn giản! 🛒 Vào **Thực đơn** → chọn món → bấm **+** thêm vào giỏ → vào **Giỏ hàng** → điền thông tin → xác nhận. Chúng tôi sẽ giao hàng trong 45 phút!";
    }
    if (str_contains($msg, 'cảm ơn') || str_contains($msg, 'thanks')) {
        return "Cảm ơn bạn đã liên hệ với Saigon Bistro! 🙏 Chúc bạn có bữa ăn ngon miệng. Mọi thắc mắc cứ hỏi chúng tôi nhé!";
    }

    // Default
    return "Cảm ơn câu hỏi của bạn! 😊 Tôi có thể giúp bạn: tư vấn **món ăn**, xem **giờ mở cửa**, tìm **địa chỉ**, hoặc hướng dẫn **đặt hàng**. Bạn cần hỗ trợ điều gì?";
}
?>
