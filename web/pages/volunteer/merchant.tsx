import XinTable from '@/components/XinTable';
import {Badge, Button, Form, Image, InputNumber, Space, Typography, message} from 'antd';
import {EnvironmentOutlined} from '@ant-design/icons';
import type {XinTableColumn} from '@/components/XinTable/typings';
import {Create, Update} from '@/api/common/table';
import createAxios from '@/utils/request';
import UrlImageUpload, {QualificationsField} from '@/components/XinFormField/UrlImageUpload';
import {regionOptions} from '@/components/XinFormField/MerchantLocation';

const {Title, Text} = Typography;

interface IMerchant {
  id: number;
  name: string;
  account: string;
  password?: string;
  contact: string;
  phone: string;
  province?: string;
  city?: string;
  district?: string;
  region?: string[];
  address?: string;
  longitude?: number;
  latitude?: number;
  logo?: string;
  qualifications?: Array<{type: string; url: string; number?: string}>;
  business_type: string;
  description?: string;
  full_address?: string;
  audit_status: number;
  status: number;
}

const businessTypeOptions = [
  '超市便利店', '餐饮美食', '生活服务', '美容美发', '教育培训', '医疗健康', '其他',
].map((v) => ({label: v, value: v}));

const statusOptions = [
  {label: '禁用', value: 0},
  {label: '启用', value: 1},
];

function buildPayload(values: any) {
  const region = values.region || [];
  const [province, city, district] = region;
  const payload = {
    ...values,
    province: province || values.province || '',
    city: city || values.city || '',
    district: district || values.district || '',
    qualifications: (values.qualifications || []).filter((q: any) => q?.url),
  };
  delete payload.region;
  delete payload._lnglat;
  delete payload.full_address;
  delete payload.audit_status_text;
  return payload;
}

export default function MerchantPage() {
  const columns: XinTableColumn<IMerchant>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true, hideInSearch: true},
    {
      title: '经营类型',
      dataIndex: 'business_type',
      valueType: 'select',
      colProps: {span: 24},
      fieldProps: {options: businessTypeOptions, placeholder: '请选择经营类型', allowClear: true},
    },
    {
      title: '商户名称',
      dataIndex: 'name',
      valueType: 'text',
      rules: [{required: true, message: '请输入商户名称'}],
      colProps: {span: 24},
      hideInSearch: true,
    },
    {
      title: '商户Logo',
      dataIndex: 'logo',
      width: 80,
      hideInSearch: true,
      colProps: {span: 24},
      rules: [{required: true, message: '请上传商户Logo'}],
      fieldRender: () => <UrlImageUpload />,
      render: (_: unknown, record: IMerchant) =>
        record.logo
          ? <Image src={record.logo} width={40} height={40} style={{objectFit: 'cover', borderRadius: 6}} />
          : '-',
    },
    {
      title: '登录账号',
      dataIndex: 'account',
      valueType: 'text',
      rules: [{required: true, message: '请输入登录账号'}],
      colProps: {span: 12},
      hideInSearch: true,
    },
    {
      title: '密码',
      dataIndex: 'password',
      valueType: 'password',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
      tooltip: '编辑时留空表示不修改密码',
    },
    {title: '联系人', dataIndex: 'contact', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {title: '电话', dataIndex: 'phone', valueType: 'text', colProps: {span: 12}, hideInSearch: true},
    {
      title: '省市区',
      dataIndex: 'region',
      valueType: 'cascader',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 24},
      rules: [{required: true, message: '请选择省市区'}],
      fieldProps: {
        options: regionOptions,
        placeholder: '请选择省 / 市 / 区',
        showSearch: true,
        changeOnSelect: false,
      },
    },
    {
      title: '详细地址',
      dataIndex: 'address',
      valueType: 'text',
      hideInSearch: true,
      colProps: {span: 24},
      rules: [{required: true, message: '请输入详细地址'}],
      fieldProps: {placeholder: '街道、门牌号等'},
      render: (_: unknown, record: IMerchant) => record.full_address || record.address || '-',
    },
    {
      title: '经纬度',
      dataIndex: '_lnglat',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 24},
      fieldRender: (form) => (
        <Space wrap>
          <Form.Item name="longitude" noStyle rules={[{required: true, message: '请填写经度'}]}>
            <InputNumber placeholder="经度" style={{width: 160}} precision={7} />
          </Form.Item>
          <Form.Item name="latitude" noStyle rules={[{required: true, message: '请填写纬度'}]}>
            <InputNumber placeholder="纬度" style={{width: 160}} precision={7} />
          </Form.Item>
          <Button
            type="primary"
            ghost
            icon={<EnvironmentOutlined />}
            onClick={async () => {
              const region = form.getFieldValue('region') || [];
              const address = form.getFieldValue('address') || '';
              const full = `${region[0] || ''}${region[1] || ''}${region[2] || ''}${address}`;
              if (!region[0] || !region[1] || !region[2] || !address) {
                message.warning('请先选择省市区并填写详细地址');
                return;
              }
              try {
                const res = await createAxios.post('/volunteer/merchant/geocode', {address: full});
                const data = (res as any)?.data?.data;
                if (data?.longitude != null && data?.latitude != null) {
                  form.setFieldsValue({longitude: data.longitude, latitude: data.latitude});
                  message.success('经纬度已获取');
                }
              } catch {
                // handled
              }
            }}
          >
            获取经纬度
          </Button>
        </Space>
      ),
    },
    {
      title: '相关资质',
      dataIndex: 'qualifications',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 24},
      tooltip: '如营业执照、食品经营许可证等',
      fieldRender: () => <QualificationsField />,
    },
    {
      title: '简介',
      dataIndex: 'description',
      valueType: 'textarea',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 24},
      fieldProps: {rows: 3},
    },
    {
      title: '状态',
      dataIndex: 'status',
      valueType: 'select',
      colProps: {span: 24},
      fieldProps: {options: statusOptions, placeholder: '请选择状态', allowClear: true},
      render: (v: number) =>
        v === 1 ? <Badge status="success" text="启用" /> : <Badge status="default" text="禁用" />,
    },
  ];

  return (
    <>
      <div className="mb-5">
        <Title level={3}>商户管理</Title>
        <Text type="secondary">管理已审核通过的入驻商户</Text>
      </div>
      <XinTable<IMerchant>
        api="/volunteer/merchant"
        columns={columns}
        rowKey="id"
        accessName="volunteer.merchant"
        formLayoutType="DrawerForm"
        drawerProps={{width: 860}}
        formProps={{grid: true, colProps: {span: 12}, layout: 'vertical'}}
        createInitialValues={{status: 1, audit_status: 1, qualifications: []}}
        requestParams={(params) => ({...params, audit_status: 1})}
        operateRender={(_record, dom) => [dom.edit, dom.del]}
        handleFinish={async (values, mode, _form, defaultValue) => {
          const payload = buildPayload(values);
          if (mode === 'create') {
            await Create('/volunteer/merchant', payload);
            window.$message?.success('创建成功');
          } else {
            await Update(`/volunteer/merchant/${defaultValue?.id}`, payload);
            window.$message?.success('更新成功');
          }
          return true;
        }}
      />
    </>
  );
}
