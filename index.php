<?php
namespace infrajs\infra;
use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\session\Session;

if (!is_file('vendor/autoload.php')) {
	chdir(explode('vendor/', __DIR__)[0]);
	require_once('vendor/autoload.php');
}

Access::debug(true);
$ans = array();

if (isset($_GET['get'])) {
	$name = Ans::REQ('name');
	$ans['id'] = Session::getId();
	$ans['data'] = Session::get($name);
} else if (isset($_GET['clear'])) {	
	
	Session::clear();

} else if (isset($_GET['set'])) {
	
	$ans = array();
	$name = Path::toutf($_REQUEST['name']);
	$val = Path::toutf($_REQUEST['val']);
	Session::set($name, $val);

}

return Ans::ret($ans);