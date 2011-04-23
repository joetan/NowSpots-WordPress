<?php
/*
Plugin Name: NowSpots
Author: Joe Tan (joetan54@gmail.com)
*/

if (!defined('NOWSPOTS_TEMPLATES_DIR')) define('NOWSPOTS_TEMPLATES_DIR', dirname(__FILE__).'/templates/');
if (!defined('NOWSPOTS_CLASSES_DIR')) define('NOWSPOTS_CLASSES_DIR', dirname(__FILE__).'/classes/');

if (!function_exists('pr')) { function pr($o) { echo '<pre>';print_r($o);echo '</pre>'; } }

class NowSpotsAds {
	var $_version = '1.0';
	var $_capability = 'edit_themes';
	var $_pages = array(
		//'nowspots' => 'NowSpots',
		'advertisers' => 'Advertisers',
		'ads' => 'Ads',
		'updates' => 'Updates',
	);
	var $_slug = 'nowspots';

	function __construct() {
		add_action('init', array(&$this, 'init'));
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
		wp_schedule_event(time()+100, 'hourly', 'nowspots_cron');
	}
	function init() {
		add_theme_support( 'post-thumbnails' );
		add_image_size('nowspots-ad-image', 140, 89, true);
		wp_enqueue_style('nowspots', '/wp-content/plugins/nowspots/css/ads.css', array(), $this->_version);
	}
	function widgets_init() {
		register_widget('NowSpots_Widget');
	}
	function admin_init() {
		add_action('wp_ajax_nowspots_get_accounts', array(&$this, 'ajax_get_accounts'));
		add_action('wp_ajax_nowspots_refresh', array(&$this, 'ajax_refresh'));
		add_action('wp_ajax_nowspots_toggle_update', array(&$this, 'ajax_toggle_update'));
		add_action("load-toplevel_page_{$this->_slug}", array(&$this, 'admin_scripts'));
		foreach ($this->_pages as $page => $name) {
			add_action("load-{$this->_slug}_page_{$this->_slug}-{$page}", array(&$this, 'admin_scripts'));
			add_action("load-{$this->_slug}_page_{$this->_slug}-{$page}", array(&$this, "settings_handler_{$page}"));
		}
		add_action("load-{$this->_slug}_page_{$this->_slug}-ads", 'add_thickbox');
//		$baseurl = plugins_url('', __FILE__);
		
		// image selection
		if ($_REQUEST['selection'] && $_REQUEST['selection'] == 'nowspots') {
			add_filter('attachment_fields_to_edit', array(&$this, 'selection_image_fields_to_edit'), 20, 2);
			add_action('post-upload-ui', array(&$this, 'selection_upload_ui'));
		}
	}
	function admin_scripts() {
		$baseurl = '/wp-content/plugins/nowspots';
		wp_enqueue_script('nowspots-admin', $baseurl.'/js/admin.js', array('jquery'), $this->_version);
		wp_enqueue_style('nowspots-admin', $baseurl.'/css/admin.css', array(), $this->_version);
		wp_localize_script('nowspots-admin', 'NowSpotsAdmin', array(
			'refresh_nonce' => wp_create_nonce( 'refresh' ),
			'save_nonce' => wp_create_nonce( 'save' ),
			)
		);
	}
	function admin_menu() {

		add_menu_page(__('NowSpots', 'nowspots'), __('NowSpots', 'nowspots'), $this->_capability, $this->_slug, array(&$this, 'settings'));
		foreach ($this->_pages as $page => $name) {
			add_submenu_page($this->_slug, __($name, 'nowspots'), __($name, 'nowspots'), $this->_capability, "{$this->_slug}-{$page}", array(&$this, "settings_{$page}"));
		}
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
	
	function ajax_get_accounts() {
		require_once NOWSPOTS_CLASSES_DIR.'Social.php';
		$id = (int) $_POST['AdvertiserID'];
		if ($id) {
			$accounts = NowSpots::find('NowSpots_SocialMediaAccounts', array('AdvertiserID' => $id));
			include(NOWSPOTS_TEMPLATES_DIR.'ad-form-accounts.html');
		} else {
			echo 'Error loading.';
		}
		die(0);
	}
	
	function ajax_refresh() {
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		$advertisers = NowSpots::getAll('NowSpots_Advertisers');
		foreach ($advertisers as $advertiser) {
			$services = $advertiser->getServices();
			echo 'Updating '.$advertiser->Name.'...<br />';
			foreach ($services as $service) {
				echo 'Fetching '.$service->Name.' ('.$service->URL.') ';
				try {
					if ($updates = $service->refresh()) {
						echo 'Done.';
					} else {
						echo 'Done, but no updates found.';
					}
				} catch (NowSpots_Exception $e) {
					echo 'ERROR: '.$e->getMessage();
					error_log($e->getMessage());
				}
				
				echo '<br />';
				flush();
			}
			echo '<hr />';
			flush();
		}
		echo 'Done. Reload the page to view updates.';
		
		if ($_POST['action'] == 'nowspots_refresh') { // ajax refresh
		}
		
		die(0);
		
	}
	function ajax_toggle_update() {
		if ($_POST['active'] && $_POST['active'] != 'false') {
			$active = 'Active';
		} else {
			$active = 'Inactive';
		}
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		$update = NowSpots::get('NowSpots_SocialMediaAccountUpdates', $_POST['id']);
		$update->update('Status', $active);
		echo json_encode(array(
			'active' => $active == 'Active',
		));
		die(0);
	}
	
	
	function settings() {
		
		include(NOWSPOTS_TEMPLATES_DIR.'index.html');
	}
	
	function settings_handler_advertisers() {
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		if ($_POST) {
			switch ($_POST['_action']) {
				case 'new':
				
					$fields = array();
					foreach (array('Name') as $field) {
						$fields[$field] = stripslashes($_POST[$field]);
					}
					$fields['Status'] = 'Active';
					$advertiser = NowSpots::create('NowSpots_Advertisers', $fields);
					
					$accounts = $this->post_vars('SocialMediaAccount');
					$advertiser->setServices($accounts);
					wp_redirect(add_query_arg(array('page' => 'nowspots-ads', '_action' => 'add', 'advertiser-id' => $advertiser->id)));
					exit;
				break;
				case 'save':
					$fields = array();
					foreach (array('Name', 'Status') as $field) {
						$fields[$field] = stripslashes($_POST[$field]);
					}
					if (!$fields['Status']) $fields['Status'] = 'Active';
					$advertiser = NowSpots::get('NowSpots_Advertisers', $_POST['id']);
					$advertiser->update($fields);
					
					$accounts = $this->post_vars('SocialMediaAccount');
					$advertiser->setServices($accounts);
					wp_redirect(add_query_arg(array( '_action' => null, 'id' => null, 'updated' => true)));
					exit;
					
				break;
			}
		}
	}
	function settings_advertisers() {
		
		if ($_GET['_action']) {
			switch ($_GET['_action']) {
				case 'edit':
					$advertiser = NowSpots::get('NowSpots_Advertisers', $_GET['id']);
					$action = 'save';
					$submit = 'Save Advertiser Information';
					include(NOWSPOTS_TEMPLATES_DIR.'advertiser-form.html');
					
				break;
				case 'deactivate':
					$advertiser = NowSpots::get('NowSpots_Advertisers', $_GET['id']);
					$advertiser->update('Status', 'Inactive');
					$message = 'Deactivated '.$advertiser->AdvertiserName;
					echo 'Done';
				break;
				case 'activate':
					$advertiser = NowSpots::get('NowSpots_Advertisers', $_GET['id']);
					$advertiser->update('Status', 'Active');
					$message = 'Activated '.$advertiser->AdvertiserName;;
					echo 'Done';
				break;
				case 'add':
					$advertiser = NowSpots::blank('NowSpots_Advertisers');
					$action = 'new';
					$submit = 'Add this Advertiser';
					include(NOWSPOTS_TEMPLATES_DIR.'advertiser-form.html');
				break;
				case 'statuses':
					$advertiser = NowSpots::get('NowSpots_Advertisers', $_GET['id']);
					$updates = $advertiser->getStatusUpdates();
				break;
			}
		} else {
			
			$advertisers = NowSpots::getAll('NowSpots_Advertisers');
			include(NOWSPOTS_TEMPLATES_DIR.'advertisers.html');
		}
		
		
	}
	function settings_handler_ads() {
		require_once NOWSPOTS_CLASSES_DIR.'Ads.php';
		require_once NOWSPOTS_CLASSES_DIR.'Advertisers.php';
		require_once NOWSPOTS_CLASSES_DIR.'Templates.php';
		require_once NOWSPOTS_CLASSES_DIR.'Social.php';
		if ($_POST) {
			switch ($_POST['_action']) {
				case 'new':
					$fields = array();
					foreach (array('AdvertiserID', 'SocialMediaAccountID', 'Name', 'Template', 'Image') as $field) {
						$fields[$field] = stripslashes($_POST['Ad'][$field]);
					}
					$fields['Status'] = 'Active';
					$ad = NowSpots::create('NowSpots_Ads', $fields);
					wp_redirect(add_query_arg(array('_action' => 'review', 'id' => $ad->id)));
					exit;
					
				break;
				case 'save':
					$fields = array();
					foreach (array('AdvertiserID', 'SocialMediaAccountID', 'Name', 'Template', 'Image', 'Status') as $field) {
						$fields[$field] = stripslashes($_POST['Ad'][$field]);
					}
					$ad = NowSpots::get('NowSpots_Ads', $_POST['id']);
					$ad->update($fields);
					wp_redirect(add_query_arg(array('_action' => null, 'id' => null)));
					exit;
				break;
			}
		
		}
	}
	
	function settings_ads() {
		 if ($_GET['_action']) {
			$templates = NowSpots_Templates::getAll();
			switch ($_GET['_action']) {
				case 'add':
					$action = 'new';
					$submit = 'Create New Ad';
					$ad = NowSpots::blank('NowSpots_Ads');
					if ($_GET['advertiser-id']) {
						$advertiser = NowSpots::get('NowSpots_Advertisers', $_GET['advertiser-id']);
						$accounts = NowSpots::find('NowSpots_SocialMediaAccounts', array('AdvertiserID' => $advertiser->id));
					} else {
						$advertisers = NowSpots::getAll('NowSpots_Advertisers');
					}
					include(NOWSPOTS_TEMPLATES_DIR.'ad-form.html');
				break;
				case 'edit':
					$action = 'save';
					$submit = 'Save Ad';
					$ad = NowSpots::get('NowSpots_Ads', $_GET['id']);
					if ($ad->AdvertiserID) {
						$advertiser = NowSpots::get('NowSpots_Advertisers', $ad->AdvertiserID);
						$accounts = NowSpots::find('NowSpots_SocialMediaAccounts', array('AdvertiserID' => $ad->AdvertiserID));
					} else {
						$advertisers = NowSpots::getAll('NowSpots_Advertisers');
					}
					include(NOWSPOTS_TEMPLATES_DIR.'ad-form.html');
				break;
				case 'review';
					$ad = NowSpots::get('NowSpots_Ads', $_GET['id']);
					$updates = $ad->getAllRecentUpdates();
					include(NOWSPOTS_TEMPLATES_DIR.'ad-review.html');
					include(NOWSPOTS_TEMPLATES_DIR.'updates.html');
				break;
			}
		} else {
			$ads = NowSpots::getAll('NowSpots_Ads');
			include(NOWSPOTS_TEMPLATES_DIR.'ads.html');
		}
	}
	
	function settings_handler_updates() {
	}
	function settings_updates() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		$updates = NowSpots::fetch_recent('NowSpots_SocialMediaAccountUpdates');
		
		include(NOWSPOTS_TEMPLATES_DIR.'updates.html');
	}
	
	
	
