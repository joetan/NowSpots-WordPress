<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_Advertisers extends NowSpots_Model {
	public 
		$Name;
		
	public function getServices() {
		require_once NOWSPOTS_CLASSES_DIR.'Social.php';
		
		return NowSpots_SocialMediaAccounts::find(array(
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
			$service = NowSpots_SocialMediaAccounts::get($params['id']);
			$service->update($params);
			
		} else {
			$params['Status'] = 'Active';
			$service = NowSpots_SocialMediaAccounts::create($params);
		}
		return $service;
	}
	
	public function getStatusUpdates() {
		
	}

}