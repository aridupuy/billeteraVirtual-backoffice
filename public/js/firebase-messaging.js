/*
 var token;
 var messaging;*/
var mercalpha = $("#mercalpha").attr("data-value");
var sid = $("#sid").attr("data-value");
var config = {
    apiKey: "AIzaSyBfXUto5e7Opwn5g-OwZ_RilaXU2UdbpmY",
//    authDomain: "cobrodigital-6cbe7.firebaseapp.com",
//    databaseURL: "https://cobrodigital-6cbe7.firebaseio.com",
    projectId: "cobrodigital-6cbe7",
//    storageBucket: "cobrodigital-6cbe7.appspot.com",
    appId: "1:827884569748:web:2e213d7564271bca",
    measurementId: "G-18K2SKX73F",
    messagingSenderId: "827884569748"
};

firebase.initializeApp(config);
messaging = firebase.messaging();
firebase.analytics();

function menssaggin_start() {
  this.mercalpha = $("#mercalpha").attr("data-value");
   this.sid = $("#sid").attr("data-value");
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('./firebase-messaging-sw.js',
                {
                    scope: "/",
                    customParamFoo: "test",
                    bar: {
                        version: "1.2.0",
                        environment: "production"
                    }
                }
        ).then(function (registration) {
            console.log('Firebase Worker Registered');

        }).catch(function (err) {
            console.log('Service Worker registration failed: ', err);
        });
    }
}

function updateUIForPushEnabled(currenttoken) {

}

function setTokenSentToServer(bool) {
    sendTokenToServer(bool);
}
async function  sendTokenToServer(currentToken) {
//    alert(currentToken);
    //console.log(currentToken);
    //var mercalpha=$("#mercalpha").attr("data-value");
    //var sid=$("#sid").attr("data-value"); 
    //$("#mercalpha").attr("data-value","");
    //$("#sid").attr("data-value","");
    //localstorage.set("token",currentToken);
    localStorage.setItem('abcd1478', currentToken);

    data = {token: currentToken,
        metodo_webservice: "registrar_token",
        identificador_dispositivo: mercalpha,
        tipo: "web",
        sid: this.sid,
        idComercio: this.mercalpha
    };
    //console.log(data);
    await $.ajax({
//        url : 'https://www.cobrodigital.com:14365/apps/externo/script_landing_service.php',
        url: 'externo/script_landing_webnotification.php',
//        url : '/externo/script_landing_webservice_2.php',
        data: data,
        method: 'post', //en este caso
        dataType: 'json',
        success: function (response) {
            token = response;
        },
        error: function (error) {
            
            token = currentToken;
        }
    });
}
// Add the public key generated from the console here.
//            messaging.usePublicVapidKey("BIrm_647Yh7aC-eQlXita9BguNP4fPIGvObLiSeD4Rfoc0KlF6ycKgx-QQHPbphyuoeIkRCf5cuvuscfjytZltQ");
messaging.requestPermission().then(function () {
    console.log('Notification permission granted.');
    return  messaging.getToken();
}).catch(function (err) {
    console.log('Unable to get permission to notify.', err);
});
messaging.getToken().then(function (currentToken) {
    if (currentToken) {
        setTokenSentToServer(currentToken);
        updateUIForPushEnabled(currentToken);
    } else {
        // Show permission request.
        console.log('No Instance ID token available. Request permission to generate one.');
        // Show permission UI.
        updateUIForPushPermissionRequired();
        setTokenSentToServer(false);
    }
}).catch(function (err) {
    console.log('An error occurred while retrieving token. ', err);
//    alert('Error retrieving Instance ID token. ', err);
    setTokenSentToServer(false);
});
messaging.onTokenRefresh(function () {
    messaging.getToken().then(function (refreshedToken) {
        console.log('Token refreshed.');
        // Indicate that the new Instance ID token has not yet been sent to the
        // app server.
        setTokenSentToServer(false);
        // Send Instance ID token to app server.
        sendTokenToServer(refreshedToken);
        // ...
    }).catch(function (err) {
        console.log('Unable to retrieve refreshed token ', err);
        showToken('Unable to retrieve refreshed token ', err);
    });
});

messaging.onMessage(function (payload) {
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        parameters: payload.notification.parameters,
    };
    const notificationData = {
        body: payload.data.body,
        tittle: payload.data.titulo,
        cuerpo: payload.data.cuerpo,
        nivel: payload.data.nivel,
        activity: payload.data.activity,
        importante: payload.data.importante,
        archivado: payload.data.archivado,
        destacado: payload.data.destacado,
    };
    // Customize notification here
    //var notificationTitle = 'Background Message Title';

    return showNotification(notificationTitle,
            notificationData, notificationOptions);
});
function showNotification(notificationTitle, notificationData, notificationOptions) {
    var nro_noti = $("#nro_noti").html();
    $("#nro_noti").html(parseInt(nro_noti) + 1);
    if (parseInt($("#nro_noti").html()) == 0) {
        $("#nro_noti").attr("style", "display:none");
    } else {
        $("#nro_noti").removeAttr("style");
    }
    if (notificationData.destacado != 0) {
        var destacado = $("#nro_destacadas");
        if (destacado != undefined) {
            var nro_destacada = $(destacado).html();
            $("#nro_destacadas").html(parseInt(nro_destacada) + 1);
        }
    }
    if (notificationData.archivado != 0) {
        var archivado = $("#nro_archivadas");
        if (archivado != undefined) {
            var nro_archivado = $(archivado).html();
            $("#nro_archivadas").html(parseInt(nro_archivado) + 1);
        }
    }
    if (notificationData.importante != 0) {
        var importante = $("#nro_importantes");
        if (importante != undefined) {
            var nro_importante = $(importante).html();
            $("#nro_importantes").html(parseInt(nro_importante) + 1);
        }
    }
    var noleida = $("#nro_no_leido");
    if (noleida != undefined) {
        var nro_noleida = $(noleida).html();
        $("#nro_no_leido").html(parseInt(nro_noleida) + 1);
    }
    var todas = $("#nro_todas");
    if (todas != undefined) {
        var nro_todas = $(todas).html();
        $("#nro_todas").html(parseInt(nro_todas) + 1);
    }
    popNoti(notificationData.cuerpo, notificationData.tittle);
    
}

/*messaging.setBackgroundMessageHandler(function (payload) {
 const notificationTitle = payload.notification.title;
 const notificationOptions = {
 body: payload.notification.body,
 };
 return self.registration.showNotification(notificationTitle,
 notificationOptions);
 });
 }*/
