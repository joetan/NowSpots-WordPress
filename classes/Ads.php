<?php
require_once(NOWSPOTS_CLASSES_DIR.'Model.php');

class NowSpots_Ads extends NowSpots_Model {
	public 
		$AdvertiserID,
		$SocialMediaAccountID,
		$Name,
		$Image,
		$Template;
		
		
	public function getMostRecentUpdate() {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		return NowSpots_SocialMediaAccountUpdates::fetch_most_recent(array(
			'AdvertiserID' => $this->AdvertiserID,
		));
		
		
	}
	
	public function getRecentUpdates($limit=100) {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		return NowSpots_SocialMediaAccountUpdates::fetch_recent(array(
			'AdvertiserID' => $this->AdvertiserID,
			'SocialMediaAccountID' => $this->SocialMediaAccountID,
			'Status' => 'Active',
		), $limit);
		
		
	}
	
	public function getAllRecentUpdates($limit=100) {
		require_once NOWSPOTS_CLASSES_DIR.'SocialUpdates.php';
		return NowSpots_SocialMediaAccountUpdates::fetch_recent(array(
			'AdvertiserID' => $this->AdvertiserID,
			'SocialMediaAccountID' => $this->SocialMediaAccountID,
		), $limit);
		
		
	}
	
	public function render($template=false) {
		if (!$template) $template = $this->Template;
		
		$updates = $this->getRecentUpdates(3);
		

		$Name = $this->Name;
		$Image = $this->Image;
		include($this->getTemplate($template));
		
	}
	
	private function getTemplate($template) {
		return NOWSPOTS_TEMPLATES_DIR.'ads/'.$template.'.html';
	}
	
}