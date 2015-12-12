<?php

use infrajs\ans\Ans;
$ans = array();
$ans['title'] = 'Проверка сессии на сервере';
return Ans::err($ans,'Не дописан тест');
$conf=Infra::config();
if(!$conf['infra']['mysql']){
	$ans['class']='bg-warning';
	return Ans::err($ans,'infra.mysql=false Нет разрешения на использование базы данных');
}

$db=&Db::pdo();
if(!$db){
	return Ans::err($ans,'Не удалось соединиться с базой данных');
}
$val=infra_session_get('test');


$conf = Infra::config();
if (!$conf['session']['sync']) {
	$ans['class'] = 'bg-warning';

	return Ans::ret($ans, 'Сессия не синхронизируется с сервером session.sync:false');
}
if (!class_exists('PDO')) {
	return Ans::err($ans, 'class PDO is required');
}
$db = &Db::pdo();
if (!$db) {
	return Ans::err($ans, 'ERROR нет базы данных');
}
$val = infra_session_get('test');

$val = (int) $val + 1;
infra_session_set('test', $val);

$d = infra_session_get();
$ans['test'] = $d['test'];
if ($d['test'] > 1) {
	return Ans::ret($ans, 'PASS');
} else {
	return Ans::err($ans, 'ERROR нажмите 1 раз F5');
}
