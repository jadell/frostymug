$('document').ready(function() {

	var $searchResults = $('#search-results');
	var latestSearch = 0;
	var loggedInAs = $('span.logged-in-as').text();

	var templateCache = {};
	var fillTemplate = function (templateName, data) {
		data = data || {};
		if (templateName in templateCache) {
			var tmpl = templateCache[templateName];
		} else {
			var tmpl = $('#'+templateName).html();
			templateCache[templateName] = tmpl;
		}
		$.each(data, function (key, value) {
			tmpl = tmpl.replace(new RegExp('<%'+key+'%>', 'g'), value);
		});
		return $(tmpl);
	};

	var starRating = {
		create: function(form) {
			var $form = $(form);
			var $selector = $form.find('div.stars');
			$selector.each(function () {
				var $list = $('<div class="star-rating"></div>');
				var found = false;
				$(this).find('input:radio').each(function (i) {
					var rating = $(this).val();
					var title = $(this).parent().text();
					var $item = $('<a href="#"></a>')
						.attr('title', title)
						.attr('data-value', rating)
						.text(rating);
					// .5 - 5 stars
					if (rating > 0) {
							$item.addClass(i % 2 == 0 ? 'right' : '');
					// 0 = not interested
					} else {
						$item.addClass('rating-none');
					}
					starRating.addHandlers($item, $form);
					$list.append($item);
					if ($(this).is(':checked')) {
						found = true;
						$item.addClass('rating-current')
							.prevAll().andSelf().addClass('rating');
					}
				});
				if (!found) {
					var estimated = $(this).find('input:radio[data-estimated]').val();
					$list.find('a[data-value="'+estimated+'"]').addClass('estimate-current')
						.prevAll().andSelf().addClass('estimate');
				}
				$(this).append($list).find('input:radio').parent().hide();
			});
			$(form).find('button').hide();
		},

		addHandlers: function(item, form) {
			var $item = $(item);
			var $form = $(form);
			$item.click(function (e) {
				e.preventDefault();
				var $star = $(this);
				$star.addClass('rating-current')
					.siblings()
						.removeClass('rating')
						.removeClass('rating-current')
						.removeClass('estimate')
						.removeClass('estimate-current')
						.end()
					.prevAll().andSelf().addClass('rating');
				$form.find('input:radio')
						.attr('checked', false)
						.end()
					.find('input:radio[value='+$star.text()+']')
						.attr('checked', true)
						.end()
					.submit();
			})

			.hover(function () {
				$(this).siblings().andSelf().removeClass('rating estimate');
				$(this).prevAll().andSelf().addClass('rating-over');
				$(this).addClass('rating-hover');
			}, function () {
				$(this).siblings().andSelf().removeClass('rating-hover');
				$(this).siblings().andSelf().removeClass('rating-over');
				$(this).parent().find('.rating-current').prevAll().andSelf().addClass('rating');
				$(this).parent().find('.estimate-current').prevAll().andSelf().addClass('estimate');
			});
		}
	};

	var addBeerTemplate = function (beer) {
		var $filled = fillTemplate('beer-data-template', {
			id : beer.id
		,	name : beer.name
		,	description : beer.description || ''
		,	breweryName : beer.brewery.name
		,	breweryId : beer.brewery.id
		,	icon : beer.icon || beer.brewery.icon || '/resources/images/beer-default.png'
		});
		$filled.find('img.label-image')
			.addClass(beer.brewery.icon ? 'glossy' : '')
			.wrap(function () {
				return '<span class="image-wrap '+ ($(this).attr('class') || '') + '" />';
			});
		$ratingForm = $filled.find('.beer-rating-form');
		if (!loggedInAs) {
			$ratingForm.submit(function (e) {
				$('#login-form input[name="beer_id"]').val(beer.id);
				$('#login-form input[name="rating"]').val($(this).find('input:radio:checked').val());
				$('#login-ask').dialog('open');
				return false;
			});
		} else {
			$ratingForm.submit(function (e) {
				$.post('/api/beer/'+beer.id+'/rating/'+loggedInAs, {
					rating : $(this).find('input:radio:checked').val()
				});
				return false;
			});
		}
		$ratingForm.find('input:radio[value='+beer.rating.rated+']').attr('checked', true);
		$ratingForm.find('input:radio[value='+beer.rating.estimated+']').attr('data-estimated', true);
		starRating.create($ratingForm);
		$(this).append($filled);
	};

	var handleBeerResult = function (container) {
		return function (idx, beer) {
			addBeerTemplate.call(container, beer);
		}
	}

	$('#search-button').click(function (e) {
		e.preventDefault();
		var searchTerm = $('#search-term').val().trim() || ' ';
		var currentSearch = latestSearch = latestSearch + 1;

		$searchResults.empty().append(fillTemplate('wait-template'));
		$.getJSON('/api/beer/search/'+encodeURI(searchTerm), function (results) {
			if (currentSearch != latestSearch) {
				return;
			}

			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(fillTemplate('no-results-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
			latestSearch = 0
		});
	});

	$('#my-ratings-button').click(function (e) {
		e.preventDefault();
		if (!loggedInAs) {
			return false;
		}
		$searchResults.empty().append(fillTemplate('wait-template'));

		$.getJSON('/api/beer/ratings/'+loggedInAs, function (results) {
			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(fillTemplate('no-results-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
		});
	});

	$('#my-recommendations-button').click(function (e) {
		e.preventDefault();
		if (!loggedInAs) {
			return false;
		}
		$searchResults.empty().append(fillTemplate('wait-template'));

		$.getJSON('/api/beer/recommendations/'+loggedInAs, function (results) {
			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(fillTemplate('no-recommendations-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
		});
	});

	$('#login-ask').dialog({
		autoOpen: false
	,	buttons: {
			'Ok': function () {
				$(this).dialog('close');
				$('#login-form').submit();
			}
		,	'No, Thanks': function () {
				$('#login-form input[name="beer_id"]').val(null);
				$('#login-form input[name="rating"]').val(null);
				$(this).dialog('close');
			}
		}
	,	draggable: false
	,	modal: true
	,	resizable: false
	});
	$('.ui-widget-overlay').live('click', function () {
		$('.ui-widget-overlay').siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
	});

	// Perform that last search again
//	$('#search-term').val() && $('#search-button').click();

});
