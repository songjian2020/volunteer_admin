import XinTable from '@/components/XinTable';
import {Badge, Button, Modal, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {useRef, useState} from 'react';
import VolunteerDetailModal, {type VolunteerDetailRecord} from '../components/VolunteerDetailModal';

const {Title, Text} = Typography;

interface IVolunteer extends VolunteerDetailRecord {
  id: number;
  name: string;
  phone: string;
  gender: string;
  age: number;
  address: string;
  audit_status: number;
  created_at: string;
}

export default function VolunteerPendingPage() {
  const tableRef = useRef<XinTableInstance<IVolunteer>>(null);
  const [detail, setDetail] = useState<IVolunteer | null>(null);

  const columns: XinTableColumn<IVolunteer>[] = [
    {title: 'ID', dataIndex: 'id', width: 70},
    {title: '姓名', dataIndex: 'name'},
    {title: '电话', dataIndex: 'phone'},
    {title: '性别', dataIndex: 'gender', render: (v: string) => v === '2' ? '女' : '男'},
    {title: '年龄', dataIndex: 'age', width: 70},
    {title: '地址', dataIndex: 'address'},
    {title: '状态', dataIndex: 'audit_status', render: (v: number) => v === 2
      ? <Badge status="error" text="已拒绝"/> : <Badge status="processing" text="待审核"/>},
  ];

  const handleAudit = (record: IVolunteer, status: number) => {
    const pass = status === 1;
    Modal.confirm({
      title: pass ? '确认通过该志愿者？' : '确认拒绝该志愿者？',
      content: `${record.name || '该申请人'}（${record.phone || '-'}）`,
      okText: pass ? '通过' : '拒绝',
      okButtonProps: pass ? undefined : {danger: true},
      cancelText: '取消',
      onOk: () => createAxios({
        url: `/volunteer/volunteer/${record.id}/audit`,
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
      <div className="mb-5"><Title level={3}>志愿者审批</Title><Text type="secondary">审核待审批的志愿者申请</Text></div>
      <XinTable<IVolunteer>
        tableRef={tableRef}
        api="/volunteer/volunteer"
        columns={columns}
        rowKey="id"
        accessName="volunteer.volunteer"
        addShow={false}
        editShow={false}
        deleteShow={false}
        operateWidth={200}
        requestParams={(params) => ({...params, audit_status: 0})}
        operateRender={(record) => [
          <Button key="detail" size="small" type="link" onClick={() => setDetail(record)}>查看详情</Button>,
          record.audit_status !== 1 && <Button key="pass" size="small" type="link" onClick={() => handleAudit(record, 1)}>通过</Button>,
          record.audit_status !== 2 && <Button key="reject" size="small" type="link" danger onClick={() => handleAudit(record, 2)}>拒绝</Button>,
        ].filter(Boolean)}
      />
      <VolunteerDetailModal open={!!detail} record={detail} onClose={() => setDetail(null)} />
    </>
  );
}
