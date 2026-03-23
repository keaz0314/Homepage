---
weight: 99
_build:
  render: true
  list: false
tags:
  - Homepage
---

# 업로드 페이지

## SoC 연구실 서버로 파일이 업로드 됩니다

<div id="otp-overlay" style="display: none;">
  <div class="otp-box">
    <h1>OTP 인증</h1>
    <input type="text" id="otp-input" placeholder="OTP 6자리 숫자 입력" maxlength="6" inputmode="numeric">
    <button id="otp-submit">확인</button>
    <h5>Google Authenticator 앱의 코드를 입력하세요.</h5>
    <p id="otp-error" class="error-message"></p>
  </div>
</div>

<div id="upload-container" style="visibility: hidden;">
  <div id="drop-zone"><p>여기에 파일이나 폴더를 드래그 앤 드롭하거나,<br>이 영역을 클릭하여 폴더를 선택하세요.</p></div>
  <input type="file" id="file-input" style="display: none;" multiple webkitdirectory>
  <div id="upload-status" style="margin-top: 15px;"></div>
</div>

<style>
  /* OTP 입력창 스타일 */
  #otp-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 9999; }
  .otp-box { background-color: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
  #otp-input { padding: 10px; margin: 10px 0; width: 200px; border: 1px solid #ccc; border-radius: 5px; text-align: center; font-size: 1.2em; letter-spacing: 5px; }
  #otp-submit { padding: 10px 20px; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer; }
  #otp-submit:hover { background-color: #0056b3; }
  .error-message { color: red; font-size: 0.9em; height: 1em; }

  /* 업로드 영역 스타일 */
  #drop-zone { border: 3px dashed #ccc; border-radius: 15px; width: 100%; height: 300px; display: flex; justify-content: center; align-items: center; text-align: center; font-size: 1.2em; color: #aaa; transition: all 0.3s ease; background-color: #f9f9f9; cursor: pointer; }
  #drop-zone.dragover { border-color: #337ab7; background-color: #eaf2fa; color: #333; }
  #upload-status .upload-item { margin-bottom: 5px; padding: 5px; border-radius: 5px; }
  #upload-status .upload-item.uploading { color: #555; background-color: #f0f0f0; }
  #upload-status .upload-item.success { color: green; background-color: #e8f5e9; }
  #upload-status .upload-item.error { color: red; background-color: #ffebee; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const otpOverlay = document.getElementById('otp-overlay');
  const otpInput = document.getElementById('otp-input');
  const otpSubmit = document.getElementById('otp-submit');
  const otpError = document.getElementById('otp-error');
  const uploadContainer = document.getElementById('upload-container');
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('file-input');
  const uploadStatus = document.getElementById('upload-status');
  let uploadCounter = 0;

  // 1. 인증 상태 확인
  fetch('/api/status.php')
    .then(res => res.json())
    .then(data => {
      if (data.isAuthenticated) {
        initializeUploader();
      } else {
        otpOverlay.style.display = 'flex';
      }
    }).catch(() => {
        uploadContainer.innerHTML = '<p style="color: red;">서버와 통신할 수 없습니다. (status.php)</p>';
    });

  // 2. OTP 제출
  otpSubmit.addEventListener('click', checkOtp);
  otpInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') checkOtp();
  });

  function checkOtp() {
    const otp = otpInput.value;
    if (!/^[0-9]{6}$/.test(otp)) {
        otpError.textContent = '6자리 숫자를 입력하세요.';
        return;
    }
    fetch('/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ otp: otp })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        initializeUploader();
      } else {
        otpError.textContent = data.message || 'OTP 코드가 올바르지 않습니다.';
        otpInput.value = '';
      }
    });
  }

  // 3. 업로더 초기화 및 이벤트 바인딩
  function initializeUploader() {
    otpOverlay.style.display = 'none';
    uploadContainer.style.visibility = 'visible';

    // 필수: 브라우저 기본 동작(파일 열기) 완벽 차단
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, preventDefaults, false);
      document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, highlight, false));
    ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, unhighlight, false));

    // 클릭하여 업로드
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async (e) => {
      if (e.target.files.length > 0) {
        await processUploads([...e.target.files]);
        e.target.value = ''; // 동일 파일 재업로드를 위해 초기화
      }
    });

    // 드래그 앤 드롭 업로드
    dropZone.addEventListener('drop', async (e) => {
      uploadStatus.innerHTML = '<div style="color:#555;">폴더 구조를 분석 중입니다. 파일이 많을 경우 수 초가 걸릴 수 있습니다...</div>';
      const items = e.dataTransfer.items;
      const filesToUpload = [];

      if (items) {
        for (let i = 0; i < items.length; i++) {
          const entry = items[i].webkitGetAsEntry();
          if (entry) {
            await collectFiles(entry, "", filesToUpload);
          }
        }
        await processUploads(filesToUpload);
      }
    }, false);
  }

  function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
  function highlight() { dropZone.classList.add('dragover'); }
  function unhighlight() { dropZone.classList.remove('dragover'); }
  function getBatchId() { return Date.now().toString(36) + Math.random().toString(36).substr(2); }

  // [핵심 수정] 100개 파일 읽기 제한 우회를 위한 재귀 읽기 함수
  async function readAllEntries(dirReader) {
    let allEntries = [];
    let readEntries = async () => {
      return new Promise((resolve, reject) => {
        dirReader.readEntries(entries => {
          if (entries.length > 0) {
            allEntries = allEntries.concat(entries);
            resolve(readEntries()); // 계속 읽기
          } else {
            resolve(allEntries); // 끝났으면 반환
          }
        }, reject);
      });
    };
    return readEntries();
  }

  // 파일 트리 수집 함수
  async function collectFiles(item, path, fileList) {
    if (item.isFile) {
      return new Promise(resolve => {
        item.file(file => {
          file.customPath = path + file.name; // 드래그 시 경로 유지를 위한 속성
          fileList.push(file);
          resolve();
        });
      });
    } else if (item.isDirectory) {
      const dirReader = item.createReader();
      const entries = await readAllEntries(dirReader); // 제한 우회 함수 사용
      for (const entry of entries) {
        await collectFiles(entry, path + item.name + "/", fileList);
      }
    }
  }

  // 순차 업로드 실행 함수
  async function processUploads(fileList) {
    if (fileList.length === 0) return;

    uploadStatus.innerHTML = `<div>총 <b style="color:blue;">${fileList.length}</b>개의 파일 업로드를 시작합니다...</div>`;
    const batchId = getBatchId();
    
    for (let i = 0; i < fileList.length; i++) {
      const file = fileList[i];
      const path = file.customPath || file.webkitRelativePath || file.name;
      const progressMsg = `[${i + 1}/${fileList.length}] `;
      
      try {
        await uploadFilePromise(file, path, batchId, progressMsg);
      } catch (error) {
        console.error("Upload Error:", error);
      }
    }
    uploadStatus.insertAdjacentHTML('afterbegin', '<div style="font-weight:bold; color:green; margin-bottom: 10px;">모든 파일 업로드가 완료되었습니다.</div>');
  }

  // 단일 파일 업로드 (Promise 기반)
  function uploadFilePromise(file, filePath, batchId, progressMsg) {
    return new Promise((resolve) => {
      const url = '/upload.php';
      const formData = new FormData();
      const currentUploadId = `upload-item-${uploadCounter++}`;

      formData.append('batch_id', batchId);
      formData.append('uploaded_file', file);
      formData.append('file_path', filePath);

      const statusElement = document.createElement('div');
      statusElement.id = currentUploadId;
      statusElement.className = 'upload-item uploading';
      statusElement.innerHTML = `${progressMsg} '${filePath}' 업로드 중...`;
      uploadStatus.prepend(statusElement); // 새 항목을 위로 추가

      fetch(url, { method: 'POST', body: formData })
      .then(async response => {
        if (!response.ok) throw new Error(await response.text());
        return response.text();
      })
      .then(data => {
        const el = document.getElementById(currentUploadId);
        el.className = 'upload-item success';
        el.innerHTML = `${progressMsg} '${data}' - 완료`;
        resolve(); // 성공 시 다음 파일로 넘어감
      })
      .catch(error => {
        const el = document.getElementById(currentUploadId);
        el.className = 'upload-item error';
        el.innerHTML = `${progressMsg} '${filePath}' - 실패: ${error.message}`;
        resolve(); // 실패해도 멈추지 않고 다음 파일 진행
      });
    });
  }
});
</script>
