/** 上传接口返回的文件信息 */
export type UploadedFilePayload = {
  disk?: string | null;
  file_url?: string | null;
  preview_url?: string | null;
  file_path?: string | null;
};

/** 接口标准响应壳 */
export type UploadApiResponse = {
  success?: boolean;
  msg?: string;
  data?: UploadedFilePayload | null;
};

const LOCAL_HOST_RE = /^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/i;
const BLOB_OR_DATA_RE = /^(blob:|data:)/i;

function currentOrigin(): string {
  if (typeof window === 'undefined' || !window.location?.origin) {
    return '';
  }
  return window.location.origin.replace(/\/$/, '');
}

function isBlobOrDataUrl(url: string): boolean {
  return BLOB_OR_DATA_RE.test(url);
}

/**
 * 把上传结果转成当前站点可预览/可入库的地址。
 * 生产环境 APP_URL 常仍是 127.0.0.1，接口返回的 file_url 无法在浏览器里加载。
 */
export function resolveUploadedFileUrl(data?: UploadedFilePayload | string | null): string {
  if (!data) return '';

  if (typeof data === 'string') {
    const url = rewriteLocalUrl(data.trim());
    return isBlobOrDataUrl(url) ? '' : url;
  }

  const origin = currentOrigin();
  let url = String(data.file_url || data.preview_url || '').trim();
  // 预览地址可能带 x-oss-process，入库应优先 file_url；若只有 process 版也可先用
  url = rewriteLocalUrl(url, origin);
  if (url && !isBlobOrDataUrl(url)) {
    return url;
  }

  const filePath = String(data.file_path || '').trim();
  if (!filePath) {
    return '';
  }

  // 已是完整 URL 的 path（兼容历史数据）
  if (/^https?:\/\//i.test(filePath)) {
    return rewriteLocalUrl(filePath, origin);
  }

  // OSS 文件不能回退成本地 /storage 路径
  if (String(data.disk || '').toLowerCase() === 'oss') {
    return '';
  }

  const fromPath = `/storage/${filePath.replace(/^\/+/, '')}`;
  return origin ? `${origin}${fromPath}` : fromPath;
}

/**
 * 从 antd Upload 的 file.response / file.url 提取可入库的图片地址。
 * 优先接口 data.file_url，忽略 blob/data 本地预览地址。
 */
export function extractUploadFileUrl(
  response?: UploadApiResponse | UploadedFilePayload | string | null,
  fallbackUrl?: string | null,
): string {
  if (typeof response === 'string') {
    return resolveUploadedFileUrl(response);
  }

  if (response && typeof response === 'object') {
    // 标准壳：{ success, data: { file_url, ... } }
    if ('data' in response && response.data && typeof response.data === 'object') {
      if ('success' in response && response.success === false) {
        return '';
      }
      const fromData = resolveUploadedFileUrl(response.data as UploadedFilePayload);
      if (fromData) return fromData;
    }

    // 直接就是文件信息
    const fromPayload = resolveUploadedFileUrl(response as UploadedFilePayload);
    if (fromPayload) return fromPayload;
  }

  return resolveUploadedFileUrl(fallbackUrl);
}

function rewriteLocalUrl(url: string, origin = currentOrigin()): string {
  if (!url) return '';
  if (LOCAL_HOST_RE.test(url) && origin) {
    return url.replace(LOCAL_HOST_RE, origin);
  }
  return url;
}
