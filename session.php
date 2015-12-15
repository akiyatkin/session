<?php
use infrajs\once\Once;
/*
Copyright 2011 ITLife, Ltd. Togliatti, Samara Oblast, Russian Federation. http://itlife-studio.ru
	
	var ses=infra.Session.init('base',view);

view объект - на клиенте создаваемый, как view=infra.View.init(); на сервере view=infra.View.init([request,response])
или infra.View.get(); если view до этого уже создавался
	
	//Основной приём работы с сессией
	ses.set('name','value');
	ses.get('name');

Данные сессии это объект и можно добавлять значения в иерархию этого объекта

	ses.set('basket.list.DF2323','12'); //В данном случае объект сессии если до этого был пустой 
	//примет вид {basket:{list:{DF2323:'12'}}}
	ses.get('basket'); //Вернётся объект {list:{DF2323:'12'}}

В данном случае точка специальный символ определяющий уровень вложенность для сохраняемого значения. Так как точка также может быть в имени свойства для этого используется следующий синтаксис.
	
	ses.set(['basket','list','KF.56','1');
	ses.get('basket.list'); //или
	ses.get(['basket','list']); //Вернёт объект {'KF.56':'1'}
*
*
*
* КУКИ
* time
* id
* pass
**/
/**/


global $infra_session_data;

function infra_session_initId()
{
	//Инициализирует сессию если её нет и возвращает id
	$id = infra_session_getId();
	if (!$id) {
		infra_session_set();
	}

	return infra_session_getId();
}
function infra_session_getName($name)
{
	return 'infra_session_'.$name;
}
function infra_session_recivenews($list = array())
{
	global $infra_session_time;
	if (!$infra_session_time) {
		$infra_session_time = 1;
	}

	$data = array( //id и time берутся из кукисов на сервере
		'time' => $infra_session_time,
		'list' => Load::json_encode($list),
	);
	global $infra_session_lasttime;
	$infra_session_lasttime = true;//Метка что вызов из php
	$oldPOST = $_POST;
	$oldREQ = $_REQUEST;
	$_POST = $data;
	$_REQUEST = $data;

	$src = '-session/sync.php';

	infra_unload($src);
	$ans = Load::loadJSON($src);
	$infra_session_time = $ans['time'];
	//echo '<pre>';
	//print_r($ans);
	//exit;
	$_POST = $oldPOST;
	$_REQUEST = $oldREQ;

	return $ans;
}
function infra_session_syncreq($list = array())
{
	//новое значение, //Отправляется пост на файл, который записывает и возвращает данные
	$ans = infra_session_recivenews($list);
	if (!$ans) {
		return;
	}
	//По сути тут set(news) но на этот раз просто sync вызываться не должен, а так всё тоже самое
	global $infra_session_data;
	$infra_session_data = infra_session_make($ans['news'], $infra_session_data);
}
function infra_session_getPass()
{
	return infra_view_getCookie(infra_session_getName('pass'));
}
function infra_session_getId()
{
	Once::exec('infra_session_getId_cache', function () {
		header('Cache-Controll: no-store');
	});

	return (int) infra_view_getCookie(infra_session_getName('id'));
}
function infra_session_getTime()
{
	return infra_view_getCookie(infra_session_getName('time'));
}
function infra_session_syncNow()
{
	$ans = infra_session_recivenews();
	if (!$ans) {
		return;
	}
	//По сути тут set(news) но на этот раз просто sync вызываться не должен, а так всё тоже самое
	global $infra_session_data;
	$infra_session_data = infra_session_make($ans['news'], $infra_session_data);
}
function infra_session_sync($list = null)
{
	$session_id = infra_session_getId();

	if (!$session_id && !$list) {
		return;//Если ничего не устанавливается и нет id то sync не делается
	}

	infra_session_syncreq($list);
}

