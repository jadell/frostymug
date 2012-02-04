$('document').ready(function() {


	var $searchResults = $('#search-results');
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
					var $item = $('<a href="#"></a>')
						.attr('title', rating)
						.text(rating)
						.addClass(i % 2 == 1 ? 'rating-right' : '');
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
			}, function () {
				$(this).siblings().andSelf().removeClass('rating-over');
				$(this).siblings('.rating-current').prevAll().andSelf().addClass('rating');
			});
		}
	};

	$('#search-button').click(function (e) {
		e.preventDefault();
		var searchTerm = $('#search-term').val();
		$searchResults.empty().append(fillTemplate('wait-template'));

		$.getJSON('/api/beer/search/'+searchTerm, function (results) {
			$searchResults.empty();
			if (results.length < 1) {
				$searchResults.append(fillTemplate('no-results-template'));
				return;
			}

			$.each(results, function (idx, beer) {
				var $filled = fillTemplate('beer-data-template', {
					id : beer.id
				,	name : beer.name
				,	description : beer.description || ''
				,	breweryName : beer.brewery.name
				,	breweryId : beer.brewery.id
				,	icon : beer.brewery.icon || '/resources/images/beer-default.png'
				});
				$('img.label-image', $filled).load(function () {
					var $this = $(this);
					$this.wrap(function () {
						return '<span class="image-wrap '+ ($this.attr('class') || '') + '" style="background:url(' + $this.attr('src') + ');" />';
					});
					$this.css("opacity", "0");
				});
				$searchResults.append($filled);

				if (loggedInAs) {
					$.getJSON('/api/beer/'+beer.id+'/rating/'+loggedInAs, function (result) {
						var $ratingForm = fillTemplate('beer-rating-template', {
							id : beer.id
						});
						$ratingForm.submit(function (e) {
							$.post('/api/beer/'+beer.id+'/rating/'+loggedInAs, {
								rating : $('input:radio:checked', $ratingForm).val()
							});
							return false;
						});
						$('input:radio[value='+result.rating+']', $ratingForm).attr('checked', true);
						$('.beer-data-name', $filled).after($ratingForm);
						starRating.create($ratingForm);
					});
				}
			});
		});
	});

});
