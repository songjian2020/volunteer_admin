import XinTable from '@/components/XinTable';
import {Avatar, Badge, Button, Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import dayjs from 'dayjs';
import {useRef} from 'react';
import type {XinTableInstance} from '@/components/XinTable/typings';

const {Title, Text} = Typography;

interface IWxUser {
  id: number;
  openid: string;
  nickname: string;
  avatar: string;
  api_token: string;
  token_expire_at: string | null;
  created_at: string;
  volunteer?: {id: number; name: string; phone: string; audit_status: number};
}

export default function WxUserPage() {
  const tableRef = useRef<XinTableInstance<IWxUser>>(null);

  const columns: XinTableColumn<IWxUser>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true},
    {title: '头像', dataIndex: 'avatar', hideInForm: true, hideInSearch: true, width: 70,
      render: (v: string) => v ? <Avatar src={v} /> : <Avatar>{'用'}</Avatar>},
    {title: '昵称', dataIndex: 'nickname', valueType: 'text'},
    {title: 'OpenID', dataIndex: 'openid', hideInForm: true},
    {title: '绑定志愿者', dataIndex: ['volunteer', 'name'], hideInForm: true, hideInSearch: true},
    {title: '志愿者电话', dataIndex: ['volunteer', 'phone'], hideInForm: true, hideInSearch: true},
    {title: '授权状态', dataIndex: 'api_token', hideInForm: true, hideInSearch: true,
      render: (_: string, record) => record.api_token && record.token_expire_at && dayjs(record.token_expire_at).isAfter(dayjs())
        ? <Badge status="success" text="已授权"/> : <Badge status="default" text="未授权"/>},
    {title: '过期时间', dataIndex: 'token_expire_at', hideInForm: true, hideInSearch: true,
      render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
    {title: '注册时间', dataIndex: 'created_at', hideInForm: true, hideInSearch: true,
      render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
  ];

  const reload = () => tableRef.current?.reload();

  const handleRevoke = (record: IWxUser) => {
    createAxios.post(`/volunteer/user/${record.id}/revoke`).then(() => {
      window.$message?.success('已撤销授权');
      void reload();
    });
  };

  return (
    <>
      <div className="mb-5"><Title level={3}>用户管理</Title><Text type="secondary">管理小程序授权登录用户</Text></div>
      <XinTable<IWxUser>
        tableRef={tableRef}
        api="/volunteer/user"
        columns={columns}
        rowKey="id"
        accessName="volunteer.user"
        addShow={false}
        operateWidth={160}
        operateRender={(record, dom) => [
          record.api_token && <Button key="revoke" size="small" type="link" onClick={() => handleRevoke(record)}>撤销授权</Button>,
          dom.edit,
          dom.del,
        ].filter(Boolean)}
      />
    </>
  );
}
