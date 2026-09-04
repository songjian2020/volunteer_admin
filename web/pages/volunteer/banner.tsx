import XinTable from '@/components/XinTable';
import {Badge, Image, Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import UrlImageUpload from '@/components/XinFormField/UrlImageUpload';
import {List} from '@/api/common/table';

const {Title, Text} = Typography;

interface IBanner {
  id: number;
  type: number;
  type_text?: string;
  title: string;
  sub: string;
  image_url: string;
  sort: number;
  status: number;
}

const typeOptions = [
  {label: '幻灯片', value: 1},
  {label: '广告', value: 2},
];

const statusOptions = [
  {label: '禁用', value: 0},
  {label: '启用', value: 1},
];

async function getNextSort(): Promise<number> {
  try {
    const {data} = await List<IBanner>('/volunteer/banner', {page: 1, pageSize: 1});
    const maxSort = Number(data?.data?.data?.[0]?.sort ?? 0);
    return (Number.isFinite(maxSort) ? maxSort : 0) + 1;
  } catch {
    return 1;
  }
}

export default function BannerPage() {
  const columns: XinTableColumn<IBanner>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true, hideInSearch: true},
    {
      title: '类型',
      dataIndex: 'type',
      valueType: 'select',
      colProps: {span: 24},
      rules: [{required: true, message: '请选择类型'}],
      fieldProps: {options: typeOptions, placeholder: '请选择类型', allowClear: true},
      render: (v: number) => typeOptions.find((o) => o.value === v)?.label || '-',
    },
    {
      title: '标题',
      dataIndex: 'title',
      valueType: 'text',
      colProps: {span: 24},
      hideInSearch: true,
    },
    {
      title: '副标题',
      dataIndex: 'sub',
      valueType: 'text',
      colProps: {span: 24},
      hideInSearch: true,
    },
    {
      title: '图片',
      dataIndex: 'image_url',
      width: 100,
      hideInSearch: true,
      colProps: {span: 24},
      rules: [{required: true, message: '请上传图片'}],
      fieldRender: () => <UrlImageUpload />,
      render: (_: unknown, record: IBanner) =>
        record.image_url
          ? <Image src={record.image_url} width={72} height={40} style={{objectFit: 'cover', borderRadius: 6}} />
          : '-',
    },
    {
      title: '排序',
      dataIndex: 'sort',
      valueType: 'digit',
      colProps: {span: 24},
      hideInSearch: true,
      fieldProps: {min: 0, style: {width: '100%'}},
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
        <Title level={3}>轮播管理</Title>
        <Text type="secondary">管理小程序首页幻灯片与广告位</Text>
      </div>
      <XinTable<IBanner>
        api="/volunteer/banner"
        columns={columns}
        rowKey="id"
        accessName="volunteer.banner"
        formLayoutType="DrawerForm"
        drawerProps={{width: 720}}
        formProps={{grid: true, colProps: {span: 24}, layout: 'vertical'}}
        createInitialValues={async () => ({
          type: 1,
          status: 1,
          sort: await getNextSort(),
        })}
      />
    </>
  );
}
