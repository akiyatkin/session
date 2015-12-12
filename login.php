<?php
namespace infrajs\session;
use infrajs\path\Path;
use infrajs\view\View;
use infrajs\infra\Infra;

Path::req('*session/session.php');

$ans = array();
$id = $_REQUEST['id'];
$pass = $_REQUEST['pass'];//md5 пароля, чтобы авторизоваться не нужно знать пароль, хэша достаточно.
$src = $_REQUEST['src'];
if ($pass && $id) {
	infra_session_change($id, $pass);
}

if (!$src) {
	$src = '';
} else {
	$src = '?'.$src;
}
$conf = Infra::config();
$path = 'http://'.View::getHost().'/';

$path .= View::getRoot().$src;
@header('Location: '.$path);
