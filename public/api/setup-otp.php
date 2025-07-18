<?php
require_once __DIR__ . '/../libs/GoogleAuthenticator.php';
require_once __DIR__ . '/config.php';

$g = new \PHPGangsta\GoogleAuthenticator();

// 새로운 비밀 키 생성
$secret = $g->createSecret();

// QR 코드 생성을 위한 정보
$website = $_SERVER['HTTP_HOST']; // 또는 원하는 이름으로 설정
$title = 'SoC Homepage';
$qrCodeUrl = $g->getQRCodeGoogleUrl($website, $secret, $title);

?>
<!DOCTYPE html>
<html>
<head>
    <title>OTP Setup</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        #qr-code { margin: 20px; border: 5px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
        .secret-key { font-family: monospace; background-color: #f0f0f0; padding: 10px; border-radius: 5px; font-size: 1.2em; }
        .instructions { margin: 30px auto; max-width: 500px; text-align: left; line-height: 1.6; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Google OTP 설정</h1>
    <div class="instructions">
        <p>1. 스마트폰에 Google Authenticator 앱을 설치하세요.</p>
        <p>2. 앱을 열고 '+' 버튼을 눌러 'QR 코드 스캔'을 선택하세요.</p>
        <p>3. 아래 QR 코드를 스캔하세요.</p>
        <p class="warning">4. <strong>매우 중요:</strong> 스캔이 완료되면, 아래 생성된 비밀 키를 복사하여 <code>static/api/config.php</code> 파일의 <code>OTP_SECRET</code> 값으로 붙여넣으세요. 이 키가 없으면 OTP 인증이 불가능합니다.</p>
        <p class="warning">5. 설정이 완료되면 보안을 위해 이 <code>setup-otp.php</code> 파일을 서버에서 삭제하거나 접근을 차단하는 것을 권장합니다.</p>
    </div>

    <div id="qr-code">
        <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
    </div>

    <h2>비밀 키</h2>
    <p>QR 코드 스캔이 불가능할 경우, 아래 키를 직접 입력하세요.</p>
    <p class="secret-key"><?php echo $secret; ?></p>

</body>
</html>
