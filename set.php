<?php
use itlife\infra\ext\Ans;

infra_require('*session/session.php');
infra_test(true);
$ans=array();
$name=infra_toutf($_REQUEST['name']);
$val=infra_toutf($_REQUEST['val']);

infra_session_set($name, $val);

$ans['data']=infra_session_get();

return Ans::ret($ans);
