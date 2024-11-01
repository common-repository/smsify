<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
    exit ("Do not access this file directly.");
?>
<script>
function smsify_getDate(timestamp) {
    setTimeout(function(){ 
        smsify_setDateTimeYear("dt_"+timestamp, timestamp*1000); 
    }, 2000);
}
</script>
<h2><?php _e("SMSify Responses"); ?></h2>
<div><i id="smsify-page-loading" class="fa fa-spinner fa-spin fa-3x"></i></div>
<table id="smsify-table" class="wp-list-table widefat fixed striped posts" style="display:none;">
    <thead>
        <tr>
            <th scope="col"><?php _e("From"); ?></th>
            <th scope="col"><?php _e("To"); ?></th>
            <th scope="col"><?php _e("Date/Time"); ?></th>
            <th scope="col"><?php _e("Message"); ?></th>            
        </tr>
    </thead>
    <tbody>
        <?php foreach ($responses as $response) : ?> 
            <tr>
                <td><?php echo $response->from;  ?></td>
                <td><?php echo $response->to;  ?></td>
                <td id="dt_<?php _e($response->date_sent) ?>"><script>smsify_getDate(<?php _e($response->date_sent);  ?>);</script></td>
                <td><?php echo $response->body;  ?></td>
            </tr>
       <?php endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <th scope="col"><?php _e("From"); ?></th>
            <th scope="col"><?php _e("To"); ?></th>
            <th scope="col"><?php _e("Date/Time"); ?></th>
            <th scope="col"><?php _e("Message"); ?></th>            
        </tr>
    </tfoot>
</table>
<script>
    setTimeout(function(){ 
        document.getElementById('smsify-page-loading').style.display = "none";
        document.getElementById('smsify-table').style.display = "block";
    }, 3000);
</script>