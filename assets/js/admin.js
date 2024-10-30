jQuery(document).ready(function($) {
	if ($('#dimwwt_bot_mode').length) {
		$('#dimwwt_bot_mode').on('change', function(e) {
			var bot_api_url = $(this).find(":selected").attr('data-url'),
				bot_api_label = $(this).find(":selected").attr('data-token-label');
			$('#bot-api-url').hide().val(bot_api_url).fadeIn(300);
			$('#bot-token-label').hide().text(bot_api_label).fadeIn(300);
		});
	}
});