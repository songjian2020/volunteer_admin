import React, { useEffect, useMemo, useState } from 'react';
import { Upload, message, Button, Space, Select, Input, Card } from 'antd';
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons';
import type { UploadFile, UploadProps } from 'antd';

export interface UrlImageUploadProps {
  value?: string | string[];
  onChange?: (value: string | string[]) => void;
  action?: string;
  disabled?: boolean;
  maxSize?: number;
  /** single 返回 string；multiple 返回 string[] */
  mode?: 'single' | 'multiple';
  maxCount?: number;
}

/** 以 URL 字符串为值的图片上传 */
const UrlImageUpload: React.FC<UrlImageUploadProps> = ({
  value,
  onChange,
  action = '/system/file/list/upload',
  disabled = false,
  maxSize = 5,
  mode = 'single',
  maxCount,
}) => {
  const uploadMaxCount = maxCount || (mode === 'single' ? 1 : 9);
  const [fileList, setFileList] = useState<UploadFile[]>([]);

  useEffect(() => {
    if (!value || (Array.isArray(value) && value.length === 0)) {
      setFileList([]);
      return;
    }
    const urls = Array.isArray(value) ? value : [value];
    setFileList(
      urls.filter(Boolean).map((url, index) => ({
        uid: `${index}-${url}`,
        name: `image-${index}`,
        status: 'done' as const,
        url,
      })),
    );
  }, [value]);

  const uploadButton = useMemo(
    () => (
      <button style={{ border: 0, background: 'none' }} type="button">
        <PlusOutlined />
        <div style={{ marginTop: 8 }}>上传</div>
      </button>
    ),
    [],
  );

  const emitChange = (list: UploadFile[]) => {
    const urls = list
      .filter((f) => f.status === 'done')
      .map((f) => f.url || f.response?.data?.file_url || f.response?.data?.preview_url || '')
      .filter(Boolean);
    if (mode === 'single') {
      onChange?.(urls[0] || '');
    } else {
      onChange?.(urls);
    }
  };

  const handleChange: UploadProps['onChange'] = ({ fileList: nextList, file }) => {
    setFileList(nextList);
    if (file.status === 'done' || file.status === 'removed' || nextList.every((f) => f.status === 'done' || f.status === 'error')) {
      const done = nextList.filter((f) => f.status === 'done').map((f) => {
        if (f.url) return f;
        const url = f.response?.data?.file_url || f.response?.data?.preview_url || '';
        return { ...f, url };
      });
      setFileList(done.concat(nextList.filter((f) => f.status !== 'done')));
      emitChange(done);
    }
    if (file.status === 'error') {
      message.error(file.response?.msg || '上传失败');
    }
  };

  return (
    <Upload
      listType="picture-card"
      name="file"
      fileList={fileList}
      disabled={disabled}
      maxCount={uploadMaxCount}
      multiple={mode === 'multiple'}
      accept="image/*"
      data={{ group_id: 0 }}
      headers={{ Authorization: `Bearer ${localStorage.getItem('token') || ''}` }}
      action={`${import.meta.env.VITE_BASE_URL}${action}`}
      beforeUpload={(file) => {
        if (!file.type.startsWith('image/')) {
          message.error('只能上传图片');
          return Upload.LIST_IGNORE;
        }
        if (file.size / 1024 / 1024 >= maxSize) {
          message.error(`图片不能超过 ${maxSize}MB`);
          return Upload.LIST_IGNORE;
        }
        return true;
      }}
      onChange={handleChange}
      onRemove={(file) => {
        const next = fileList.filter((item) => item.uid !== file.uid);
        setFileList(next);
        emitChange(next);
        return true;
      }}
    >
      {fileList.length >= uploadMaxCount ? null : uploadButton}
    </Upload>
  );
};

export default UrlImageUpload;

export interface QualificationItem {
  type: string;
  url: string;
  number?: string;
}

const QUAL_TYPES = ['营业执照', '食品经营许可证', '卫生许可证', '其他资质'];

/** 商户资质多条编辑 */
export const QualificationsField: React.FC<{
  value?: QualificationItem[];
  onChange?: (value: QualificationItem[]) => void;
}> = ({ value = [], onChange }) => {
  const list = Array.isArray(value) ? value : [];

  const update = (next: QualificationItem[]) => onChange?.(next);

  const add = () => {
    update([...list, { type: '营业执照', url: '', number: '' }]);
  };

  const remove = (index: number) => {
    update(list.filter((_, i) => i !== index));
  };

  const patch = (index: number, patchData: Partial<QualificationItem>) => {
    update(list.map((item, i) => (i === index ? { ...item, ...patchData } : item)));
  };

  return (
    <div style={{ width: '100%' }}>
      <Space direction="vertical" style={{ width: '100%' }} size={12}>
        {list.map((item, index) => (
          <Card
            key={index}
            size="small"
            title={`资质 ${index + 1}`}
            extra={<Button type="link" danger icon={<DeleteOutlined />} onClick={() => remove(index)}>删除</Button>}
          >
            <Space direction="vertical" style={{ width: '100%' }} size={8}>
              <Select
                style={{ width: '100%' }}
                options={QUAL_TYPES.map((t) => ({ label: t, value: t }))}
                value={item.type}
                onChange={(type) => patch(index, { type })}
              />
              <Input
                placeholder="证件编号（选填）"
                value={item.number}
                onChange={(e) => patch(index, { number: e.target.value })}
              />
              <UrlImageUpload
                value={item.url}
                onChange={(url) => patch(index, { url: String(url || '') })}
              />
            </Space>
          </Card>
        ))}
      </Space>
      <Button type="dashed" block icon={<PlusOutlined />} onClick={add} style={{ marginTop: 12 }}>
        添加资质
      </Button>
    </div>
  );
};
