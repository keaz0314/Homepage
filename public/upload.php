<?php
// 인증 확인
require_once __DIR__ . '/api/config.php';
if (!isset($_SESSION['otp_authenticated']) || $_SESSION['otp_authenticated'] !== true) {
    http_response_code(403);
    echo "오류: 인증되지 않은 접근입니다.";
    exit;
}

// 기본 업로드 디렉토리 설정
$base_upload_dir = '/mnt/NAS/SoC-NAS/Homepage/uploads/';
// 매핑 파일을 저장할 임시 디렉토리 (웹 서버가 쓰기 가능해야 함)
$map_dir = sys_get_temp_dir();

// 업로드된 파일, 파일 경로, 배치 ID 정보 확인
if (isset($_FILES['uploaded_file']) && isset($_POST['file_path']) && isset($_POST['batch_id'])) {

    // --- 보안 강화 ---
    $file_path = $_POST['file_path'];
    $batch_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['batch_id']); // batch_id 보안 처리
    $path_without_dots = str_replace('..', '', $file_path);
    $sanitized_path = ltrim($path_without_dots, '/\\');

    // 매핑 파일 경로 정의
    $map_file = $map_dir . '/upload_map_' . $batch_id . '.txt';

    // --- 파일/폴더 이름 결정 로직 (매핑 파일 사용) ---
    $final_top_level_name = '';

    // 매핑 파일이 존재하는지 확인
    if (file_exists($map_file)) {
        // 존재하면, 결정된 폴더 이름을 읽어옴
        $final_top_level_name = trim(file_get_contents($map_file));
    } else {
        // 존재하지 않으면, 이 요청이 배치의 첫 요청임
        $path_parts_temp = explode('/', $sanitized_path);
        $top_level_name = $path_parts_temp[0];
        $final_top_level_name = $top_level_name; // 기본값은 원본 이름
        $full_top_level_path = $base_upload_dir . $top_level_name;

        // 최상위 폴더/파일이 이미 존재하는지 확인
        if (file_exists($full_top_level_path)) {
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
            } while (file_exists($base_upload_dir . $final_top_level_name));
        }
        // 결정된 최종 이름을 매핑 파일에 기록
        file_put_contents($map_file, $final_top_level_name);
    }
    // --------------------------------------------------------

    // 결정된 최종 이름으로 경로 재구성
    $path_parts = explode('/', $sanitized_path);
    $path_parts[0] = $final_top_level_name;
    $final_sanitized_path = implode('/', $path_parts);

    // 파일 이름과 디렉토리 경로 분리 (최종 경로 기준)
    $directory_path = dirname($final_sanitized_path);
    $file_name = basename($final_sanitized_path);

    // 최종 저장될 전체 디렉토리 경로 생성
    $final_dir = $base_upload_dir . $directory_path;

    // 디렉토리 생성 로직
    if ($directory_path !== '.' && !is_dir($final_dir)) {
        if (!@mkdir($final_dir, 0775, true) && !is_dir($final_dir)) {
            http_response_code(500);
            echo "오류: 디렉토리 생성에 실패했습니다.";
            exit;
        }
    }

    // 최종 파일 저장 경로
    $target_file = $final_dir . '/' . $file_name;

    // 임시 파일을 최종 경로로 이동
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target_file)) {
        echo htmlspecialchars($final_sanitized_path); // JS에서 성공 메시지에 사용할 최종 경로 반환
    } else {
        http_response_code(500);
        echo "오류: 파일 업로드에 실패했습니다.";
    }

} else {
    http_response_code(400);
    echo "오류: 필수 정보가 누락되었습니다.";
}
?>