function &infra_session_make($list, &$data = array())
{
	Each::fora($list, function ($li) use (&$data) {
		$data = &Sequence::set($data, $li['name'], $li['value']);
		$r=null; return $r;
	});

	return $data;
}
function infra_session_get($name = '', $def = null)
{
	Once::exec('infra_session_getinitsync', function () {
		infra_session_sync();
	});
	$name = Sequence::right($name);
	global $infra_session_data;
	$val = Sequence::get($infra_session_data, $name);
	if (is_null($val)) {
		return $def;
	} else {
		return $val;
	}
}
function infra_session_set($name = '', $value = null)
{
	//if(infra_session_get($name)===$value)return; //если сохранена ссылка то изменение её не попадает в базу данных и не синхронизируется
	$right = Sequence::right($name);

	if (is_null($value)) {
		//Удаление свойства
		$last = array_pop($right);
		$val = infra_session_get($right);
		if ($last && Each::isAssoc($val) === true) {
			//Имеем дело с ассоциативным массивом
			$iselse = false;
			foreach ($val as $i => $valval) {
				if ($i != $last) {
					$iselse = true;
					break;
				}
			}
			if (!$iselse) {
				//В объекте ничего больше нет кроме удаляемого свойства... или и его может даже нет
				//Зачит надо удалить и сам объект
				return infra_session_set($right, null);
			} else {
				array_push($right, $last);//Если есть ещё что-то то работает в обычном режиме
			}
		}
	}
	$li = array('name' => $right,'value' => $value);
	global $infra_session_data;

	infra_session_sync($li);
	$infra_session_data = infra_session_make($li, $infra_session_data);
}

function infra_session_getLink($email = false)
{
	$host = infra_view_getHost();
	$path = infra_view_getRoot();
	if ($email) {
		$user = infra_session_getUser($email);
		if (!$user) {
			return 'http://'.$host.'/'.$path;
		}
		$pass = md5($user['password']);
		$id = $user['session_id'];
	} else {
		$pass = infra_view_getCookie(infra_session_getName('pass'));
		$id = infra_view_getCookie(infra_session_getName('id'));
	}
	$link = 'http://'.$host.'/'.$path.'?-session/login.php?id='.$id.'&pass='.$pass;

	return $link;
}
/*
function infra_session_getValue($name,$def){//load для <input value="...
	$value=infra_session_get($name);
	if(is_null($value))$value=$def;
	$value=preg_replace('/"/','&quot;',$value);
	return $value;
}
function infra_session_getText($name,$def){ //load для <texarea>...
	$value=infra_session_get($name);
	if(is_null($value))$value=$def;
	$value=preg_replace('/</','&lt;',$value);
	$value=preg_replace('/>/','&gt;',$value);
	return $value;
}*/

function infra_session_setPass($password, $session_id = null)
{
	$db = &Db::pdo();
	if (!$db) {
		return;
	}

	if (is_null($session_id)) {
		$session_id = infra_session_initId();
	}
	$sql = 'UPDATE ses_sessions
				SET password = ?
				WHERE session_id=?';
	$stmt = $db->prepare($sql);

	return $stmt->execute(array($password, $session_id));
}
function infra_session_getEmail($session_id = false)
{
	if (!$session_id) {
		$session_id = infra_session_getId();
	}
	$user = infra_session_getUser($session_id);

	return $user['email'];
}
function infra_session_setEmail($email)
{
	$db = &Db::pdo();
	if (!$db) {
		return;
	}

	$session_id = infra_session_initId();
	$sql = 'UPDATE ses_sessions
				SET email = ?, date=now()
				WHERE session_id=?';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($email, $session_id));

	return true;
}
function infra_session_getVerify()
{
	$user = infra_session_getUser();

	return (bool) $user['verify'];
}
function infra_session_setVerify()
{
	$session_id = infra_session_getId();
	$db = &Db::pdo();
	if (!$db) {
		return;
	}
	$sql = 'UPDATE ses_sessions
				SET verify = 1
				WHERE session_id=?';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($session_id));
}
function infra_session_getUser($email = null)
{
	if (!$email) {
		$email = infra_session_getId();
	}

	return Once::exec('infra_session_getUser', function ($email) {
		$db = &Db::pdo();
		if (!$db) {
			return;
		}
		if (infra_isInt($email)) {
			$sql = 'select * from ses_sessions where session_id=?';
		} else {
			$sql = 'select * from ses_sessions where email=?';
		}
		$stmt = $db->prepare($sql);
		$stmt->execute(array($email));
		$userData = $stmt->fetch(PDO::FETCH_ASSOC);

		return $userData;
	}, array($email));
}

