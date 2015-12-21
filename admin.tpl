{root:}
<html>
<head>
	<link href="vendor/twbs/bootstrap/dist/css/bootstrap.css" rel="stylesheet">
	<script src="?-config/js.php"></script>
	<script src="vendor/components/jquery/jquery.js"></script>
	<script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
	<script>infra.Crumb.init()</script>
</head>
<body>
	{:head}
	<form id="form">
	Смотрим: <input name="id" value="{id}" onchange="infra.Crumb.go('?id='+this.value)"><br>
	<input type="submit" style="display:none">
	</form>
	Всего: {count}<br>
	user:
	<pre><code>{user}</code></pre>
	data:
	<pre><code>{data}</code></pre>
</body>
</html>
{head:}
	<nav class="navbar navbar-default">
	  <div class="container-fluid">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
		  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		  </button>
		  <a class="navbar-brand" href="?-infra/tests.php">Tests</a>
		</div>

		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		  <ul class="nav navbar-nav">
				<li role="presentation"><a href="?-infra/tests.php">tests</a></li>
				<li role="presentation"><a href="?-infra/admin.php">admin</a></li>
				<li role="presentation"><a href="?-imager/admin.php">imager</a></li>
				<li role="presentation"><a href="?-infra/install.php">install</a></li>
				<li role="presentation"><a href="?-infra/dirs.php">dirs</a></li>
				<li role="presentation"><a href="?-infra/config.php">config</a></li>
				<li role="presentation"><a href="?-session/admin.php">session</a></li>
				<li role="presentation"><a href="./">site</a></li>
		  </ul>
		</div><!-- /.navbar-collapse -->
	  </div><!-- /.container-fluid -->
	</nav>