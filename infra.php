<?php
namespace infrajs\crumb;
use infrajs\controller\Controller;
use infrajs\each\Each;
use infrajs\controller\Run;
use infrajs\controller\Layer;
use infrajs\path\Path;
use infrajs\event\Event;
use infrajs\sequence\Sequence;
use infrajs\template\Template;
use infrajs\session\Session;
use infrajs\controller\External;


Path::reqif('-controller/infra.php');
	
$cl = function ($name, $def = null) { return Session::get($name, $def); };
Sequence::set(Template::$scope, Sequence::right('infra.session.get'), $cl);

$cl = function () { return Session::getLink(); };
Sequence::set(Template::$scope, Sequence::right('infra.session.getLink'), $cl);

$cl = function () { return Session::getTime(); };
Sequence::set(Template::$scope, Sequence::right('infra.session.getTime'), $cl);

$cl = function () { return Session::getId(); };
Sequence::set(Template::$scope, Sequence::right('infra.session.getId'), $cl);
