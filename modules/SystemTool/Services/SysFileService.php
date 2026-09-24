<?php

namespace Modules\SystemTool\Services;

use App\Exceptions\HttpResponseException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Common\Services\OssUploadService;
use Modules\SystemTool\Enum\FileType;
use Modules\SystemTool\Models\SysFileModel;
use Modules\SystemTool\Settings\StorageSettings;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 文件服务类
 */
class SysFileService
{

    /**
     * 获取回收站列表
     * @param array $params
     * @return array
     */
    public function getTrashedList(array $params = []): array
    {
        $pageSize = $params['pageSize'] ?? 10;
        return SysFileModel::onlyTrashed()
            ->paginate($pageSize)
            ->toArray();
    }

    /**
     * 获取存储磁盘实例
     */
    protected function disk($disk = null): FilesystemAdapter
    {
        if(!$disk) {
            $disk = config('filesystems.default');
        }
        if ($disk === 's3' && !self::isS3Configured()) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.storage.s3_not_configured')]);
        }
        if ($disk === 'oss') {
            throw new HttpResponseException(['success' => false, 'msg' => 'OSS 磁盘不支持此文件操作，请使用 OssUploadService']);
        }
        /** @var FilesystemAdapter */
        return Storage::disk($disk);
    }

    /**
     * 生成存储路径
     */
    protected function generateStoragePath(string $extension): string
    {
        return date('Ymd') . '/' . uniqid() . '.' . $extension;
    }

    /**
     * 上传文件
     * @param UploadedFile $file 文件
     * @param int $groupId 分组 ID
     * @param int $channel 上传来源 0：匿名用户，10：后台用户，20：APP用户
     * @param int|null $user_id 上传用户 ID
     * @return array
     */
    public function upload(UploadedFile $file, int $groupId = 0, int $channel = 0, ?int $user_id = null): array
    {
        $originalName = (string) $file->getClientOriginalName();
        $fileExt = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $contents = $this->readUploadedContents($file);
        if ($contents === '') {
            throw new HttpResponseException([
                'success' => false,
                'msg' => '读取上传文件失败（内容为空），请检查 PHP upload_tmp_dir / 文件大小限制',
            ]);
        }
        if ($fileExt === '') {
            $fileExt = $this->extensionFromMagicBytes($contents);
        }
        if ($fileExt === '') {
            $fileExt = 'bin';
        }
        // 推断文件类型
        $fileType = FileType::guessFromExtension($fileExt);
        // 获取磁盘
        $disk = StorageSettings::get('filesystems.default', config('filesystems.default', 'local'));

        $ossFileUrl = null;
        if ($disk === 'oss') {
            if (!OssUploadService::isConfigured()) {
                throw new HttpResponseException(['success' => false, 'msg' => 'OSS 未配置，请检查 .env 中的 OSS_* 变量']);
            }
            try {
                $uploaded = OssUploadService::uploadUploadedFile($file);
            } catch (\Throwable $e) {
                throw new HttpResponseException([
                    'success' => false,
                    'msg' => 'OSS 上传失败：' . ($e->getMessage() !== '' ? $e->getMessage() : '未知错误'),
                ]);
            }
            $storagePath = $uploaded['file_path'];
            $ossFileUrl = $uploaded['file_url'] ?? null;
        } else {
            $storagePath = $this->generateStoragePath($fileExt);
            try {
                $this->storeToDisk($disk, $storagePath, $contents, $fileExt);
            } catch (HttpResponseException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new HttpResponseException([
                    'success' => false,
                    'msg' => '上传失败[' . $disk . ']：' . ($e->getMessage() !== '' ? $e->getMessage() : '写入存储失败'),
                ]);
            }
        }
        // 保存到数据库
        $model = new SysFileModel();
        $model->disk = $disk;
        $model->group_id = $groupId;
        $model->channel = $channel;
        $model->file_type = $fileType->value;
        $model->file_path = $storagePath;
        $model->file_name = $originalName !== '' ? $originalName : ('upload.' . $fileExt);
        $model->file_size = strlen($contents);
        $model->file_ext = $fileExt;
        $model->uploader_id = $user_id;
        $model->save();

        $result = $model->toArray();
        // OSS 场景确保前端一定能拿到可入库的公网 URL（避免 accessor/配置异常导致空地址）
        if ($disk === 'oss') {
            $publicUrl = is_string($ossFileUrl) && $ossFileUrl !== ''
                ? $ossFileUrl
                : oss_public_url($storagePath);
            if ($publicUrl === '') {
                throw new HttpResponseException(['success' => false, 'msg' => 'OSS 上传成功但未生成访问地址，请检查 OSS_BASE_URL']);
            }
            $result['file_url'] = $publicUrl;
            if (empty($result['preview_url'])) {
                $result['preview_url'] = oss_optimized_url($publicUrl, 'list');
            }
        }

        return $result;
    }

    /** 可靠读取上传文件内容（兼容 getRealPath 为空的环境） */
    protected function readUploadedContents(UploadedFile $file): string
    {
        try {
            $contents = $file->getContent();
            if (is_string($contents) && $contents !== '') {
                return $contents;
            }
        } catch (\Throwable) {
        }

        foreach (array_filter([$file->getRealPath(), $file->getPathname()]) as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                $contents = @file_get_contents($path);
                if (is_string($contents) && $contents !== '') {
                    return $contents;
                }
            }
        }

        return '';
    }

    /**
     * 写入对应磁盘。s3 走无 ACL 的 PutObject，兼容阿里云 OSS（常关闭对象 ACL）。
     */
    protected function storeToDisk(string $disk, string $storagePath, string $contents, string $extension): void
    {
        if ($disk === 's3') {
            $this->storeToS3WithoutAcl($storagePath, $contents, $extension);

            return;
        }

        $stored = $this->disk($disk)->put($storagePath, $contents, ['visibility' => 'public']);
        if (!$stored) {
            throw new \RuntimeException($this->guessPutFailureReason($disk));
        }
    }

    protected function storeToS3WithoutAcl(string $storagePath, string $contents, string $extension): void
    {
        $filesystem = $this->disk('s3');
        if (! method_exists($filesystem, 'getClient')) {
            $stored = $filesystem->put($storagePath, $contents);
            if (!$stored) {
                throw new \RuntimeException($this->guessPutFailureReason('s3'));
            }

            return;
        }

        $config = config('filesystems.disks.s3', []);
        $bucket = (string) ($config['bucket'] ?? '');
        if ($bucket === '') {
            throw new \RuntimeException('S3 Bucket 未配置');
        }

        $params = [
            'Bucket' => $bucket,
            'Key' => ltrim($storagePath, '/'),
            'Body' => $contents,
            'ContentType' => $this->guessMimeType($extension),
        ];
        // 不传 ACL，避免阿里云等关闭对象 ACL 的 Bucket 拒绝写入

        $filesystem->getClient()->putObject($params);
    }

    protected function guessMimeType(string $extension): string
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

    protected function guessPutFailureReason(string $disk): string
    {
        if ($disk === 'local') {
            $root = (string) config('filesystems.disks.local.root', '');
            if ($root !== '' && !is_dir($root)) {
                return "本地目录不存在：{$root}";
            }
            if ($root !== '' && !is_writable($root)) {
                return "本地目录不可写：{$root}";
            }

            return '本地存储写入失败，请检查 public/storage 目录权限';
        }

        if ($disk === 's3') {
            return 'S3/OSS 兼容存储写入失败，请检查密钥、Bucket、Endpoint，并确认未强制对象 ACL';
        }

        return '存储写入失败';
    }

    /** 不依赖 finfo，按文件头识别常见图片扩展名 */
    protected function extensionFromMagicBytes(string $contents): string
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

    /**
     * 软删除文件（移入回收站）
     */
    public function delete(int $id): bool
    {
        $file = SysFileModel::find($id);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        return (bool) $file->delete();
    }

    /**
     * 批量软删除文件
     */
    public function batchDelete(array $fileIds): int
    {
        return SysFileModel::whereIn('id', $fileIds)->delete();
    }

    /**
     * 恢复已删除的文件
     */
    public function restore(int $fileId): bool
    {
        $file = SysFileModel::withTrashed()->find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        return $file->restore();
    }

    /**
     * 批量恢复文件
     */
    public function batchRestore(array $fileIds): int
    {
        return SysFileModel::withTrashed()->whereIn('id', $fileIds)->restore();
    }

    /**
     * 彻底删除文件（含物理删除）
     */
    public function forceDelete(int $fileId): bool
    {
        $file = SysFileModel::withTrashed()->find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        $this->deletePhysicalFile($file);

        return (bool) $file->forceDelete();
    }

    /**
     * 批量彻底删除文件
     */
    public function batchForceDelete(array $fileIds): int
    {
        $files = SysFileModel::withTrashed()->whereIn('id', $fileIds)->get();
        $count = 0;

        foreach ($files as $file) {
            $this->deletePhysicalFile($file);
            $file->forceDelete();
            $count++;
        }

        return $count;
    }

    /**
     * 下载文件
     */
    public function download(int $fileId, ?string $filename = null): StreamedResponse
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        $downloadName = $filename ?? $file->file_name;
        return $this->disk($file->disk)->download($file->file_path, $downloadName);
    }

    /**
     * 获取文件流式响应（用于在线预览等场景）
     */
    public function stream(int $fileId): StreamedResponse
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        $mimeType = $this->getMimeType($file->file_ext);

        return response()->stream(
            function () use ($file) {
                $stream = $this->disk($file->disk)->readStream($file->file_path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $file->file_name . '"',
            ]
        );
    }

    /**
     * 获取文件内容
     */
    public function getContent(int $fileId): string
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        return $this->disk($file->disk)->get($file->file_path);
    }

    /**
     * 获取文件访问URL
     */
    public function getUrl(int $fileId): ?string
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            return null;
        }

        if ($file->disk === 'oss') {
            return oss_public_url($file->file_path);
        }

        return $this->disk($file->disk)->url($file->file_path);
    }

    /**
     * 根据路径获取访问URL
     */
    public function getUrlByPath(string $path, ?string $disk = null): string
    {
        $disk = $disk ?? config('filesystems.default', 'local');
        if ($disk === 'oss') {
            return oss_public_url($path);
        }

        return $this->disk($disk)->url($path);
    }

    /**
     * 获取文件元数据信息
     */
    public function getMetadata(int $fileId): ?array
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            return null;
        }

        $disk = $this->disk($file->disk);

        return [
            'id' => $file->id,
            'name' => $file->file_name,
            'path' => $file->file_path,
            'disk' => $file->disk,
            'size' => $file->file_size,
            'extension' => $file->file_ext,
            'mime_type' => $this->getMimeType($file->file_ext),
            'last_modified' => $disk->exists($file->file_path)
                ? date('Y-m-d H:i:s', $disk->lastModified($file->file_path))
                : null,
            'url' => $this->getUrl($file->id),
            'group_id' => $file->group_id,
            'uploader_id' => $file->uploader_id,
            'created_at' => $file->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $file->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 复制文件
     */
    public function copy(int $fileId, int $targetGroupId = 0): array
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        $newPath = $this->generateStoragePath($file->file_ext);

        $this->disk($file->disk)->copy($file->file_path, $newPath);

        // 创建新的文件记录
        $newFile = SysFileModel::create([
            'disk' => $file->disk,
            'group_id' => $targetGroupId,
            'channel' => $file->channel,
            'file_name' => $file->file_name,
            'file_type' => $file->file_type,
            'file_path' => $newPath,
            'file_size' => $file->file_size,
            'file_ext' => $file->file_ext,
            'uploader_id' => Auth::id(),
        ]);

        return $newFile->toArray();
    }

    /**
     * 批量复制文件
     */
    public function batchCopy(array $fileIds, int $targetGroupId = 0): bool
    {
        $files = SysFileModel::whereIn('id', $fileIds)->get();
        $fileArray = [];

        foreach ($files as $file) {

            $newPath = $this->generateStoragePath($file->file_ext);

            $this->disk($file->disk)->copy($file->file_path, $newPath);

            $fileArray[] =[
                'disk' => $file->disk,
                'group_id' => $targetGroupId,
                'channel' => $file->channel,
                'file_name' => $file->file_name,
                'file_type' => $file->file_type,
                'file_path' => $newPath,
                'file_size' => $file->file_size,
                'file_ext' => $file->file_ext,
                'uploader_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        return SysFileModel::insert($fileArray);
    }

    /**
     * 移动文件
     */
    public function move(int $fileId, int $groupId): bool
    {
        $file = SysFileModel::find($fileId);
        return $file->update(['group_id' => $groupId]);
    }

    /**
     * 批量移动
     */
    public function batchMove(array $fileIds, int $groupId): int
    {
        return SysFileModel::whereIn('id', $fileIds)->update(['group_id' => $groupId]);
    }

    /**
     * 重命名文件
     */
    public function rename(int $fileId, string $newName): bool
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            throw new HttpResponseException(['success' => false, 'msg' => __('system.file.not_found')]);
        }

        return $file->update(['file_name' => $newName]);
    }

    /**
     * 检查文件是否存在
     */
    public function exists(int $fileId): bool
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            return false;
        }

        if ($file->disk === 'oss') {
            return OssUploadService::isConfigured();
        }

        return $this->disk($file->disk)->exists($file->file_path);
    }

    /**
     * 获取文件大小（字节）
     */
    public function getSize(int $fileId): ?int
    {
        $file = SysFileModel::find($fileId);
        if (!$file) {
            return null;
        }

        return $this->disk($file->disk)->size($file->file_path);
    }

    /**
     * 获取MIME类型
     */
    protected function getMimeType(string $extension): string
    {
        $mimeTypes = [
            // 图片
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            // 音频
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            // 视频
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            // 文档
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // 压缩包
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            '7z' => 'application/x-7z-compressed',
            // 其他
            'json' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * 清空回收站文件
     */
    public function cleanTrashed(): int
    {
        $expiredFiles = SysFileModel::onlyTrashed()->get();
        $count = 0;
        foreach ($expiredFiles as $file) {
            $this->deletePhysicalFile($file);
            $file->forceDelete();
            $count++;
        }
        return $count;
    }

    protected function deletePhysicalFile(SysFileModel $file): void
    {
        if ($file->disk === 'oss') {
            OssUploadService::delete($file->file_path);

            return;
        }

        $this->disk($file->disk)->delete($file->file_path);
    }

    /**
     * 检查 S3 配置是否有效
     * @return bool
     */
    public static function isS3Configured(): bool
    {
        $storageConfig = config('filesystems.disks.s3');

        if (empty($storageConfig)) {
            return false;
        }

        return !empty($storageConfig['key'])
            && !empty($storageConfig['secret'])
            && !empty($storageConfig['bucket'])
            && !empty($storageConfig['region']);
    }
}
