<?php
/**
 *
 * Copyright: © 2022
 * {@link https://cloudinn.io/products/smsify SMSify.}
 * ( coded in Australia )
 *
 * Released under the terms of the GNU General Public License.
 * You should have received a copy of the GNU General Public License,
 * along with this software. In the main directory, see: /licensing/
 * If not, see: {@link http://www.gnu.org/licenses/}. 
 *
 * @package SMSify
 * @version 6.0.2
 */
/*
Plugin Name: SMSify
Plugin URI: https://cloudinn.io/products/smsify
Description: <strong>SMSify</strong> is a premium SMS plugin that allows you to <strong>send and receive SMS</strong> within your own WordPress dashboard. SMSify allows you to <strong>import contacts</strong> from a csv file and <strong>schedule recurring SMS messages</strong>.  It features a native WordPress interface that is very simple to use. Screenshots available.  
Author: Cloud Inn
Version: 6.0.2
Author URI: https://cloudinn.io/products/smsify
*/

if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

require_once 'includes/functions.php';
require_once 'modules/usergroups/UserGroupsExtended.php';
require_once 'modules/importusers/import-users-from-csv.php';

add_action( 'admin_menu', 'smsify_menu' );
function smsify_menu() {
	add_user_meta(get_current_user_id(), 'smsify-track-optin', 1, true); // ensure 'true' is set, which means no duplicate meta keys are allowed for the same user.

	$smsify_page = add_menu_page( 'SMSify', 'SMSify', 'manage_options', 'smsify-settings', null, plugin_dir_url( __FILE__ ) . 'images/sms_icon.png' );
	$smsify_page_settings = add_submenu_page( 'smsify-settings', 'SMSify - settings', 'Settings', 'manage_options', 'smsify-settings', 'smsify_settings');
	$smsify_page_groups = add_submenu_page( 'smsify-settings', 'SMSify - groups', 'User Groups', 'manage_options', 'edit-tags.php?taxonomy=user-group');
	$smsify_page_integrations = add_submenu_page( 'smsify-settings', 'SMSify - integrations', 'Integrations', 'manage_options', 'smsify-integrations', 'smsify_integrations');
	$smsify_page_responses = add_submenu_page( 'smsify-settings', 'SMSify - responses', 'Responses', 'manage_options', 'smsify-responses', 'smsify_responses');
	$smsify_page_schedules = add_submenu_page( 'smsify-settings', 'SMSify - schedules', 'Schedules', 'manage_options', 'smsify-schedules', 'smsify_schedules');
	$smsify_page_reporting = add_submenu_page( 'smsify-settings', 'SMSify - reporting', 'Reporting', 'manage_options', 'smsify-reporting', 'smsify_reporting');


	if (get_user_meta(get_current_user_id(), 'smsify-track-optin', true))	{
		$smsify_params = smsify_getConfig();
		wp_register_script('smsify-freshpaint', 
							$smsify_params->jsurl . '/smsify-custom.js', 
							array(), 
							$smsify_params->appVersion);
		wp_enqueue_script('smsify-freshpaint');
	}
}

