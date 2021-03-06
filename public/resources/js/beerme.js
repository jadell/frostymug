$('document').ready(function() {

	var $searchResults = $('#search-results');
	var latestSearch = 0;
	var loggedInAs = $('.logged-in-as').text();

	var templateCache = {};
	var render = function (templateName, data) {
		data = data || {};
		if (templateName in templateCache) {
			var tmpl = templateCache[templateName];
		} else {
			var tmpl = $('#'+templateName).html();
			templateCache[templateName] = tmpl;
		}

		return $($.mustache(tmpl, data));
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
		var icon = (beer.icon || beer.brewery.icon || '/resources/images/beer-default.png').replace(/^https?/, 'https');
		var $filled = render('beer-data-template', {
			id : beer.id
		,	name : beer.name
		,	description : beer.description || null
		,	breweryDescription : beer.brewery.description || null
		,	breweryName : beer.brewery.name
		,	breweryId : beer.brewery.id
		,	icon : icon
		});
		$ratingForm = $filled.find('.beer-rating-form');
		if (!loggedInAs) {
			$ratingForm.submit(function (e) {
				$('#login-form input[name="beer_id"]').val(beer.id);
				$('#login-form input[name="rating"]').val($(this).find('input:radio:checked').val());
				$('#login-ask').modal();
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
	$('#beer-search-form').submit(function (e) {
		e.preventDefault();
		var searchTerm = $('input[name="search-term"]').val().trim() || ' ';
		var currentSearch = latestSearch = latestSearch + 1;

		$searchResults.empty().append(render('wait-template'));
		$.getJSON('/api/beer/search/'+encodeURI(searchTerm), function (results) {
			if (currentSearch != latestSearch) {
				return;
			}

			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(render('no-results-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
			latestSearch = 0
		});
	});

	$('a.beer-data-more').live('click', function () {
		$(this).siblings('div.beer-data-more-info').fadeIn();
		$(this).siblings('a.beer-data-less').show();
		$(this).hide();
	});
	$('a.beer-data-less').live('click', function () {
		$(this).siblings('div.beer-data-more-info').fadeOut();
		$(this).siblings('a.beer-data-more').show();
		$(this).hide();
	});

	$('a[href="#my-ratings"]').click(function (e) {
		e.preventDefault();
		if (!loggedInAs) {
			return false;
		}
		$searchResults.empty().append(render('wait-template'));

		$.getJSON('/api/beer/ratings/'+loggedInAs, function (results) {
			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(render('no-results-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
		});
	});

	$('a[href="#recommendations"]').click(function (e) {
		e.preventDefault();
		if (!loggedInAs) {
			return false;
		}
		$searchResults.empty().append(render('wait-template'));

		$.getJSON('/api/beer/recommendations/'+loggedInAs, function (results) {
			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(render('no-recommendations-template'));
				return;
			}

			$.each(results, handleBeerResult($searchResults));
		});
	});

	$('a.side-tab:not(.side-tab-open)').live('click', function (e) {
		e.preventDefault();
		var $self = $(this);
		$self.addClass('side-tab-open');
		var $container = $self.closest('div.side-tab-container');
		var left = $container.offset().left;
		var width = $container.innerWidth();

		$container.css('left', left)
		.animate({
			left: left - width
		});
	});
	$('a.side-tab-open').live('click', function (e) {
		e.preventDefault();
		var $self = $(this);
		var $container = $self.closest('div.side-tab-container');
		var left = $(document).width();

		$container.animate({
			left: left
		}, function () {
			$container.css('left', '100%');
			$self.removeClass('side-tab-open');
		});
	});
	$('a[href="#contact"]').click(function (e) {
		e.preventDefault();
		$('a[href="#feedback"]').trigger('click');
	});
	$('button#feedback-cancel').click(function (e) {
		e.preventDefault();
		$('a[href="#feedback"]').trigger('click');
	});

	$('#login-ask').on('hide', function () {
		$('#login-form input[name="beer_id"]').val(null);
		$('#login-form input[name="rating"]').val(null);
	});
	$('#login-ask button.btn-primary').click(function () {
		$('#login-form').submit();
	});

	// Perform that last search again
	$('input[name="search-term"]').val().trim() && $('#beer-search-form').trigger('submit');

	// Fade out any alerts
	setTimeout(function () {
		$('.alert').alert('close');
	}, 10000);
});
