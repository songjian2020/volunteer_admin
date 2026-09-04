<?php

return [
    // 兼容 .env；优先读取后台「网站配置 → 微信小程序」
    'wx_app_id' => env('WX_APP_ID', ''),
    'wx_app_secret' => env('WX_APP_SECRET', ''),
];
