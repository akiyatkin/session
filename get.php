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

$ans['data'] = infra_session_get($name);

return Ans::ret($ans);
