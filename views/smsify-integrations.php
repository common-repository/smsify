<?php if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
    exit ("Do not access this file directly.");
?>
<?php 
$counter = 0;
?>
<div class="wrap" style="background-color:#fff; max-width: 920px; padding: 30px;">
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab nav-tab<?php $smsify_integrations_tab === 'webhooks' ? _e('-active') : _e(''); ?>" href="<?php _e(add_query_arg(array('tab'=>'webhooks'))) ?>"> <img src="<?php echo plugin_dir_url( __DIR__ )?>images/webhooks_logo_square.png" alt="Zapier Logo" height="15" /> Webhooks</a>
        <a class="nav-tab nav-tab<?php $smsify_integrations_tab === 'contact7' ? _e('-active') : _e(''); ?>" href="<?php _e(add_query_arg(array('tab'=>'contact7'))) ?>">Contact Form 7</a>
    </h2>
    <?php if($smsify_integration_notice) { ?>
        <div class="updated">
            <p><?php _e( $smsify_integration_notice, 'my-text-domain' ); ?></p>
        </div>
    <?php } ?>
    <?php if($smsify_integrations_tab === 'webhooks') { ?>
        <div style="padding-top:25px;">
            Webhooks empower you to automate your work across 1000s of apps-so you can move forward, faster.<br/>
            Webhooks are supported by some of the leading workflow automation platforms such as:
            <ul>
                <li><a href="https://zapier.com/page/webhooks/" title="Zapier Webhooks" target="_blank">Zapier</a></li>
                <li><a href="https://ifttt.com/maker_webhooks" title="IFTTT Webhooks" target="_blank">IFTTT</a></li>
                <li><a href="https://www.make.com/en/help/tools/webhooks" title="Make Webhooks" target="_blank">Make (Formerly Integromat)</a></li>
                <li>and many others...</li>
            </ul>
            <p>Webhooks are the most flexible way to integrate your WordPress website with other systems. For example, when you send an SMS, we can notify any 3rd party application via the webhook URL that you soecify here.</p>
        </div>
        <div style="padding-top:25px;">    
            <form name="integration-form" id="integration-form" method="POST">
                <label for="output_sms_webhook_url"><strong>Outbout SMS Webhook URL:</strong>
                </label><input type="url" id="outbound_sms_webhook_url" name="outbound_sms_webhook_url" value="<?php _e($smsify_integration_webhooks['outbound_sms_webhook_url'])?>" maxlength="500" placeholder="https://example.com/blabla" pattern="https://.*" />
                <div style="padding-top:30px;">
                    <input type="submit" name="smsify_webhook_save" class="button button-primary action" value="SAVE" />
                </div>
            </form>
        </div>
    <?php } ?>
    <?php if($smsify_integrations_tab === 'contact7') { ?>
        <h2><?php _e("Contact Form 7") ?></h2>
        <p>To eliminate spam, please install reCAPTCHA plugin for Contact Form 7 under <strong>"Contact->Integration menu"</strong>. Make sure you have activated this plugin on <strong>SMSify->Settings</strong> page.</p>
        <p><strong>By default, the following message will be sent via the SMS when your Contact Form 7 is submitted successfully (You can customise this message in the Message column below):</strong><br>
        <?php _e($smsify_default_message) ?>.<br><br>
        <strong>Variables</strong>
        <br> 
        <?php _e($smsify_message_help); ?></p>
        <br>
        <strong>Help</strong>
        <p>For help and feature requests, please contact <a href="mailto:support@cloudinn.io">support@cloudinn.io</a></p>
        <form name="integration-form" id="integration-form" method="POST">
            <table class="wp-list-table widefat">
                <tbody>
                    <tr>
                        <td scope="row"><strong>Contact 7 Forms</strong></td>
                        <td><strong>Status</strong></td>
                        <td><strong>Number to notify</strong></td>
                        <td><strong>Message</strong></td>
                    </tr>
                    <?php foreach($smsify_cf7_forms as $form) : $counter++ ?>
                        <tr class="alternate"<?php if($counter % 2 == 0) { echo ' style="background:#eee"'; } ?>>
                            <td scope="row"><?php _e($form->post_title) ?></label></td>
                            <td><?php _e($form->post_status) ?></td>
                            <td><input type="number" name="smsify_cf7_notify_<?php _e($form->ID) ?>" placeholder="Number to notify" maxlength="20" value="<?php _e($smsify_integration_mobiles['smsify_cf7_notify_'.$form->ID]) ?>"/></label></td>
                            <td><textarea name="smsify_cf7_message_<?php _e($form->ID) ?>" rows="3" cols="30" maxlength="150"><?php $smsify_integration_mobiles['smsify_cf7_message_'.$form->ID] ? _e($smsify_integration_mobiles['smsify_cf7_message_'.$form->ID]) : _e($smsify_default_message); ?></textarea></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <br>
            <input type="submit" name="smsify_integration_save" class="button button-primary action" value="SAVE" />
        </form>
    <?php } ?>
</div>