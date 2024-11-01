<?php
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");
function smsify_getConfig() {
	global $smsify_params;
	global $current_user;
	$smsify_params = new stdClass();
	$smsify_params->appVersion = '6.0.2';
	$smsify_params->api_key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
	$smsify_params->apiprotocol = 'https';
	$smsify_params->apihost = 'api.cloudinn.io';
	$smsify_params->apiEndpoint = $smsify_params->apiprotocol . '://' . $smsify_params->apihost;
	$smsify_params->cssurl = plugins_url() . '/smsify/css';
	$smsify_params->jsurl = plugins_url() . '/smsify/js';
	$smsify_params->imageurl = plugins_url() . '/smsify/images';
	$smsify_params->smsifydir = $_SERVER["DOCUMENT_ROOT"] . '/' . PLUGINDIR . '/smsify';
	
	$smsify_params->messages = array(
							"send_group_confirmation" => __("You are about to send a group SMS. Would you like to continue?")
	);

	wp_register_style('smsify', 
						$smsify_params->cssurl . '/smsify.css', 
						array(), 
						$smsify_params->appVersion,
						'all');
	wp_register_style('jquery-ui-1.10.3.custom.min', 
						$smsify_params->cssurl . '/jquery-ui-1.10.3.custom.min.css',
						array(),
						$smsify_params->appVersion,
						'all');
	wp_register_style('smsify-font-awesome', 
						$smsify_params->cssurl . '/font-awesome.min.css',
						array(),
						$smsify_params->appVersion,
						'all');
	wp_register_script('smsify-momentjs', 
						$smsify_params->jsurl . '/moment.min.js', 
						array(), 
						$smsify_params->appVersion);
	wp_register_script('smsify-sms-controller', 
						$smsify_params->jsurl . '/sendsmscontroller.min.js', 
						array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'smsify-momentjs'), 
						$smsify_params->appVersion);

	return $smsify_params;
}

add_action( 'personal_options_update', 'smsify_update_sms_user_data' );
add_action( 'edit_user_profile_update', 'smsify_update_sms_user_data' );
function smsify_update_sms_user_data( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
	update_user_meta( $user_id, 'smsify_mobile', $_POST['smsify_mobile'] );
	update_user_meta( $user_id, 'smsify_message', $_POST['smsify_message'] );
	update_user_meta( $user_id, 'smsify_sender_id', $_POST['smsify_sender_id'] );
}

