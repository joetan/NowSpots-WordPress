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
		add_action('widgets_init', array(&$this, 'widgets_init'));
		add_action('admin_init', array(&$this, 'admin_init'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
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
		wp_schedule_event(time(), 'hourly', 'nowspots_cron');
	}
	function widgets_init() {
		register_widget('NowSpots_Widget');
	}
	function admin_init() {
		add_action('wp_ajax_nowspots_refresh', array(&$this, 'refresh'));
//		$baseurl = plugins_url('', __FILE__);
		$baseurl = '/../wp-content/plugins/nowspots';
		wp_enqueue_script('nowspots-admin', $baseurl.'/js/admin.js', array('jquery'), $this->_version);
		wp_localize_script('nowspots-admin', 'NowSpotsAdmin', array(
			'refresh_nonce' => wp_create_nonce( 'refresh' ),
			'save_nonce' => wp_create_nonce( 'save' ),
			)
		);
		
		
	}
	function admin_menu() {
		$slug = 'nowspots';
		add_menu_page(__('NowSpots', 'nowspots'), __('NowSpots', 'nowspots'), $this->_capability, $slug, array(&$this, 'settings'));
		add_submenu_page($slug, 'Advertisers', 'Advertisers', $this->_capability, $slug.'-advertisers', array(&$this, 'settings_advertisers'));
		add_submenu_page($slug, 'Ads', 'Ads', $this->_capability, $slug.'-ads', array(&$this, 'settings_ads'));
		add_submenu_page($slug, 'Updates', 'Updates', $this->_capability, $slug.'-updates', array(&$this, 'settings_updates'));
	}
	
	function post_vars($key) {
		$data = array();
		foreach ($_POST[$key] as $field => $fields) {
			foreach ($fields as $k => $v) {
				$data[$k][$field] = stripslashes($v);
			}
		}
		
		return $data;
		
	}
	
	function cron() {
		error_log('starting cron');
	}
	
	function refresh() {
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		$advertisers = NowSpots_Advertisers::getAll();
		foreach ($advertisers as $advertiser) {
			$services = $advertiser->getServices();
			echo 'Updating '.$advertiser->Name.'...<br />';
			foreach ($services as $service) {
				echo 'Fetching '.$service->Name.' ('.$service->URL.') ';
				if ($updates = $service->refresh()) {
					echo 'Done';
				} else {
					echo 'Error';
				}
				
				echo '<br />';
				flush();
			}
			
			echo '<hr /><br /><br />';
			flush();
		}
		echo 'Done. Reload the page to view updates.';
		
		if ($_POST['action'] == 'nowspots_refresh') { // ajax refresh
		}
		
		die(0);
		
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
					
					$accounts = $this->post_vars('SocialMediaAccount');
					$advertiser->setServices($accounts);
					echo 'Done';
				break;
				case 'save':
					$fields = array();
					foreach (array('Name') as $field) {
						$fields[$field] = stripslashes($_POST[$field]);
					}
					$fields['Status'] = 'Active';
					$advertiser = NowSpots_Advertisers::get($_POST['id']);
					$advertiser->update($fields);
					
					$accounts = $this->post_vars('SocialMediaAccount');
					$advertiser->setServices($accounts);
					echo 'done';
				break;
			}
		} elseif ($_GET['action']) {
			switch ($_GET['action']) {
				case 'edit':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$action = 'save';
					$submit = 'Save Advertiser Information';
					include(NOWSPOTS_TEMPLATES_DIR.'advertiser-form.html');
					
				break;
				case 'deactivate':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$advertiser->update('Status', 'Inactive');
					$message = 'Deactivated '.$advertiser->AdvertiserName;
					echo 'Done';
				break;
				case 'activate':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$advertiser->update('Status', 'Active');
					$message = 'Activated '.$advertiser->AdvertiserName;;
					echo 'Done';
				break;
				case 'add':
					$advertiser = NowSpots_Advertisers::blank();
					$action = 'new';
					$submit = 'Add this Advertiser';
					include(NOWSPOTS_TEMPLATES_DIR.'advertiser-form.html');
				break;
				case 'statuses':
					$advertiser = NowSpots_Advertisers::get($_GET['id']);
					$updates = $advertiser->getStatusUpdates();
				break;
			}
		} else {
			
			$advertisers = NowSpots_Advertisers::getAll();
			include(NOWSPOTS_TEMPLATES_DIR.'advertisers.html');
		}
		
		
	}
	function settings_ads() {
		require_once NOWSPOTS_CLASSES_DIR.'Ads.php';
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		require_once NOWSPOTS_CLASSES_DIR.'Templates.php';
		if ($_POST) {
			switch ($_POST['action']) {
				case 'new':
					$fields = array();
					foreach (array('AdvertiserID', 'Name', 'Template') as $field) {
						$fields[$field] = stripslashes($_POST['Ad'][$field]);
					}
					$fields['Status'] = 'Active';
					$ad = NowSpots_Ads::create($fields);
					echo 'done';
					
				break;
				case 'save':
					$fields = array();
					foreach (array('AdvertiserID', 'Name', 'Template', 'Status') as $field) {
						$fields[$field] = stripslashes($_POST['Ad'][$field]);
					}
					$ad = NowSpots_Ads::get($_POST['id']);
					$ad->update($fields);
					echo 'done';
				break;
			}
		
		} elseif ($_GET['action']) {
			$advertisers = NowSpots_Advertisers::getAll();
			$templates = NowSpots_Templates::getAll();
			switch ($_GET['action']) {
				case 'add':
					$action = 'new';
					$submit = 'Create New Ad';
					$ad = NowSpots_Ads::blank();
					include(NOWSPOTS_TEMPLATES_DIR.'ad-form.html');
				break;
				case 'edit':
					$action = 'save';
					$submit = 'Save Ad';
					$ad = NowSpots_Ads::get($_GET['id']);
					include(NOWSPOTS_TEMPLATES_DIR.'ad-form.html');
				break;
				case 'review';
					$ad = NowSpots_Ads::get($_GET['id']);
					$updates = $ad->getRecentUpdates();
					include(NOWSPOTS_TEMPLATES_DIR.'ad-review.html');
					include(NOWSPOTS_TEMPLATES_DIR.'updates.html');
				break;
			}
		} else {
			$ads = NowSpots_Ads::getAll();
			include(NOWSPOTS_TEMPLATES_DIR.'ads.html');
		}
	}
	function settings_updates() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		$updates = NowSpots_SocialMediaAccountUpdates::fetch_recent();
		
		include(NOWSPOTS_TEMPLATES_DIR.'updates.html');
	}
	
	
}

