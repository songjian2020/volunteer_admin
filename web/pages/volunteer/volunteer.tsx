import XinTable from '@/components/XinTable';
import {Badge, Button, Input, InputNumber, Modal, Space, Tooltip, Typography} from 'antd';
import {EyeOutlined, PayCircleOutlined} from '@ant-design/icons';
import type {XinTableColumn, XinTableInstance} from '@/components/XinTable/typings';
import createAxios from '@/utils/request';
import {useRef, useState} from 'react';
import VolunteerDetailModal, {type VolunteerDetailRecord} from './components/VolunteerDetailModal';

const {Title, Text} = Typography;

interface IVolunteer extends VolunteerDetailRecord {
  id: number;
  name: string;
  phone: string;
  audit_status: number;
  total_points: number;
  total_hours: number;
  activity_count: number;
  star_level: number;
}

const genderOptions = [
  {label: '男', value: '1'},
  {label: '女', value: '2'},
];

export default function VolunteerPage() {
  const tableRef = useRef<XinTableInstance<IVolunteer>>(null);
  const [detail, setDetail] = useState<IVolunteer | null>(null);

  const columns: XinTableColumn<IVolunteer>[] = [
    {title: 'ID', dataIndex: 'id', width: 70, hideInForm: true},
    {
      title: '姓名',
      dataIndex: 'name',
      valueType: 'text',
      rules: [{required: true, message: '请输入姓名'}],
      colProps: {span: 12},
    },
    {
      title: '电话',
      dataIndex: 'phone',
      valueType: 'text',
      rules: [{required: true, message: '请输入电话'}],
      colProps: {span: 12},
    },
    {
      title: '性别',
      dataIndex: 'gender',
      valueType: 'select',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
      fieldProps: {options: genderOptions, placeholder: '请选择性别', allowClear: true},
    },
    {
      title: '年龄',
      dataIndex: 'age',
      valueType: 'digit',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
      fieldProps: {min: 0, max: 120, style: {width: '100%'}},
    },
    {
      title: '文化程度',
      dataIndex: 'education',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {
      title: '政治面貌',
      dataIndex: 'political_status',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {
      title: '身份证号',
      dataIndex: 'id_card',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {
      title: '家庭住址',
      dataIndex: 'address',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {
      title: '特长',
      dataIndex: 'specialty',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 24},
    },
    {
      title: '紧急联系人',
      dataIndex: 'emergency_contact',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {
      title: '紧急联系电话',
      dataIndex: 'emergency_phone',
      valueType: 'text',
      hideInTable: true,
      hideInSearch: true,
      colProps: {span: 12},
    },
    {title: '累计积分', dataIndex: 'total_points', hideInForm: true},
    {title: '服务时长', dataIndex: 'total_hours', hideInForm: true},
    {title: '活动数', dataIndex: 'activity_count', hideInForm: true},
    {title: '星级', dataIndex: 'star_level', hideInForm: true},
    {
      title: '状态',
      dataIndex: 'audit_status',
      hideInSearch: true,
      hideInForm: true,
      render: () => <Badge status="success" text="已通过"/>,
    },
  ];

  const handleAdjustPoints = (record: IVolunteer) => {
    let points = 0;
    let reason = '管理员调整积分';
    Modal.confirm({
      title: '调整积分',
      content: (
        <Space direction="vertical" style={{width: '100%'}}>
          <InputNumber placeholder="积分(正数增加，负数扣减)" style={{width: '100%'}} onChange={v => { points = Number(v || 0); }} />
          <Input placeholder="原因" defaultValue={reason} onChange={e => { reason = e.target.value; }} />
        </Space>
      ),
      onOk: () => {
        if (!points) {
          window.$message?.warning('积分不能为0');
          return Promise.reject();
        }
        return createAxios({
          url: `/volunteer/volunteer/${record.id}/points`,
          method: 'post',
          data: {points, reason},
        }).then(() => {
          window.$message?.success('操作成功');
          void tableRef.current?.reload();
        });
      },
    });
  };

  return (
    <>
      <div className="mb-5"><Title level={3}>志愿者管理</Title><Text type="secondary">管理已审核通过的志愿者信息与积分</Text></div>
      <XinTable<IVolunteer>
        tableRef={tableRef}
        api="/volunteer/volunteer"
        columns={columns}
        rowKey="id"
        accessName="volunteer.volunteer"
        addShow
        editShow={false}
        formLayoutType="DrawerForm"
        drawerProps={{width: 720}}
        formProps={{grid: true, colProps: {span: 12}, layout: 'vertical'}}
        createInitialValues={{gender: '1'}}
        operateWidth={180}
        requestParams={(params) => ({...params, audit_status: 1})}
        operateRender={(record, dom) => [
          <Tooltip title="查看详情" key="detail">
            <Button type="primary" size="small" icon={<EyeOutlined />} onClick={() => setDetail(record)} />
          </Tooltip>,
          <Tooltip title="调积分" key="points">
            <Button type="primary" size="small" icon={<PayCircleOutlined />} onClick={() => handleAdjustPoints(record)} />
          </Tooltip>,
          dom.del,
        ]}
      />
      <VolunteerDetailModal open={!!detail} record={detail} onClose={() => setDetail(null)} />
    </>
  );
}
