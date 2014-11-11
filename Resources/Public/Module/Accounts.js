jQuery(function($) {
	$('#accounts').on('change', 'select[data-reload]', function() {
		this.form.submit();
	});
});