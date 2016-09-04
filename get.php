<?php
namespace infrajs\infra;
use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\session\Session;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');
	require_once('vendor/autoload.php');
}

Access::test(true);
$ans = array();
$name = Path::toutf($_REQUEST['name']);
$ans['id'] = Session::getId();
$ans['data'] = Session::get($name);

return Ans::ret($ans);
