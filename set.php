<?php

use infrajs\ans\Ans;

Path::req('*session/session.php');
infra_test(true);
$ans = array();
$name = Path::toutf($_REQUEST['name']);
$val = Path::toutf($_REQUEST['val']);

infra_session_set($name, $val);

return Ans::ret($ans);
