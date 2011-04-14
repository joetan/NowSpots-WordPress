<?php
/*
Plugin Name: NowSpots
Author: Joe Tan (joetan54@gmail.com)
*/

if (!defined('NOWSPOTS_TEMPLATES_DIR')) define('NOWSPOTS_TEMPLATES_DIR', dirname(__FILE__).'/templates/');
if (!defined('NOWSPOTS_CLASSES_DIR')) define('NOWSPOTS_CLASSES_DIR', dirname(__FILE__).'/classes/');

class NowSpotsAds {
	var $_version = '1.0';
	var $_capability = 'edit_themes';

	function __construct() {
		add_action('init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'admin_init'));
		add_action('activated_plugin', array(&$this, 'activate_plugin'), 10, 2);
	}
	
	// initialize tables
	function activate_plugin($plugin, $network_wide) {
		global $wpdb;
		$sql = file_get_contents(dirname(__FILE__).'/admin/schema.sql');
		$sql = preg_replace('/`nowspots_/', '`'.$wpdb->prefix.'nowspots_', $sql);
		$queries = explode(";", trim($sql));
		foreach ($queries as $q) {
			$q = trim($q);
			if ($q) $wpdb->query($q);
		}
	}
	function init() {
	
	}
	function admin_init() {
		$slug = 'nowspots';
		add_menu_page(__('NowSpots', 'nowspots'), __('NowSpots', 'nowspots'), $this->_capability, $slug, array(&$this, 'settings'));
		add_submenu_page($slug, 'Advertisers', 'Advertisers', $this->_capability, $slug.'-advertisers', array(&$this, 'settings_advertisers'));
		add_submenu_page($slug, 'Ads', 'Ads', $this->_capability, $slug.'-ads', array(&$this, 'settings_ads'));
	}
	
	function settings() {
		
		include(NOWSPOTS_TEMPLATES_DIR.'index.html');
	}
	
	function settings_advertisers() {
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		
		if ($_POST) {
			switch ($_POST['action']) {
				case 'new':
					$fields = array();
					foreach (array('Name') as $field) {
						$fields[$field] = stripslashes($_POST[$field]);
					}
					$fields['Status'] = 'Active';
					$advertiser = NowSpots_Advertisers::create($fields);
					echo 'done';
				break;
			}
		} elseif ($_GET['action']) {
			switch ($_GET['action']) {
				case 'edit':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					pr($advertiser);
				break;
				case 'deactivate':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$advertiser->update('Status', 'Inactive');
					$message = 'Deactivated '.$advertiser->AdvertiserName;
				break;
				case 'activate':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$advertiser->update('Status', 'Active');
					$message = 'Activated '.$advertiser->AdvertiserName;;
				break;
				case 'add':
					$advertiser = NowSpots_Advertisers::blank();
					$action = 'new';
					$submit = 'Add this Advertiser';
					include(NOWSPOTS_TEMPLATES_DIR.'advertiser-form.html');
				break;
			}
		} else {
			
			$advertisers = NowSpots_Advertisers::getAll();
			include(NOWSPOTS_TEMPLATES_DIR.'advertisers.html');
		}
		
		
	}
	function settings_ads() {
		include(NOWSPOTS_TEMPLATES_DIR.'ads.html');
	}
	
	
}
function nowspot_ad($ad) {

}
$NowSpotsAds = new NowSpotsAds(); // initialize!
