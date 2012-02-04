<!DOCTYPE html>
<html>
<head>
	<title>BeerMe!</title>

	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
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

		<div id="search-form">
			<input type="text" id="search-term" />
			<a href="#" class="button" id="search-button">Search</a>
		</div>
	</div>

	<div id="content">
		<div id="search-results">
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
		<img src="<%icon%>" class="label-image" title="<%description%>" alt="<%name%> by <%breweryName%>"/>
		<div class="beer-data-name"><%name%></div>
		<div class="beer-data-brewery-name"><%breweryName%></div>
	</div>
</script>

<script type="text/template" id="beer-rating-template">
	<form class="beer-rating-form" method="POST">
		<input type="hidden" class="beer-id" value="<%id%>" />
		<div class="stars">
			<label><input class="rating-1"  name="rating" type="radio" value="1" >1</label>
			<label><input class="rating-2"  name="rating" type="radio" value="2" >2</label>
			<label><input class="rating-3"  name="rating" type="radio" value="3" >3</label>
			<label><input class="rating-4"  name="rating" type="radio" value="4" >4</label>
			<label><input class="rating-5"  name="rating" type="radio" value="5" >5</label>
			<label><input class="rating-6"  name="rating" type="radio" value="6" >6</label>
			<label><input class="rating-7"  name="rating" type="radio" value="7" >7</label>
			<label><input class="rating-8"  name="rating" type="radio" value="8" >8</label>
			<label><input class="rating-9"  name="rating" type="radio" value="9" >9</label>
			<label><input class="rating-10" name="rating" type="radio" value="10">10</label>
		</div>
		<button>Rate</button>
	</form>
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
