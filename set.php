<?php
namespace infrajs\infra;
use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');
	require_once('vendor/autoload.php');
}

Access::test(true);

Path::req('-session/session.php');

$ans = array();
$name = Path::toutf($_REQUEST['name']);
$val = Path::toutf($_REQUEST['val']);

infra_session_set($name, $val);

return Ans::ret($ans);
