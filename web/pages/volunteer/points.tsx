import XinTable from '@/components/XinTable';
import {Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import dayjs from 'dayjs';

const {Title, Text} = Typography;

interface IPointsLog {
  id: number;
  volunteer_id: number;
  type: string;
  reason: string;
  points: number;
  created_at: string;
  volunteer?: {name: string; phone: string};
}

export default function PointsPage() {
  const columns: XinTableColumn<IPointsLog>[] = [
    {title: 'ID', dataIndex: 'id', width: 70},
    {title: '志愿者', dataIndex: ['volunteer', 'name']},
    {title: '电话', dataIndex: ['volunteer', 'phone']},
    {title: '类型', dataIndex: 'type'},
    {title: '原因', dataIndex: 'reason'},
    {title: '积分', dataIndex: 'points'},
    {title: '时间', dataIndex: 'created_at', render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
  ];

  return (
    <>
      <div className="mb-5"><Title level={3}>积分记录</Title><Text type="secondary">查看志愿者积分变动明细</Text></div>
      <XinTable<IPointsLog> api="/volunteer/points" columns={columns} rowKey="id" accessName="volunteer.points" addShow={false} editShow={false} deleteShow={false} />
    </>
  );
}
