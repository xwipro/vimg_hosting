<?php
// 引入公共文件
require_once 'common.php';
set_cors_headers('POST', 'Content-Type');
check_method('POST');

// 获取参数
$input = file_get_contents("php://input");
$postData = json_decode($input, true);

// 参数校验
if (!isset($postData["username"], $postData["password"])) {
    json_response(400, '参数不完整');
}

$username = trim($postData["username"]);
$password = trim($postData["password"]);

try {
    $pdo = get_db();
    // 查询用户
    $stmt = $pdo->prepare("SELECT password FROM user WHERE uid = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user["password"])) {
        json_response(401, '账号或者密码错误');
    }

    // 生成Token
    $token = create_jwt($username);
    // 更新登录状态
    $stmt = $pdo->prepare("UPDATE user SET islogin = 'Y' WHERE uid = ?");
    $stmt->execute([$username]);

    json_response(200, '登录成功', ['token' => $token]);
} catch (Exception $e) {
    json_response(500, '服务器异常');
}