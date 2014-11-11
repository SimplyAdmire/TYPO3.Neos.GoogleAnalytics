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
			var that = this,
				nodePath = InspectorController.nodeSelection.get('selectedNode.nodePath');

			HttpClient.getResource(
				$('link[rel="typo3-neos-googleanalytics-data"]').attr('href') + '?node=' + nodePath,
				{dataType: 'json'}
			).then(
				function(results) {
					var data = {
						pageviews: results['ga:pageviews']
					};

					that.set('results', data);
				}
			);
			return this._super();
		}
	});
});