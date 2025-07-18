<?php
require_once __DIR__ . '/../libs/GoogleAuthenticator.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$g = new \PHPGangsta\GoogleAuthenticator();
$data = json_decode(file_get_contents('php://input'), true);

// 로그아웃 처리
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

// OTP 코드 검증
if (isset($data['otp'])) {
    $otp = $data['otp'];
    $secret = OTP_SECRET;

    // verifyCode의 세 번째 인자(discrepancy)는 시간 오차를 허용하는 설정입니다.
    // 1은 30초 전후의 코드를 허용함을 의미합니다. (서버-클라이언트 시간 동기화 문제 대비)
    if ($g->verifyCode($secret, $otp, 1)) {
        $_SESSION['otp_authenticated'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP code']);
    }
} else {
    // 잘못된 요청 처리
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'OTP code is required']);
}
