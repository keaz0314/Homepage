<?php
// 디버깅 설정
$debug_log_file = '/tmp/upload_debug.log';
file_put_contents($debug_log_file, "--- New Upload Request ---\n", FILE_APPEND);

function write_log($message) {
    global $debug_log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_log_file, "[$timestamp] $message\n", FILE_APPEND);
}

write_log("Script start.");

// 인증 확인
require_once __DIR__ . '/api/config.php';
write_log("Config loaded. Checking authentication.");

if (!isset($_SESSION['otp_authenticated']) || $_SESSION['otp_authenticated'] !== true) {
    write_log("Authentication failed. OTP not set or not true.");
    http_response_code(403);
    echo "오류: 인증되지 않은 접근입니다.";
    exit;
}

write_log("Authentication successful.");

// 기본 업로드 디렉토리 설정
$base_upload_dir = '/mnt/NAS/SoC-NAS/Homepage/uploads/';
$map_dir = sys_get_temp_dir();
write_log("Base upload dir: $base_upload_dir");
write_log("Map dir: $map_dir");

// 업로드된 파일, 파일 경로, 배치 ID 정보 확인
if (isset($_FILES['uploaded_file']) && isset($_POST['file_path']) && isset($_POST['batch_id'])) {
    write_log("File upload data received.");
    write_log("Uploaded file info: " . print_r($_FILES['uploaded_file'], true));
    write_log("File path: " . $_POST['file_path']);
    write_log("Batch ID: " . $_POST['batch_id']);

    // --- 보안 강화 ---
    $file_path = $_POST['file_path'];
    $batch_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['batch_id']);
    $path_without_dots = str_replace('..', '', $file_path);
    $sanitized_path = ltrim($path_without_dots, '/\\');
    write_log("Sanitized path: $sanitized_path");

    // 매핑 파일 경로 정의
    $map_file = $map_dir . '/upload_map_' . $batch_id . '.txt';
    write_log("Map file path: $map_file");

    // --- 파일/폴더 이름 결정 로직 (매핑 파일 사용) ---
    $final_top_level_name = '';

    if (file_exists($map_file)) {
        $final_top_level_name = trim(file_get_contents($map_file));
        write_log("Map file exists. Final top level name from file: $final_top_level_name");
    } else {
        write_log("Map file does not exist. This is the first request of the batch.");
        $path_parts_temp = explode('/', $sanitized_path);
        $top_level_name = $path_parts_temp[0];
        $final_top_level_name = $top_level_name;
        $full_top_level_path = $base_upload_dir . $top_level_name;
        write_log("Original top level name: $top_level_name");

        if (file_exists($full_top_level_path)) {
            write_log("Top level path exists: $full_top_level_path. Generating new name.");
            $counter = 1;
            $name_without_ext = pathinfo($top_level_name, PATHINFO_FILENAME);
            $extension = pathinfo($top_level_name, PATHINFO_EXTENSION);
            
            do {
                $new_name_suffix = " (" . $counter . ")";
                if (strpos($sanitized_path, '/') !== false || empty($extension)) {
                    $final_top_level_name = $name_without_ext . $new_name_suffix;
                } else {
                    $final_top_level_name = $name_without_ext . $new_name_suffix . "." . $extension;
                }
                $counter++;
                write_log("Trying new name: $final_top_level_name");
            } while (file_exists($base_upload_dir . $final_top_level_name));
        }
        write_log("Final top level name determined: $final_top_level_name");
        file_put_contents($map_file, $final_top_level_name);
        write_log("Wrote final name to map file.");
    }
    
    $path_parts = explode('/', $sanitized_path);
    $path_parts[0] = $final_top_level_name;
    $final_sanitized_path = implode('/', $path_parts);
    write_log("Final sanitized path: $final_sanitized_path");

    $directory_path = dirname($final_sanitized_path);
    $file_name = basename($final_sanitized_path);
    write_log("Directory path: $directory_path, File name: $file_name");

    $final_dir = $base_upload_dir . $directory_path;
    write_log("Final directory to create: $final_dir");

    if ($directory_path !== '.' && !is_dir($final_dir)) {
        write_log("Directory does not exist. Attempting to create it.");
        // 디렉토리 생성 전/후로 소유권/권한 확인 로그 추가
        write_log("Checking parent directory permissions for: " . dirname($final_dir));
        write_log("Parent directory perms: " . substr(sprintf('%o', fileperms(dirname($final_dir))), -4));
        
        if (!@mkdir($final_dir, 0775, true) && !is_dir($final_dir)) {
            write_log("mkdir failed!");
            http_response_code(500);
            echo "오류: 디렉토리 생성에 실패했습니다.";
            exit;
        }
        chmod($final_dir, 0775);
        write_log("mkdir successful or directory already exists.");
    }

    $target_file = $final_dir . '/' . $file_name;
    write_log("Target file path: $target_file");
    write_log("Moving temporary file '" . $_FILES['uploaded_file']['tmp_name'] . "' to target.");

    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target_file)) {
        write_log("move_uploaded_file successful.");
        echo htmlspecialchars($final_sanitized_path);
        chmod($final_dir, 0775);
    } else {
        $error = error_get_last();
        write_log("move_uploaded_file failed. Error: " . ($error['message'] ?? 'Unknown error'));
        http_response_code(500);
        echo "오류: 파일 업로드에 실패했습니다.";
    }

} else {
    write_log("Upload failed: Required POST data or $_FILES not set.");
    http_response_code(400);
    echo "오류: 필수 정보가 누락되었습니다.";
}
write_log("Script end.\n");
?>