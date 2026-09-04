import XinTable from '@/components/XinTable';
import {Badge, Button, DatePicker, Image, Modal, QRCode, Space, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import {Create, Update} from '@/api/common/table';
import createAxios from '@/utils/request';
import dayjs, {type Dayjs} from 'dayjs';
import {useRef, useState} from 'react';
import UrlImageUpload from '@/components/XinFormField/UrlImageUpload';
import RichTextEditor from '@/components/XinFormField/RichTextEditor';

const {Title, Text} = Typography;

interface IActivity {
  id: number;
  title: string;
  type: string;
  theme?: string;
  location: string;
  contact?: string;
  contact_phone?: string;
  points: number;
  recruit_count: number;
  signup_count: number;
  status: number;
  cover_url?: string;
  description?: string;
  start_time: number;
  end_time?: number;
  signup_start_time?: number;
  signup_end_time?: number;
  checkin_code_in?: string;
  checkin_code_out?: string;
}

const typeOptions = ['邻里守望', '矛盾调解', '助残帮困', '心理辅导', '突发事件处置'].map((v) => ({label: v, value: v}));

const statusOptions = [
  {label: '草稿', value: 0},
  {label: '发布', value: 1},
  {label: '结束', value: 2},
];

const toDayjs = (value: unknown): Dayjs | null => {
  if (!value) return null;
  if (dayjs.isDayjs(value)) return value;
  if (typeof value === 'number') return dayjs.unix(value);
  return dayjs(value as string);
};

const toUnix = (value: unknown): number | null => {
  if (!value) return null;
  if (typeof value === 'number') return value;
  return dayjs(value as Dayjs).unix();
};

const dateTimeFieldRender = (field: keyof IActivity) => (form: { getFieldValue: (name: string) => unknown; setFieldValue: (name: string, value: Dayjs | null) => void }) => (
  <DatePicker
    showTime
    style={{width: '100%'}}
    value={toDayjs(form.getFieldValue(field as string))}
    onChange={(value) => form.setFieldValue(field as string, value)}
  />
);

export default function ActivityPage() {
  const tableRef = useRef<XinTableInstance<IActivity>>(null);
  const [qrOpen, setQrOpen] = useState(false);
  const [current, setCurrent] = useState<IActivity | null>(null);

  const openQr = (record: IActivity) => {
    setCurrent(record);
    setQrOpen(true);
  };

  const refreshCodes = async () => {
    if (!current?.id) return;
    const res = await createAxios.post(`/volunteer/activity/${current.id}/refreshCodes`);
    const data = (res as any)?.data?.data;
    if (data) {
      setCurrent({...current, ...data});
      window.$message?.success('签到码已刷新');
      void tableRef.current?.reload();
    }
  };

  const columns: XinTableColumn<IActivity>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true, hideInSearch: true},
    {
      title: '活动类型',
      dataIndex: 'type',
      valueType: 'select',
      colProps: {span: 24},
      rules: [{required: true, message: '请选择活动类型'}],
      fieldProps: {options: typeOptions, placeholder: '请选择类型', allowClear: true},
    },
    {
      title: '活动标题',
      dataIndex: 'title',
      valueType: 'text',
      rules: [{required: true, message: '请输入活动标题'}],
      colProps: {span: 24},
      hideInSearch: true,
    },
    {title: '活动主题', dataIndex: 'theme', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {title: '活动地点', dataIndex: 'location', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {title: '联系人', dataIndex: 'contact', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {title: '联系电话', dataIndex: 'contact_phone', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {
      title: '活动积分',
      dataIndex: 'points',
      valueType: 'digit',
      colProps: {span: 12},
      hideInSearch: true,
      tooltip: '完成活动基准积分，按时长结算时按服务比例折算',
    },
    {title: '招募人数', dataIndex: 'recruit_count', valueType: 'digit', colProps: {span: 12}, hideInSearch: true},
    {
      title: '封面图',
      dataIndex: 'cover_url',
      width: 90,
      hideInSearch: true,
      colProps: {span: 24},
      fieldRender: () => <UrlImageUpload />,
      render: (_: unknown, record: IActivity) =>
        record.cover_url
          ? <Image src={record.cover_url} width={48} height={48} style={{objectFit: 'cover', borderRadius: 6}} />
          : '-',
    },
    {
      title: '活动介绍',
      dataIndex: 'description',
      hideInSearch: true,
      hideInTable: true,
      colProps: {span: 24},
      fieldRender: () => <RichTextEditor height={320} />,
    },
    {
      title: '报名开始',
      dataIndex: 'signup_start_time',
      colProps: {span: 12},
      hideInSearch: true,
      fieldRender: dateTimeFieldRender('signup_start_time'),
      render: (v: number) => (v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '报名截止',
      dataIndex: 'signup_end_time',
      colProps: {span: 12},
      hideInSearch: true,
      fieldRender: dateTimeFieldRender('signup_end_time'),
      render: (v: number) => (v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '开始时间',
      dataIndex: 'start_time',
      colProps: {span: 12},
      hideInSearch: true,
      fieldRender: dateTimeFieldRender('start_time'),
      render: (v: number) => (v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '结束时间',
      dataIndex: 'end_time',
      colProps: {span: 12},
      hideInSearch: true,
      fieldRender: dateTimeFieldRender('end_time'),
      render: (v: number) => (v ? dayjs.unix(v).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '状态',
      dataIndex: 'status',
      valueType: 'select',
      colProps: {span: 24},
      fieldProps: {options: statusOptions, placeholder: '请选择状态', allowClear: true},
      render: (v: number) =>
        v === 1 ? <Badge status="success" text="发布" />
          : v === 2 ? <Badge status="default" text="结束" />
            : <Badge status="warning" text="草稿" />,
    },
    {title: '已报名', dataIndex: 'signup_count', hideInForm: true, hideInSearch: true},
  ];

  return (
    <>
      <div className="mb-5">
        <Title level={3}>活动管理</Title>
        <Text type="secondary">发布活动并生成入场/离场签到二维码</Text>
      </div>
      <XinTable<IActivity>
        tableRef={tableRef}
        api="/volunteer/activity"
        columns={columns}
        rowKey="id"
        accessName="volunteer.activity"
        formLayoutType="DrawerForm"
        drawerProps={{width: 860}}
        formProps={{grid: true, colProps: {span: 12}, layout: 'vertical'}}
        createInitialValues={{status: 1}}
        operateProps={{width: 200}}
        operateRender={(record, dom) => [
          <Button key="qr" size="small" type="link" onClick={() => openQr(record)}>签到码</Button>,
          dom.edit,
          dom.del,
        ]}
        handleFinish={async (values, mode, _form, defaultValue) => {
          const payload = {
            ...values,
            signup_start_time: toUnix(values.signup_start_time),
            signup_end_time: toUnix(values.signup_end_time),
            start_time: toUnix(values.start_time),
            end_time: toUnix(values.end_time),
          };
          if (mode === 'create') {
            await Create('/volunteer/activity', payload);
            window.$message?.success('创建成功，已自动生成签到码');
          } else {
            await Update(`/volunteer/activity/${defaultValue?.id}`, payload);
            window.$message?.success('更新成功');
          }
          return true;
        }}
      />

      <Modal
        title={current ? `签到二维码 - ${current.title}` : '签到二维码'}
        open={qrOpen}
        onCancel={() => setQrOpen(false)}
        width={640}
        footer={[
          <Button key="refresh" onClick={refreshCodes}>刷新签到码</Button>,
          <Button key="close" type="primary" onClick={() => setQrOpen(false)}>关闭</Button>,
        ]}
      >
        {current && (
          <Space size={40} style={{width: '100%', justifyContent: 'center', padding: '16px 0'}}>
            <div style={{textAlign: 'center'}}>
              <QRCode value={current.checkin_code_in || '-'} size={180} />
              <div style={{marginTop: 12, fontWeight: 600}}>入场签到码</div>
              <Text type="secondary" copyable>{current.checkin_code_in || '-'}</Text>
            </div>
            <div style={{textAlign: 'center'}}>
              <QRCode value={current.checkin_code_out || '-'} size={180} />
              <div style={{marginTop: 12, fontWeight: 600}}>离场签到码</div>
              <Text type="secondary" copyable>{current.checkin_code_out || '-'}</Text>
            </div>
          </Space>
        )}
        <Text type="secondary">现场打印或投屏二维码供志愿者扫码；刷新后旧码立即失效。</Text>
      </Modal>
    </>
  );
}
