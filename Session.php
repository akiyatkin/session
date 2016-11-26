<?php
namespace infrajs\session;
use infrajs\once\Once;
use infrajs\view\View;
use infrajs\load\Load;
use infrajs\each\Each;
use infrajs\db\Db;
use infrajs\sequence\Sequence;
use infrajs\nostore\Nostore;
use infrajs\config\Config;
use PDO;

global $infra_session_data;
$infra_session_data = null;
class Session
{
	public static function initId() {
		//Инициализирует сессию если её нет и возвращает id
		$id = Session::getId();
		if (!$id) {
			Session::set();
		}

		return Session::getId();
	}
	public static function getName($name)
	{
		return 'infra_session_'.$name;
	}
	public static function recivenews($list = array())
	{
		global $infra_session_time;
		if (!$infra_session_time) $infra_session_time = 1;

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

		Load::unload($src);
		$ans = Load::loadJSON($src);
		$infra_session_time = $ans['time'];
		
		$_POST = $oldPOST;
		$_REQUEST = $oldREQ;

		return $ans;
	}
	public static function syncreq($list = array())
	{
		//новое значение, //Отправляется пост на файл, который записывает и возвращает данные
		$ans = Session::recivenews($list);
		if (!$ans) return;
		//По сути тут set(news) но на этот раз просто sync вызываться не должен, а так всё тоже самое
		global $infra_session_data;
		$infra_session_data = Session::make($ans['news'], $infra_session_data);
	}
	public static function getPass()
	{
		return View::getCookie(Session::getName('pass'));
	}
	public static function getId()
	{
		Once::exec(__FILE__.'getId_cache', function () {
			Nostore::on();
		});

		return (int) View::getCookie(Session::getName('id'));
	}
	public static function getTime()
	{
		return View::getCookie(Session::getName('time'));
	}
	public static function syncNow()
	{
		$ans = Session::recivenews();
		if (!$ans) {
			return;
		}
		//По сути тут set(news) но на этот раз просто sync вызываться не должен, а так всё тоже самое
		global $infra_session_data;
		$infra_session_data = Session::make($ans['news'], $infra_session_data);
	}
	public static function sync($list = null)
	{
		$session_id = Session::getId();
		if (!$session_id && !$list) {
			return;//Если ничего не устанавливается и нет id то sync не делается
		}
		Session::syncreq($list);
	}