add_action( 'wp_ajax_smsify_sms_handler', 'smsify_sms_handler' );
function smsify_sms_handler($passthrough=false) {
	do_action('smsify_before_send_sms');
	if ( !current_user_can( 'edit_user', $user_id ) && !$passthrough ) { 
		die("Invalid request - 000"); 
	}
	
	if(!isset($_POST['send_to'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 002");
	}
	
	if(!isset($_POST['message'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 003");
	}
	
	if(!isset($_POST['user_id'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 004");
	}
	
	if(!isset($_POST['scheduler'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 005");
	}
	
	if(!isset($_POST['schedule_date_time'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 006");
	}
	
	$error = false;
	$validationMessage = "";
	$returnMessage = new stdClass();
	$message = $_POST['message'];
	$first_name = $_POST['first_name'];
	$last_name = $_POST['last_name'];
	$mobile = $_POST['send_to'];
	$key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
	$method = $_POST['method'];
	$scheduler = $_POST['scheduler'];
	$schedule_date_time = $_POST['schedule_date_time'];
	$run_every = $_POST['run_every'];
	$run_times = $_POST['run_times'];
	
	if(strlen($message) > 160) {
		$error = true;
		$validationMessage = __("Your message seems to be longer than 160 characters.");
	}
	
	if(trim($message) == "" ) {
		$error = true;
		$validationMessage = __("Please enter your SMS message");
	}
	
	if(strlen($mobile) < 10) {
		$error = true;
		$validationMessage = __("Your mobile number seems to be invalid.\nPlease correct it and try again.");
	}
	
	if(!is_numeric($mobile)) {
		$error = true;
		$validationMessage = __("Your mobile number seems to be invalid.\nPlease correct it and try again.");
	}
	
	if(isset($_POST['sender_id']) && !is_numeric($_POST['sender_id'])) {
		$error = true;
		$validationMessage = __("Your Sender ID seems to be invalid.\nPlease correct it and try again.");
	}
	
	if($scheduler && $schedule_date_time == "") {
		$error = true;
		$validationMessage = __("If you choose to schedule your SMS, you must specify schedule date and time.");
	}
	
	if(!$error) {    
		$smsify_params = smsify_getConfig();
		$contact = new stdClass();
		$contact->first_name = $first_name;
		$contact->last_name = $last_name;
		$contact->mobile_number = $mobile;
		$args = array('timeout' => 30, 
						"sslverify" => false,
						"method" => "POST",
						"headers" => array("x-smsify-key" => $key, 'Content-Type' => 'application/json'),
						"body" => json_encode(array(
							"contacts" => array($contact),
							"message" => $message,
							"scheduler" => $scheduler,
							"run_every" => intval($run_every),
							"run_times" => intval($run_times),
							"schedule_date_time" => $schedule_date_time,
							"current_user" => wp_get_current_user()->user_login
						))
					);

		if(isset($_POST['sender_id'])) {
			$args['body']['sender_id'] = $_POST['sender_id'];
		}

		$result = wp_remote_post($smsify_params->apiEndpoint . "/sendsms", $args);
		if ( is_wp_error( $result ) ) {
			$validationMessage = $result->get_error_message();
			$returnMessage->status = false;
		}else if($result['response']['code'] != 200) {
			$validationMessage = __($result['body']);
			$returnMessage->status = false;
		} else {
			$response = json_decode($result['body']);
			if($response->code == 200) {
				$returnMessage->status = true;
				if($scheduler) {    
					$validationMessage = __("Your SMS has been scheduled successfully.");
					$d = date_parse($schedule_date_time);
					//Update stats for reporting
					smsify_update_usage(1, $d['year'], $d['month']);
				} else {
					$validationMessage = __("Your SMS has been queued and will be sent shortly.");
					//Update stats for reporting
					smsify_update_usage(1);
				}
			} else {
				$returnMessage->status = false;
				$validationMessage = __($response->message);
			}
		}
		$returnMessage->message = $validationMessage;
   	} else {
		$returnMessage->status = false;
		$returnMessage->message = $validationMessage;
   	}

   	$smsify_webhooks = smsify_get_integration_webhooks();
   	if(isset($smsify_webhooks['outbound_sms_webhook_url']) && $smsify_webhooks['outbound_sms_webhook_url']) {
   		$webhook_args = new stdClass();
   		$webhook_args->webhook_url = $smsify_webhooks['outbound_sms_webhook_url'];
   		$webhook_args->body = $args['body'];
   		do_action('smsify_notify_webhook', $webhook_args);
   	}
   
   if (!$passthrough) {	
   	echo json_encode($returnMessage);
   	die();
   }
}

add_action( 'smsify_send_sms_hook', 'smsify_hook_send_sms' );
function smsify_hook_send_sms($args) {
	do_action('smsify_before_hook_send_sms', $args);
	$error = false;
	$validationMessage = "";
	$returnMessage = new stdClass();
	$key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
	$run_every = 0;
	$run_times = 0;
	$scheduler = 0;
	$schedule_date_time = "";

	if(!$error) {    
		$smsify_params = smsify_getConfig();
		$contact = new stdClass();
		$contact->first_name = isset($args->first_name) ? $args->first_name : '';
		$contact->last_name = isset($args->last_name) ? $args->last_name : '';
		$contact->mobile_number = $args->send_to;
		$params = array('timeout' => 30, 
						"sslverify" => false,
						"method" => "POST",
						"headers" => array("x-smsify-key" => $key, 'Content-Type' => 'application/json'),
						"body" => json_encode(array(
							"contacts" => array($contact),
							"message" => $args->message,
							"scheduler" => $scheduler,
							"run_every" => intval($run_every),
							"run_times" => intval($run_times),
							"schedule_date_time" => $schedule_date_time
						))
					);

		$result = wp_remote_post($smsify_params->apiEndpoint . "/sendsms", $params);
		if ( is_wp_error( $result ) ) {
			$validationMessage = $result->get_error_message();
			$returnMessage->status = false;
		}else if($result['response']['code'] != 200) {
			$validationMessage = __($result['body']);
			$returnMessage->status = false;
		} else {
			$response = json_decode($result['body']);
			if($response->code == 200) {
				$returnMessage->status = true;
				if($scheduler) {    
					$validationMessage = __("Your SMS has been scheduled successfully.");
					$d = date_parse($schedule_date_time);
					//Update stats for reporting
					smsify_update_usage(1, $d['year'], $d['month']);
				} else {
					$validationMessage = __("Your SMS has been queued and will be sent shortly.");
					//Update stats for reporting
					smsify_update_usage(1);
				}
			} else {
				$returnMessage->status = false;
				$validationMessage = __($response->message);
			}
		}
		$returnMessage->message = $validationMessage;
   } else {
		$returnMessage->status = false;
		$returnMessage->message = $validationMessage;
   }

   do_action('smsify_after_hook_send_sms', $returnMessage);
   
   return $returnMessage;
}

add_action( 'wp_ajax_smsify_sms_group_handler', 'smsify_sms_group_handler' );
function smsify_sms_group_handler() {
	do_action('smsify_before_group_send_sms');
	global $wpdb;
	
	if ( !current_user_can( 'edit_user', $user_id ) ) { die("Invalid request - 000"); }
	
	if(!isset($_POST['message'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 001");
	}
	
	if(!isset($_POST['tag_id'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 002");
	}
	
	if(!isset($_POST['scheduler'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 003");
	}
	
	if(!isset($_POST['schedule_date_time'])) {
		//header("HTTP/1.1 500 Invalid Request");
		die("Invalid request - 004");
	}
	
	$error = false;
	$validationMessage = "";
	$message = $_POST['message'];
	$key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
	$method = $_POST['method'];
	$tag_id = $_POST['tag_id'];
	$taxonomy = $_POST['taxonomy'];
	$scheduler = $_POST['scheduler'];
	$schedule_date_time = $_POST['schedule_date_time'];
	$run_every = $_POST['run_every'];
	$run_times = $_POST['run_times'];
	$returnMessage = new stdClass();
	
	if(strlen($message) > 160) {
		$error = true;
		$validationMessage = __("Your message seems to be longer than 160 characters.");
	}
	
	if(trim($message) == "" ) {
		$error = true;
		$validationMessage = __("Please enter your SMS message");
	}
	
	if($scheduler && $schedule_date_time == "") {
		$error = true;
		$validationMessage = __("If you choose to schedule your SMS, you must specify schedule date and time.");
	}
	
	if(isset($_POST['sender_id']) && !is_numeric($_POST['sender_id'])) {
		$error = true;
		$validationMessage = __("Your Sender ID seems to be invalid.\nPlease correct it and try again.");
	}
	
	if(!$error) {
		$user_ids = get_objects_in_term($tag_id, $taxonomy );
		$args = array('include'=>$user_ids);
		$users = get_users($args);
		foreach($users as $user) {
			if(strlen(trim($user->smsify_mobile))) {    
				$thisUser = new stdClass();
				$thisUser->first_name = $user->first_name;
				$thisUser->last_name = $user->last_name;
				$thisUser->mobile_number = $user->smsify_mobile;
				$contacts[] = $thisUser;
			}
		}
								   
		$smsify_params = smsify_getConfig();
		$args = array("timeout" => 30, 
						"sslverify" => false,
						"method" => "POST",
						"headers" => array("x-smsify-key" => $key, 'Content-Type' => 'application/json'),
						"body" => json_encode(array(
							"contacts" => $contacts,
							"message" => $message,
							"run_every" => intval($run_every),
							"run_times" => intval($run_times),
							"scheduler" => $scheduler,
							"schedule_date_time" => $schedule_date_time,
							"current_user" => wp_get_current_user()->user_login
						))
					);
		
		if(isset($_POST['sender_id'])) {
			$args['body']['sender_id'] = $_POST['sender_id'];
		}
		
		$result = wp_remote_post($smsify_params->apiEndpoint . "/sendsms", $args);
		
		if ( is_wp_error( $result ) ) {
			$validationMessage = $result->get_error_message();
			$returnMessage->status = false;
		}else if($result['response']['code'] != 200) {
			$validationMessage = __($result['body']);
			$returnMessage->status = false;
		} else {
			$response = json_decode($result['body']);
			if($response->code == 200) {
				$returnMessage->status = true;
				if($scheduler) {    
					$validationMessage = __("Your SMS has been scheduled successfully.");
					$d = date_parse($schedule_date_time);
					//Update stats for reporting
					smsify_update_usage(count($contacts), $d['year'], $d['month']);
				} else {
					$validationMessage = __("Your SMS has been queued and will be sent shortly.");
					//Update stats for reporting
					smsify_update_usage(count($contacts));
				}
			} else {
				$returnMessage->status = false;
				$validationMessage = __($response->message);
			}
		}
		$returnMessage->message = $validationMessage;
   } else {
	   $returnMessage->status = false;
	   $returnMessage->message = $validationMessage;
   }
   
   	$smsify_webhooks = smsify_get_integration_webhooks();
   	if(isset($smsify_webhooks['outbound_sms_webhook_url']) && $smsify_webhooks['outbound_sms_webhook_url']) {
   		$webhook_args = new stdClass();
   		$webhook_args->webhook_url = $smsify_webhooks['outbound_sms_webhook_url'];
   		$webhook_args->body = $args['body'];
   		do_action('smsify_notify_webhook', $webhook_args);
   	}

   echo json_encode($returnMessage);
   die();
}

add_action( 'wp_ajax_smsify_sms_remove_schedule_handler', 'smsify_delete_schedule' );
function smsify_delete_schedule() {
	if ( !current_user_can( 'edit_user', $user_id ) ) { die("Invalid request - 000"); }
	
	if(!isset($_POST['method'])) {
		die("Invalid request - 001");
	}

	if(!isset($_POST['task_id'])) {
		die("Invalid request - 002");
	}
	
	$error = false;
	$validationMessage = "";
	$task_id = $_POST['task_id'];
	$key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
	$method = $_POST['method'];
	$returnMessage = new stdClass();
	
	if(!$error) {    
		$smsify_params = smsify_getConfig();
		$args = array(	"method" => "DELETE",
						"timeout" => 30, 
						"sslverify" => false,
						"headers" => array("x-smsify-key" => $key)
				);

		$result = wp_remote_post($smsify_params->apiEndpoint . "/schedule/remove/".$task_id, $args);

		if ( is_wp_error( $result ) ) {
			$validationMessage = $result->get_error_message();
			$returnMessage->status = false;
		}else if($result['response']['code'] != 200) {
			$validationMessage = __($result['body']);
			$returnMessage->status = false;
		} else {
			$result = json_decode($result['body']);
			$returnMessage->status = true;
			$validationMessage = __($result->message);
		}
		$returnMessage->message = $validationMessage;
	} else {
		$returnMessage->status = false;
		$returnMessage->message = $validationMessage;
	}
   echo json_encode($returnMessage);
   die();
}

function smsify_reporting_yearly_template($selected_year) {
	$yearly_packet = new stdClass();
	$yearly_packet->$selected_year = new stdClass();
	
	for($i=1; $i <= 12; $i++) {
		$yearly_packet->$selected_year->$i = 0;
	}
	return $yearly_packet;
}

function smsify_get_yearly_stats($year, $user_id=0) {
	global $wpdb;    
	$meta_key = "smsify_" . $year;
	$report = smsify_reporting_yearly_template($year);
	
	$sql = "SELECT u.user_login, um.meta_value, um.user_id FROM " . $wpdb->prefix . "usermeta um 
	INNER JOIN " . $wpdb->prefix . "users u ON (u.ID = um.user_id) 
	WHERE um.meta_key = %s";
	if($user_id) {
		$sql .= " AND u.ID = %d";
		$user_stats = $wpdb->get_results(
					$wpdb->prepare(
						$sql, $meta_key, $user_id));
	} else {
		$user_stats = $wpdb->get_results(
					$wpdb->prepare(
						$sql, $meta_key));
	}
	
	if(count($user_stats)) {
		//Loop through each user's numbers and add them up
		foreach($user_stats as $user)  {
			$user = json_decode($user->meta_value);
			foreach($user->$year as $month_num => $total) {
			   $report->$year->$month_num += $total;
			}            
		}   
	}
	return $report;
}

function smsify_get_yearly_stats_for_user($year, $user_id) {
	$meta_key = 'smsify_'.$year;
	$report = get_user_meta($user_id, $meta_key, true);

	if($report) {
		$report = json_decode($report);
	} else {
		$report = smsify_reporting_yearly_template($year);
	}
	
	return $report;
}

function smsify_update_usage($total, $year=null, $month=null) {
	if(!$year) {
		$year = date('Y');
	}        
	if(!$month) {
		$month = date('n');
	}
	$user_id = get_current_user_id();
	$meta_key = 'smsify_'.$year;
	$stats = get_user_meta($user_id, $meta_key, true);
	
	if($stats) {
		$stats = json_decode($stats);
	} else {
		$stats = smsify_reporting_yearly_template($year);        
	}
	$stats->$year->$month += $total;
	update_user_meta($user_id, $meta_key, json_encode($stats));
}

/**
 * Gets users that have sent SMS to at least one contact
 */
function smsify_get_users() {
	global $wpdb;    
	$meta_key = "smsify_2%"; //should be good for approx another 787 years
	
	$sql = "SELECT u.user_login, u.ID FROM " . $wpdb->prefix . "users u 
	INNER JOIN " . $wpdb->prefix . "usermeta um ON (u.ID = um.user_id) 
	WHERE um.meta_key LIKE %s
	GROUP BY u.ID";
	
	$users = $wpdb->get_results(
					$wpdb->prepare(
						$sql, $meta_key));
	return $users;   
}

function smsify_get_credits($key) {
	global $smsify_params;
	$args = array('timeout' => 30, 'sslverify' => false, 'headers' => array('x-smsify-key' => $key));
	$result = wp_remote_get($smsify_params->apiEndpoint . "/account/credits", $args);
	// error_log(print_r($result, true));
	return $result;
}

function smsify_get_cf7forms() {
	$args = array(
		'numberposts' => 10,
		'post_type'   => 'wpcf7_contact_form',
		'fields' => array('ID', 'post_title', 'post_status')
	);

	return get_posts( $args );
}

function smsify_get_cf7_mobiles() {
	return unserialize(get_site_option('smsify_integrations'));
}

function smsify_get_integration_webhooks() {
	$smsify_webhooks = unserialize(get_site_option('smsify_webhooks'));
	if (!isset($smsify_webhooks['outbound_sms_webhook_url'])) {
		$smsify_webhooks['outbound_sms_webhook_url'] = "";
	}
	return $smsify_webhooks;
}

add_action("smsify_notify_webhook", "smsify_notify_webhook_handler");  
function smsify_notify_webhook_handler($args) {
	$payload = array('timeout' => 30, 
					"sslverify" => false,
					"method" => "POST",
					"blocking" => false,
					"headers" => ['Content-Type' => 'application/json'],
					"body" => $args->body,
				);
	$result = wp_remote_post($args->webhook_url, $payload);
	return $result;
}

add_action("wpcf7_before_send_mail", "smsify_cf7_before_send");  
function smsify_cf7_before_send($cf7) {
	// get the contact form object
	$wpcf = WPCF7_ContactForm::get_current();
	$submission = WPCF7_Submission::get_instance();
	$submission = $submission->get_posted_data();

	$smsify_integration_mobiles = smsify_get_cf7_mobiles();
	if(isset($smsify_integration_mobiles['smsify_cf7_notify_'.$wpcf->id]) && strlen($smsify_integration_mobiles['smsify_cf7_notify_'.$wpcf->id])) {
		// error_log(print_r($submission, true));
		$_POST['message'] = str_ireplace('[message]', $submission['your-message'], $smsify_integration_mobiles['smsify_cf7_message_' . $wpcf->id]);
		$_POST['message'] = str_ireplace('[name]', $submission['your-name'], $_POST['message']);
		$_POST['message'] = str_ireplace('[email]', $submission['your-email'], $_POST['message']);
		$_POST['message'] = substr($_POST['message'], 0, 150) . '...';

		$_POST['user_id'] = 0;
		$_POST['first_name'] = "";
		$_POST['last_name'] = "";
		$_POST['send_to'] = $smsify_integration_mobiles['smsify_cf7_notify_'.$wpcf->id];
		$_POST['method'] = 'post';
		$_POST['scheduler'] = false;
		$_POST['schedule_date_time'] = false;
		$_POST['run_every'] = 1;
		$_POST['run_times'] = 1;	
	}
	smsify_sms_handler(true);

	return $wpcf;
}

if(!function_exists("smsify_twoway_encrypt")) {	
	function smsify_twoway_encrypt($stringToHandle = "",$encryptDecrypt = 'e'){
		// Set default output value
		$output = null;
		// Set secret keys
		$secret_key = AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT;
		$key = hash('sha256',$secret_key);
		$iv = substr(hash('sha256',AUTH_SALT),0,16);
		// Check whether encryption or decryption
		if($encryptDecrypt == 'e'){
		// We are encrypting
		$output = base64_encode(openssl_encrypt($stringToHandle,"AES-256-CBC",$key,0,$iv));
		}else if($encryptDecrypt == 'd' && strlen(trim($stringToHandle)) !== 32 ){
		// We are decrypting
		$output = openssl_decrypt(base64_decode($stringToHandle),"AES-256-CBC",$key,0,$iv);
		} else {
			// If API key is stored unencrpyted, don't try to decrypt it.
			$output = $stringToHandle;
		}
		return $output;
	}
}