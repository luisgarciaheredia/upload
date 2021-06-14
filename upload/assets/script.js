var app = {

    name: "upload",

    users: [
        {
            user: "luisgarcia",
            pass: "luisgarcia",
            name: "Luis",
            type: "admin"
        },
        {
            user: "admin",
            pass: "admin",
            name: "Administrador",
            type: "admin"
        }
    ],

    init: function () {
        var _this = this;
        _this.set_url_base();
        _this.set_dom();
        _this.set_listeners();
        _this.set_defaults();
        _this.show_app();
    },

    set_url_base: function () {
        var _this = this;
        _this.urlBase = window.location.hostname === "luisgarciaheredia.com"
                ? "http://luisgarciaheredia.com/entel3/entel/upload" : "http://localhost/upload/upload";
    },

    set_dom: function () {
        var _this = this;
        _this.dom = {};


        // login

        _this.dom.input_login_user = document.querySelectorAll("#input-login-user")[0];
        _this.dom.input_login_pass = document.querySelectorAll("#input-login-pass")[0];
        _this.dom.form_login = document.querySelectorAll("#login form")[0];
        _this.dom.div_login = document.querySelectorAll("#login")[0];


        // content

        _this.dom.div_content = document.querySelectorAll("#content")[0];


        // menu

        _this.dom.link_menu = document.querySelectorAll("#side a");


        // pickup form

//        _this.dom.select_mes = document.querySelectorAll("#select_mes")[0];
//        _this.dom.div_embudo = document.querySelectorAll("#mod_form form .embudo")[0];
        _this.dom.form_register = document.querySelectorAll("#mod_form_pickup form")[0];
//        _this.dom.button_guardar = document.querySelectorAll("#mod_form button[type=submit]")[0];
//        _this.dom.div_progress = document.querySelectorAll("#mod_form .preloader-wrapper")[0];
//        _this.dom.div_modal_content = document.querySelectorAll("#mod_form .modal-content")[0];


        // inar form

        _this.dom.inar_filename = document.querySelectorAll("#mod_form_inar input")[0];
        _this.dom.inar_form = document.querySelectorAll("#mod_form_inar form")[0];
        _this.dom.inar_button_guardar = document.querySelectorAll("#mod_form_inar button[type=submit]")[0];
        _this.dom.inar_div_progress = document.querySelectorAll("#mod_form_inar .preloader-wrapper")[0];
        _this.dom.inar_div_modal_content = document.querySelectorAll("#mod_form_inar .modal-content")[0];

    },

    set_listeners: function () {
        var _this = this;


        // login 

        _this.dom.form_login.addEventListener("submit", _this.submit_form_login.bind(_this));


        // menu

        _this.dom.link_menu.forEach(function (element) {
            element.addEventListener("click", _this.click_link_menu.bind(_this));
        });


        // pickup form

        //_this.dom.select_mes.addEventListener("change", _this.change_select_mes.bind(_this));
        //_this.dom.form_register.addEventListener("submit", _this.submit_form_register.bind(_this));


        // inar form

        _this.dom.inar_form.addEventListener("submit", _this.submit_inar_form.bind(_this));

    },

    show_preloader: function () {
        var _this = this;
        _this.dom.inar_div_progress.classList.remove("hide");
        _this.dom.inar_div_modal_content.classList.add("hide");
        _this.dom.inar_button_guardar.classList.add("disabled");
    },

    hide_preloader: function () {
        var _this = this;
        _this.dom.inar_div_progress.classList.add("hide");
        _this.dom.inar_div_modal_content.classList.remove("hide");
        _this.dom.inar_button_guardar.classList.remove("disabled");
    },

    click_link_menu: function (event) {
        var _this = this;
        _this.menu_item = event.target.id;

        if (_this.menu_item === "lnk_pickup") {


            // pickup

            _this.click_pickup_menu();


        } else if (_this.menu_item === "lnk_inar") {


            // inar

            _this.click_inar_menu();


            // mes actual

            var mes = (new Date()).getMonth();
            //_this.dom.select_mes.value = mes;
            //_this.dom.select_mes.dispatchEvent(new Event('change'));
        }
    },

    click_pickup_menu: function () {
        var _this = this;
        //_this.clear_configuraciones_form();
        //_this.build_configuraciones_form();
        //_this.fill_conifguraciones_data();
    },

    click_inar_menu: function () {
        var _this = this;
    },

    submit_inar_form: function (event) {
        var _this = this;
        event.stopPropagation();
        event.preventDefault();

        _this.show_preloader();
        var url = _this.urlBase + "/api/inar/update/";
        _this.sendRequest("filename=" + _this.dom.inar_filename.value, "POST", url).then(function (response) {
            _this.hide_preloader();
            M.toast({html: response.message, classes: 'rounded'});
            console.log(response);

        }, function (error) {
            console.log(error);
        });
    },

//    submit_form_register: function (event) {
//        var _this = this;
//        event.stopPropagation();
//        event.preventDefault();
//        alert("hola");
//    },

    submit_form_login: function (event) {
        var _this = this;
        event.preventDefault();
        var user = _this.dom.input_login_user.value;
        var pass = _this.dom.input_login_pass.value;
        var name = "";
        var login = false;
        _this.users.forEach(function (element) {
            if (element.user === user && element.pass === pass) {
                login = true;
                name = element.name;
            }
        });
        if (login) {

            M.toast({html: '¡Login exitoso! Bienvenido, ' + name + '.', classes: 'rounded'});


            // variables sesion

            window.localStorage.setItem(_this.name + "_logged", "1");
            window.localStorage.setItem(_this.name + "_user", user);


            // muestra sistema

            _this.dom.div_login.classList.add("hide");
            _this.dom.div_content.classList.remove("hide");


        } else {
            M.toast({html: 'Usuario o contraseña incorrecta.', classes: 'rounded'});
        }

    },

    set_defaults: function (callback) {
        var _this = this;


        // iniciar modal

        var options = {
            opacity: 0.75
        };
        var elems = document.querySelectorAll('.modal');
        M.Modal.init(elems, options);


        // iniciar select

        var elems = document.querySelectorAll('select');
        M.FormSelect.init(elems, options);


        // get campanas config

//        var url = _this.urlBase + "/app/config/campanas_config.json";
//        _this.sendRequest("", "GET", url).then(function (response) {
//            _this.config = response;
//            callback();
//        }, function (error) {
//            console.log(error);
//        });

    },

    show_app: function () {
        var _this = this;


        // verifica logeo

        if (window.localStorage.getItem(_this.name + "_logged") === "1") {


            // muestra sistema

            _this.dom.div_login.classList.add("hide");
            _this.dom.div_content.classList.remove("hide");


        } else {


            // muestra login

            _this.dom.div_login.classList.remove("hide");
            _this.dom.div_content.classList.add("hide");
        }
    },

    sendRequest: function (parameters, method, url) {
        var xhr = new XMLHttpRequest();
        return new Promise(function (resolve, reject) {
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 300) {
                        reject("Error, status code = " + xhr.status);
                    } else {
                        resolve(JSON.parse(xhr.response));
                    }
                }
            };
            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(parameters);
        });
    }

};

document.addEventListener("DOMContentLoaded", function () {
    app.init();
});