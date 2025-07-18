<?php
// 인증 확인
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['otp_authenticated']) || $_SESSION['otp_authenticated'] !== true) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => '인증되지 않은 접근입니다.']);
    exit;
}

// 기본 디렉토리 설정
$base_dir = __DIR__ . '/../../uploads/';
$base_url = '/uploads';

function get_directory_tree($dir, $base_url_path) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $url_path = $base_url_path . '/' . rawurlencode($item);
        if (is_dir($path)) {
            $files[$item] = [
                'name' => $item,
                'type' => 'folder',
                'children' => get_directory_tree($path, $url_path)
            ];
        } else {
            $files[$item] = [
                'name' => $item,
                'type' => 'file',
                'url' => $url_path
            ];
        }
    }
    return $files;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$file_tree = get_directory_tree($base_dir, $base_url);
echo json_encode($file_tree);
