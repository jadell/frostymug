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
			<?php echo $user['email']; ?>
			<a href="/logout" class="button" id="logout-button">Logout</a>
		<?php else : ?>
			<form action="/login" method="POST">
				<input type="hidden" name="openid_identifier" value="https://www.google.com/accounts/o8/id" />
				<button>Login with Google</button>
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
	<div class="beer-rating-form">
		Rating: <select>
			<option>0</option><option>1</option><option>2</option><option>3</option>
			<option>4</option><option>5</option><option>6</option><option>7</option>
			<option>8</option><option>9</option><option>10</option>
		</select>
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
