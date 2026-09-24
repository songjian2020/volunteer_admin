export const POINTS_LOG_TYPE_LABELS: Record<string, string> = {
  activity: '活动服务',
  exchange: '积分兑换',
  offline_activity: '社区活动',
  admin_grant: '管理员赋分',
  admin_deduct: '管理员扣减',
  other: '其他调整',
  manual: '管理员调整（历史）',
};

export const POINTS_LOG_TYPE_FILTER_OPTIONS = Object.entries(POINTS_LOG_TYPE_LABELS).map(
  ([value, label]) => ({value, label}),
);

export const MANUAL_POINTS_TYPE_OPTIONS = [
  {label: '社区活动', value: 'offline_activity'},
  {label: '管理员赋分', value: 'admin_grant'},
  {label: '管理员扣减', value: 'admin_deduct'},
  {label: '其他调整', value: 'other'},
];

export function getPointsLogTypeLabel(type?: string) {
  if (!type) return '-';
  return POINTS_LOG_TYPE_LABELS[type] || type;
}

export function getPointsLogTypeTagColor(type?: string): string {
  switch (type) {
    case 'activity':
      return 'blue';
    case 'exchange':
      return 'orange';
    case 'offline_activity':
      return 'green';
    case 'admin_grant':
      return 'gold';
    case 'admin_deduct':
      return 'red';
    case 'manual':
      return 'purple';
    default:
      return 'default';
  }
}
