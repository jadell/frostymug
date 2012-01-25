$('document').ready(function() {

	var searchResults = $('#search-results');
	var templates = {};

	var fillTemplate = function (templateName, data) {
		data = data || {};
		var tmpl = $($('#'+templateName).html());
		$.each(data, function (key, value) {
			tmpl.html(tmpl.html().replace('&lt;%'+key+'%&gt;', value));
		});
		return tmpl;
	};

	$('#search-button').click(function () {
		var searchTerm = $('#search-term').val();
		searchResults.empty().append(fillTemplate('wait-template'));

		$.getJSON('/api/beer/search/'+searchTerm, function (results) {
			searchResults.empty();

			if (results.length < 1) {
				searchResults.append(fillTemplate('no-results-template'));
				return;
			}

			$.each(results, function (idx, beer) {
				var filled = fillTemplate('beer-data-template', {
					id : beer.id
				,	name : beer.name
				,	description : beer.description || ''
				,	breweryName : beer.brewery.name
				,	breweryId : beer.brewery.id
				,	icon : beer.icon || '/resources/images/beer-default.jpg'
				});
				searchResults.append(filled);
			});
		});
	});

});
