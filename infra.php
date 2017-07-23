<?php
use infrajs\event\Event;
use infrajs\sequence\Sequence;
use infrajs\template\Template;
use infrajs\session\Session;

Event::one('Controller.oninit', function () {
	$cl = function ($name = null, $def = null) { return Session::get($name, $def); };
	Sequence::set(Template::$scope, Sequence::right('Session.get'), $cl);

	$cl2 = function () { return Session::getLink(); };
	Sequence::set(Template::$scope, Sequence::right('Session.getLink'), $cl2);

	$cl3 = function () { return Session::getTime(); };
	Sequence::set(Template::$scope, Sequence::right('Session.getTime'), $cl3);

	$cl4 = function () { return Session::getId(); };
	Sequence::set(Template::$scope, Sequence::right('Session.getId'), $cl4);
});
