<html>
<head>
	<title><?=htmlentities($title)?></title>
	<style type="text/css">
		body {
			font-family:Arial,Helvetica,sans-serif;
			line-height:150%;	
			padding:20px 0;
			color: #333333;
			margin: 1em;
		}
	
		h2 {
			font-weight:normal;
			margin:0;
			padding:5px 0 8px;
		}
		
		#jugranknav {
			float: right;
			width: 20%;
			backgroun-color: #ffffee;
		}

		#jugrankcontent {
			width: 95%;
			min-height: 800px;
			background-color: #fefefe;
		}	

		#jugranknav > ul {
			font-size: 1.1em;
			background-color: #dfdfdf;
			padding-top: 10px;
			padding-bottom: 10px;
			border: 1px dashed #cfcfcf;
		}
		
		#jugranknav > ul > li {
			list-style-image: none;
			list-style-type: none;
		}

		#jugranknav > ul > li > a {
			list-style-image: none;
			list-style-type: none;
			color: #333333;
			padding: 2px;
			text-decoration: none;
		}

		#jugranknav > ul > li > a:active {
			list-style-image: none;
			list-style-type: none;
			color: 666666;
			padding: 2px;
			text-decoration: none;
		}

		#jugranknav > ul > li:hover {
			list-style-image: none;
			list-style-type: none;
			background-color: #eeeeee;
			padding: 2px;
			text-decoration: none;
		}

			
	</style>
</head>
<body>
<!--	<div id="jugranknav">
		<ul>
			<li><a href="index.php?page=JugRankSystem">JugRank - Einf&uuml;hrung</a></li>
			<li><a href="index.php?page=SimulateTournament">Turnier simulieren</a></li>
			<li><a href="index.php?page=JugRankForm">Turnier spielen</a></li>
			<li><a href="index.php?page=Stats">Statistiken</a></li>
			<li><a href="index.php?page=Source">Quellcode</a></li>
		</ul>
	</div>
-->
	<div id="jugrankcontent"><?=$content?></div> 
</body>
</html>