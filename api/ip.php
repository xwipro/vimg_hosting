<?php
require_once "common.php";
set_cors_headers('GET', 'Content-Type,token');
check_method('GET');

// 执行JWT全套校验，失效/过期/篡改/未登录都会直接终止输出401
$uid = verify_jwt();
$pdo = get_db();

$ip_str = get_ip();
$ip_area = get_area($ip_str);

json_response(200, "ip获取成功", [
    "ip" => $ip_str,
    "location" => $ip_area
]);
?>
