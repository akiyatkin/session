<?php

infra_test(true);

infra_require('*session/session.php');

$data = array();

if (!empty($_GET['id'])) {
	$id = (int) $_GET['id'];
} else {
	$id = infra_session_getId();
}

$db = infra_db();

$stmt = infra_stmt('select count(*) from ses_sessions');
$stmt->execute();
$data['count'] = $stmt->fetchColumn();

$data['id'] = $id;

$data['data'] = infra_session_user_get($id);
$data['data'] = print_r($data['data'], true);

$data['user'] = infra_session_getUser($id);
$data['user'] = print_r($data['user'], true);

echo infra_template_parse('*session/admin.tpl', $data);
