<?php
use infrajs\once\Once;
use infrajs\view\View;
use infrajs\db\Db;
use infrajs\ans\Ans;
use infrajs\each\Each;
use infrajs\each\Fix;
use infrajs\load\Load;
use infrajs\session\Session;
use infrajs\sequence\Sequence;
use infrajs\router\Router;

if (!is_file('vendor/autoload.php')) {
	chdir(explode('vendor/', __DIR__)[0]);
	require_once('vendor/autoload.php');
	Router::init();
}
$ans = array();

try {
	$db = &Db::pdo();
} catch (Exception $e) {
	$db = false;
}

if (!$db) return Ans::err($ans, 'Нет соединения с базой данных. Сессия только в браузере.');


$session_id = View::getCookie('infra_session_id');
$session_pass = View::getCookie('infra_session_pass');

$timelast = isset($_REQUEST['time']) ? (int) $_REQUEST['time'] : View::getCookie(Session::getName('time'));
if (!$timelast) $timelast = 0;
$ans['timelast'] = $timelast;
$time = time();//время синхронизации и время записываемых данных, устанавливается в cookie
$ans['time'] = $time;
$list = Load::json_decode($_POST['list']);

Each::exec($list, function &(&$li) use ($time) {
	$li['time'] = $time; //У каждого сета добавляем его момет, что бы он начал попадат в выборку по времени в своём периуде
	$r = null; return $r;
});

if ($session_id) {
	$session_id = Once::exec('sync_php_checksession', function ($session_id, $session_pass) {
		$db = &Db::pdo();
		$sql = 'select password from ses_sessions where session_id=?';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($session_id));
		$real_pass = $stmt->fetchColumn();
		if (md5($real_pass) != $session_pass) {
			$session_id = false;
		}

		return $session_id;
	}, array($session_id, $session_pass), isset($_GET['re']));
}
$ans['auth'] = !!$session_id;
//Здесь session_id проверенный
if ($session_id && $timelast <= $time) {
	$sql = 'select name, value, unix_timestamp(time) as time from ses_records where session_id=? and time>=from_unixtime(?) order by time, rec_id';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($session_id, $timelast));
	$news = $stmt->fetchAll();
	if ($list) {
		$ans['list'] = $list;
	}

	//$ans['orignews']=$news;
	if ($news) {
		$ans['news'] = $news;
		Each::fora($ans['news'], function &(&$n) use ($list, &$ans) {
			$n['value'] = Load::json_decode($n['value'], true);
			$n['name'] = Sequence::right($n['name']);
			$r = Each::exec($list, function &($item) use (&$n, &$ans) {
				//Устанавливаемое значение ищим в новости
				$r = null;
				//найдено совпадение новости с устанавливаемым значением.. новость удаляем
				$a = Sequence::contain($item['name'], $n['name']);
				if ($a || $a == array()) {
					$r = true;
					return $r; //news Длиннее... и часть новости изменена в устанавливаемом значение
				}
				$ans['a'] = $a;
				//Новость ищим в устанавливаемом значение
				$right = Sequence::contain($n['name'], $item['name']);
				if ($right) {
					$n['value'] = Sequence::set($n['value'], $right, $item['value']);//Новость осталась но она включает устанавливаемые данные
				}
				return $r;
			});

			if ($r) {
				$del = new Fix('del');
				return $del;
			}
			$r = null;
			return $r;
		});
	}
}

if ($list) {
	if (!$session_id) {
		$pass = md5(print_r($list, true).time().rand());
		$pass = substr($pass, 0, 8);
		$sql = 'insert into `ses_sessions`(`password`) VALUES(?)';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($pass));
		$session_id = $db->lastInsertId();

		/*if(!empty($_SERVER['HTTP_ORIGIN'])) {
			header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Credentials: true');
		}*/

		View::setCookie('infra_session_id', $session_id);
		View::setCookie('infra_session_pass', md5($pass));
		$ans['auth'] = true;
		//$ans['created'] = true;
	}
	Session::writeNews($list, $session_id);
	//$ans['news']=array_merge($news,$list);
}

return Ans::ret($ans);
/**/
