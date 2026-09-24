<?php

namespace Modules\Common\Services;

/**
 * OSS 图片处理 URL（x-oss-process）拼接，参数来自网站配置
 */
class OssImageUrlService
{
    public static function isEnabled(): bool
    {
        $value = site_config('oss.image_process_enabled', '0');

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function getProcessParams(string $scene = 'list'): string
    {
        if (! self::isEnabled()) {
            return '';
        }

        if ($scene === 'detail') {
            $detail = trim((string) site_config('oss.image_process_detail', ''));
            if ($detail !== '') {
                return $detail;
            }

            return '';
        }

        return trim((string) site_config('oss.image_process_params', ''));
    }

    public static function isOssUrl(string $url): bool
    {
        if (! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $baseHost = strtolower((string) parse_url((string) config('oss.base_url', ''), PHP_URL_HOST));
        if ($baseHost !== '' && $host === $baseHost) {
            return true;
        }

        $bucket = strtolower(trim((string) config('oss.bucket', '')));
        if ($bucket !== '' && str_contains($host, $bucket)) {
            return true;
        }

        return false;
    }

    public static function hasProcessParam(string $url): bool
    {
        return str_contains($url, 'x-oss-process=');
    }

    public static function optimize(?string $url, string $scene = 'list'): string
    {
        if ($url === null) {
            return '';
        }

        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (! self::isOssUrl($url) || self::hasProcessParam($url)) {
            return $url;
        }

        $params = self::getProcessParams($scene);
        if ($params === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'x-oss-process=' . $params;
    }
}
