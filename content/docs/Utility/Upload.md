---
weight: 99
---

# 업로드 페이지

## SoC 연구실 서버로 파일이 업로드 됩니다


<style>
  /* 이전 답변의 CSS와 동일 */
  #drop-zone { border: 3px dashed #ccc; border-radius: 15px; width: 100%; height: 300px; display: flex; justify-content: center; align-items: center; text-align: center; font-size: 1.2em; color: #aaa; transition: all 0.3s ease; background-color: #f9f9f9; cursor: pointer; }
  #drop-zone.dragover { border-color: #337ab7; background-color: #eaf2fa; color: #333; }
  #upload-status .upload-item { margin-bottom: 5px; padding: 5px; border-radius: 5px; }
  #upload-status .upload-item.uploading { color: #555; background-color: #f0f0f0; }
  #upload-status .upload-item.success { color: green; background-color: #e8f5e9; }
  #upload-status .upload-item.error { color: red; background-color: #ffebee; }
</style>

<div id="drop-zone"><p>여기에 파일이나 폴더를 드래그 앤 드롭하거나,<br>이 영역을 클릭하여 폴더를 선택하세요.</p></div>
<input type="file" id="file-input" style="display: none;" multiple webkitdirectory>
<div id="upload-status" style="margin-top: 15px;"></div>

<script>
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('file-input');
  const uploadStatus = document.getElementById('upload-status');
  let uploadCounter = 0;

  // --- 기본 이벤트 리스너 설정 ---
  dropZone.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', (e) => handleFileSelection(e.target.files));

  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
  });
  ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, highlight, false));
  ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, unhighlight, false));
  dropZone.addEventListener('drop', handleDrop, false);

  function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
  function highlight(e) { dropZone.classList.add('dragover'); }
  function unhighlight(e) { dropZone.classList.remove('dragover'); }

  // --- 드롭 및 파일 선택 처리 로직 수정 ---
  function getBatchId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }
  
  function handleDrop(e) {
    uploadStatus.innerHTML = '';
    const batchId = getBatchId(); // 업로드 묶음에 대한 고유 ID 생성
    const items = e.dataTransfer.items;
    if (items) {
      for (let i = 0; i < items.length; i++) {
        const entry = items[i].webkitGetAsEntry();
        if (entry) {
          traverseFileTree(entry, "", batchId);
        }
      }
    }
  }
  
  function handleFileSelection(files) {
    uploadStatus.innerHTML = '';
    const batchId = getBatchId(); // 업로드 묶음에 대한 고유 ID 생성
    [...files].forEach(file => {
      uploadFile(file, file.webkitRelativePath, batchId);
    });
  }

  function traverseFileTree(item, path, batchId) {
    if (item.isFile) {
      item.file(file => {
        uploadFile(file, path + file.name, batchId);
      });
    } else if (item.isDirectory) {
      const dirReader = item.createReader();
      dirReader.readEntries(entries => {
        entries.forEach(entry => {
          traverseFileTree(entry, path + item.name + "/", batchId);
        });
      });
    }
  }

  // --- 업로드 로직 수정 ---
  function uploadFile(file, filePath, batchId) {
    const url = '/upload.php';
    const formData = new FormData();
    const currentUploadId = `upload-item-${uploadCounter++}`;
    
    // FormData에 batch_id 추가
    formData.append('batch_id', batchId);
    formData.append('uploaded_file', file);
    formData.append('file_path', filePath);

    // ... (이하 로그 출력 부분은 이전과 동일) ...
    const statusElement = document.createElement('div');
    statusElement.id = currentUploadId;
    statusElement.className = 'upload-item uploading';
    statusElement.innerHTML = `'${filePath}' 업로드 중...`;
    uploadStatus.appendChild(statusElement);

    fetch(url, {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (response.ok) return response.text();
      return response.text().then(text => { throw new Error(text) });
    })
    .then(data => {
      const currentStatusElement = document.getElementById(currentUploadId);
      currentStatusElement.className = 'upload-item success';
      currentStatusElement.innerHTML = `'${data}' - 업로드 완료.`; // 서버가 알려준 최종 경로로 표시
    })
    .catch(error => {
      const currentStatusElement = document.getElementById(currentUploadId);
      currentStatusElement.className = 'upload-item error';
      currentStatusElement.innerHTML = `'${filePath}' - 업로드 실패: ${error.message}`;
    });
  }
</script>