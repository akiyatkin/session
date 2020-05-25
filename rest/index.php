<?php

use infrajs\session\Session;
use infrajs\db\Db;
use infrajs\access\Access;
use infrajs\rest\Rest;
use infrajs\ans\Ans;


Access::debug(true);



return Rest::get( function(){
	$html = Rest::parse('-session/rest/layout.tpl');
	return Ans::html($html);
}, 'stat', function () {

	$db = Db::pdo();
	echo '<pre>';

	$sql = 'SELECT table_schema "DB Name", Round(Sum(data_length + index_length) / 1024 / 1024, 1) "DB Size in MB", TABLE_ROWS FROM information_schema.tables GROUP BY table_schema';
	$req = $db->prepare($sql);
	$req->execute();
	$res = $req->fetchAll();
	print_r($res);


	$sql = 'SELECT table_name AS `Table`,
round(((data_length + index_length) / 1024 / 1024), 2) `Size in MB`, TABLE_ROWS
FROM information_schema.TABLES
WHERE table_schema = "'.Db::$conf['database'].'"';
	$req = $db->prepare($sql);
	$req->execute();
	$res = $req->fetchAll();
	print_r($res);

	exit;
	//ses_records
	//ses_sessions
	
}, 'clear', [function () {
		//Удалить все записи у пользователей без email старее 1 месяца.
		$db = Db::pdo();
		$sql = 'DELETE r,s FROM ses_records r
	RIGHT JOIN ses_sessions s ON s.session_id = r.session_id
	WHERE s.email is null';
		$req = $db->prepare($sql);
		$req->execute();
		echo 'Удалено записей и сессий по которым не было указанного email: '.$req->rowCount();
		
	}, function ($clear, $email) {
		//Удалить все записи у пользователей без email старее 1 месяца.
		$db = Db::pdo();
		$sql = 'DELETE r,s FROM ses_records r
	RIGHT JOIN ses_sessions s ON s.session_id = r.session_id
	WHERE s.email = ?';
		$req = $db->prepare($sql);
		$req->execute([$email]);
		$sesemail = Session::getEmail();
		echo 'Удалён аккаунт c email '.$email.': '.$req->rowCount();
}], 'users', function () {
	//Access::admin(true);
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


				