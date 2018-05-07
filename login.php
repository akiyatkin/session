<?php
use infrajs\session\Session;
use infrajs\path\Path;
use infrajs\view\View;
use infrajs\ans\Ans;

$ans = array();
$id = Ans::REQ('id','int');
$pass = Ans::REQ('pass');//md5 пароля, чтобы авторизоваться не нужно знать пароль, хэша достаточно.
$src = Ans::REQ('src','string','');

if ($id && $pass) {
	Session::change($id, $pass);
}

$path = View::getPath().$src;
@header('Location: '.$path);
