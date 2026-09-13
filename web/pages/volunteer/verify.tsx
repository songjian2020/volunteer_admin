import XinTable from '@/components/XinTable';
import {Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import dayjs from 'dayjs';

const {Title, Text} = Typography;

interface IVerifyRecord {
  id: number;
  order_no: string;
  goods_name: string;
  exchange_code: string;
  num: number;
  points: number;
  verify_time?: number;
  volunteer?: {id: number; name: string; phone: string};
  verify_merchant?: {id: number; name: string; account?: string; phone?: string};
}

export default function MerchantVerifyPage() {
  const columns: XinTableColumn<IVerifyRecord>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInSearch: true},
    {
      title: '核销商户',
      dataIndex: 'merchant_name',
      hideInTable: true,
      hideInForm: true,
    },
    {
      title: '核销商户',
      dataIndex: ['verify_merchant', 'name'],
      hideInSearch: true,
      render: (_: unknown, record) => record.verify_merchant?.name || '-',
    },
    {title: '订单号', dataIndex: 'order_no'},
    {title: '商品名称', dataIndex: 'goods_name'},
    {title: '兑换码', dataIndex: 'exchange_code'},
    {
      title: '志愿者',
      dataIndex: 'volunteer_name',
      hideInTable: true,
      hideInForm: true,
    },
    {
      title: '电话',
      dataIndex: 'volunteer_phone',
      hideInTable: true,
      hideInForm: true,
    },
    {
      title: '志愿者',
      dataIndex: ['volunteer', 'name'],
      hideInSearch: true,
      render: (_: unknown, record) => record.volunteer?.name || '-',
    },
    {
      title: '电话',
      dataIndex: ['volunteer', 'phone'],
      hideInSearch: true,
      width: 120,
      render: (_: unknown, record) => record.volunteer?.phone || '-',
    },
    {title: '数量', dataIndex: 'num', width: 80, hideInSearch: true},
    {
      title: '积分',
      dataIndex: 'points',
      width: 90,
      hideInSearch: true,
      render: (v: number) => <Text strong style={{color: '#1677ff'}}>{v ?? 0}</Text>,
    },
    {
      title: '核销时间',
      dataIndex: 'verify_time',
      valueType: 'dateRange',
      fieldProps: {style: {width: '100%'}},
      normalize: (value: unknown) => {
        if (!Array.isArray(value)) return value;
        return value.map((v) => (v && dayjs(v).isValid() ? dayjs(v).format('YYYY-MM-DD') : v));
      },
      render: (v: number) => (v ? dayjs.unix(Number(v)).format('YYYY-MM-DD HH:mm') : '-'),
    },
  ];

  return (
    <>
      <div className="mb-5">
        <Title level={3}>核销查询</Title>
        <Text type="secondary">查看各商户的商品核销记录</Text>
      </div>
      <XinTable<IVerifyRecord>
        api="/volunteer/verify"
        columns={columns}
        rowKey="id"
        accessName="volunteer.verify"
        addShow={false}
        editShow={false}
        deleteShow={false}
        keywordSearchShow={false}
      />
    </>
  );
}
