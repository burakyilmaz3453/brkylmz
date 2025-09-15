<?php
// upload.php — basit ve düz bir yükleme ucu.
// Kaydeder: uploads/banner.jpg (kapak) ve uploads/avatar.jpg (avatar)
// Not: PHP'de GD eklentisi gerekli (çoğu hostingde varsayılan).

header('Content-Type: application/json; charset=utf-8');
$kind = $_GET['type'] ?? '';
if(!in_array($kind, ['cover','avatar'], true)){
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid type']); exit;
}
if(empty($_FILES['file']['tmp_name'])){
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'no file']); exit;
}

// Boyut sınırı (10 MB)
if($_FILES['file']['size'] > 10 * 1024 * 1024){
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'file too big']); exit;
}

$dir = __DIR__ . '/uploads';
if(!is_dir($dir)) mkdir($dir, 0775, true);

$tmp = $_FILES['file']['tmp_name'];
$data = @file_get_contents($tmp);
$src  = @imagecreatefromstring($data);
if(!$src){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'unsupported image']); exit; }

$w = imagesx($src); $h = imagesy($src);

// Basit yeniden boyutlandırma (sadece küçült)
if($kind === 'cover'){ $maxW = 1600; $maxH = 900; }
else                 { $maxW = 600;  $maxH = 600; }

$scale = min(1.0, min($maxW / $w, $maxH / $h));
$nw = max(1, (int)round($w * $scale));
$nh = max(1, (int)round($h * $scale));

$dst = imagecreatetruecolor($nw, $nh);
imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh, $w,$h);

// .jpg olarak yaz (sabit isimler)
$target = $dir . '/' . ($kind === 'cover' ? 'banner.jpg' : 'avatar.jpg');
imagejpeg($dst, $target, 88);

imagedestroy($src); imagedestroy($dst);

// Cache busting için zaman ekle
$url = 'uploads/' . ($kind === 'cover' ? 'banner.jpg' : 'avatar.jpg') . '?v=' . time();

echo json_encode(['ok'=>true, 'url'=>$url]);
