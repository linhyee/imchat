var chat = {
	wSock : null,
	ws : function() {
		try {
			this.wSock = new WebSocket(config.wsserver);
			app.printMsg('Try connecting server....');
			app.handshake = false;

			this.wSock.onopen = this.onopen;
			this.wSock.onmessage = this.onmessage;
			this.wSock.onclose = this.onclose;
			this.wSock.onerror = this.onerror;

			log('WebSocket - status ' + this.wSock.readyState);
		} catch (ex) {
			log(ex);
		}
	},

	onopen: function(msg) {
		if (this.readyState == 1) {
			app.printMsg('Connected server successfully!');
			app.handshake = true;
		}
		log("Welcome - status " + this.readyState);
	},

	onmessage: function(msg) {
		try {
			var data = JSON.parse(msg.data);
			switch(data.type) {
			case 'presence':
				if (data.hasOwnProperty('session')) {
					if (data.session.islogin) {
						app.showInputBox();
						app.name = data.session.name;
						app.email = data.session.email;

						$.each(data.session.history_msg, function(i, v){
							var _msg = v.from+': '+v.msg;
							app.printMsg($.parseEmotion(_msg));
						});
					} else {
						alert("Login Error! Bad email format or Name less than 2 characers!!!")
					}
				}
				app.updateRoster(data.roster);
				break;
			case 'msg':
				var _msg = data.from+': '+data.data;
				app.printMsg($.parseEmotion(_msg));
				break;
			}
		} catch(ex) {
			log(ex);
		}
		log("Received: " + msg.data);
	},

	onclose: function(msg) {
		app.printMsg("Googbye!")
		app.handshake = false;
		app.name = '';
		app.email = '';
		log("Welcome - status " + this.readyState);
	},

	onerror : function(msg) {}
};

var app = {
	handshake : false,
	name : '',
	email : '',

	init : function() {
		this.off();
		this.copyright();
		$('.emo').emotion();
	},

	run : function() {
		this.init();
		chat.ws();
	},

	message: function() {
		var txt,msg;
		txt = $("#txt");
		msg = txt.val();
		if (!msg) {
			alert("Message can not be empty!!!");
			return;
		}
		if (chat.wSock.readyState == 3) {
			alert("Connection lost!!!")
			return;
		}
		if (!app.name) {
			alert("No login!!!")
			return;
		}
		txt.val('');
		txt.focus();
		try {
			chat.wSock.send(JSON.stringify({type:'msg',data:msg}));
		} catch(ex) {
			log(ex);
		}
	},

	updateRoster : function(roster) {
		var userlist = '';
		$(roster).each(function(i, data){
			userlist += '<li uid="'+data.name+'">' +
				'<img src="./assets/images/f1/f_'+data.icon+'.jpg"/>'+
				'<span>'+data.name+'&lt;'+data.email+'&gt;</span>'+
				'</li>'
		});
		if (userlist) {
			$('#box1 ul').html(userlist);
		}
	},

	showInputBox : function() {
		$('#loginbox').remove();
		$('#inputbox').show();
	},

	login: function(obj) {
		if (!app.handshake) {
			return;
		}
		var name = $(obj).find('input[name=name]').val();
		var email = $(obj).find('input[name=email]').val();

		var emailPatten = new RegExp('^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$');
		if (emailPatten.test(email) == false) {
			alert('Bad email format!');
			return;
		}
		if (name.trim().length < 2) {
			alert("The length of name is less than 2");
			return;
		}

		msg = {type:'login', name:name, email:email};
		try {
			chat.wSock.send(JSON.stringify(msg));
		} catch(ex) {
			log(ex);
		}
	},

	off : function() {
		$('#txt').keydown(function(e) {
			if (e.ctrlKey && (e.keyCode == 13 || e.keyCode == 10)) {
				app.message();
			}
		});
	},

	printMsg : function(msg) {
		var isBottom = app.msgPanelIsBottom();
		$('#row-1').append('<p>'+msg+'</p>');

		if (isBottom) {
			app.msgPanelSetBottom();
		}
	},

	msgPanelIsBottom : function() {
		var msgPanel = document.getElementById('row-1');
		return msgPanel.scrollHeight - msgPanel.scrollTop == msgPanel.clientHeight;
	},

	msgPanelSetBottom : function() {
		var msgPanel = document.getElementById('row-1');
		msgPanel.scrollTop = msgPanel.scrollHeight;
	},

	copyright : function() {
		console.log("您好！请多多指教(714480119@qq.com).");
	}
};

function log(msg) {
	console.log(msg);
}

function rnd(n, m){
	var random = Math.floor(Math.random()*(m-n+1)+n);
	return random;
}
app.run();