function infra_session_clear()
{
}
function infra_session_logout()
{
	$email = infra_session_getEmail();
	if (!$email) {
		return;
	}
	infra_view_setCookie(infra_session_getName('pass'));
	infra_view_setCookie(infra_session_getName('id'));
	infra_view_setCookie(infra_session_getName('time'));
	infra_session_syncNow();
}
function infra_session_change($session_id, $pass = null)
{
	$email = infra_session_getEmail();
	$session_id_old = infra_session_initId();
	if (!$email) {
		//Текущая сессия не авторизированная
		$email = infra_session_getEmail($session_id);
		if ($email) {
			//А вот новая сессия аторизированна, значит нужно объединить сессии и грохнуть старую
			$newans = infra_session_recivenews();
			//Нужно это всё записать в базу данных для сессии 1
			infra_session_writeNews($newans['news'], $session_id);

			//Теперь старую сессию нужно удалить полностью
			//Надо подчистить 2 таблицы
			if ($session_id_old) {
				//хз бывает ли такое что его нет
				$conf = Infra::config();
				$tables = $conf['session']['change_session_tables'];//Массив с таблицами в которых нужно изменить session_id неавторизированного пользователя, при авторизации
				$db = Db::pdo();

				Each::forr($tables, function () use ($session_id_old, $session_id, &$db) {
					$sql = 'UPDATE images SET session_id = ? WHERE session_id = ?;';
					$stmt = $db->prepare($sql);
					$stmt->execute(array($session_id, $session_id_old));
				});

				$sql = 'DELETE from ses_records where session_id=?';
				$stmt = $db->prepare($sql);
				$stmt->execute(array($session_id_old));
				$sql = 'DELETE from ses_sessions where session_id=?';
				$stmt = $db->prepare($sql);
				$stmt->execute(array($session_id_old));
			}
		}
	}

	global $infra_session_data;
	$infra_session_data = array();

	if (is_null($pass)) {
		$user = infra_session_getUser($session_id);
		$pass = md5($user['password']);
	}

	infra_view_setCookie(infra_session_getName('pass'), $pass);
	infra_view_setCookie(infra_session_getName('id'), $session_id);
	infra_view_setCookie(infra_session_getName('time'), 1);
	infra_session_syncNow();
}
function &infra_session_user_init($email)
{
	$user = infra_session_getUser($email);
	$session_id = $user['session_id'];
	$nowsession_id = infra_session_getId();
	if ($session_id == $nowsession_id) {
		return infra_session_get();
	}

	return Once::exec('infra_session_user_init', function ($session_id) {
		$sql = 'select name, value, unix_timestamp(time) as time from ses_records where session_id=? order by time,rec_id';
		$db = Db::pdo();
		$stmt = $db->prepare($sql);
		$stmt->execute(array($session_id));
		$news = $stmt->fetchAll();

		if (!$news) {
			$news = array();
		}

		$obj = array();
		Each::forr($news, function (&$v) use (&$obj) {
			if ($v['value'] == 'null') {
				$value = null;
			} else {
				$value = Load::json_decode($v['value']);
			}
			$right = Sequence::right($v['name']);
			$obj = Sequence::set($obj, $right, $value);
		});

		return $obj;
	}, array($session_id));
}
function infra_session_user_get($email, $short = array(), $def = null)
{
	$obj = &infra_session_user_init($email);
	$right = Sequence::right($short);
	$value = Sequence::get($obj, $right);
	if (is_null($value)) {
		$value = $def;
	}

	return $value;
}

/**
 * Записывает в сессию session_id или email имя и значение.
 *
 * @param string|int	  $email Может быть $session_id
 * @param string|string[] $short Может быть $right путь до значения в объекте
 * @param mixed		   $value Значение для записи. Любое значение записывается даже null, которое по факту приводит к удалению значения
 *
 * @return void|string Строка-ошибка
 */
function infra_session_user_set($email, $short = array(), $value = null)
{
	$user = infra_session_getUser($email);
	if (!$user) {
		return 'Email Not Found';
	}
	$obj = &infra_session_user_init($email);

	$right = Sequence::right($short);
	Sequence::set($obj, $right, $value);

	$list = array();
	$list['name'] = $right;
	$list['value'] = $value;
	$list['time'] = time();

	infra_session_writeNews($list, $user['session_id']);
}
function infra_session_writeNews($list, $session_id)
{
	if (!$list) {
		return;
	}
	$db = Db::pdo();
	global $infra_session_lasttime;
	$isphp = !!$infra_session_lasttime;
	$sql = 'insert into `ses_records`(`session_id`, `name`, `value`, `time`) VALUES(?,?,?,FROM_UNIXTIME(?))';
	$stmt = $db->prepare($sql);
	$sql = 'delete from `ses_records` where `session_id`=? and `name`=? and `time`<=FROM_UNIXTIME(?)';
	$delstmt = $db->prepare($sql);
	Each::fora($list, function ($rec) use ($isphp, &$delstmt, &$stmt, $session_id) {
		$r=null; 
		if (!$isphp && $rec['name'][0] == 'safe') return $r;
		$name = Sequence::short($rec['name']);
		$delstmt->execute(array($session_id, $name, $rec['time']));
		$stmt->execute(array($session_id, $name, Load::json_encode($rec['value']), $rec['time']));
		return $r;
	});
}
