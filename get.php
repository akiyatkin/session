<?php

use infrajs\ans\Ans;

infra_test(true);
Path::req('*session/session.php');
$ans = array();
$name = Path::toutf($_REQUEST['name']);

$ans['data'] = infra_session_get($name);

return Ans::ret($ans);
