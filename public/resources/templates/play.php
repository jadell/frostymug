<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>FrostyMug - Beer Ratings and Recommendations</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link href="resources/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="resources/css/beerme-bootstrap.css" rel="stylesheet">
		<!-- HTML5 shim -->
		<!--[if lt IE 9]>
			<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->

		<!-- Le fav and touch icons
		<link rel="shortcut icon" href="resources/ico/favicon.ico">
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="resources/ico/apple-touch-icon-114-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="resources/ico/apple-touch-icon-72-precomposed.png">
		<link rel="apple-touch-icon-precomposed" href="resources/ico/apple-touch-icon-57-precomposed.png">
		-->
	</head>

	<body>

		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

					<a class="brand" href="/">FrostyMug</a>

					<form id="beer-search-form" class="form-search">
						<input type="text" name="search-term" class="input-medium search-query" placeholder="Search for beers" value="<?php echo $lastSearch; ?>"/>
						<button type="submit" class="btn"><i class="icon-search"></i> Search</button>
					</form>

					<?php if ($user) : ?>
					<div class="nav-collapse">
						<ul class="nav">
							<li><a href="#my-ratings">My Ratings</a></li>
							<li><a href="#recommendations">Recommendations</a></li>
						</ul>
						<ul class="nav pull-right">
							<li class="dropdown" id="account-dropdown">
								<a href="#account-dropdown" class="dropdown-toggle logged-in-as" data-toggle="dropdown">
									<?php echo $user['email']; ?>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<li><a href="/logout">Logout</a></li>
								</ul>
							</li>
						</ul>
					</div><!--/.nav-collapse -->


					<?php else : ?>
					<form id="login-form" action="/login" method="POST">
						<input type="hidden" name="openid_identifier" value="https://www.google.com/accounts/o8/id" />
						<input type="hidden" name="beer_id" />
						<input type="hidden" name="rating" />
						<button type="submit" class="btn btn-primary">Login</button>
					</form>
					<?php endif; ?>

				</div>
			</div>
		</div>

		<div class="container">
			<div id="search-results">
				<h2>Welcome to FrostyMug!</h2>
				<p>Use the search box to find your favorite beers or explore new ones.</p>
				<p>Log in with your Google account to start rating the beers you discover.</p>
				<p>Get new recommendations with <a href="#recommendations">Recommendations</a>.</p>
				<p>Keep track of the beers you've rated with <a href="#my-ratings">My Ratings</a>.</p>
			</div>

			<footer>
				<p>
					<a href="#about" data-toggle="modal">About</a> &bullet;
					<a href="#contact">Contact</a> &bullet;
					&copy; <a href="http://www.everymansoftware.com">Everyman Software</a> 2012
				</p>
			</footer>

		</div> <!-- /container -->

		<!-- Modals -->

		<div class="modal hide fade" id="login-ask" style="display:none;">
			<div class="modal-header">
				Log In to Rate?
			</div>
			<div class="modal-body">
				<p>You must be logged in to save a rating. Do you wish to log in now?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary">Ok</button>
				<button type="button" class="btn" data-dismiss="modal">No, Thanks</button>
			</div>
		</div>

		<div class="modal hide fade" id="about" style="display:none;">
			<div class="modal-header">
				About FrostyMug
			</div>
			<div class="modal-body">
				<p>FrostyMug is a simple, easy-to-use way to keep track of your favorite beers and get recommendations about new brews to try.</p>
				<a class="powered-by blue" href="http://neo4j.org/">
					<img src="resources/images/neo4j-clear-small-Enterprise.png" title="Powered By Neo4j" alt="Powered By Neo4j">
				</a>
				<a class="powered-by grey" href="http://www.brewerydb.com/">
					<img src="resources/images/Powered-By-BreweryDB.png" title="Powered By BreweryDB.com" alt="Powered By BreweryDB.com">
				</a>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn" data-dismiss="modal"><i class="icon-remove"></i> Close</button>
			</div>
		</div>

		<!-- Placed at the end of the document so the pages load faster -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="resources/bootstrap/js/bootstrap.min.js"></script>
		<script src="resources/js/jquery.mustache.js"></script>
		<script src="resources/js/beerme.js"></script>
	</body>

	<script type="text/template" id="beer-data-template">
		<div class="beer-data">
			<div class="beer-info" title="{{name}} - {{breweryName}}">
				<img src="{{icon}}" class="label-image" title="{{description}}" alt="{{name}} by {{breweryName}}"/>
				<div class="beer-data-name">{{name}}</div>
				<div class="beer-data-brewery-name">{{breweryName}}</div>
			</div>
			<form class="beer-rating-form" method="POST">
				<input type="hidden" class="beer-id" value="{{id}}" />
				<div class="stars">
					<label><input name="rating" type="radio" value="0" >Not interested</label>
					<label><input name="rating" type="radio" value="1" >.5 star</label>
					<label><input name="rating" type="radio" value="2" >1 star</label>
					<label><input name="rating" type="radio" value="3" >1.5 stars</label>
					<label><input name="rating" type="radio" value="4" >2 stars</label>
					<label><input name="rating" type="radio" value="5" >2.5 stars</label>
					<label><input name="rating" type="radio" value="6" >3 stars</label>
					<label><input name="rating" type="radio" value="7" >3.5 stars</label>
					<label><input name="rating" type="radio" value="8" >4 stars</label>
					<label><input name="rating" type="radio" value="9" >4.5 stars</label>
					<label><input name="rating" type="radio" value="10">5 stars</label>
				</div>
				<button>Rate</button>
			</form>
		</div>
	</script>

	<script type="text/template" id="wait-template">
		<div class="wait">Please wait...</div>
	</script>

	<script type="text/template" id="no-results-template">
		<div class="result no-results">No results matched your search.</div>
	</script>

	<script type="text/template" id="no-recommendations-template">
		<div class="result no-recommendations">
			<h2>Sorry, we can&apos;t recommend any beers to you right now.</h2>
			<p>
				There may be a few reasons for this.
				<ul>
					<li>
						<p class="reason">Maybe: You haven&apos;t rated enough beers.</p>
						<p>Recommendations are based off of your own ratings of beers you have tried.
						If you haven&apos;t rated enough beers, we can&apos;t determine your tastes
						well enough to give you good recommendations.</p>
						<p class="solution">Solution: Rate more beers!</p>
					</li>
					<li>
						<p class="reason">Maybe: We couldn&apos;t find other users similar to you.</p>
						<p>We look for other users who have the same taste in beer as you, and
						find things they like that you haven&apos;t tried. If no other users are close
						enough to your tastes, we can&apos;t give you good recommendations.</p>
						<p class="solution">Solution: Get your friends and drinking buddies
						to sign up and rate beers!</p>
					</li>
				</ul>
			</p>
		</div>
	</script>

</html>