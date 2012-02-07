$('document').ready(function() {

	var $searchResults = $('#search-results');
	var latestSearch = 0;
	var loggedInAs = $('span.logged-in-as').text();

	var fillTemplate = function (templateName, data) {
		data = data || {};
		var tmpl = $('#'+templateName).html();
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
				$(this).find('input:radio').each(function (i) {
					var rating = $(this).val();
					var title = $(this).parent().text();
					var $item = $('<a href="#"></a>')
						.attr('title', title)
						.text(rating);
					// .5 - 5 stars
					if (rating > 0) {
							$item.addClass(i % 2 == 0 ? 'rating-right' : '');
					// 0 = not interested
					} else {
						$item.addClass('rating-none');
					}
					starRating.addHandlers($item, $form);
					$list.append($item);
					if ($(this).is(':checked')) {
						$item.addClass('rating-current')
							.prevAll().andSelf().addClass('rating');
					}
				});
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
				$(this).siblings().andSelf().removeClass('rating');
				$(this).prevAll().andSelf().addClass('rating-over');
				$(this).addClass('rating-hover');
			}, function () {
				$(this).siblings().andSelf().removeClass('rating-hover');
				$(this).siblings().andSelf().removeClass('rating-over');
				$(this).parent().find('.rating-current').prevAll().andSelf().addClass('rating');
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
		,	icon : beer.brewery.icon || '/resources/images/beer-default.png'
		});
		$filled.find('img.label-image')
			.addClass(beer.brewery.icon ? 'glossy' : '')
			.wrap(function () {
				return '<span class="image-wrap '+ ($(this).attr('class') || '') + '" />';
			});
		$ratingForm = $filled.find('.beer-rating-form');
		if (!loggedInAs) {
			$ratingForm.hide();
		} else {
			$ratingForm.submit(function (e) {
				$.post('/api/beer/'+beer.id+'/rating/'+loggedInAs, {
					rating : $(this).find('input:radio:checked').val()
				});
				return false;
			});
			$ratingForm.find('input:radio[value='+beer.rating.rated+']').attr('checked', true);
			starRating.create($ratingForm);
		}
		$(this).append($filled);
	};

	var handleBeerResult = function (container) {
		return function (idx, beer) {
			addBeerTemplate.call(container, beer);
		}
	}

	var autoCompleteCache = {};
	$('#search-term').autocomplete({
		source : function (request, responseCallback) {
			var term = request.term;
			if (term in autoCompleteCache) {
				responseCallback(autoCompleteCache[term]);
				return;
			}
			$.getJSON('/api/beer/search/name/'+encodeURI(term), function (result) {
				autoCompleteCache[term] = result;
				responseCallback(result);
			});
		}
	,	select : function () {
			$('#search-button').click();
		}
	,	search : function () {
			if (latestSearch != 0) {
				return false;
			}
		}
	,	delay : 100
	,	minLength : 3
	,	position : {
			offset : "0 3"
		}
	});

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

});
