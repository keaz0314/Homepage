<?php
// 이 파일은 웹에서 직접 접근할 수 없도록 서버 설정(예: .htaccess)을 통해 보호하는 것이 가장 좋습니다.

// **중요!** setup-otp.php를 통해 새 비밀 키를 생성한 후, 아래 값을 실제 비밀 키로 교체하세요.
// 예: define('OTP_SECRET', 'ABCDE12345KLMNO');
// 초기 설정 전에는 아래 기본값을 사용합니다.
define('OTP_SECRET', 'XWQL76PHLPLVFGWF');

// 세션 설정
if (session_status() == PHP_SESSION_NONE) {
    // 세션 쿠키의 유효 기간을 0으로 설정하여 브라우저 종료 시 삭제되도록 합니다.
    session_set_cookie_params(0);
    // 서버 측 세션 유지 시간을 10분(600초)으로 설정합니다.
    ini_set('session.gc_maxlifetime', 600);
    // 세션 가비지 컬렉션(GC) 확률을 설정하여 세션이 제때 정리되도록 합니다.
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 1);
    session_start();
}
