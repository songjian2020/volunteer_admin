import {List} from '@/api/common/table.ts';
import createAxios from '@/utils/request';
import {Alert, Button, Drawer, Form, Input, InputNumber, Select, Space, Typography} from 'antd';
import {useEffect, useState} from 'react';
import {MANUAL_POINTS_TYPE_OPTIONS} from '../constants/pointsLogType';

const {Text} = Typography;

export interface AdjustPointsVolunteer {
  id: number;
  name?: string;
  phone?: string;
  total_points?: number;
}

interface VolunteerOption extends AdjustPointsVolunteer {
  label: string;
  value: number;
}

interface Props {
  open: boolean;
  volunteer?: AdjustPointsVolunteer | null;
  onClose: () => void;
  onSuccess?: () => void;
}

export default function AdjustPointsDrawer({open, volunteer, onClose, onSuccess}: Props) {
  const [form] = Form.useForm();
  const [submitting, setSubmitting] = useState(false);
  const [options, setOptions] = useState<VolunteerOption[]>([]);
  const [searching, setSearching] = useState(false);
  const selectedType = Form.useWatch('type', form);
  const presetVolunteer = volunteer?.id ? volunteer : null;

  useEffect(() => {
    if (!open) return;
    form.resetFields();
    form.setFieldsValue({
      type: 'admin_grant',
      reason: '',
      points: undefined,
      volunteer_id: presetVolunteer?.id,
    });
    if (presetVolunteer) {
      setOptions([{
        ...presetVolunteer,
        value: presetVolunteer.id,
        label: `${presetVolunteer.name || ''} ${presetVolunteer.phone || ''}`.trim(),
      }]);
    } else {
      setOptions([]);
      void searchVolunteers('');
    }
  }, [open, volunteer?.id, form]);

  const searchVolunteers = async (keyword: string) => {
    setSearching(true);
    try {
      const params: Record<string, unknown> = {
        audit_status: 1,
        page: 1,
        pageSize: 20,
      };
      const kw = keyword.trim();
      if (kw) {
        params.keywordSearch = kw;
      }
      const {data} = await List<AdjustPointsVolunteer>('/volunteer/volunteer', params);
      const list = data.data?.data || [];
      setOptions(list.map((item) => ({
        ...item,
        value: item.id,
        label: `${item.name || ''} ${item.phone || ''}`.trim(),
      })));
    } finally {
      setSearching(false);
    }
  };

  const handleSubmit = async () => {
    const values = await form.validateFields();
    const volunteerId = presetVolunteer?.id ?? values.volunteer_id;
    if (!volunteerId) {
      window.$message?.warning('请选择志愿者');
      return;
    }
    setSubmitting(true);
    try {
      await createAxios({
        url: `/volunteer/volunteer/${volunteerId}/points`,
        method: 'post',
        data: {
          type: values.type,
          points: values.points,
          reason: values.reason,
        },
      });
      window.$message?.success('操作成功');
      onClose();
      onSuccess?.();
    } finally {
      setSubmitting(false);
    }
  };

  const pointsPlaceholder = selectedType === 'admin_deduct'
    ? '请输入负数，如 -10'
    : selectedType === 'admin_grant'
      ? '请输入正数，如 30'
      : '正数增加，负数扣减';

  return (
    <Drawer
      title="调整积分"
      open={open}
      onClose={onClose}
      width={480}
      destroyOnHidden
      extra={null}
      footer={
        <Space style={{float: 'right'}}>
          <Button onClick={onClose}>取消</Button>
          <Button type="primary" loading={submitting} onClick={() => void handleSubmit()}>确定</Button>
        </Space>
      }
    >
      <Form form={form} layout="vertical">
        {!presetVolunteer && (
          <Form.Item
            name="volunteer_id"
            label="志愿者"
            rules={[{required: true, message: '请选择志愿者'}]}
          >
            <Select
              showSearch
              filterOption={false}
              placeholder="输入姓名或电话搜索"
              options={options}
              loading={searching}
              onSearch={(value) => void searchVolunteers(value)}
              optionRender={(option) => {
                const item = option.data as VolunteerOption;
                return (
                  <div>
                    <div>{item.name} {item.phone}</div>
                    <Text type="secondary">当前积分：{item.total_points ?? 0}</Text>
                  </div>
                );
              }}
            />
          </Form.Item>
        )}
        {presetVolunteer && (
          <Alert
            type="info"
            showIcon
            className="mb-4"
            message={`${presetVolunteer.name || ''} ${presetVolunteer.phone || ''}`.trim()}
            description={`当前积分：${presetVolunteer.total_points ?? 0}`}
          />
        )}
        <Form.Item
          name="type"
          label="操作类型"
          rules={[{required: true, message: '请选择操作类型'}]}
        >
          <Select options={MANUAL_POINTS_TYPE_OPTIONS} placeholder="请选择操作类型" />
        </Form.Item>
        <Form.Item
          name="points"
          label="积分"
          rules={[{required: true, message: '请输入积分'}]}
          extra={selectedType === 'admin_deduct' ? '管理员扣减需输入负数' : undefined}
        >
          <InputNumber placeholder={pointsPlaceholder} style={{width: '100%'}} />
        </Form.Item>
        <Form.Item name="reason" label="原因">
          <Input.TextArea rows={3} placeholder="如：3月社区清扫、表彰奖励等" maxLength={200} showCount />
        </Form.Item>
      </Form>
    </Drawer>
  );
}
