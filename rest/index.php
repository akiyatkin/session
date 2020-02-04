<?php

use infrajs\session\Session;
use infrajs\db\Db;
use infrajs\access\Access;
use infrajs\rest\Rest;
use infrajs\ans\Ans;






return Rest::get( function(){
	$html = Rest::parse('-session/rest/layout.tpl');
	return Ans::html($html);
}, 'stat', function () {
	$sql = 'SELECT table_schema "DB Name", Round(Sum(data_length + index_length) / 1024 / 1024, 1) "DB Size in MB", TABLE_ROWS FROM information_schema.tables GROUP BY table_schema';
	$db = Db::pdo();
	$req = $db->prepare($sql);
	$req->execute();
	$res = $req->fetchAll();
	echo '<pre>';
	print_r($sql);
	print_r($res);
	exit;
	//ses_records
	//ses_sessions
	$db = Db::pdo();
}, 'clear', function () {
	$db = Db::pdo();
}, 'users', function () {
	Access::admin(true);
	$db = Db::pdo();

	$sql = 'SELECT email, date, verify, password from ses_sessions where email is not null';
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$table = $stmt->fetchAll();
	$ans = array();
	if (sizeof($table)) {
		foreach ($table as $k => $v) {
			foreach ($v as $i => $val) if (!$table[$k][$i]) $table[$k][$i] = '&nbsp;';
		}
		//echo '<pre>';
		//print_r($table);
		//exit;
		
		$ans['table'] = $table;
		$ans['head'] = array_keys($table[0]);
	}
	$html = Rest::parse('-session/rest/layout.tpl', $ans, 'users');
	return Ans::html($html);
});


				