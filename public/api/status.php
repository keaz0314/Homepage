<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// 세션에 인증 정보가 있는지 확인
if (isset($_SESSION['otp_authenticated']) && $_SESSION['otp_authenticated'] === true) {
    echo json_encode(['isAuthenticated' => true]);
} else {
    echo json_encode(['isAuthenticated' => false]);
}
