<?php

use infrajs\session\Session;
use infrajs\db\Db;
use infrajs\access\Access;
use infrajs\rest\Rest;
use infrajs\ans\Ans;

Access::admin(true);

return Rest::get( function(){
	$html = Rest::parse('-session/rest/layout.tpl');
	return Ans::html($html);
}, 'users', function () {
	$db = Db::pdo();

	$sql = 'SELECT email, date, verify from ses_sessions where email is not null';
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$table = $stmt->fetchAll();
	$ans = array();
	if (sizeof($table)) {
		$ans['table'] = $table;
		$ans['head'] = array_keys($table[0]);
	}
	$html = Rest::parse('-session/rest/layout.tpl', $ans, 'users');
	return Ans::html($html);
});


				