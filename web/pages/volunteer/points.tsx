import XinTable from '@/components/XinTable';
import {Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import dayjs from 'dayjs';

const {Title, Text} = Typography;

interface IPointsLog {
  id: number;
  volunteer_id: number;
  type: string;
  type_text?: string;
  reason: string;
  points: number;
  created_at: string;
  volunteer?: {name: string; phone: string};
}

const typeOptions = [
  {label: '活动服务', value: 'activity'},
  {label: '积分兑换', value: 'exchange'},
  {label: '管理员调整', value: 'manual'},
];

export default function PointsPage() {
  const columns: XinTableColumn<IPointsLog>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInSearch: true},
    {title: '志愿者', dataIndex: 'volunteer_name', hideInTable: true, hideInForm: true},
    {title: '电话', dataIndex: 'volunteer_phone', hideInTable: true, hideInForm: true},
    {title: '志愿者', dataIndex: ['volunteer', 'name'], hideInSearch: true},
    {title: '电话', dataIndex: ['volunteer', 'phone'], hideInSearch: true},
    {
      title: '类型',
      dataIndex: 'type',
      valueType: 'select',
      fieldProps: {options: typeOptions, placeholder: '请选择类型', allowClear: true},
      render: (_: string, record) => record.type_text || typeOptions.find((o) => o.value === record.type)?.label || record.type || '-',
    },
    {title: '原因', dataIndex: 'reason', hideInSearch: true},
    {title: '积分', dataIndex: 'points', hideInSearch: true},
    {
      title: '时间',
      dataIndex: 'created_at',
      valueType: 'dateRange',
      fieldProps: {style: {width: '100%'}},
      normalize: (value: unknown) => {
        if (!Array.isArray(value)) return value;
        return value.map((v) => (v && dayjs(v).isValid() ? dayjs(v).format('YYYY-MM-DD') : v));
      },
      render: (v: string) => (v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'),
    },
  ];

  return (
    <>
      <div className="mb-5"><Title level={3}>积分记录</Title><Text type="secondary">查看志愿者积分变动明细</Text></div>
      <XinTable<IPointsLog>
        api="/volunteer/points"
        columns={columns}
        rowKey="id"
        accessName="volunteer.points"
        addShow={false}
        editShow={false}
        deleteShow={false}
        keywordSearchShow={false}
      />
    </>
  );
}