function smsify_settings() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$smsify_params = smsify_getConfig();
	wp_enqueue_script('smsify-sms-controller');
	$validationMessage = "";
	$valid_key = false;
	$smsify_enable_sender_id_override = false;
	$credits = 0;
	$smsify_user_optin = intval(get_user_meta(get_current_user_id(), 'smsify-track-optin', true));

	// handle tracking opt-in
	if(isset($_POST['activate']) || isset($_POST['update'])) {
		if(isset($_POST['smsify-user-optin'])) {
			update_user_meta(get_current_user_id(), 'smsify-track-optin', 1);
			$smsify_user_optin = true;
		} else {
			update_user_meta(get_current_user_id(), 'smsify-track-optin', 0);
			$smsify_user_optin = false;            
		}
	}

	if(isset($_POST['activate'])) {
		if(strlen(trim($_POST['apiKey'])) === 32) {
			$api_key = trim($_POST['apiKey']);
			$response = smsify_get_credits($api_key);
			if ( is_wp_error( $response ) ) {
				$validationMessage = $response->get_error_message();
			}else if($response['response']['code'] != 200) {
				$validationMessage = __($response['response']['message']);
			} else {
				$credits = json_decode($response['body'])->result;

				update_site_option('smsify-api-key', smsify_twoway_encrypt($api_key));
				if($credits > 0) {    
					$validationMessage = __("Your API Key has been validated successfully. You can now start sending SMS messages to your users.");
				} else {
					$validationMessage = __("Your API Key has been validated successfully, but you don't seem to have any credits on your account. Please recharge your account to continue sending SMS.");
				}
				$valid_key = true;
			}
		} else {
			$validationMessage = __("Your settings have been updated, but you will not be able to use the plugin until you get your API key. Go to <a href='https://cloudinn.io/products/smsify?ref=pluginactivation' target='_blank'>SMSify plugin homepage</a> to get your API key.");
		}
	} else if(isset($_POST['deactivate'])) {
		delete_site_option('smsify-api-key');
		delete_site_option('smsify_enable_sender_id_override');
		$validationMessage = __("Before you start using SMSify, please activate the plugin by pasting your API Key in the text field provided.");
	} else if(isset($_POST['update'])) {
		if(strlen(trim($_POST['apiKey'])) === 32) {
			update_site_option('smsify-api-key', smsify_twoway_encrypt(trim($_POST['apiKey'])));
			$response = smsify_get_credits(trim($_POST['apiKey']));
			$credits = json_decode($response['body'])->result;
		}
		if(isset($_POST['smsify-enable-sender-id-override'])) {
			update_site_option('smsify-enable-sender-id-override', 1);
			$smsify_enable_sender_id_override = true;
		} else {
			update_site_option('smsify-enable-sender-id-override', 0);
			$smsify_enable_sender_id_override = false;            
		}    
		if(trim($_POST['apiKey']) !== '--- Not shown ---' && strlen(trim($_POST['apiKey'])) !== 32) {
			$api_key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
			$valid_key = false;
			$validationMessage = __("Invalid API Key");
		} else {
			$api_key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
			$valid_key = true;
			$validationMessage = __("Your settings have been updated successfully");            
			$response = smsify_get_credits($api_key);
			$credits = json_decode($response['body'])->result;
		}
	} else {
		$api_key = smsify_twoway_encrypt(get_site_option('smsify-api-key'), 'd');
		$smsify_enable_sender_id_override = get_site_option('smsify-enable-sender-id-override');
		if($api_key) {
			$validationMessage = __("Your API Key has been validated successfully. You can now start sending SMS messages to your users.");
			$valid_key = true;
			$response = smsify_get_credits($api_key);
			
			if ( is_wp_error( $response ) ) {
				$validationMessage = $response->get_error_message();
			} else if($response['response']['code'] !== 200) {
				$validationMessage = __($response['body']);
			} else {
				$credits = json_decode($response['body'])->result;
			}
		} else {
			$validationMessage = __("Before you start using SMSify, please activate the plugin by pasting your API Key in the text field provided.");            
		}
	}

	require_once 'views/smsify-settings.php';
	do_action('smsify_after_settings_page');
}

add_action( 'show_user_profile', 'smsify_extra_user_profile_fields', 99998 );
add_action( 'edit_user_profile', 'smsify_extra_user_profile_fields', 99998 );
function smsify_extra_user_profile_fields( $user ) {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}    
	$smsify_params = smsify_getConfig();
	wp_enqueue_style('smsify');
	wp_enqueue_style('smsify-font-awesome');
	wp_enqueue_style('jquery-ui-1.10.3.custom.min');
	wp_enqueue_script('smsify-sms-controller');
	require_once 'views/smsify-send-user.php';
}

