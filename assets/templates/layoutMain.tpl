<html>
<head>
    <meta charset="utf-8" />
    <title>StravaStat</title>
    <meta name="description" content="" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<base href="/" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#4279b8" />
    <meta name="msapplication-navbutton-color" content="#4279b8" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#4279b8" />
    <link rel="icon" href="/favicon.ico" />
    <link href="assets/css/main.min.css?v={{ assets_version }}" rel="stylesheet" />
</head>
<body class="body">
	<div class="container">
		<header class="header">
			<h1 class="header__header">StravaStat</h1>
			<div class="header__subheader">If you rides not on Strava it didn't happen</div>
		</header>
	</div>
	
	<div class="container">
    	{{output|raw}}
	</div>

	<div class="container">
		<footer class="footer">
			<div>
				<a href="https://github.com/mishantrop/stravastat" target="_blank" rel="noopener">
					https://github.com/mishantrop/stravastat
				</a>
			</div>
			<div>
				2017
			</div>
			<div>
				<div>Время: {{ t }} s</div>
				<div>Память: {{ m }}</div>
			</div>
		</footer>
	</div>
</body>
<script src="node_modules/jquery/dist/jquery.min.js"></script>
<script src="node_modules/tablesorter/dist/js/jquery.tablesorter.min.js"></script>
<script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCuDbJkYFLKzqcLkAAvp9sYLs_vRSzRAb0"></script>
<script src="assets/js/main.min.js?v={{ assets_version }}"></script>
</html>