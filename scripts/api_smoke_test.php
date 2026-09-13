<?php
$base = 'http://127.0.0.1:8080/api';
$results = [];

function req(string $method, string $path, array $data = [], ?string $token = null): array
{
    global $base;
    $url = $base . $path;
    if (strtoupper($method) === 'GET' && $data) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'token: ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 25,
    ]);
    if (in_array(strtoupper($method), ['POST', 'PUT'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string) $body, true);
    return [
        'http' => $code,
        'errno' => $errno,
        'error' => $err,
        'json' => $json,
        'success' => is_array($json) ? ($json['success'] ?? null) : null,
        'msg' => is_array($json) ? (string) ($json['msg'] ?? '') : substr((string) $body, 0, 120),
        'data' => is_array($json) ? ($json['data'] ?? null) : null,
    ];
}

function test(string $name, string $method, string $path, array $data = [], ?string $token = null, bool $expectSuccess = true): void
{
    global $results;
    $r = req($method, $path, $data, $token);
    $reachable = $r['errno'] === 0 && $r['http'] > 0 && $r['http'] !== 404 && $r['http'] !== 500;
    $bizOk = $r['success'] === true || $r['success'] === 1 || $r['success'] === 'true';
    if ($expectSuccess) {
        $status = ($reachable && $bizOk) ? 'PASS' : 'FAIL';
    } else {
        // endpoint exists; business may fail
        $status = $reachable ? ($bizOk ? 'PASS' : 'SOFT') : 'FAIL';
    }
    $results[] = [
        'name' => $name,
        'method' => $method,
        'path' => $path,
        'status' => $status,
        'http' => $r['http'],
        'success' => $r['success'],
        'msg' => $r['msg'],
    ];
    echo sprintf("%-4s %-6s %-50s http=%-3s msg=%s\n", $status, $method, $path, $r['http'], mb_substr($r['msg'], 0, 50));
}

echo "=== PUBLIC ===\n";
test('首页', 'GET', '/volunteer.index/index');
test('积分简介', 'GET', '/volunteer.index/article', ['type' => 1]);
test('兑换说明', 'GET', '/volunteer.index/article', ['type' => 2]);
test('公告列表', 'GET', '/volunteer.index/announcements', ['current' => 1, 'pageSize' => 10]);
test('地理编码', 'GET', '/volunteer.index/geocode', ['address' => '重庆市沙坪坝区万寿山社区'], null, false);
test('活动类型', 'GET', '/volunteer.activity/types');
test('活动列表', 'GET', '/volunteer.activity/list', ['current' => 1, 'pageSize' => 10]);
test('活动详情', 'GET', '/volunteer.activity/detail', ['id' => 1]);
test('商品列表', 'GET', '/volunteer.mall/goodsList', ['current' => 1, 'pageSize' => 20]);
test('商品详情', 'GET', '/volunteer.mall/goodsDetail', ['id' => 1]);
test('注册配置', 'GET', '/volunteer.volunteer/registerConfig');
test('风采列表', 'GET', '/volunteer.showcase/list', ['current' => 1, 'pageSize' => 10]);
test('风采详情', 'GET', '/volunteer.showcase/detail', ['id' => 1]);
test('附近商户', 'GET', '/volunteer.merchant/list');

echo "\n=== LOGIN ===\n";
$login = req('POST', '/volunteer.volunteer/login', ['code' => 'dev_mode_fixed_code', 'nickname' => '张志愿者']);
$userToken = $login['data']['token'] ?? ($login['data']['api_token'] ?? null);
echo 'user login success=' . var_export($login['success'], true) . ' token=' . ($userToken ? substr($userToken, 0, 10) . '...' : 'NONE') . ' keys=' . (is_array($login['data']) ? implode(',', array_keys($login['data'])) : '-') . "\n";
if (!$userToken && is_array($login['data'])) {
    echo 'login data sample: ' . substr(json_encode($login['data'], JSON_UNESCAPED_UNICODE), 0, 300) . "\n";
}

$mLogin = req('POST', '/volunteer.merchant/login', ['account' => 'merchant', 'password' => '123456']);
$merchantToken = $mLogin['data']['token'] ?? ($mLogin['data']['api_token'] ?? null);
if (!$merchantToken && is_array($mLogin['data']['merchant'] ?? null)) {
    $merchantToken = $mLogin['data']['merchant']['api_token'] ?? null;
}
echo 'merchant login success=' . var_export($mLogin['success'], true) . ' token=' . ($merchantToken ? substr($merchantToken, 0, 10) . '...' : 'NONE') . ' keys=' . (is_array($mLogin['data']) ? implode(',', array_keys($mLogin['data'])) : '-') . "\n";
if (!$merchantToken && is_array($mLogin['data'])) {
    echo 'merchant data sample: ' . substr(json_encode($mLogin['data'], JSON_UNESCAPED_UNICODE), 0, 300) . "\n";
}

echo "\n=== USER AUTH ===\n";
test('志愿者信息', 'GET', '/volunteer.volunteer/info', [], $userToken);
test('积分明细', 'GET', '/volunteer.volunteer/points', [], $userToken);
test('积分搜索', 'GET', '/volunteer.volunteer/searchPoints', ['keyword' => '张'], $userToken);
test('星级', 'GET', '/volunteer.volunteer/starLevel', [], $userToken);
test('服务证', 'GET', '/volunteer.volunteer/certificates', [], $userToken);
test('我的活动', 'GET', '/volunteer.volunteer/myActivities', [], $userToken);
test('我的订单', 'GET', '/volunteer.mall/myOrders', [], $userToken);
test('更新资料', 'POST', '/volunteer.volunteer/updateProfile', ['nickname' => '张志愿者'], $userToken);

echo "\n=== MERCHANT AUTH ===\n";
test('商户看板', 'GET', '/volunteer.merchant/dashboard', [], $merchantToken);
test('商户商品', 'GET', '/volunteer.merchant/goods', [], $merchantToken);
test('商户商品详情', 'GET', '/volunteer.merchant/goods/detail', ['id' => 3], $merchantToken);
test('核销记录', 'GET', '/volunteer.merchant/verifyRecords', ['all' => 0], $merchantToken);
test('结算', 'GET', '/volunteer.merchant/settlement', [], $merchantToken);
test('商户资料', 'GET', '/volunteer.merchant/profile', [], $merchantToken);

echo "\n=== WRITE (safe) ===\n";
// signup may fail if already signed - soft
test('活动报名(可重复失败)', 'POST', '/volunteer.activity/signup', ['id' => 1], $userToken, false);

echo "\n=== SUMMARY ===\n";
$counts = [];
foreach ($results as $r) {
    $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
}
foreach ($counts as $k => $v) {
    echo "$k: $v\n";
}
echo "\nIssues:\n";
foreach ($results as $r) {
    if ($r['status'] !== 'PASS') {
        echo "- [{$r['status']}] {$r['name']} {$r['method']} {$r['path']} http={$r['http']} success=" . var_export($r['success'], true) . " msg={$r['msg']}\n";
    }
}