	public static function &make($list, &$data = array())
	{
		Each::exec($list, function &($li) use (&$data) {
			$data = Sequence::set($data, $li['name'], $li['value']);
			$r=null; return $r;
		});
		return $data;
	}
	public static function get($name = '', $def = null)
	{
		Once::exec(__FILE__.'getinitsync', function () {
			Session::sync();
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
	public static function set($name = '', $value = null)
	{
		//if(Session::get($name)===$value)return; //если сохранена ссылка то изменение её не попадает в базу данных и не синхронизируется
		$right = Sequence::right($name);

		if (is_null($value)) {
			//Удаление свойства
			$last = array_pop($right);
			$val = Session::get($right);
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
					return Session::set($right, null);
				} else {
					array_push($right, $last);//Если есть ещё что-то то работает в обычном режиме
				}
			}
		}
		$li = array('name' => $right,'value' => $value);
		global $infra_session_data;

		Session::sync($li);
		$infra_session_data = Session::make($li, $infra_session_data);
	}

	public static function getLink($email = false)
	{
		$host = View::getHost();
		$path = View::getRoot();
		if ($email) {
			$user = Session::getUser($email);
			if (!$user) {
				return 'http://'.$host.'/'.$path;
			}
			$pass = md5($user['password']);
			$id = $user['session_id'];
		} else {
			$pass = View::getCookie(Session::getName('pass'));
			$id = View::getCookie(Session::getName('id'));
		}
		$link = 'http://'.$host.'/'.$path.'?-session/login.php?id='.$id.'&pass='.$pass;

		return $link;
	}

	public static function setPass($password, $session_id = null)
	{
		$db = &Db::pdo();
		if (!$db) {
			return;
		}

		if (is_null($session_id)) {
			$session_id = Session::initId();
		}
		$sql = 'UPDATE ses_sessions
					SET password = ?
					WHERE session_id=?';
		$stmt = $db->prepare($sql);

		return $stmt->execute(array($password, $session_id));
	}
	public static function getEmail($session_id = false)
	{
		if (!$session_id) {
			$session_id = Session::getId();
		}
		$user = Session::getUser($session_id);

		return $user['email'];
	}
	public static function setEmail($email)
	{
		$db = &Db::pdo();
		if (!$db) {
			return;
		}

		$session_id = Session::initId();
		$sql = 'UPDATE ses_sessions
					SET email = ?, date=now()
					WHERE session_id=?';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($email, $session_id));

		return true;
	}
	public static function getVerify()
	{
		$user = Session::getUser();

		return (bool) $user['verify'];
	}
	public static function setVerify()
	{
		$session_id = Session::getId();
		$db = &Db::pdo();
		if (!$db) return;
		$sql = 'UPDATE ses_sessions
					SET verify = 1
					WHERE session_id=?';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($session_id));
	}
	public static function getUser($email = null)
	{
		if (!$email) $email = Session::getId();

		return Once::exec(__FILE__.'getUser', function ($email) {
			$db = &Db::pdo();
			if (!$db) return;
			if (Each::isInt($email)) {
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

	public static function clear()
	{
		$db = &Db::pdo();
		if (!$db) return;
		$session_id = Session::getId();
		if (!$session_id) return;

		global $infra_session_data;
		$safe = $infra_session_data['safe'];
		//Удалить всё и сделать запись '', null и safe
		$sql = 'delete from `ses_records` where `session_id`=?';
		$stmt = $db->prepare($sql);
		$r = $stmt->execute(array($session_id));
		$infra_session_data = array();
		if ($safe) {
			Session::set('safe', $safe);
		}
	}
	public static function logout()
	{
		$email = Session::getEmail();
		if (!$email) return;
		View::setCookie(Session::getName('pass'));
		View::setCookie(Session::getName('id'));
		View::setCookie(Session::getName('time'));
		Session::syncNow();
	}
	public static function change($session_id, $pass = null)
	{
		$email = Session::getEmail();
		$session_id_old = Session::initId();
		if (!$email) {
			//Текущая сессия не авторизированная
			$email = Session::getEmail($session_id);
			if ($email) {
				//А вот новая сессия аторизированна, значит нужно объединить сессии и грохнуть старую
				$newans = Session::recivenews();
				//Нужно это всё записать в базу данных для сессии 1
				Session::writeNews($newans['news'], $session_id);

				//Теперь старую сессию нужно удалить полностью
				//Надо подчистить 2 таблицы
				if ($session_id_old) {
					//хз бывает ли такое что его нет
					$conf = Config::get();
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
			$user = Session::getUser($session_id);
			$pass = md5($user['password']);
		}

		View::setCookie(Session::getName('pass'), $pass);
		View::setCookie(Session::getName('id'), $session_id);
		View::setCookie(Session::getName('time'), 1);
		Session::syncNow();
	}
	public static function &user_init($email)
	{
		$user = Session::getUser($email);
		$session_id = $user['session_id'];
		$nowsession_id = Session::getId();
		if ($session_id == $nowsession_id) {
			return Session::get();
		}

		return Once::exec(__FILE__.'user_init', function ($session_id) {
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
	public static function user_get($email, $short = array(), $def = null)
	{
		$obj = &Session::user_init($email);
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
	public static function user_set($email, $short = array(), $value = null)
	{
		$user = Session::getUser($email);
		if (!$user) {
			return 'Email Not Found';
		}
		$obj = &Session::user_init($email);

		$right = Sequence::right($short);
		Sequence::set($obj, $right, $value);

		$list = array();
		$list['name'] = $right;
		$list['value'] = $value;
		$list['time'] = time();

		Session::writeNews($list, $user['session_id']);
	}
	public static function writeNews($list, $session_id)
	{
		if (!$list) return;
		$db = &Db::pdo();
		global $infra_session_lasttime;
		$isphp = !!$infra_session_lasttime;
		$sql = 'insert into `ses_records`(`session_id`, `name`, `value`, `time`) VALUES(?,?,?,FROM_UNIXTIME(?))';
		$stmt = $db->prepare($sql);
		$sql = 'delete from `ses_records` where `session_id`=? and `name`=? and `time`<=FROM_UNIXTIME(?)';
		$delstmt = $db->prepare($sql);
		Each::exec($list, function &($rec) use ($isphp, &$delstmt, &$stmt, $session_id) {
			$r = null;
			if (!$isphp && $rec['name'][0] == 'safe') return $r;
			$name = Sequence::short($rec['name']);
			$delstmt->execute(array($session_id, $name, $rec['time']));
			$stmt->execute(array($session_id, $name, Load::json_encode($rec['value']), $rec['time']));
			if (!$isphp && !$name) {
				//Сохранится safe
				Session::clear();
			}
			return $r;
		});
	}

}


