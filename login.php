<?php
use infrajs\session\Session;
use infrajs\path\Path;
use infrajs\view\View;
use infrajs\ans\Ans;

$ans = array();
$id = Ans::REQ('id','int');
$pass = Ans::REQ('pass');//md5 пароля, чтобы авторизоваться не нужно знать пароль, хэша достаточно.
$src = Ans::REQ('src','string','');
$path = View::getPath().$src;

$user = Session::getUser($id);
if (!$id || !$pass) {
	@header('Location: '.$path.'&error=1');
}
if (md5($user['password']) != $pass) {
	@header('Location: '.$path.'&error=2');
}


Session::change($id, $user['password']);

@header('Location: '.$path);



