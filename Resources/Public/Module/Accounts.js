jQuery(function($) {
	$('#accounts').on('change', 'select[data-reload]', function() {
		var prefix = window.location + (window.location.href.indexOf('?') > 0 ? '&' : '?');
		window.location = prefix + this.name + '=' + this.value;
	});
});