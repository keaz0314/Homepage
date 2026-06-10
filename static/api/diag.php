<?php
// --- 진단 스크립트 ---
require_once __DIR__ . '/config.php';

// 오류를 화면에 표시하도록 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "--- PHP 환경 진단 시작 ---\n\n";

// 1. PHP 버전 확인
echo "1. PHP Version: " . phpversion() . "\n";

// 2. 필수 함수 존재 여부 확인
$random_ok = function_exists('random_bytes');
echo "2. 'random_bytes' 함수 존재 여부: " . ($random_ok ? 'OK' : '실패! - OTP 비밀 키를 생성할 수 없습니다.') . "\n";

// 3. 세션 쓰기 권한 확인
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$session_path = session_save_path();
$session_writable = is_writable($session_path);
echo "3. 세션 저장 경로 쓰기 가능 여부: " . ($session_writable ? 'OK' : '실패! - 세션을 시작할 수 없습니다.') . "\n";
echo "   (경로: " . $session_path . ")\n";
session_destroy(); // 진단 후 세션 정리

// 4. 라이브러리 파일 읽기 권한 확인
$lib_path = __DIR__ . '/../libs/GoogleAuthenticator.php';
$lib_readable = is_readable($lib_path);
echo "4. OTP 라이브러리 파일 읽기 가능 여부: " . ($lib_readable ? 'OK' : '실패! - 라이브러리를 불러올 수 없습니다.') . "\n";
echo "   (경로: " . realpath($lib_path) . ")\n";

// 5. 설정 파일 읽기 권한 확인
$config_path = __DIR__ . '/config.php';
$config_readable = is_readable($config_path);
echo "5. 설정 파일 읽기 가능 여부: " . ($config_readable ? 'OK' : '실패! - 설정 파일을 불러올 수 없습니다.') . "\n";
echo "   (경로: " . realpath($config_path) . ")\n";

echo "\n--- 진단 완료 ---\n";

// 만약 이 스크립트가 실행되지 않고 여전히 500 오류가 발생한다면,
// PHP 자체에 문법 오류가 있거나(이 파일 제외), 웹 서버 설정(예: .htaccess) 문제일 가능성이 높습니다.
