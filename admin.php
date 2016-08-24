<?php
namespace infrajs\infra;
use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\ans\Ans;
use infrajs\session\Session;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');
	require_once('vendor/autoload.php');
}

Access::test(true);

$data = array();

if (!empty($_GET['id'])) {
	$id = (int) $_GET['id'];
} else {
	$id = Session::getId();
}

$db = Db::pdo();

$stmt = Db::stmt('select count(*) from ses_sessions');
$stmt->execute();
$data['count'] = $stmt->fetchColumn();

$data['id'] = $id;

$data['data'] = infra_session_user_get($id);
$data['data'] = print_r($data['data'], true);

$data['user'] = Session::getUser($id);
$data['user'] = print_r($data['user'], true);

echo Template::parse('-session/admin.tpl', $data);
