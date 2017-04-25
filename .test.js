Event.one('Controller.onshow', function(){
	var test=infra.test;
	var server={
		set:function(name,value){
			var path='?-session/set.php?name='+name+'&val='+value;
			infra.unload(path);
			var r=infra.loadJSON(path);
		},
		get:function(name){
			var path='?-session/get.php?name='+name;
			infra.unload(path);
			var r=infra.loadJSON(path);
			return r['data'];
		}
	}
	test.tasks.push([
		'guarantee - может понадобиться одна перезагрузка для теста',
		function(){
			test.check();
		},
		function(){

			var name='test.guarantee';
			var clt=infra.session.get(name);
			var srv=server.get(name);
			if(clt&&srv){
				//Сбросим для следующего теста
				infra.session.set('test.guarantee',null,true);	
				return test.ok();
			}
			if (!clt && !srv) {
				infra.session.set('test.guarantee','ok');
				location.href=location.href;
				return;
			}
			console.info('Fix');
			console.log('?-session/get.php?name=guarantee');
			console.log('?-session/set.php?name=guarantee&val=ok');
			console.log('?-session/set.php?name=guarantee');
			if (clt) {
				return test.err('На сервере значения нет, а в браузере есть');
			}
			if (srv) {
				return test.err('На сервере есть установленное значение, а в браузере такого значения нет');
			}
			
		}
	]);

	test.tasks.push([
		'В одной секунде. Клиент потом сервер',
		function(){
			infra.session.set('test','client',true);
			server.set('test','server');
			infra.session.syncNow();
			test.check();
		},
		function(){
			if(infra.session.get('test')!='server')return test.err(infra.session.get('test'));
			infra.session.set('test',null,true);
			test.ok();
		}
	]);


	test.tasks.push([
		'В одной секунде. Cервер потом клиент',
		function(){
			server.set('test','server');
			infra.session.set('test','client',true);
			infra.session.syncNow();
			test.check();
		},
		function(){
			if(infra.session.get('test')!='client')return test.err(infra.session.get('test'));
			infra.session.set('test',null,true);
			test.ok();
		}
	]);

	test.tasks.push([
		'Асинхронно. В одной секунде. Клиент потом сервер',
		function(){
			infra.session.set('test','client');
			server.set('test','server');
			infra.session.syncNow();
			test.check();
		},
		function(){//Синхронная запись Клиент придёт позже... и это норм.
			if(infra.session.get('test')!='client')return test.err(infra.session.get('test'));
			infra.session.set('test',null,true);
			test.ok();
		}
	]);


	test.tasks.push([
		'Асинхронно. В одной секунде. Cервер потом клиент',
		function(){
			server.set('test','server');
			infra.session.set('test','client');
			infra.session.syncNow();
			test.check();
		},
		function(){
			if(infra.session.get('test')!='client')return test.err(infra.session.get('test'));
			infra.session.set('test',null,true);
			test.ok();
		}
	]);

	test.tasks.push([
		'Удаление последнего свойства в объекте',
		function(){
			infra.session.set('test.test.test',{count:1},true,function(){});
			infra.session.set('test.test.test.count',null,true,function(){});
			test.check();
		},
		function(){
			if(infra.session.get('test'))return test.err('Не удалился test');
			test.ok();
		}
	]);

	test.tasks.push([
		'Работа с одим name',
		function(){
			infra.session.set('test',true,true);
			infra.session.set('test',null,true);
			test.check();
		},
		function(){
			if(infra.session.get('test'))return test.err('Не удалился test');
			test.ok();
		}
	]);
	
	test.tasks.push([
		'Срабатывание callback при удалении',
		function(){
			infra.session.set('test.test',null,true,function(){
				test.check();
			});
		},
		function(){
			test.ok();
		}
	]);
	test.tasks.push([
		'Установка и удаление срабатываение callback',
		function(){
			infra.session.set('test.test',true,true); infra.session.set('test.test',null,true,function(){
				test.check();
			});
		},
		function(){
			test.ok();
		}
	]);
	test.tasks.push([
		'Туда Сюда',
		function(){
			infra.session.set('test.test',true,true); 
			infra.session.set('test.test',null,true);
			infra.session.set('test.test',true,true);
			infra.session.set('test.test',null,true);
			test.check();
		},
		function(){
			if(infra.session.get('test.test'))test.err('Значение осталось');
			test.ok();
		}
	]);
	test.tasks.push([
		'Проверка safe',
		function(){
			infra.session.set('safe.test1',true,true); 
			infra.session.set('safe.test2',1,false,function(){
				test.check();		
			});
		},
		function(){
			if(infra.session.get('safe.test1')||infra.session.get('safe.test2'))test.err('Мы как-то установили значение в safe');
			test.ok();
		}
	]);
	
	test.exec();
});
