<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
?>
<script>
	function smsify_setDateTimeDefer(id, thedate) {
		setTimeout(function(){ 
			smsify_setDateTime(id, thedate); 
		}, 2000);
	}
</script>
<h2><?php _e("SMSify Schedules"); ?></h2>
<div class="smsify-message update-nag"></div>
<div><i id="smsify-page-loading" class="fa fa-spinner fa-spin fa-3x"></i></div>
<table id="smsify-table" class="wp-list-table widefat fixed striped posts" style="display:none;">
	<thead>
		<tr>
			<th><?php _e("Date Created"); ?></th>
			<th><?php _e("Initial Run"); ?></th>
			<th><?php _e("Last Run"); ?></th>
			<th><?php _e("Next Run"); ?></th>
			<th><?php _e("Run Every (Days)"); ?></th>
			<th><?php _e("Run Times Required"); ?></th>
			<th><?php _e("Actual Run Times"); ?></th>
			<th><?php _e("Action"); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($response as $schedule) { ?>
			<tr>
				<td id="<?php _e(md5($schedule->local_task_id . $schedule->dt_created) ) ?>"><script>smsify_setDateTimeDefer('<?php _e(md5($schedule->local_task_id . $schedule->dt_created) ) ?>', '<?php _e($schedule->dt_created) ?>')</script></td>
				<td id="<?php _e(md5($schedule->local_task_id . $schedule->start_at) ) ?>"><script>smsify_setDateTimeDefer('<?php _e(md5($schedule->local_task_id . $schedule->start_at) ) ?>', '<?php _e($schedule->start_at) ?>')</script></td>
				<td id="<?php _e(md5($schedule->local_task_id . $schedule->dt_last_run) ) ?>"><script>smsify_setDateTimeDefer('<?php _e(md5($schedule->local_task_id . $schedule->dt_last_run) ) ?>', '<?php _e($schedule->dt_last_run) ?>')</script></td>
				<td id="<?php _e(md5($schedule->local_task_id . $schedule->dt_next_run) ) ?>"><script>smsify_setDateTimeDefer('<?php _e(md5($schedule->local_task_id . $schedule->dt_next_run) ) ?>', '<?php _e($schedule->dt_next_run) ?>')</script></td>
				<td><?php _e($schedule->run_every); ?></td>
				<td><?php _e($schedule->run_times); ?></td>
				<td><?php _e($schedule->actual_run_times); ?></td>
				<td><span class="smsify-spinner spinner_<?php _e($schedule->local_task_id) ?>"><i class="fa fa-spinner fa-spin"></i></span><button id="<?php _e($schedule->local_task_id ) ?>" class="button button-primary smsify-deleteschedule"><i class="fa fa-times"></i> <?php _e("DELETE"); ?></button></td>
			</tr>
		<?php } ?>
	</tbody>
</table>
<script>
	setTimeout(function(){ 
		document.getElementById('smsify-page-loading').style.display = "none";
		document.getElementById('smsify-table').style.display = "block";
	}, 3000);
</script>