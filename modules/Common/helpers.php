<?php

use Modules\SystemTool\Services\SysSiteConfigService;

if (! function_exists('site_config')) {
    /**
     * 获取或设置系统配置
     *
     * @param  string|null  $name  配置名称，格式：'group.key' 或 'group'，为null时返回所有配置
     * @param  mixed|null  $default  默认值（仅在获取时使用）
     * @return mixed
     *
     * @example
     *   site_config('site.name')           // 获取配置
     *   site_config('site.name', 'Default') // 带默认值获取
     *   site_config('site')                 // 获取整个组
     *   site_config()                       // 获取所有配置
     */
    function site_config(?string $name = null, mixed $default = null): mixed
    {
        return SysSiteConfigService::getSiteConfig($name, $default);
    }
}

if (! function_exists('web_path')) {
    /**
     * Get the path to the web of the install.
     *
     * @param string $path
     * @return string
     */
    function web_path(string $path = ''): string
    {
        return base_path('web'. DIRECTORY_SEPARATOR . $path);
    }
}

if (! function_exists('public_site_url')) {
    /**
     * 当前站点可访问的绝对地址。
     * 优先用本次请求的域名/协议，避免 APP_URL 仍是 127.0.0.1 导致图片无法回显。
     */
    function public_site_url(string $path = ''): string
    {
        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '/') {
            $path = '';
        }

        $base = '';
        try {
            $request = request();
            $host = (string) $request?->getHost();
            if ($host !== '') {
                $base = rtrim($request->getSchemeAndHttpHost(), '/');
                $forwardedProto = strtolower((string) $request->header('X-Forwarded-Proto', ''));
                if (str_contains($forwardedProto, 'https') && str_starts_with($base, 'http://')) {
                    $base = 'https://' . substr($base, 7);
                }
            }
        } catch (\Throwable $e) {
            $base = '';
        }

        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        }

        if ($base === '') {
            return $path;
        }

        return $base . $path;
    }
}

if (! function_exists('public_storage_url')) {
    /**
     * 本地磁盘文件的公开访问地址：/storage/{file_path}
     */
    function public_storage_url(?string $filePath): string
    {
        $filePath = ltrim((string) $filePath, '/');
        if ($filePath === '') {
            return '';
        }

        return public_site_url('storage/' . $filePath);
    }
}

if (! function_exists('getTreeData')) {
    /**
     * 获取树形数据
     *
     * @param array $list
     * @param int $parentId
     * @param string[] $params
     * @return array
     */
    function getTreeData(
        array &$list,
        int $parentId = 0,
        array $params = []
    ): array
    {
        $params = array_merge($params, [
            'id' => 'id',
            'parent_id' => 'parent_id',
            'children' => 'children'
        ]);
        $data = [];
        foreach ($list as $k => $item) {
            if ($item[$params['parent_id']] == $parentId) {
                $children = getTreeData($list, $item[$params['id']], $params);
                !empty($children) && $item[$params['children']] = $children;
                $data[] = $item;
                unset($list[$k]);
            }
        }
        usort($data, static function (array $a, array $b): int {
            $order = ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            return $order !== 0 ? $order : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        });
        return $data;
    }
}
