import { Config } from '/vendor/infrajs/config/Config.js'
import { View } from '/vendor/infrajs/view/View.js'
import { Seq } from '/vendor/infrajs/sequence/Seq.js'
import { Path } from '/vendor/infrajs/path/Path.js'
/*	
	var ses=infra.Session.init('base',view);

view объект - на клиенте создаваемый, как view=View.init(); на сервере view=View.init([request,response])
или View.get(); если view до этого уже создавался
	
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
**/
/**/

/*
	С помощью init получется объект сессии
	var session=infra.session;
	Типов сессий 5: data, base, face, tamp, temp
	Аналог $_SESSION из php это сессия base - не синхронизируется, сохраняется на диск, на клиенте хранится в локальном хранилище

* @param {string} type тип сессии
* @return {object}
*/
let Session = {
	init: function () {
		this.init = function () { };
		var list = this.storageLoad();
		this.data = this.make(list, null);
		this.syncNow();
	},
	getLink: function () {
		var id = View.getCookie(this._getName('id'));
		if (!id) return '';
		var host = View.getHost();
		var path = View.getRoot();
		var pass = View.getCookie(this._getName('pass'));
		var link = 'http://' + host + '/' + path + '?-session/login.php?id=' + id + '&pass=' + pass;
		return link;
	},
	_getName: function (name) {
		return 'infra_session_' + name;
	},
	stor: (function () {//функции для работы с локальным хранилищем браузера
		var iestor = false;
		var localstor = false;

		var is = false;
		try { is = !!window.localStorage; } catch (e) { };
		try { isses = !!window.sessionStorage; } catch (e) { };
		if (!is && !isses) {
			var iestor = document.getElementsByTagName('head');
			if (iestor && iestor[0]) {
				iestor = iestor[0];
			} else {
				infra.error('Не найден элемент head для локального хранилища');
				return {};
			}
			if (iestor && iestor.addBehavior) {
				iestor.addBehavior("#default#userData");
			} else {
				localstor = {};
			}
			try {
				iestor.load('namespace');
				iestor.getAttribute('test');
			} catch (e) {//infra.error(e,'stor.load',arguments.callee,"bug в ieTester ie6 Object doesn't doesn't support this property or method на getAttribute хотя alert(this.iestor.getAttribute) показывает функцию");
				iestor = false;//Просто будем на кукисах
			}
		}
		return {
			load: function (name) {
				if (is) {
					var val = window.localStorage['session.' + name];
				} else if (isses) {
					var val = window.sessionStorage['session.' + name];
				} else if (iestor) {
					iestor.load('namespace');
					var val = iestor.getAttribute(name);
				} else {
					//var view=View.init();
					//name=view.setCOOKIE(this._getName('time'),0);//Хранение локально невозможно
					var val = localstor[name];
				}
				//infra.exec(val,'session stor ar');
				try {
					if (val) val = eval('(' + val + ')');
				} catch (e) {
					val = [];
				}
				return val;
			},
			save: function (name, list) {
				list = Session.source(list);
				if (is) {
					window.localStorage['session.' + name] = list;
				} else if (isses) {
					window.sessionStorage['session.' + name] = list;
				} else if (iestor) {
					iestor.setAttribute(name, list);
					iestor.save('namespace');
				} else {
					localstor[name] = list;
				}
			}
		}
	})(),
	storageLoad: function () {//get
		var res = this.stor.load(this._getName('data'));
		if (!res) res = [];
		return res;
	},
	dataSave: function (nlist, repl) {
		if (repl) {
			this.data = this.make(nlist, {});
		} else {
			this.data = this.make(nlist, this.data);
		}
	},
	storage_repl: false,
	storage_process: false,
	storage_wait: [],
	storageSave: function (nlist, repl) {//set
		//nlist это корректный список {name:'',value:''}
		if (repl) {
			this.storage_repl = true;
			this.storage_wait = [nlist];
		} else {
			this.storage_wait.push(nlist);
		}
		if (!this.storage_process) {
			this.storage_process = true;
			var that = this;
			setTimeout(function () {
				that.storage_process = false;
				var repl = that.storage_repl;
				that.storage_repl = false;
				var nlist = that.storage_wait;
				that.storage_wait = [];
				if (repl) {
					var list = that.right(nlist);
				} else {
					var list = that.storageLoad();
					list.push(nlist);
					list = that.right(list);
				}
				var dataname = that._getName('data');
				that.stor.save(dataname, list);
			}, 1);
		}
	},
	syncreq: async (list, sync, callback) => { //новое значение, //Отправляется пост на файл, который записывает и возвращает данные
		var cb = (ans) => {
			if (!ans || !ans.result) return callback('Некорректный ответ сервера');
			//if(ans.msg)alert(ans.msg);

			//Если по инициативе сервера был выход, нужно сбросить клиентскую сессию
			if (!ans.auth) {
				Session.logout();
				return callback();
			}
			/*if (ans.created) {
				Session.storageSave([],true);
				Session.data={};
				var sentname=Session._getName('sent');
				var waitname=Session._getName('wait');
				Session.stor.save(waitname,false);
				Session.stor.save(sentname,false);
				
				Session.storageSave(list);
				Session.dataSave(list);
			}*/
			var timename = Session._getName('time');
			View.setCookie(timename, ans.time);//Время определяется на сервере, выставляется на клиенте

			//По сути тут set(news) но на этот раз просто sync вызываться не должен, а так всё тоже самое
			Session.storageSave(ans.news);
			Session.dataSave(ans.news);


			Event.tik('Session.onsync');
			Event.fire('Session.onsync'); //Сначало синхроинизируется корзина action=sync и сессия перезаписалась в файл заявки

			callback(); //Потом по окончанию синхронизации запускается Controller.check()

		};
		var data = {//id и time берутся из кукисов на сервере
			list: Session.source(list)
		}
		var load_path = Path.theme('-session/sync.php');
		let options = {
			url: load_path,
			timeout: 120000,
			async: !sync,
			type: 'POST',
			data: data,
			dataType: 'json',
			complete: function (req) {
				try {
					var ans = eval("(" + req.responseText + ")");
				} catch (e) {
					var ans = false;
				}
				cb(ans);
			}
		}
		if (!sync) {
			(async () => {
				let CDN = (await import('/vendor/akiyatkin/load/CDN.js')).default
				await CDN.load('jquery')
				$.ajax(options);
			})()
		} else {
			$.ajax(options);
		}
	},
	clear: function (cb) {
		Session.set('', null, false, cb);
		/*if (!cb) cb = function () { };
		var data = Session.get();
		var counter = 0;
		var check = function () {
			if (counter) return;
			cb();
		}
		for (var name in data) {
			var val = data[name];
			counter++;
			Session.set(name,null,false,function(){
				counter--;
				check();
			});
		}
		check();*/
	},
	logout: function () {
		this.storageSave([], true);
		this.data = {};

		var sentname = this._getName('sent');
		var waitname = this._getName('wait');
		this.stor.save(waitname, false);
		this.stor.save(sentname, false);

		View.setCookie(this._getName('time'));//Время определяется на сервере, выставляется на клиенте
		View.setCookie(this._getName('id'));
		View.setCookie(this._getName('pass'));
	},
	getId: function () {
		return View.getCOOKIE(this._getName('id'));
	},
	getTime: function () {//Нужно для определения последнего сеанса связи с сервером
		return View.getCOOKIE(this._getName('time'));
	},
	is: function () {
		for (var i in this.data) return true;
		return false;
	},
	right: function (list) {
		var rsent = [];
		infra.fora(list, function (li) {
			var short = Seq.short(li.name);
			if (infra.forr(rsent, function (rli) {
				if (Seq.short(rli.name) == short) return true;
			})) return;
			rsent.unshift(li);
		}, true);
		return rsent;
	},
	wait: [],
	callbacks: [],
	process: false,
	process_timer: false,
	syncNow: () => {
		Session.sync(null, true);
		//Не срабатывает если id нет
	},
	async: () => {
		return new Promise(resolve => Session.sync(null, null, resolve))
	},
	sync: function (list, sync, callback) { //false,false,callback
		if (!callback) callback = function () { };
		if (!this.getId() && (!list || (list.constructor == Array && list.length == 0)) && !this.is()) {//Если ничего не устанавливается и нет id то sync не делается
			return callback();
		}

		this.wait.push(list);
		this.callbacks.push(callback);
		var that = this;
		if (sync) {
			list = that.wait;
			that.wait = [];
			if (that.process) {
				clearTimeout(that.process_timer);
				that.process = false;
			}
			that._sync(list, sync, function () {
				var callbacks = that.callbacks;
				that.callbacks = [];
				for (var i = 0, l = callbacks.length; i < l; i++) {
					callbacks[i]();
				}
			});
		} else {
			if (that.process) return;
			that.process = true;
			clearTimeout(that.process_timer);
			that.process_timer = setTimeout(function () {
				that.process = false;
				var list = that.wait;
				that.wait = [];
				that._sync(list, sync, function () {
					var callbacks = that.callbacks;
					that.callbacks = [];
					for (var i = 0, l = callbacks.length; i < l; i++) {
						callbacks[i]();
					}
				});
			}, 1);
		}
	},
	isSync: function () {
		var sentname = this._getName('sent');
		var waitname = this._getName('wait');
		var sent = this.stor.load(sentname);//в sent хранится что уже в процессе отправления
		var wait = this.stor.load(waitname);//в wait скадывается всё новое что нужно отправить
		if (!sent && !wait) return false;
		return true;
	},
	_sync: function (list, sync, callback) {// Сюда попадает пулл запросво в одном setTimeout 1
		var sentname = this._getName('sent');
		var waitname = this._getName('wait');


		var wait = this.stor.load(waitname);//Задержка
		if (wait && list) wait.push(list);
		else if (wait && !list) wait = wait;
		else if (!wait && list) wait = [list];
		else if (!wait && !list) wait = [];
		wait = this.right(wait);
		var conf = Config.get();

		if (conf.session.sync && sync) {//Если просто вызыван sync с одним параметром или без
			this.stor.save(sentname, wait);//Всё записалось в sent и после успешной отправки очистится
			this.stor.save(waitname, false);//wait становится пустым, но пока будет отправка он может наполняться

			return this.syncreq(wait, sync, function (err) {
				if (err) {
					this.stor.save(waitname, wait);
					this.stor.save(sentname, false);
					callback(err);
				} else {
					this.stor.save(sentname, false);//Всё записалось в sent и после успешной отправки очистится
					callback(err);
				}
				//Event.tik('Session.onsync');
				//Event.fire('Session.onsync');
			}.bind(this));//синхронно вызываем сразу, вразрез с асинхронными
		}


		this.stor.save(waitname, wait);

		if (!conf.session.sync) {
			callback(false);
			return;
		}
		if (this.syncing) {
			this.syncing.push(callback);//при ошибке сессия больше не обновляется.. обработчике копятся.. и тп..
			return;
		} else {
			this.syncing = [callback];
		}

		var next = function () {//Возвращается был новый запрос или нет.
			var sent = this.stor.load(sentname);//в sent хранится что уже в процессе отправления
			var wait = this.stor.load(waitname);//в wait скадывается всё новое что нужно отправить
			if (!sent && !wait) return false;//Отправлять нечего. При пустой синхронизации будет true wait []

			if (!sent) sent = [];//Далее собираем всё в sent очищаем wait
			if (wait) sent.push(wait);//sent и wait могут быть одновременно если был разрыв связи при прошлом запросе

			sent = this.right(sent);

			this.stor.save(sentname, sent);//Всё записалось в sent и после успешной отправки очистится
			this.stor.save(waitname, false);//wait становится пустым, но пока будет отправка он может наполняться

			this.syncreq(sent, sync, function (err) {
				this.stor.save(sentname, false);
				if (err) {
					//setTimeout(next,5000);
					var wait = this.stor.load(waitname);
					if (wait) sent.push(wait);//добавили текущий sent в начало wait
					this.stor.save(waitname, this.right(sent));

					//фильтр натыкали а позиции показались без фильтра
					var calls = this.syncing;
					infra.forr(calls, function (ca) { ca(true) });
					this.syncing = false;
					conf.session.sync = false;//Ошибка отправка на сервер больше не будет работать пока не обновится страница
					//Event.tik('Session.onsync');
					//Event.fire('Session.onsync');
				} else {
					var r = next();
					if (!r) {//А если был запрос, попадём сюда снова после его окончания
						var calls = this.syncing;//Чтоб небыло замыканий прежде чем запускать обработчики очищается syncing
						this.syncing = false;
						infra.forr(calls, function (ca) { ca(false) });
						//Event.tik('Session.onsync');
						//Event.fire('Session.onsync');
					}
				}
			}.bind(this));
			return true;
		}.bind(this);
		setTimeout(next, Config.get('session').interval);
	},
	source: function (obj) {
		return JSON.stringify(obj);
	},
	make: function (list, data) {
		infra.fora(list, function (li) {
			if (!li) return;
			data = Seq.set(data, li.name, li.value);
		}.bind(this));
		return data;
	},
	get: function (name, def) { //data может быть undefined. get всегда синхронный сессия синхронно в первый раз синхронизировалась.
		this.init();
		name = Seq.right(name);
		var val = Seq.get(this.data, name);
		if (typeof (val) === 'undefined') return def;
		return val;
	},
	set: function (name, value, sync, fn) {
		if (value && typeof (value) == 'object' && value.constructor != Array) {
			for (var i in value) break;
			if (!i) {
				//alert('Запись в сессию пустого объекта невозможна,\nИначе объект {} превратится на сервере в массив []\nукажите в объекте какое-то свойство. Запись в '+name);
				value = null;
			}
		}

		var right = Seq.right(name);
		if (right.length > 1 && value === null || typeof (value) == 'undefined') { //Удаление свойства	
			var last = right.pop();
			var val = infra.session.get(right);

			if (last && val && typeof (val) == 'object' && val.constructor != Array) {
				var iselse = false;
				for (var i in val) {
					if (i != last) {
						iselse = true;
						break;
					}
				}
				if (!iselse) {//В объекте ничего больше нет кроме удаляемого свойства... или и его может даже нет
					//Зачит надо удалить и сам объект
					return infra.session.set(right, null, sync, fn);
				} else {
					right.push(last);//Если есть ещё что-то то работает в обычном режиме
				}
			}
		}



		var li = { name: right, value: value };
		if (right[0] == 'safe') {
			if (fn) fn();
			return false;
		}
		//При set делается 2 действия


		Session.storageSave(li);//Задержка!!!!
		Session.dataSave(li);

		Session.sync(li, sync, fn);//2 true синхронно
	},
	getValue: function (name, def) {//load для <input value="...
		var value = this.get(name);
		if (typeof (value) == 'undefined') value = def;
		value = value.replace(/"/g, '&quot;');
		return value;
	},
	getText: function (name, def) {//load для <texarea>...
		var value = this.get(name);
		if (typeof (value) == 'undefined') value = def;
		value = value.replace(/</g, '&lt;');
		value = value.replace(/>/g, '&gt;');
		return value;
	}
};
window.Session = infra.session = Session
export {Session}