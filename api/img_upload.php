<?php
// 引入公共文件
require_once 'common.php';
set_cors_headers('POST', 'Content-Type,token');
check_method('POST');

// 验证JWT
$uid = verify_jwt();
$pdo = get_db();

// 文件校验
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(400, '请选择上传文件');
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

// 校验格式/大小
if (!in_array($ext, ALLOW_EXT)) {
    json_response(400, '不支持该类型文件');
}
if ($file["size"] > MAX_SIZE) {
    json_response(400, '文件不能超过20MB');
}

// 生成文件
$filename = bin2hex(random_bytes(16)) . "." . $ext;
if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
$dest = "uploads/" . $filename;

// 上传文件
if (!move_uploaded_file($file["tmp_name"], UPLOAD_PATH . $filename)) {
    json_response(500, '文件上传失败');
}

// 入库
$stmt = $pdo->prepare("INSERT INTO imgs (uid, img_name, curr_time) VALUES (?, ?, ?)");
$stmt->execute([$uid, $filename, date("Y-m-d H:i:s")]);

json_response(200, '上传成功', [
    "path" => $dest,
    "name" => $filename
]);