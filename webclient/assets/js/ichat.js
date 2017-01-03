var chat = {
	wSock : null,
	storage : null,	

	ws : function () {
		try {
			this.wSock = new WebSocket(config.wsserver);
			this.wSock.onopen = this.onOpen;
			this.wSock.onmessage = this.onMessage;
			this.wSock.onclose = this.onClose;
			this.wSock.onerror = this.onError;
		}
		catch (ex) {
			log(ex);
		}
	},

	onOpen: function (event) {
		log("Welcome - status " + this.readyState);
	},

	onMessage: function (event) {
		log("Received: " + "");
	},

	onClose: function (event) {
		log("Welcome - status " + this.readyState);
	},

	onError : function (event) {
	}
};

var webui = {

};

var webctl = {
	doLogin : function (name, email) {
	},

	doLogout : function () {
	},
};

var app = {
	init : function () {
		this.off();
		this.copyright();
	},

	off : function () {
		document.onkeydown = function (event){
			if ( event.keyCode==116){
				event.keyCode = 0;
				event.cancelBubble = true;
				return false;
			} 
		}		
	},

	copyright : function() {
		console.log("您好！请多多指教.");
	}
};

function $(id) { return document.getElementById(id);}
function log(msg) { console.log(msg);}

// chat.ws();