	function selection_image_fields_to_edit($fields, $attachment) {
		$url = wp_get_attachment_url($attachment->ID);
		add_action('admin_print_footer_scripts', array(&$this, 'hide_wpgallery'), 100);
		add_action('admin_print_footer_scripts', array(&$this, 'selection_upload_ui'), 100);
		
		unset($fields['image_alt'], $fields['post_content'], $fields['post_excerpt'], $fields['url'], $fields['image_url'], $fields['align'], $fields['image-size']);
		if ($thumbnail = wp_get_attachment_image_src($attachment->ID, 'nowspots-ad-image')) {
			$thumbnail_src = $thumbnail[0];
		} else {
			$thumbnail_src = false;
		}
		
		$fields['select-image'] = array(
			'label' => 'URL',
			'value' => $url,
			'type' => 'hidden',
			'extra_rows' => array(
				'select' => 
				'<input type="button" class="button button-secondary" value="Select" onclick="set_upload_selection('.($thumbnail_src ? "'$thumbnail_src'" : 'document.getElementById(\'attachments['.$attachment->ID.'][select-image]\').value').', \''.$_REQUEST['selection'].'\', '.$attachment->ID.')" />'
				),
		);
		return $fields;
	}
	function selection_upload_ui() {
		?><script type="text/javascript">
		function prepareMediaItem(fileObj, serverData) {
			var f = ( typeof shortform == 'undefined' ) ? 1 : 2, item = jQuery('#media-item-' + fileObj.id);
			// Move the progress bar to 100%
			jQuery('.bar', item).remove();
			jQuery('.progress', item).hide();
		
			try {
				if ( typeof topWin.tb_remove != 'undefined' )
					topWin.jQuery('#TB_overlay').click(topWin.tb_remove);
			} catch(e){}
		
			// Old style: Append the HTML returned by the server -- thumbnail and form inputs
			if ( isNaN(serverData) || !serverData ) {
				item.append(serverData);
				prepareMediaItemInit(fileObj);
			}
			// New style: server data is just the attachment ID, fetch the thumbnail and form html from the server
			else {
				item.load('async-upload.php', {attachment_id:serverData, fetch:f, selection:1}, function(){
					prepareMediaItemInit(fileObj);updateMediaForm()
					jQuery("#media-upload table.widefat, #sort-buttons, #gallery-settings, tr.align, tr.image-size, tr.submit td.savesend input.button, .wp-post-thumbnail").hide();
					
					
					});
			}
		}
		function set_upload_selection(url, sid, attachment_id) {
			var win = window.dialogArguments || opener || parent || top;
			win.set_upload_selection(url, sid, attachment_id);
			win.tb_remove();
		}
		
		</script><?php
	}
		
		
	function hide_wpgallery() {
		?><script type="text/javascript">
			jQuery(function($) { $("#media-upload table.widefat, #sort-buttons, #gallery-settings, tr.align, tr.image-size, tr.submit td.savesend input.button, .wp-post-thumbnail").hide();  });
		</script><?php
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
		$ads = NowSpots::getAll('NowSpots_Ads');
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
function nowspot_ad($ad_id, $template=false) {
	echo nowspot_get_ad($ad_id, $template);
}
function nowspot_get_ad($ad_id, $template=false) {
	require_once NOWSPOTS_CLASSES_DIR.'Ads.php';

	$ad = NowSpots::get('NowSpots_Ads', $ad_id);
	if ($ad->Status != 'Active') return;
	
	$html = $ad->render($template);

	
	// TODO: transaction logging
	
}
$NowSpotsAds = new NowSpotsAds(); // initialize!
