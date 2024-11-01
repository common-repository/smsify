<?php if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
?>
<?php if($smsify_params->api_key) : ?>
	<?php wp_enqueue_style('smsify-font-awesome'); ?>
	<h3><?php _e("Send SMS to this Group")?></h3>
	<div class="smsify-send">
		<div class="smsify-sending"><img src="<?php echo $smsify_params->imageurl ?>/loading-image.gif" alt="loading..." /><p><?php _e("Sending SMS"); ?></p></div>
		<div class="smsify-confirmation"></div>
		<input type="hidden" name="smsify_confirmation" id="smsify_confirmation" value="<?php echo $smsify_params->messages['send_group_confirmation']; ?>" />
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="smsify_message"><?php _e("Message"); ?></label></th>
					<td>
						<textarea name="user-group[sms-message]" id="smsify_message" rows="5" cols="50" maxlength="160"><?php echo self::get_meta('sms-message'); ?></textarea>
						<br/>
						<span class="description"><?php _e("Maximum 160 characters."); ?><br/><?php _e("HINT: Use {first_name} {last_name} to insert personal details for each contact. For example, Dear {first_name} - would translate to: Dear John."); ?></span>
					</td>
				</tr>
				<?php if(get_site_option('smsify-enable-sender-id-override')) : ?>
				<tr>
					<th><label for="smsify_sender_id"><?php _e("Sender ID"); ?></label></th>
					<td>
						<input type="number" name="user-group[smsify-sender-id]" id="smsify_sender_id" value="<?php echo self::get_meta('smsify-sender-id'); ?>" class="regular-text" maxlength="15" /><br />
						<span class="description"><?php _e("If you purchased additional Sender ID(s), enter it here. Use this with caution. If your SenderID is incorrect, the message will not get delivered and you may be charged for SMS credit(s). Leave blank to use default Sender ID."); ?></span>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th><label for="smsify-scheduler"><?php _e("Schedule SMS"); ?></label></th>
					<td><input type="checkbox" name="smsify-scheduler" id="smsify-scheduler" value="1" /></td>
				</tr>
				<tr class="smsify-scheduleblock">
					<th><label for="smsify-schedule-date"><?php _e("Schedule Date"); ?></label></th>
					<td>
						<input type="text" name="smsify-schedule-date" id="smsify-schedule-date" readonly="readonly" />
					</td>
				</tr>
				<tr class="smsify-scheduleblock">
					<th><label for="smsify-schedule-time"><?php _e("Schedule Time"); ?></label></th>
					<td>
						<select name="smsify-schedule-time" id="smsify-schedule-time">
							<option value="">-- Please select --</option>
							<option value="0">12:00 AM</option>
							<option value="30">12:30 AM</option>
							<option value="60">01:00 AM</option>
							<option value="90">01:30 AM</option>
							<option value="120">02:00 AM</option>
							<option value="150">02:30 AM</option>
							<option value="180">03:00 AM</option>
							<option value="210">03:30 AM</option>
							<option value="240">04:00 AM</option>
							<option value="270">04:30 AM</option>
							<option value="300">05:00 AM</option>
							<option value="330">05:30 AM</option>
							<option value="360">06:00 AM</option>
							<option value="390">06:30 AM</option>
							<option value="420">07:00 AM</option>
							<option value="450">07:30 AM</option>
							<option value="480">08:00 AM</option>
							<option value="510">08:30 AM</option>
							<option value="540">09:00 AM</option>
							<option value="570">09:30 AM</option>
							<option value="600">10:00 AM</option>
							<option value="630">10:30 AM</option>
							<option value="660">11:00 AM</option>
							<option value="690">11:30 AM</option>
							<option value="720">12:00 PM</option>
							<option value="750">12:30 PM</option>
							<option value="780">01:00 PM</option>
							<option value="810">01:30 PM</option>
							<option value="840">02:00 PM</option>
							<option value="870">02:30 PM</option>
							<option value="900">03:00 PM</option>
							<option value="930">03:30 PM</option>
							<option value="960">04:00 PM</option>
							<option value="990">04:30 PM</option>
							<option value="1020">05:00 PM</option>
							<option value="1050">05:30 PM</option>
							<option value="1080">06:00 PM</option>
							<option value="1110">06:30 PM</option>
							<option value="1140">07:00 PM</option>
							<option value="1170">07:30 PM</option>
							<option value="1200">08:00 PM</option>
							<option value="1230">08:30 PM</option>
							<option value="1260">09:00 PM</option>
							<option value="1290">09:30 PM</option>
							<option value="1320">10:00 PM</option>
							<option value="1350">10:30 PM</option>
							<option value="1380">11:00 PM</option>
							<option value="1410">11:30 PM</option>
						</select>
						<br/>
                        <p class="description">Your local timezone is detected automatically and is used to deliver messages.</p>
					</td>
				</tr>
				<tr class="smsify-scheduleblock">
					<th><label for="run_every">Run every 'n' days<br/>(0 = not recurring):</label></th>
					<td><input id="run_every" type="number" value="0" min="0" max="365" step="1" /> days</td>
				</tr>
				<tr class="smsify-scheduleblock">
					<th><label for="run_times">Run times<br/>(0 = forever):</label></th>
					<td><input id="run_times" type="number" value="1" min="0" max="365" step="1" /></td>
				</tr>
				<tr class="smsify-scheduleblock">
					<th>Recurring SMS</th>
					<td><p class="description">Hint: If you wanted to send a weekly SMS, for 4 weeks in a row you would set Run Every to 7 and Run Times to 4.</p></td>
				</tr>
			</tbody>
		</table>
		<button class="button button-primary smsify-send-group-sms"><i class="fa fa-mobile fa-2x"></i> <?php _e("SEND SMS"); ?></button>
	</div>
<?php else : ?>
	<div class="error"><p><?php _e("You must activte SMSify before you can start sending SMS to this group. <a href='admin.php?page=smsify-settings'>Activate SMSify now.</a>"); ?></p></div>
<?php endif ?>