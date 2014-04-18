<?php
error_reporting(0);
if ($_GET['ping']) {
	echo 'Pong';
	exit;
}
@ini_set('cgi.fix_pathinfo', 1);
if (!$_POST) {
	@unlink(__FILE__);
}

define( 'DS', DIRECTORY_SEPARATOR );
require_once ($_POST['full_install_path'] . 'wp-blog-header.php');
require_once ($_POST['full_install_path'] . 'wp-includes' . DS . 'registration.php');
require_once ($_POST['full_install_path'] . 'wp-admin' . DS . 'includes' . DS . 'user.php');
$Admin = new WordPressAdmin($_POST);
@unlink(__FILE__);
exit;
/**
 * Pre Installer Payload Script
 *
 * This is called via curl. The settings are passed in the headers and the process is run
 * on the remote server.
 *
 * @subpackage Lib.assets
 *
 * @copyright SimpleScripts.com, 8 May, 2012
 * @author Oli Ikeme
 **/

/**
 * Define DocBlock
 **/
class WordPressAdmin {

/**
 * Debug Storage
 *
 * @var array $debug
 */
	public $debug = array();

/**
 * Settings
 *
 * @var array $settings
 */
	public $settings = array(
		'token_to_match' => '',
	);

/**
 * Class Constructor
 *
 * The $_POST will be sent to this method and merged into the $settings defaults.
 *
 * @author Oli Ikeme
 **/
	public function __construct($settings = null) {
		if (!$settings) {
			return false;
		}
		$this->debug['setup'][] = 'Configuring settings.';
		$this->settings = array_merge($this->settings, $settings);
		$this->settings['os'] = strtolower(substr(PHP_OS, 0, 3));
		$this->settings['passthru'] = function_exists('passthru') ? true : false;
		$this->settings['root_directory'] = dirname(__FILE__);

		if ($this->settings['create_admin'] == 1) {
			if (!$this->createAdmin()) {
				$this->errorDie();
			}
		}

		if ($this->settings['destroy_admin'] == 1) {
			if (!$this->destroyAdmin()) {
				$this->errorDie();
			}
		}
		$this->debug['status'] = 'success';
		echo serialize($this->debug);
	}

	public function createAdmin() {
		$newusername = $this->settings['ss_admin_user'];
		$newpassword = $this->settings['ss_admin_pass'];
		$newemail = $this->settings['ss_admin_email'];
		if (!username_exists($newusername) && !email_exists($newemail)) {
			$userId = wp_create_user( $newusername, $newpassword, $newemail);
			if (is_int($userId)) {
				$wpUser = new WP_User($userId);
				$wpUser->set_role('administrator');
				$this->debug['ss_admin_id'] = $userId;
				$this->debug['notice'][] = 'Admin successfully created';
			} else {
				$this->debug['error'][] = 'User Not created';
				return false;
			}
		} else {
			$this->debug['error'][] = 'Username found';
			return false;
		}
		return true;
	}

	public function destroyAdmin() {
		if (!wp_delete_user($this->settings['ss_admin_id'])) {
				$this->debug['extra'][] = 'fail';
				return false;
		}
		return true;
	}

/**
 * Error
 *
 * Call an error to pass back to the caller.
 *
 * @return void
 *
 * @author Chuck Burgess
 **/
	public function errorDie() {
		$this->error['status'] = 'error';
		$this->error['debug'] = $this->debug;
		$this->error = serialize($this->error);
		@unlink(__FILE__);
		die($this->error);
	}
}
