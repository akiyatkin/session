<?php
use infrajs\session\Session;
use infrajs\path\Path;
use infrajs\view\View;
use infrajs\config\Config;
use infrajs\infra\Infra;


if (!is_file('vendor/autoload.php')) {
	chdir(explode('vendor/', __DIR__)[0]);
	require_once('vendor/autoload.php');
}

$ans = array();
$id = $_REQUEST['id'];
$pass = $_REQUEST['pass'];//md5 пароля, чтобы авторизоваться не нужно знать пароль, хэша достаточно.
$src = $_REQUEST['src'];

if ($id && $pass) {
	Session::change($id, $pass);
}

if (!$src) {
	$src = '';
} else {
	$src = '/'.$src;
}
$conf = Config::get();
$path = 'http://'.View::getHost().'/';

$path .= View::getRoot().$src;
@header('Location: '.$path);
