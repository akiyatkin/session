<?php

use infrajs\infra\ext\Ans;

infra_test(true);
infra_require('*session/session.php');
$ans = array();
$name = infra_toutf($_REQUEST['name']);

$ans['data'] = infra_session_get($name);

return Ans::ret($ans);
