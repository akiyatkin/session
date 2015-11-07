<?php


$ans = array();
$ans['title'] = 'Проверка сессии на сервере';

$conf=infra_config();
if(!$conf['infra']['mysql']){
	$ans['class']='bg-warning';
	return infra_err($ans,'infra.mysql=false Нет разрешения на использование базы данных');
}

$db=&infra_db();
if(!$db){
	return infra_err($ans,'Не удалось соединиться с базой данных');
}
$val=infra_session_get('test');


$conf = infra_config();
if (!$conf['session']['sync']) {
	$ans['class'] = 'bg-warning';

	return infra_ret($ans, 'Сессия не синхронизируется с сервером session.sync:false');
}
if (!class_exists('PDO')) {
	return infra_err($ans, 'class PDO is required');
}
$db = &infra_db();
if (!$db) {
	return infra_err($ans, 'ERROR нет базы данных');
}
$val = infra_session_get('test');

$val = (int) $val + 1;
infra_session_set('test', $val);

$d = infra_session_get();
$ans['test'] = $d['test'];
if ($d['test'] > 1) {
	return infra_ret($ans, 'PASS');
} else {
	return infra_err($ans, 'ERROR нажмите 1 раз F5');
}
