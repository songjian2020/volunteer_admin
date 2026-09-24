<?php

namespace Modules\Common\Services;

use Illuminate\Http\UploadedFile;
use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 阿里云 OSS 通用上传服务
 */
class OssUploadService
{
    public static function isConfigured(): bool
    {
        return self::accessKeyId() !== ''
            && self::accessKeySecret() !== ''
            && self::bucket() !== ''
            && self::endpoint() !== '';
    }

    /**
     * @return array{object_key: string, file_path: string, file_url: string}
     */
    public static function uploadBinary(string $contents, string $extension, string $subdir = ''): array
    {
        if ($contents === '') {
            throw new \InvalidArgumentException('上传内容为空');
        }
        if (! self::isConfigured()) {
            throw new \RuntimeException('OSS 未配置，请检查 .env 中的 OSS_* 变量');
        }

        $extension = strtolower(ltrim($extension, '.'));
        if ($extension === '') {
            $extension = self::extensionFromMagicBytes($contents) ?: 'bin';
        }

        $filePath = self::buildFilePath($extension, $subdir);
        $objectKey = self::buildObjectKey($filePath);

        $client = self::client();
        $client->putObject(self::bucket(), $objectKey, $contents, [
            OssClient::OSS_HEADERS => [
                'Content-Type' => self::mimeType($extension),
            ],
        ]);

        return [
            'object_key' => $objectKey,
            'file_path' => $filePath,
            'file_url' => oss_public_url($filePath),
        ];
    }

    /**
     * @return array{object_key: string, file_path: string, file_url: string}
     */
    public static function uploadUploadedFile(UploadedFile $file, string $subdir = ''): array
    {
        $realPath = $file->getRealPath();
        $contents = (is_string($realPath) && is_file($realPath))
            ? (string) file_get_contents($realPath)
            : '';
        if ($contents === '') {
            throw new \InvalidArgumentException('读取上传文件失败');
        }

        $originalName = (string) $file->getClientOriginalName();
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = self::extensionFromMagicBytes($contents) ?: 'bin';
        }

        return self::uploadBinary($contents, $extension, $subdir);
    }

    public static function delete(string $filePath): bool
    {
        if (! self::isConfigured()) {
            return false;
        }
        $filePath = ltrim(str_replace('\\', '/', $filePath), '/');
        if ($filePath === '') {
            return false;
        }

        try {
            self::client()->deleteObject(self::bucket(), self::buildObjectKey($filePath));

            return true;
        } catch (OssException) {
            return false;
        }
    }

    public static function buildFilePath(string $extension, string $subdir = ''): string
    {
        $subdir = trim(str_replace('\\', '/', $subdir), '/');
        $name = date('Ymd') . '/' . uniqid('', true) . '.' . ltrim($extension, '.');

        return $subdir !== '' ? ($subdir . '/' . $name) : $name;
    }

    public static function buildObjectKey(string $filePath): string
    {
        $prefix = self::uploadPrefix();
        $filePath = ltrim(str_replace('\\', '/', $filePath), '/');

        return $prefix !== '' ? ($prefix . '/' . $filePath) : $filePath;
    }

    protected static function client(): OssClient
    {
        return new OssClient(
            self::accessKeyId(),
            self::accessKeySecret(),
            self::endpoint(),
            false
        );
    }

    protected static function accessKeyId(): string
    {
        return trim((string) config('oss.access_key_id', ''));
    }

    protected static function accessKeySecret(): string
    {
        return trim((string) config('oss.access_key_secret', ''));
    }

    protected static function bucket(): string
    {
        return trim((string) config('oss.bucket', ''));
    }

    protected static function endpoint(): string
    {
        $useInternal = (bool) config('oss.use_internal', false);
        $endpoint = $useInternal
            ? trim((string) config('oss.internal_endpoint', ''))
            : trim((string) config('oss.endpoint', ''));

        return $endpoint;
    }

    protected static function uploadPrefix(): string
    {
        return trim((string) config('oss.upload_prefix', 'uploads'), '/');
    }

    protected static function extensionFromMagicBytes(string $contents): string
    {
        $head = substr($contents, 0, 16);
        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) {
            return 'png';
        }
        if (str_starts_with($head, 'GIF87a') || str_starts_with($head, 'GIF89a')) {
            return 'gif';
        }
        if (str_starts_with($head, 'RIFF') && substr($contents, 8, 4) === 'WEBP') {
            return 'webp';
        }

        return '';
    }

    protected static function mimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
