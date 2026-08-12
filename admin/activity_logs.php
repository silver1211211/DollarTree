<?php
require_once __DIR__ . '/../config.php';
require_admin();
global $pdo;
$page_title = 'Activity Logs'; $active_nav = 'logs';
$type_f=$_GET['type']??'';
$where=$type_f?"WHERE al.activity_type='$type_f'":'';
$logs=$pdo->query("SELECT al.*,u.username FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id $where ORDER BY al.created_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
$types=$pdo->query("SELECT DISTINCT activity_type FROM activity_logs ORDER BY activity_type")->fetchAll(PDO::FETCH_COLUMN);
include '_layout.php';
?>
<div class="flex gap-10 mb-16" style="margin-bottom:14px;flex-wrap:wrap;">
  <a href="activity_logs.php" class="btn btn-ghost btn-sm <?php echo!$type_f?'btn-green':'';?>">All</a>
  <?php foreach($types as $t):?><a href="activity_logs.php?type=<?php echo urlencode($t);?>" class="btn btn-ghost btn-sm <?php echo $type_f===$t?'btn-blue':'';?>"><?php echo htmlspecialchars($t);?></a><?php endforeach;?>
</div>
<div class="card">
  <div class="table-wrap"><table>
    <thead><tr><th>Time</th><th>User</th><th>Type</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach($logs as $l):?>
    <tr>
      <td class="mono" style="font-size:11px;white-space:nowrap;"><?php echo date('M d H:i:s',strtotime($l['created_at']));?></td>
      <td><?php echo $l['username']?'<strong>'.htmlspecialchars($l['username']).'</strong>':'<span class="muted">System</span>';?></td>
      <td><span class="badge b-blue"><?php echo htmlspecialchars($l['activity_type']);?></span></td>
      <td style="max-width:400px;font-size:12px;color:var(--tm);"><?php echo htmlspecialchars($l['description']??'');?></td>
      <td class="mono" style="font-size:10px;"><?php echo htmlspecialchars($l['ip_address']??'—');?></td>
    </tr>
    <?php endforeach; if(empty($logs)):?><tr class="empty-row"><td colspan="5"><i class="fa-solid fa-inbox"></i> No logs found</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>
<?php include '_layout_end.php'; ?>