function smsify_schedules() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$validationMessage = "";
	$smsify_params = smsify_getConfig();
	wp_enqueue_style('smsify');
	wp_enqueue_style('smsify-font-awesome');
	wp_enqueue_script('smsify-sms-controller');
	
	$args = array('timeout' => 30, 'sslverify' => false, 'headers' => array('x-smsify-key' => $smsify_params->api_key));
	$response = wp_remote_get($smsify_params->apiEndpoint . "/schedule/list", $args);
	
	if ( is_wp_error( $response ) ) {
		$validationMessage = $response->get_error_message();
	}else if($response['response']['code'] != 200) {
		$validationMessage = __(json_decode($response['body'])->message);
		wp_die( __($validationMessage) );
	} else {
		$response = json_decode($response['body'])->result;
		require_once 'views/smsify-schedules.php';
	}
}

function smsify_reporting() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$smsify_params = smsify_getConfig();
	$current_year = date('Y');
	if(isset($_GET['year']) && is_numeric($_GET['year']) && strlen($_GET['year']) == 4) {
		$selected_year = $_GET['year'];
	} else {
		$selected_year = $current_year;
	}
	$grandtotal = 0;
	$user_id = 0;
	
	if(isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
	}
	 
	$stats = smsify_get_yearly_stats($selected_year, $user_id);
	$users = smsify_get_users();
	require_once 'views/smsify-reporting.php';
}

function smsify_responses() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$smsify_params = smsify_getConfig();
	wp_enqueue_style('smsify-font-awesome');
	wp_enqueue_script('smsify-sms-controller');
	$args = array('timeout' => 30, 'sslverify' => false, 'headers' => array('x-smsify-key' => $smsify_params->api_key));
	$response = wp_remote_get($smsify_params->apiEndpoint . "/account/data/responses", $args);
	if ( is_wp_error( $response ) ) {
		$validationMessage = $response->get_error_message();
	}else if($response['response']['code'] != 200) {
		$validationMessage = __(json_decode($response['body'])->message);
		wp_die( __($validationMessage) );
	} else {
		$account = json_decode($response['body'])->result;
		$responses = $account->responses;    
		require_once 'views/smsify-responses.php';
	}
	
}

function smsify_integrations() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$smsify_integration_notice = "";
	$smsify_integrations_tab = "webhooks";

	if (isset($_GET['tab'])) {
		$smsify_integrations_tab = $_GET['tab'];
	}

	switch($smsify_integrations_tab) {
		case 'webhooks':
			$smsify_integration_webhooks = array();

			if (isset($_POST['smsify_webhook_save'])) {
				update_site_option('smsify_webhooks', serialize($_POST));
				$smsify_integration_webhooks = $_POST;
				$smsify_integration_notice = "Your settings have been saved successfully.";
			} else {
				$smsify_integration_webhooks = smsify_get_integration_webhooks();
			}
			break;
		case 'contact7':
			$smsify_default_message = 'From: [name] [email] Message: [message]';
			$smsify_message_help = '[name], [email] and [message] variables will be replaced with the following Contact Form 7 form fields respectively: <ul><ol>your-name</ol><ol>your-email</ol><ol>your-message</ol></ul>';
			$smsify_integration_mobiles = array();
			$smsify_cf7_forms = smsify_get_cf7forms();

			if (isset($_POST['smsify_integration_save'])) {
				update_site_option('smsify_integrations', serialize($_POST));
				$smsify_integration_mobiles = $_POST;
				$smsify_integration_notice = "Your settings have been saved successfully.";
			} else {
				$smsify_integration_mobiles = smsify_get_cf7_mobiles();
			}
			break;
		default: 
			$smsify_integrations_tab = "webhooks";
			break;
	}

	require_once 'views/smsify-integrations.php';
}

// Add settings link on plugin page
function smsify_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=smsify-settings">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'smsify_settings_link' );
