import XinTable from '@/components/XinTable';
import {Badge, Button, Modal, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {useRef} from 'react';

const {Title, Text} = Typography;

interface IMerchant {
  id: number;
  name: string;
  account: string;
  contact: string;
  phone: string;
  business_type: string;
  audit_status: number;
}

export default function MerchantPendingPage() {
  const tableRef = useRef<XinTableInstance<IMerchant>>(null);

  const columns: XinTableColumn<IMerchant>[] = [
    {title: 'ID', dataIndex: 'id', width: 70},
    {title: '商户名称', dataIndex: 'name'},
    {title: '登录账号', dataIndex: 'account'},
    {title: '联系人', dataIndex: 'contact'},
    {title: '电话', dataIndex: 'phone'},
    {title: '经营类型', dataIndex: 'business_type'},
    {title: '状态', dataIndex: 'audit_status', render: (v: number) => v === 2
      ? <Badge status="error" text="已拒绝"/> : <Badge status="processing" text="待审核"/>},
  ];

  const audit = (record: IMerchant, status: number) => {
    const pass = status === 1;
    Modal.confirm({
      title: pass ? '确认通过该商户？' : '确认拒绝该商户？',
      content: `${record.name || '该商户'}（${record.account || record.phone || '-'}）`,
      okText: pass ? '通过' : '拒绝',
      okButtonProps: pass ? undefined : {danger: true},
      cancelText: '取消',
      onOk: () => createAxios({
        url: `/volunteer/merchant/${record.id}/audit`,
        method: 'post',
        data: {audit_status: status},
      }).then(() => {
        window.$message?.success(pass ? '已通过' : '已拒绝');
        void tableRef.current?.reload();
      }),
    });
  };

  return (
    <>
      <div className="mb-5"><Title level={3}>商户审批</Title><Text type="secondary">审核待审批的入驻商户</Text></div>
      <XinTable<IMerchant>
        tableRef={tableRef}
        api="/volunteer/merchant"
        columns={columns}
        rowKey="id"
        accessName="volunteer.merchant"
        addShow={false}
        editShow={false}
        deleteShow={false}
        operateWidth={140}
        requestParams={(params) => ({...params, audit_status: 0})}
        operateRender={(record) => [
          record.audit_status !== 1 && <Button key="pass" size="small" type="link" onClick={() => audit(record, 1)}>通过</Button>,
          record.audit_status !== 2 && <Button key="reject" size="small" type="link" danger onClick={() => audit(record, 2)}>拒绝</Button>,
        ].filter(Boolean)}
      />
    </>
  );
}
