import AuthButton from '@/components/AuthButton';
import {Badge, Button, Descriptions, Modal, Space} from 'antd';
import {PayCircleOutlined} from '@ant-design/icons';
import dayjs from 'dayjs';

export interface VolunteerDetailRecord {
  id: number;
  name?: string;
  phone?: string;
  gender?: string;
  age?: number;
  education?: string;
  political_status?: string;
  id_card?: string;
  address?: string;
  specialty?: string;
  emergency_contact?: string;
  emergency_phone?: string;
  audit_status?: number;
  audit_status_text?: string;
  total_points?: number;
  total_hours?: number;
  activity_count?: number;
  star_level?: number;
  star_level_text?: string;
  certificate_no?: string;
  created_at?: string;
  updated_at?: string;
}

function genderText(v?: string) {
  if (v === '2') return '女';
  if (v === '1') return '男';
  return v || '-';
}

function auditBadge(status?: number, text?: string) {
  if (status === 1) return <Badge status="success" text={text || '已通过'} />;
  if (status === 2) return <Badge status="error" text={text || '已拒绝'} />;
  return <Badge status="processing" text={text || '待审核'} />;
}

function formatTime(v?: string) {
  return v ? dayjs(v).format('YYYY-MM-DD HH:mm') : '-';
}

interface Props {
  open: boolean;
  record: VolunteerDetailRecord | null;
  onClose: () => void;
  onAdjustPoints?: (record: VolunteerDetailRecord) => void;
}

export default function VolunteerDetailModal({open, record, onClose, onAdjustPoints}: Props) {
  return (
    <Modal
      title="志愿者详情"
      open={open}
      onCancel={onClose}
      footer={
        <Space>
          {onAdjustPoints && record && (
            <AuthButton auth="volunteer.volunteer.points">
              <Button icon={<PayCircleOutlined />} onClick={() => onAdjustPoints(record)}>
                调整积分
              </Button>
            </AuthButton>
          )}
          <Button type="primary" onClick={onClose}>关闭</Button>
        </Space>
      }
      width={720}
      destroyOnHidden
    >
      {record && (
        <Descriptions column={2} bordered size="small">
          <Descriptions.Item label="ID">{record.id}</Descriptions.Item>
          <Descriptions.Item label="状态">{auditBadge(record.audit_status, record.audit_status_text)}</Descriptions.Item>
          <Descriptions.Item label="姓名">{record.name || '-'}</Descriptions.Item>
          <Descriptions.Item label="电话">{record.phone || '-'}</Descriptions.Item>
          <Descriptions.Item label="性别">{genderText(record.gender)}</Descriptions.Item>
          <Descriptions.Item label="年龄">{record.age ?? '-'}</Descriptions.Item>
          <Descriptions.Item label="文化程度">{record.education || '-'}</Descriptions.Item>
          <Descriptions.Item label="政治面貌">{record.political_status || '-'}</Descriptions.Item>
          <Descriptions.Item label="身份证号" span={2}>{record.id_card || '-'}</Descriptions.Item>
          <Descriptions.Item label="家庭住址" span={2}>{record.address || '-'}</Descriptions.Item>
          <Descriptions.Item label="特长" span={2}>{record.specialty || '-'}</Descriptions.Item>
          <Descriptions.Item label="紧急联系人">{record.emergency_contact || '-'}</Descriptions.Item>
          <Descriptions.Item label="紧急联系电话">{record.emergency_phone || '-'}</Descriptions.Item>
          <Descriptions.Item label="证书编号">{record.certificate_no || '-'}</Descriptions.Item>
          <Descriptions.Item label="星级">
            {record.star_level_text ? `${record.star_level_text}星` : (record.star_level ?? '-')}
          </Descriptions.Item>
          <Descriptions.Item label="累计积分">{record.total_points ?? 0}</Descriptions.Item>
          <Descriptions.Item label="服务时长">{record.total_hours ?? 0}</Descriptions.Item>
          <Descriptions.Item label="参与活动数">{record.activity_count ?? 0}</Descriptions.Item>
          <Descriptions.Item label="申请时间">{formatTime(record.created_at)}</Descriptions.Item>
          <Descriptions.Item label="更新时间">{formatTime(record.updated_at)}</Descriptions.Item>
        </Descriptions>
      )}
    </Modal>
  );
}
