Event.one('Controller.oninit', function () { //Если есть контроллер. Подключение без прямой зависимости
	//session и template
	Sequence.set(Template.scope,Sequence.right('Session.get'),function(name,def){
		return Session.get(name,def);
	});
	Sequence.set(Template.scope,Sequence.right('Session.getLink'),function(){
		return Session.getLink();
	});
	Sequence.set(Template.scope,Sequence.right('Session.getTime'),function(){
		return Session.getTime();
	});
	Sequence.set(Template.scope,Sequence.right('Session.getId'),function(){
		return Session.getId();
	});
});