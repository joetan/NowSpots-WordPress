<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_SocialMediaAccounts extends NowSpots_Model {
	public 
		$AdvertiserID,
		$Type,
		$Name,
		$URL,
		$Data;

	protected $className = 'NowSpots_SocialMediaAccounts';
		
	public function __construct(Array $properties) {
		if (!isset($properties['Data'])) {
			$properties['Data'] = '';
		}
		parent::__construct($properties);
	}
	
	public function save() {
		if (!$this->Type) {
			$this->Type = $this->getType($this->URL);
		}
		
		return parent::save();
	}
	
	private function getType($URL) {
		$parts = parse_url(trim($URL));
		if (preg_match('/facebook\.com$/', $parts['host'])) {
			return 'Facebook';
		} elseif (preg_match('/twitter\.com$/', $parts['host'])) {
			return 'Twitter';
		} else {
			throw new NowSpots_Exception('Missing or unknown type');
		}
		
		
	}
	
	public function refresh() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		
		$updates = array();
		if ($this->Status != 'Active') {
			return array();
		}
		switch ($this->Type) {
			case 'Facebook':
				$id = $this->parseID($this->URL, $this->Type);

				$response = wp_remote_get("http://graph.facebook.com/$id/feed");

				if (!is_wp_error($response)) {
					$json = wp_remote_retrieve_body($response);
					$data = json_decode($json);
					if (isset($data->data) && is_array($data->data)) {
						$statuses = $data->data;
					} else {
						 throw new NowSpots_Exception('Unable to fetch Facebook updates from '.$this->URL);
					}

					foreach ($statuses as $status) {
						$updates[] = NowSpots::create('NowSpots_SocialMediaAccountUpdates', array(
							'AdvertiserID' => $this->AdvertiserID,
							'SocialMediaAccountID' => $this->getID(),
							'UpdateID' => $status->id,
							'Title' => '',
							'Text' => $status->message,
							'URL' => $status->link,
							'CreatedDate' => $status->created_time,
						));
					}
				}
			break;
			case 'Twitter':
				$screen_name = $this->parseID($this->URL, $this->Type);
				$response = wp_remote_get('http://api.twitter.com/1/statuses/user_timeline.json?'.http_build_query(array(
					'screen_name' => $screen_name,
					'trim_user' => true,
					
				)));
				if (!is_wp_error($response)) {
					$data = wp_remote_retrieve_body($response);
					$statuses = json_decode($data);
					foreach ($statuses as $status) {
						$updates[] = NowSpots::create('NowSpots_SocialMediaAccountUpdates', array(
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
	
	public function parseID($url, $type=false) {
		if (!$type) {
			$type = $this->getType($url);
		}
	
		switch (strtolower($type)) {
			case 'facebook':
				$parts = parse_url($url);
				
				$paths = explode('/', $parts['path']);
				array_shift($paths); // remove root "/"
				$len = count($paths);
				
				if (preg_match('/.*\.php$/', $parts['path']) && $parts['query']) {
					parse_str($parts['query'], $params);
					if (isset($params['id'])) {
						$id = $params['id'];
					} else {
						$id = false;
					}
				} elseif ($len == 1) {
					$id = $paths[0];
				} elseif ($paths[0] == 'pages' && $len == 3) {
					$id = $paths[2];
				} else {
					$id = false;
				}
				if (!$id) {
					throw new NowSpots_Exception('Unable to parse social media acount id from '.$url);
				}
				return $id;
			break;
			case 'twitter':
				$parts = parse_url($url);
				$path = $parts['path'];
				$frag = $parts['fragment'];
				if ($path == '/' && $frag) {
					$path = substr($frag, 1);
				}
				
				$screen_name = preg_replace('@^/([^/]*)/?.*@', '$1', $path);
				if (!$screen_name) {
					throw new NowSpots_Exception('Unable to parse social media acount id from '.$url);
				}
				return $screen_name;
			break;
			default:
				throw new NowSpots_Exception('Unable to parse social media acount id from '.$url);
			break;
		}
	}
	
	
}