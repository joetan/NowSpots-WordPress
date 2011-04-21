<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_SocialMediaAccounts extends NowSpots_Model {
	public 
		$AdvertiserID,
		$Type,
		$Name,
		$URL,
		$Data;
		
	protected function __construct(Array $properties) {
		if (!isset($properties['Data'])) {
			$properties['Data'] = '';
		}
		parent::__construct($properties);
	}
	
	public static function create(Array $properties) {
		if (!$properties['Type']) {
			throw new NowSpots_Exception('Missing Type');
		} else {
			return parent::create($properties);
		}
	}
	public function refresh() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		
		$updates = array();
		switch ($this->Type) {
			case 'Facebook':
				
			break;
			case 'Twitter':
				$parts = parse_url($this->URL);
				$path = $parts['path'];
				$frag = $parts['fragment'];
				if ($path == '/' && $frag) {
					$path = substr($frag, 1);
				}
				
				$screen_name = preg_replace('@^/([^/]*)/?.*@', '$1', $path);
				if (!$screen_name) {
					throw new NowSpots_Exception('Unable to parse social media acount id from '.$this->URL);
				}
				$response = wp_remote_get('http://api.twitter.com/1/statuses/user_timeline.json?'.http_build_query(array(
					'screen_name' => $screen_name,
					'trim_user' => true,
					
				)));
				if (!is_wp_error($response)) {
					$data = wp_remote_retrieve_body($response);
					$statuses = json_decode($data);
					foreach ($statuses as $status) {
						$updates[] = NowSpots_SocialMediaAccountUpdates::create(array(
							'AdvertiserID' => $this->AdvertiserID,
							'SocialMediaAccountID' => $this->getID(),
							'UpdateID' => $status->id_str,
							'Title' => '',
							'Text' => $status->text,
							'URL' => 'http://twitter.com/'.$screen_name.'/status/'.$status->id_str,
							'CreatedDate' => $status->created_at,
						));
					}
				}
			break;
			default:
				throw new NowSpots_Exception('Unknown social media type: '.$Type);
			break;
		}
		return $updates;
	}
	
	
}