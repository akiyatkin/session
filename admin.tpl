{root:}
<html>
<head>
	<link href="vendor/twbs/bootstrap/dist/css/bootstrap.css" rel="stylesheet">
	<script src="/-config/js.php"></script>
	<script src="/vendor/components/jquery/jquery.js"></script>
	<script src="/vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
	<script type="module">
		import {Crumb} from '/vendor/infrajs/controller/src/Crumb.js'
		window.Crumb = Crumb
		Crumb.init()
	</script>
</head>
<body>
	<form id="form">
	Смотрим: <input name="id" value="{id}" onchange="Crumb.go('?id='+this.value)"><br>
	<input type="submit" style="display:none">
	</form>
	Всего: {count}<br>
	user:
	<pre><code>{user}</code></pre>
	data:
	<pre><code>{data}</code></pre>
</body>
</html>