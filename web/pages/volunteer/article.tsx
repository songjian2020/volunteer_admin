import XinTable from '@/components/XinTable';
import {Badge, Image, Typography} from 'antd';
import type {XinTableColumn} from '@/components/XinTable/typings';
import UrlImageUpload from '@/components/XinFormField/UrlImageUpload';
import RichTextEditor from '@/components/XinFormField/RichTextEditor';
import {List} from '@/api/common/table';

const {Title, Text} = Typography;

interface IArticle {
  id: number;
  title: string;
  type: number;
  cover_url?: string;
  content?: string;
  status: number;
  sort: number;
}

const typeOptions = [
  {label: '积分制简介', value: 1},
  {label: '兑换说明', value: 2},
  {label: '公告', value: 3},
];

const statusOptions = [
  {label: '禁用', value: 0},
  {label: '启用', value: 1},
];

async function getNextSort(): Promise<number> {
  try {
    // 接口默认按 sort 降序，取第一条即为当前最大排序
    const {data} = await List<IArticle>('/volunteer/article', {page: 1, pageSize: 1});
    const maxSort = Number(data?.data?.data?.[0]?.sort ?? 0);
    return (Number.isFinite(maxSort) ? maxSort : 0) + 1;
  } catch {
    return 1;
  }
}

export default function ArticlePage() {
  const columns: XinTableColumn<IArticle>[] = [
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
      rules: [{required: true, message: '请输入标题'}],
      colProps: {span: 24},
      hideInSearch: true,
    },
    {
      title: '缩略图',
      dataIndex: 'cover_url',
      width: 90,
      hideInSearch: true,
      colProps: {span: 24},
      fieldRender: () => <UrlImageUpload />,
      render: (_: unknown, record: IArticle) =>
        record.cover_url
          ? <Image src={record.cover_url} width={48} height={48} style={{objectFit: 'cover', borderRadius: 6}} />
          : '-',
    },
    {
      title: '内容',
      dataIndex: 'content',
      hideInSearch: true,
      hideInTable: true,
      colProps: {span: 24},
      fieldRender: () => <RichTextEditor height={380} />,
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
        v === 1
          ? <Badge status="success" text="启用" />
          : <Badge status="default" text="禁用" />,
    },
  ];

  return (
    <>
      <div className="mb-5">
        <Title level={3}>文章公告</Title>
        <Text type="secondary">管理积分制简介、兑换说明和公告</Text>
      </div>
      <XinTable<IArticle>
        api="/volunteer/article"
        columns={columns}
        rowKey="id"
        accessName="volunteer.article"
        formLayoutType="DrawerForm"
        drawerProps={{width: 860}}
        formProps={{grid: true, colProps: {span: 24}, layout: 'vertical'}}
        createInitialValues={async () => ({
          status: 1,
          sort: await getNextSort(),
        })}
      />
    </>
  );
}
