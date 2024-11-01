<?php if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
    exit ("Do not access this file directly.");
?>
<?php 
//TODO Add Users array loop.
?>
<h2><?php _e("SMS Usage in Year " . $selected_year . " by month") ?></h2>
<small>NOTE: Recurring scheduled messages will NOT appear in this report.</small>
<form action="" method="get">
    <input type="hidden" name="page" value="smsify-reporting">
    <div class="tablenav top">
        <div class="alignleft">
            <label for="year">Year        
                <select id="year" name="year">
                    <option value="<?php echo $current_year+1 ?>"<?php if($current_year+1 == $selected_year) {echo ' selected="selected"';} ?>><?php echo $current_year+1 ?></option>
                    <option value="<?php echo $current_year ?>"<?php if($current_year == $selected_year) {echo ' selected="selected"';} ?>><?php echo $current_year ?></option>
                    <?php for($i=-1; $i > -2; $i--) : ?>
                        <option value="<?php echo $current_year+$i ?>"<?php if($current_year+$i == $selected_year) {echo ' selected="selected"';} ?>><?php echo $current_year+$i ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label for="user_id">User:        
                <select id="user_id" name="user_id">
                    <option value=""><?php _e("All Users"); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo $user->ID ?>"<?php if($user->ID == $_GET['user_id']){echo ' selected="selected"';} ?>><?php echo $user->user_login ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <input type="submit" name="" id="doaction" class="button action" value="SHOW REPORT">
        </div>
    </div>
</form>
<table class="wp-list-table widefat fixed">
    <tbody>
        <?php foreach($stats->$selected_year as $month_num => $total) : ?>
            <tr class="alternate"<?php if($month_num % 2 == 0) { echo ' style="background:#eee"'; } ?>>
                <td scope="row"><?php echo date('F', mktime(0, 0, 0, $month_num, 10)); ?></label></td>
                <td><?php echo $total; $grandtotal += $total; ?></td>
            </tr>
        <?php endforeach ?>
            <tr>
                <th scope="row"><strong><?php _e("Total"); ?></strong></th>
                <td><strong><?php echo $grandtotal ?></strong></td>
            </tr>
    </tbody>
</table>
