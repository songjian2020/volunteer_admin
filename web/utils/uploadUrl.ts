/** 上传接口返回的文件信息 */
export type UploadedFilePayload = {
  file_url?: string | null;
  preview_url?: string | null;
  file_path?: string | null;
};

const LOCAL_HOST_RE = /^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/i;

function currentOrigin(): string {
  if (typeof window === 'undefined' || !window.location?.origin) {
    return '';
  }
  return window.location.origin.replace(/\/$/, '');
}

/**
 * 把上传结果转成当前站点可预览的地址。
 * 生产环境 APP_URL 常仍是 127.0.0.1，接口返回的 file_url 无法在浏览器里加载。
 */
export function resolveUploadedFileUrl(data?: UploadedFilePayload | string | null): string {
  if (!data) return '';

  if (typeof data === 'string') {
    return rewriteLocalUrl(data.trim());
  }

  const origin = currentOrigin();
  const fromPath = data.file_path
    ? `/storage/${String(data.file_path).replace(/^\/+/, '')}`
    : '';

  let url = String(data.file_url || data.preview_url || '').trim();
  url = rewriteLocalUrl(url, origin);

  if (!url && fromPath) {
    url = origin ? `${origin}${fromPath}` : fromPath;
  }

  return url;
}

function rewriteLocalUrl(url: string, origin = currentOrigin()): string {
  if (!url) return '';
  if (LOCAL_HOST_RE.test(url) && origin) {
    return url.replace(LOCAL_HOST_RE, origin);
  }
  return url;
}
