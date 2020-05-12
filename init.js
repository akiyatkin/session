import { Seq } from '/vendor/infrajs/sequence/Seq.js'
import { Session } from '/vendor/infrajs/session/Session.js'
import { Template } from '/vendor/infrajs/template/Template.js'
//session и template
Seq.set(Template.scope, Seq.right('Session.get'), function (name, def) {
    return Session.get(name, def);
});
Seq.set(Template.scope, Seq.right('Session.getLink'), function () {
    return Session.getLink();
});
Seq.set(Template.scope, Seq.right('Session.getTime'), function () {
    return Session.getTime();
});
Seq.set(Template.scope, Seq.right('Session.getId'), function () {
    return Session.getId();
});