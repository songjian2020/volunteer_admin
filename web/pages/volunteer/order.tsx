import XinTable from '@/components/XinTable';
import {Badge, Button, Descriptions, Image, Modal, QRCode, Space, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import dayjs from 'dayjs';
import {useRef, useState} from 'react';

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
  verify_time?: number;
  volunteer?: {id: number; name: string; phone: string};
  goods?: {id: number; image_url?: string};
}

const statusOptions = [
  {label: '待核销', value: 1},
  {label: '已核销', value: 2},
  {label: '已取消', value: 3},
];

const statusBadge = (v: number) =>
  v === 2 ? <Badge status="success" text="已核销"/>
    : v === 3 ? <Badge status="default" text="已取消"/>
      : <Badge status="processing" text="待核销"/>;

/** 与小程序扫码格式一致 */
function exchangeQrValue(code?: string) {
  const c = String(code || '').trim().toUpperCase();
  return c ? `WSS-EX:${c}` : '';
}

export default function OrderPage() {
  const tableRef = useRef<XinTableInstance<IOrder>>(null);
  const [detail, setDetail] = useState<IOrder | null>(null);
  const [verifyTarget, setVerifyTarget] = useState<IOrder | null>(null);
  const [verifying, setVerifying] = useState(false);

  const columns: XinTableColumn<IOrder>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInSearch: true},
    {title: '订单号', dataIndex: 'order_no'},
    {title: '志愿者', dataIndex: ['volunteer', 'name'], hideInSearch: true},
    {title: '电话', dataIndex: ['volunteer', 'phone'], hideInSearch: true},
    {title: '商品名称', dataIndex: 'goods_name'},
    {title: '兑换码', dataIndex: 'exchange_code'},
    {title: '数量', dataIndex: 'num', width: 80, hideInSearch: true},
    {title: '积分', dataIndex: 'points', width: 80, hideInSearch: true},
    {
      title: '状态',
      dataIndex: 'status',
      valueType: 'select',
      fieldProps: {options: statusOptions, placeholder: '请选择状态', allowClear: true},
      render: (v: number) => statusBadge(v),
    },
    {title: '创建时间', dataIndex: 'created_at', hideInSearch: true, render: (v: string) => v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-'},
  ];

  const openDetail = (record: IOrder) => setDetail(record);

  const openVerify = (record: IOrder) => setVerifyTarget(record);

  const closeVerify = () => {
    if (verifying) return;
    setVerifyTarget(null);
  };

  const confirmVerify = async () => {
    if (!verifyTarget) return;
    setVerifying(true);
    try {
      await createAxios({
        url: `/volunteer/order/${verifyTarget.id}/verify`,
        method: 'post',
      });
      window.$message?.success('核销成功');
      setVerifyTarget(null);
      void tableRef.current?.reload();
    } finally {
      setVerifying(false);
    }
  };

  return (
    <>
      <div className="mb-5"><Title level={3}>兑换订单</Title><Text type="secondary">查看居民积分兑换订单及核销状态</Text></div>
      <XinTable<IOrder>
        tableRef={tableRef}
        api="/volunteer/order"
        columns={columns}
        rowKey="id"
        accessName="volunteer.order"
        addShow={false}
        editShow={false}
        deleteShow={false}
        keywordSearchShow={false}
        operateWidth={180}
        operateRender={(record) => [
          <Button key="view" size="small" type="link" onClick={() => openDetail(record)}>查看订单</Button>,
          record.status === 1 && <Button key="verify" size="small" type="link" onClick={() => openVerify(record)}>核销订单</Button>,
        ].filter(Boolean)}
      />

      <Modal
        title="订单明细"
        open={!!detail}
        onCancel={() => setDetail(null)}
        footer={<Button type="primary" onClick={() => setDetail(null)}>关闭</Button>}
        width={640}
      >
        {detail && (
          <Descriptions column={2} bordered size="small">
            <Descriptions.Item label="订单号" span={2}>{detail.order_no}</Descriptions.Item>
            <Descriptions.Item label="商品" span={2}>{detail.goods_name}</Descriptions.Item>
            <Descriptions.Item label="图片" span={2}>
              {detail.goods?.image_url ? <Image src={detail.goods.image_url} width={64} /> : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="志愿者">{detail.volunteer?.name || '-'}</Descriptions.Item>
            <Descriptions.Item label="电话">{detail.volunteer?.phone || '-'}</Descriptions.Item>
            <Descriptions.Item label="数量">{detail.num}</Descriptions.Item>
            <Descriptions.Item label="积分">{detail.points}</Descriptions.Item>
            <Descriptions.Item label="兑换码" span={2}>{detail.exchange_code}</Descriptions.Item>
            <Descriptions.Item label="状态">{statusBadge(detail.status)}</Descriptions.Item>
            <Descriptions.Item label="下单时间">{detail.created_at ? dayjs(detail.created_at).format('YYYY-MM-DD HH:mm') : '-'}</Descriptions.Item>
            <Descriptions.Item label="核销时间" span={2}>
              {detail.verify_time ? dayjs.unix(Number(detail.verify_time)).format('YYYY-MM-DD HH:mm') : '-'}
            </Descriptions.Item>
          </Descriptions>
        )}
      </Modal>

      <Modal
        title="核销订单"
        open={!!verifyTarget}
        onCancel={closeVerify}
        width={480}
        destroyOnHidden
        footer={[
          <Button key="cancel" onClick={closeVerify} disabled={verifying}>取消</Button>,
          <Button key="ok" type="primary" loading={verifying} onClick={confirmVerify}>确定核销</Button>,
        ]}
      >
        {verifyTarget && (
          <Space direction="vertical" size={16} style={{width: '100%', alignItems: 'center', padding: '8px 0'}}>
            <div style={{textAlign: 'center'}}>
              <QRCode
                value={exchangeQrValue(verifyTarget.exchange_code) || '-'}
                size={200}
                style={{margin: '0 auto'}}
              />
              <div style={{marginTop: 12, color: '#999', fontSize: 13}}>核销二维码（供商户扫码）</div>
            </div>
            <Descriptions column={1} bordered size="small" style={{width: '100%'}}>
              <Descriptions.Item label="商品">{verifyTarget.goods_name}</Descriptions.Item>
              <Descriptions.Item label="志愿者">{verifyTarget.volunteer?.name || '-'}</Descriptions.Item>
              <Descriptions.Item label="数量 / 积分">
                {verifyTarget.num} 件 / {verifyTarget.points} 积分
              </Descriptions.Item>
              <Descriptions.Item label="核销码">
                <Text copyable strong style={{letterSpacing: 1, fontFamily: 'monospace', color: '#1677ff'}}>
                  {verifyTarget.exchange_code}
                </Text>
              </Descriptions.Item>
              <Descriptions.Item label="订单号">{verifyTarget.order_no}</Descriptions.Item>
            </Descriptions>
            <Text type="secondary" style={{alignSelf: 'flex-start'}}>
              核对信息无误后点击「确定核销」，核销后不可撤销。
            </Text>
          </Space>
        )}
      </Modal>
    </>
  );
}
