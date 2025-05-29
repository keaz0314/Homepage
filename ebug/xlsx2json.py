import pandas as pd
import json
import os

def convert_xlsx_to_json(input_filename="data.xlsx", output_filename="../data/data.json"):
    """
    Reads an XLSX file from the same directory as the script,
    converts its first sheet to JSON, and saves it in the same directory.

    Args:
        input_filename (str): The name of the input XLSX file.
        output_filename (str): The name for the output JSON file.
    """
    # 스크립트가 실행되는 현재 작업 디렉토리를 기준으로 파일 경로 설정
    current_directory = os.getcwd()
    input_file_path = os.path.join(current_directory, input_filename)
    output_file_path = os.path.join(current_directory, output_filename)

    try:
        # XLSX 파일의 첫 번째 시트 읽기
        # 만약 특정 시트를 지정하고 싶다면, sheet_name 파라미터를 추가하세요.
        # 예: df = pd.read_excel(input_file_path, sheet_name="Sheet1")
        df = pd.read_excel(input_file_path)

        # DataFrame을 JSON으로 변환 (레코드 리스트 형태)
        # 다른 JSON 구조를 원하시면 'orient' 파라미터를 변경할 수 있습니다.
        # (예: 'split', 'index', 'columns', 'values', 'table')
        json_data = df.to_json(orient="records", indent=4, force_ascii=False) # force_ascii=False로 한글 깨짐 방지

        # JSON 파일로 저장
        with open(output_file_path, 'w', encoding='utf-8') as f:
            f.write(json_data)
        
        print(f"성공: '{input_file_path}' 파일이 '{output_file_path}'로 변환되어 저장되었습니다.")

    except FileNotFoundError:
        print(f"오류: 입력 파일 '{input_file_path}'을(를) 찾을 수 없습니다.")
    except Exception as e:
        print(f"오류 발생: {e}")

if __name__ == "__main__":
    # 기본 파일 이름을 사용하거나, 필요시 다른 파일 이름을 전달할 수 있습니다.
    # 예: convert_xlsx_to_json("my_data.xlsx", "my_output.json")
    convert_xlsx_to_json()
