define(
[
	'Library/jquery-with-dependencies',
	'text!./AnalyticsView.html',
	'Content/Inspector/InspectorController',
	'Shared/HttpClient'
],
function(
	$,
	template,
	InspectorController,
	HttpClient
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		results: null,

		init: function() {
			Ember.Handlebars.helper('numberformat', function(value, options) {
				return parseFloat(value).toFixed(2);
			});

			var that = this,
				nodePath = InspectorController.nodeSelection.get('selectedNode.nodePath');

			HttpClient.getResource(
				$('link[rel="typo3-neos-googleanalytics-data"]').attr('href') + '?node=' + nodePath,
				{dataType: 'json'}
			).then(
				function(results) {
					if (results.error) {
						that.set('error', results.error);
					} else {
						that.set('results', results);
					}
				}
			);
			return this._super();
		},

		hasAuthenticationError: function() {
			return this.get('error.type') === 'AuthenticationRequiredException';
		}.property('error'),

		startDateFormatted: function() {
			var d = new Date(this.get('results.startDate'));
			return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate()
		}.property('results.startDate'),

		endDateFormatted: function() {
			var d = new Date(this.get('results.endDate'));
			return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate()
		}.property('results.endDate')
	});
});