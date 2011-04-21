<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_Ads extends NowSpots_Model {
	public 
		$AdvertiserID,
		$Name,
		$Template
		;
		
		
	public function getMostRecentUpdate() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		return NowSpots_SocialMediaAccountUpdates::fetch_most_recent(array(
			'AdvertiserID' => $this->AdvertiserID,
		));
		
		
	}
	
	public function getRecentUpdates() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		return NowSpots_SocialMediaAccountUpdates::fetch_recent(array(
			'AdvertiserID' => $this->AdvertiserID,
		), 100);
		
		
	}
}