import XinTable from '@/components/XinTable';
import {Badge, Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import dayjs from 'dayjs';

const {Title, Text} = Typography;

interface ISignup {
  id: number;
  activity_id: number;
  volunteer_id: number;
  status: number;
  status_text: string;
  service_hours: number;
  earned_points: number;
  checkin_start_time: number;
  checkin_end_time: number;
  created_at: string;
  activity?: {id: number; title: string; type: string; start_time: number};
  volunteer?: {id: number; name: string; phone: string};
}

export default function SignupPage() {
  const columns: XinTableColumn<ISignup>[] = [
    {title: 'ID', dataIndex: 'id', width: 70},
    {title: '活动', dataIndex: ['activity', 'title']},
    {title: '活动类型', dataIndex: ['activity', 'type'], hideInSearch: true},
    {title: '志愿者', dataIndex: ['volunteer', 'name']},
    {title: '电话', dataIndex: ['volunteer', 'phone']},
    {title: '活动ID', dataIndex: 'activity_id', hideInTable: true},
    {title: '志愿者ID', dataIndex: 'volunteer_id', hideInTable: true},
    {title: '状态', dataIndex: 'status', valueType: 'select',
      fieldProps: {options: [{label: '已报名', value: 1}, {label: '进行中', value: 2}, {label: '已完成', value: 3}]},
      render: (v: number) => v === 3 ? <Badge status="success" text="已完成"/> : v === 2 ? <Badge status="processing" text="进行中"/> : <Badge status="default" text="已报名"/>},
    {title: '服务时长', dataIndex: 'service_hours', hideInSearch: true},
    {title: '获得积分', dataIndex: 'earned_points', hideInSearch: true},
    {title: '签到时间', dataIndex: 'checkin_start_time', hideInSearch: true,
      render: (v: number) => v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'},
    {title: '签退时间', dataIndex: 'checkin_end_time', hideInSearch: true,
      render: (v: number) => v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'},
    {title: '报名时间', dataIndex: 'created_at', hideInSearch: true,
      render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
  ];

  return (
    <>
      <div className="mb-5"><Title level={3}>报名查询</Title><Text type="secondary">查询志愿活动报名记录及签到情况</Text></div>
      <XinTable<ISignup>
        api="/volunteer/signup"
        columns={columns}
        rowKey="id"
        accessName="volunteer.signup"
        addShow={false}
        editShow={false}
        deleteShow={false}
      />
    </>
  );
}
