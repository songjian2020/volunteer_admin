import XinTable from '@/components/XinTable';
import {Badge, Button, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {useRef} from 'react';

const {Title, Text} = Typography;

interface IVolunteer {
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
    createAxios.put(`/volunteer/volunteer/${record.id}/audit`, {audit_status: status}).then(() => {
      window.$message?.success('操作成功');
      void tableRef.current?.reload();
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
        operateWidth={140}
        requestParams={(params) => ({...params, audit_status: 0})}
        operateRender={(record) => [
          record.audit_status !== 1 && <Button key="pass" size="small" type="link" onClick={() => handleAudit(record, 1)}>通过</Button>,
          record.audit_status !== 2 && <Button key="reject" size="small" type="link" danger onClick={() => handleAudit(record, 2)}>拒绝</Button>,
        ].filter(Boolean)}
      />
    </>
  );
}