class NowSpots_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(false, $name = 'NowSpots');	
	}
	function widget($args, $instance) {
		extract( $args );
		$ad_id = $instance['ad_id'];
        
        echo $before_widget; 
		if ( $title ) echo $before_title . $title . $after_title; 
		nowspot_ad($ad_id);
		echo $after_widget;

		
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['ad_id'] = strip_tags($new_instance['ad_id']);
		return $instance;
	}
	function form($instance) {
		require_once NOWSPOTS_CLASSES_DIR.'Ads.php';
		$ads = NowSpots_Ads::getAll();
		?>
		<label for="<?php echo $this->get_field_id('ad_id'); ?>">Select ad to display:</label> <br />
		<select name="<?php echo $this->get_field_name('ad_id'); ?>">
		<option></option>
		<?php foreach ($ads as $ad):?>
		<option value="<?php echo $ad->id;?>" <?php if ($ad->id == $instance['ad_id']) echo 'selected="SELECTED"';?> ><?php echo $ad->Name;?> (<?php echo $ad->Template;?>)</option>
		<?php endforeach;?>
		</select> 
		          
		<?php
	}
}

function nowspots_cron() {
	NowSpotsAds::cron();
}
function nowspot_ad($ad_id) {
	echo nowspot_get_ad($ad_id);
}
function nowspot_get_ad($ad_id) {
	require_once NOWSPOTS_CLASSES_DIR.'Ads.php';

	$ad = NowSpots_Ads::get($ad_id);
	if ($ad->Status != 'Active') return;
	
	$update = $ad->getMostRecentUpdate();
	echo '--- this is the ad code --';
	pr($ad);
	pr($update);
	echo '-- end ad code --';
	// TODO: transaction logging
	
}
$NowSpotsAds = new NowSpotsAds(); // initialize!
