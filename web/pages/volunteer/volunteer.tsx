import XinTable from '@/components/XinTable';
import {Badge, Button, Input, InputNumber, Modal, Space, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {useRef} from 'react';

const {Title, Text} = Typography;

interface IVolunteer {
  id: number;
  name: string;
  phone: string;
  audit_status: number;
  total_points: number;
  total_hours: number;
  activity_count: number;
  star_level: number;
}

export default function VolunteerPage() {
  const tableRef = useRef<XinTableInstance<IVolunteer>>(null);

  const columns: XinTableColumn<IVolunteer>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true},
    {title: '姓名', dataIndex: 'name'},
    {title: '电话', dataIndex: 'phone'},
    {title: '累计积分', dataIndex: 'total_points'},
    {title: '服务时长', dataIndex: 'total_hours'},
    {title: '活动数', dataIndex: 'activity_count'},
    {title: '星级', dataIndex: 'star_level'},
    {title: '状态', dataIndex: 'audit_status', hideInSearch: true,
      render: () => <Badge status="success" text="已通过"/>},
  ];

  const handleAdjustPoints = (record: IVolunteer) => {
    let points = 0;
    let reason = '管理员调整积分';
    Modal.confirm({
      title: '调整积分',
      content: (
        <Space direction="vertical" style={{width: '100%'}}>
          <InputNumber placeholder="积分(正数增加，负数扣减)" style={{width: '100%'}} onChange={v => { points = Number(v || 0); }} />
          <Input placeholder="原因" defaultValue={reason} onChange={e => { reason = e.target.value; }} />
        </Space>
      ),
      onOk: () => {
        if (!points) {
          window.$message?.warning('积分不能为0');
          return Promise.reject();
        }
        return createAxios({
          url: `/volunteer/volunteer/${record.id}/points`,
          method: 'post',
          data: {points, reason},
        }).then(() => {
          window.$message?.success('操作成功');
          void tableRef.current?.reload();
        });
      },
    });
  };

  return (
    <>
      <div className="mb-5"><Title level={3}>志愿者管理</Title><Text type="secondary">管理已审核通过的志愿者信息与积分</Text></div>
      <XinTable<IVolunteer>
        tableRef={tableRef}
        api="/volunteer/volunteer"
        columns={columns}
        rowKey="id"
        accessName="volunteer.volunteer"
        addShow={false}
        editShow={false}
        operateWidth={120}
        requestParams={(params) => ({...params, audit_status: 1})}
        operateRender={(record, dom) => [
          <Button key="points" size="small" type="link" onClick={() => handleAdjustPoints(record)}>调积分</Button>,
          dom.del,
        ]}
      />
    </>
  );
}
