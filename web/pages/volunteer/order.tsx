import XinTable from '@/components/XinTable';
import {Badge, Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import dayjs from 'dayjs';

const {Title, Text} = Typography;

interface IOrder {
  id: number;
  order_no: string;
  goods_name: string;
  exchange_code: string;
  points: number;
  num: number;
  status: number;
  created_at: string;
}

export default function OrderPage() {
  const columns: XinTableColumn<IOrder>[] = [
    {title: 'ID', dataIndex: 'id', width: 70},
    {title: '订单号', dataIndex: 'order_no'},
    {title: '商品', dataIndex: 'goods_name'},
    {title: '兑换码', dataIndex: 'exchange_code'},
    {title: '数量', dataIndex: 'num', width: 80},
    {title: '积分', dataIndex: 'points', width: 80},
    {title: '状态', dataIndex: 'status', render: (v: number) => v === 2 ? <Badge status="success" text="已核销"/> : v === 3 ? <Badge status="default" text="已取消"/> : <Badge status="processing" text="待核销"/>},
    {title: '创建时间', dataIndex: 'created_at', render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
  ];

  return (
    <>
      <div className="mb-5"><Title level={3}>兑换订单</Title><Text type="secondary">查看居民积分兑换订单及核销状态</Text></div>
      <XinTable<IOrder> api="/volunteer/order" columns={columns} rowKey="id" accessName="volunteer.order" addShow={false} editShow={false} deleteShow={false} />
    </>
  );
}
