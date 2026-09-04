import React, { useEffect, useState } from 'react';
import { Editor, Toolbar } from '@wangeditor/editor-for-react';
import type { IDomEditor, IEditorConfig, IToolbarConfig } from '@wangeditor/editor';
import '@wangeditor/editor/dist/css/style.css';
import { message } from 'antd';

export interface RichTextEditorProps {
  value?: string;
  onChange?: (html: string) => void;
  placeholder?: string;
  height?: number;
  disabled?: boolean;
  /** 上传接口（相对 VITE_BASE_URL） */
  uploadAction?: string;
}

/**
 * 多功能富文本编辑器（基于 wangEditor）
 */
const RichTextEditor: React.FC<RichTextEditorProps> = ({
  value = '',
  onChange,
  placeholder = '请输入内容...',
  height = 360,
  disabled = false,
  uploadAction = '/system/file/list/upload',
}) => {
  const [editor, setEditor] = useState<IDomEditor | null>(null);

  useEffect(() => {
    return () => {
      if (editor == null) return;
      editor.destroy();
      setEditor(null);
    };
  }, [editor]);

  useEffect(() => {
    if (!editor) return;
    if (disabled) editor.disable();
    else editor.enable();
  }, [disabled, editor]);

  const toolbarConfig: Partial<IToolbarConfig> = {
    excludeKeys: ['fullScreen'],
  };

  const editorConfig: Partial<IEditorConfig> = {
    placeholder,
    MENU_CONF: {
      uploadImage: {
        async customUpload(file: File, insertFn: (url: string, alt?: string, href?: string) => void) {
          try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('group_id', '0');
            const res = await fetch(`${import.meta.env.VITE_BASE_URL}${uploadAction}`, {
              method: 'POST',
              headers: {
                Authorization: `Bearer ${localStorage.getItem('token') || ''}`,
              },
              body: formData,
            });
            const json = await res.json();
            if (json?.success === false) {
              message.error(json?.msg || '图片上传失败');
              return;
            }
            const data = json?.data || json;
            const url = data?.file_url || data?.preview_url || '';
            if (!url) {
              message.error('图片上传失败：未返回地址');
              return;
            }
            insertFn(url, file.name, url);
          } catch {
            message.error('图片上传出错');
          }
        },
      },
    },
  };

  return (
    <div style={{ border: '1px solid #d9d9d9', borderRadius: 8, overflow: 'hidden' }}>
      <Toolbar
        editor={editor}
        defaultConfig={toolbarConfig}
        mode="default"
        style={{ borderBottom: '1px solid #d9d9d9' }}
      />
      <Editor
        defaultConfig={editorConfig}
        value={value || ''}
        onCreated={setEditor}
        onChange={(ed) => {
          const html = ed.getHtml();
          onChange?.(html === '<p><br></p>' ? '' : html);
        }}
        mode="default"
        style={{ height, overflowY: 'hidden' }}
      />
    </div>
  );
};

export default RichTextEditor;
