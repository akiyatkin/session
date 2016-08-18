<?php
use infrajs\ans\Ans;
use infrajs\session\Session;
use infrajs\router\Router;
use infrajs\once\Once;
use infrajs\access\Access;


if (!is_file('vendor/autoload.php')) {
	chdir('../../../');
	require_once('vendor/autoload.php');
	Router::init(); //Нужен чтобы работал конфиг .infra.json
}
Access::test(true);


Session::get();

$ans = array();
return Ans::ret($ans);


