<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_Advertisers extends NowSpots_Model {
	public 
		$Name;
		
	private static $className = 'NowSpots_Advertisers';
		
		
		
	public function getServices() {
		require_once NOWSPOTS_CLASSES_DIR.'Social.php';
		
		return NowSpots::find('NowSpots_SocialMediaAccounts', array(
			'AdvertiserID' => $this->getID(),
			'Status' => 'Active',
		));
	}
	
	public function setServices($services) {
		$return = array();
		foreach ($services as $service) {
			if ($service['Name'] && $service['URL']) {
				$return[] = $this->setService($service);
			}
		}
		return $return;
	}
	
	public function setService($params) {
		$params['AdvertiserID'] = $this->getID();
		require_once NOWSPOTS_CLASSES_DIR.'Social.php';
		if ($params['id']) {
			$service = NowSpots::get('NowSpots_SocialMediaAccounts', $params['id']);
			$service->update($params);
			
		} else {
			$params['Status'] = 'Active';
			$service = NowSpots::create('NowSpots_SocialMediaAccounts', $params);
		}
		return $service;
	}
	
	public function getStatusUpdates() {
		
	}

}