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
	
	$('#updates input.active').click(function() {
		var tr = $(this).closest('tr');
		$.post(ajaxurl, {
			'action':'nowspots_toggle_update',
			'active':$(this).attr('checked'),
			'id':$(this).attr('rel')
		}, function(response) {
			if (response.active) {
				tr.removeClass('inactive');
			} else {
				tr.addClass('inactive');
			}
		}, 'json');
		
		
	});
	$('#Advertiser').change(function() {
		$('#account-list').html('Loading...');
		$.post(ajaxurl, {
			'action':'nowspots_get_accounts',
			'AdvertiserID':$(this).val()
		}, function(html) {
			$('#account-list').html(html);
		}, 'html');
	});
	
	$('#duplicate').click(function() {
		var orig = $(this).closest('tfoot').find('tr:first');
		var tbody = $(this).closest('table').find('tbody');
		
		//var dupe = orig.find('#duplicate').detach();
		var tr = orig.clone();
		orig.find(':input').val('')
		///tr.append(dupe);
		tbody.append(tr);
	})
	
});


function set_upload_selection(url, sid, attachment_id) { 
	if (url) {
	var $ = jQuery;
	
		$('#Image').val(url);
		var img = new Image();
		$(img).attr('src', url).attr('id', 'img');
		$('#img').replaceWith(img);
		
	}
}
