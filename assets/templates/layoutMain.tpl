<html>
<head>
    <meta charset="utf-8" />
    <title>StravaStat</title>
    <meta name="description" content="" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#4279b8" />
    <meta name="msapplication-navbutton-color" content="#4279b8" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#4279b8" />
    <link rel="icon" href="/favicon.ico" />
    <link href="assets/css/main.min.css?v={{ assets_version }}" rel="stylesheet" />
</head>
<body>
	<div class="container">
		<header>
			<h1>StravaStat</h1>
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
		</footer>
	</div>
</body>
<script src="node_modules/jquery/dist/jquery.min.js"></script>
<script src="node_modules/tablesorter/dist/js/jquery.tablesorter.min.js"></script>
<script src="assets/js/main.min.js"></script>
</html>