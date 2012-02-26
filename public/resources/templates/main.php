<!DOCTYPE html>
<html>
<head>
	<title>BeerMe!</title>

	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
	<script type="text/javascript" src="resources/js/beerme.js"></script>
	<link rel="stylesheet" type="text/css" href="resources/css/beerme.css" />
<body>

<div id="main">
	<div id="head">
		<?php if ($user) : ?>
			<span class="logged-in-as"><?php echo $user['email']; ?></span>
			<a href="/logout" class="button" id="logout-button">Logout</a>
		<?php else : ?>
			<form action="/login" method="POST">
				<input type="hidden" name="openid_identifier" value="https://www.google.com/accounts/o8/id" />
				<button type="submit">Login with Google</button>
			</form>
		<?php endif; ?>

		<form id="search-form">
			<input type="text" id="search-term" value="<?php echo $lastSearch; ?>" />
			<button id="search-button">Search</button>
		</form>

		<?php if ($user) : ?>
			<div id="my-ratings-form">
				<a href="#" class="button" id="my-ratings-button">My Ratings</a>
			</div>
			<div id="my-recommendations-form">
				<a href="#" class="button" id="my-recommendations-button">Recommendations</a>
			</div>
		<?php endif; ?>
	</div>

	<div id="content">
		<div id="search-results">
			<h2>Welcome to BeerMe!</h2>
			<p>Use the search box to find your favorite beers or explore new ones.</p>
			<p>Log in with your Google account to start rating the beers you discover.</p>
			<p>Keep track of the beers you've rated with "My Ratings".</p>
			<p>Get new recommendations with "Recommendations".</p>
		</div>
	</div>

	<div class="clear"></div>
</div>

<div id="footer">
	<a class="powered-by grey" href="http://www.brewerydb.com/">
		<img src="/resources/images/Powered-By-BreweryDB.png" title="Powered By BreweryDB.com" alt="Powered By BreweryDB.com">
	</a>
	<a class="powered-by blue" href="http://neo4j.org/">
		<img src="/resources/images/neo4j-clear-small-Enterprise.png" title="Powered By Neo4j" alt="Powered By Neo4j">
	</a>
</div>


</body>

<script type="text/template" id="beer-data-template">
	<div class="beer-data">
		<div class="beer-info" title="<%name%> - <%breweryName%>">
			<img src="<%icon%>" class="label-image" title="<%description%>" alt="<%name%> by <%breweryName%>"/>
			<div class="beer-data-name"><%name%></div>
			<div class="beer-data-brewery-name"><%breweryName%></div>
		</div>
		<form class="beer-rating-form" method="POST">
			<input type="hidden" class="beer-id" value="<%id%>" />
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

<script type="text/template" id="no-results-template">
	<div class="result no-results">No results matched your search.</div>
</script>

<script type="text/template" id="bad-login-template">
	<div class="result bad-login">Bad login.</div>
</script>

<script type="text/template" id="wait-template">
	<div class="wait">Please wait...</div>
</script>

</html>
