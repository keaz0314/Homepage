<?php
// 이 파일은 웹에서 직접 접근할 수 없도록 서버 설정(예: .htaccess)을 통해 보호하는 것이 가장 좋습니다.

// **중요!** setup-otp.php를 통해 새 비밀 키를 생성한 후, 아래 값을 실제 비밀 키로 교체하세요.
// 예: define('OTP_SECRET', 'ABCDE12345KLMNO');
// 초기 설정 전에는 아래 기본값을 사용합니다.
define('OTP_SECRET', 'YOUR_DEFAULT_SECRET_CHANGE_ME');

// 세션 설정
if (session_status() == PHP_SESSION_NONE) {
    // 세션 타임아웃을 1시간(3600초)으로 설정합니다.
    session_set_cookie_params(3600);
    session_start();
}
