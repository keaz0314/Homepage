import os
import json

def scan_directory_to_dict(base_dir, current_path):
    """
    재귀적으로 디렉토리를 스캔하여 중첩된 사전(dict) 구조를 만듭니다.
    """
    items = {}
    try:
        # 항목들을 이름순으로 정렬
        entries = sorted(os.listdir(current_path))
        for entry_name in entries:
            full_path = os.path.join(current_path, entry_name)
            # 웹 URL을 위한 상대 경로 생성
            relative_path_for_url = os.path.relpath(full_path, start=base_dir)
            # 윈도우 경로 구분자 '\'를 웹 경로 구분자 '/'로 변경하고, URL 경로를 /share/로 시작하도록 수정
            url = "/share/" + relative_path_for_url.replace(os.path.sep, '/')

            if os.path.isdir(full_path):
                # 하위 폴더의 내용을 재귀적으로 스캔
                children_items = scan_directory_to_dict(base_dir, full_path)
                # 폴더 정보 기록
                items[entry_name] = {
                    "type": "folder",
                    "name": entry_name,
                    "children": children_items
                }
            else:
                # 파일 정보 기록
                items[entry_name] = {
                    "type": "file",
                    "name": entry_name,
                    "url": url
                }
    except OSError as e:
        print(f"오류: 디렉토리에 접근할 수 없습니다. 경로: {current_path}, 오류: {e}")
    return items

def main():
    # 스캔할 소스 디렉토리
    source_dir = "/mnt/NAS/SoC-NAS/Homepage/uploads"
    # 생성될 JSON 파일 경로 수정
    output_json_path = "/home/jsh/SoC-Homepage/files.json"
    
    print(f"'{source_dir}' 폴더를 스캔합니다...")
    
    if not os.path.isdir(source_dir):
        print(f"오류: 소스 디렉토리 '{source_dir}'를 찾을 수 없습니다.")
        return

    # 최상위 디렉토리 스캔 시작
    result_dict = scan_directory_to_dict(source_dir, source_dir)

    print(f"스캔 완료. 결과를 '{output_json_path}' 파일에 저장합니다.")
    
    # JSON 파일로 저장
    try:
        # 출력 파일의 디렉토리가 없으면 생성
        os.makedirs(os.path.dirname(output_json_path), exist_ok=True)
        
        with open(output_json_path, 'w', encoding='utf-8') as f:
            # ensure_ascii=False로 한글이 깨지지 않도록 하고, indent로 가독성 높임
            json.dump(result_dict, f, ensure_ascii=False, indent=2)
            
        print("JSON 파일 생성이 완료되었습니다.")
    except IOError as e:
        print(f"오류: 파일 쓰기에 실패했습니다. 경로: {output_json_path}, 오류: {e}")

if __name__ == "__main__":
    main()