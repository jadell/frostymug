$('document').ready(function() {

	var loggedInAs = null;

	var searchResults = $('#search-results');
	var templates = {};

	var fillTemplate = function (templateName, data) {
		data = data || {};
		var tmpl = $('#'+templateName).html();
		$.each(data, function (key, value) {
			tmpl = tmpl.replace(new RegExp('<%'+key+'%>', 'g'), value);
		});
		return $(tmpl);
	};

	$('#search-button').click(function (e) {
		e.preventDefault();
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
				,	icon : beer.brewery.icon || '/resources/images/beer-default.png'
				});
				$('img.label-image', filled).load(function () {
					$(this).wrap(function () {
						return '<span class="image-wrap '+ ($(this).attr('class') || '') + '" style="background:url(' + $(this).attr('src') + ');" />';
					});
					$(this).css("opacity", "0");
				});
				searchResults.append(filled);
			});
		});
	});

	$('#login-button').click(function (e) {
		e.preventDefault();
		$('.bad-login').remove();
		var email = $('#login-email').val();
		if (!email) {
			return;
		}

		$.post('/api/user/login', {
			email: email
		,	password: 'foo'
		}, function(response) {
			if (!response.email) {
				searchResults.append(fillTemplate('bad-login-template'));
				return;
			}

			loggedInAs = response.email;
			var filled = fillTemplate('logged-in-template', {
				email: response.email
			});
			$('#login-form')
				.hide()
				.after(filled)
				.find('input').val('');
		}, 'json');
	});

	$('#logout-button').live('click', function (e) {
		e.preventDefault();
		console.log('here');
		if (!loggedInAs) {
			return;
		}

		$.getJSON('/api/user/logout', {
			email: loggedInAs
		});

		$('.logged-in').remove();
		$('#login-form').show();
		loggedInAs = null
	});

});
