jQuery(function($) {
	$('#refresh .button').click(function() {
		$('#refresh .status').html('Refreshing all accounts...');console.log(ajaxurl)
		$.post(ajaxurl, {
			'action':'nowspots_refresh', 
			'refresh_nonce':NowSpotsAdmin['refresh_nonce']
		}, function(response) {
			$('#refresh .status').html(response);
		}, 'html');
		
		return false;
	});
});
