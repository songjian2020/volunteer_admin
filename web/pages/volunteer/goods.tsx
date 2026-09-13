import XinTable from '@/components/XinTable';
import {Badge, Button, Image, Typography} from 'antd';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {List} from '@/api/common/table';
import {useEffect, useRef, useState} from 'react';
import UrlImageUpload from '@/components/XinFormField/UrlImageUpload';
import RichTextEditor from '@/components/XinFormField/RichTextEditor';

const {Title, Text} = Typography;

interface IGoods {
  id: number;
  name: string;
  category: string;
  points: number;
  stock: number;
  status: number;
  merchant_id: number;
  merchant?: {id: number; name: string} | null;
  image_url: string;
  description?: string;
  sort: number;
}

const categoryOptions = ['日用百货', '粮油食品', '清洁用品', '家居用品', '便民服务', '其他'].map((v) => ({
  label: v,
  value: v,
}));

const statusOptions = [
  {label: '下架', value: 0},
  {label: '上架', value: 1},
  {label: '待审核', value: 2},
];

async function getNextSort(): Promise<number> {
  try {
    const {data} = await List<IGoods>('/volunteer/goods', {page: 1, pageSize: 1});
    const maxSort = Number(data?.data?.data?.[0]?.sort ?? 0);
    return (Number.isFinite(maxSort) ? maxSort : 0) + 1;
  } catch {
    return 1;
  }
}

export default function GoodsPage() {
  const tableRef = useRef<XinTableInstance<IGoods>>(null);
  const [merchantOptions, setMerchantOptions] = useState<{label: string; value: number}[]>([
    {label: '社区自营', value: 0},
  ]);

  useEffect(() => {
    List<any>('/volunteer/merchant', {page: 1, pageSize: 500, audit_status: 1, status: 1})
      .then((res) => {
        const rows = res?.data?.data?.data || [];
        const options = [
          {label: '社区自营', value: 0},
          ...rows.map((m: any) => ({label: m.name, value: m.id})),
        ];
        setMerchantOptions(options);
      })
      .catch(() => {});
  }, []);

  const columns: XinTableColumn<IGoods>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true, hideInSearch: true},
    {
      title: '商品名称',
      dataIndex: 'name',
      valueType: 'text',
      rules: [{required: true, message: '请输入商品名称'}],
      colProps: {span: 24},
      hideInSearch: true,
    },
    {
      title: '商品分类',
      dataIndex: 'category',
      valueType: 'select',
      colProps: {span: 24},
      rules: [{required: true, message: '请选择商品分类'}],
      fieldProps: {options: categoryOptions, placeholder: '请选择商品分类', allowClear: true},
      render: (v: string) => v || '-',
    },
    {
      title: '所属商户',
      dataIndex: 'merchant_id',
      valueType: 'select',
      colProps: {span: 24},
      rules: [{required: true, message: '请选择所属商户'}],
      fieldProps: {
        options: merchantOptions,
        placeholder: '请选择所属商户',
        showSearch: true,
        optionFilterProp: 'label',
        allowClear: false,
      },
      render: (_: unknown, record: IGoods) => {
        if (record.merchant?.name) return record.merchant.name;
        if (!record.merchant_id) return '社区自营';
        return merchantOptions.find((o) => o.value === record.merchant_id)?.label || `商户#${record.merchant_id}`;
      },
    },
    {
      title: '所需积分',
      dataIndex: 'points',
      valueType: 'digit',
      rules: [{required: true, message: '请输入积分'}],
      colProps: {span: 12},
      hideInSearch: true,
    },
    {
      title: '库存',
      dataIndex: 'stock',
      valueType: 'digit',
      rules: [{required: true, message: '请输入库存'}],
      colProps: {span: 12},
      hideInSearch: true,
    },
    {
      title: '商品图片',
      dataIndex: 'image_url',
      width: 90,
      hideInSearch: true,
      colProps: {span: 24},
      fieldRender: () => <UrlImageUpload />,
      render: (_: unknown, record: IGoods) =>
        record.image_url
          ? <Image src={record.image_url} width={48} height={48} style={{objectFit: 'cover', borderRadius: 6}} />
          : '-',
    },
    {
      title: '描述',
      dataIndex: 'description',
      hideInSearch: true,
      hideInTable: true,
      colProps: {span: 24},
      fieldRender: () => <RichTextEditor height={280} />,
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
        v === 1 ? <Badge status="success" text="上架" />
          : v === 0 ? <Badge status="default" text="下架" />
            : <Badge status="processing" text="待审核" />,
    },
  ];

  return (
    <>
      <div className="mb-5">
        <Title level={3}>商品管理</Title>
        <Text type="secondary">管理积分商城兑换商品</Text>
      </div>
      <XinTable<IGoods>
        tableRef={tableRef}
        api="/volunteer/goods"
        columns={columns}
        rowKey="id"
        accessName="volunteer.goods"
        formLayoutType="DrawerForm"
        drawerProps={{width: 860}}
        formProps={{grid: true, colProps: {span: 12}, layout: 'vertical'}}
        createInitialValues={async () => ({
          status: 1,
          merchant_id: 0,
          category: '日用百货',
          sort: await getNextSort(),
        })}
        operateProps={{width: 180}}
        operateRender={(record, dom) => [
          record.status === 2 && (
            <Button
              key="audit"
              size="small"
              type="link"
              onClick={() =>
                createAxios({
                  url: `/volunteer/goods/${record.id}/audit`,
                  method: 'post',
                  data: {status: 1},
                }).then(() => {
                  window.$message?.success('已审核通过');
                  void tableRef.current?.reload();
                })
              }
            >
              审核通过
            </Button>
          ),
          dom.edit,
          dom.del,
        ].filter(Boolean)}
      />
    </>
  );
}
