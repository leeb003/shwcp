jQuery(document).ready(function($) {
	"use strict";

    // Show the login dialog box on click
    $('a#show_login').on('click', function(e){
		$.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_login_object.ajaxurl,
            data: {
                'action': 'ajax-wcpfrontend', //calls wp_ajax_nopriv_ajaxlogin
                 },
            success: function(response){
				showLogInDiag(response.title, response.body, response.login_button, response.close);
            }
        });
		
        e.preventDefault();
    });

	/* Show dialog to login  - also in frontend-ajax.js */
    function showLogInDiag(title, body, login_button, close) {
		var form = $(document).find('#login').prop("outerHTML");

        $('.wcp-modal').find('.modal-title').html(title);
        $('.wcp-modal').find('.modal-body').html(form);
		$('.modal-body').find('form').attr('id', 'login-displayed');
        var footer = '<button type="button" class="btn btn-primary login-button">'
                   + login_button + '</button>'
                   + '<button type="button" class="btn btn-default" data-dismiss="modal">'
                   + close + '</button>';
        $('.wcp-modal').find('.modal-footer').html(footer);
        $('.wcp-modal').modal();
    }

    // Perform AJAX login
	$(document).on('keypress','.login-password', function (e) {
		if (e.which == 13) {
			submit_login(e);
		}
	});
	// Perform AJAX login		
	$(document).on('click', '.login-button', function(e) {
		submit_login(e);
	});

	function submit_login(e) {
        $('.modal-content p.status').removeClass('wcp-red').show().text(ajax_login_object.loadingmessage);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_login_object.ajaxurl,
            data: { 
                'action': 'ajaxlogin', //calls wp_ajax_nopriv_ajaxlogin
                'username': $('.modal-content .login-username').val(), 
                'password': $('.modal-content .login-password').val(), 
                'security': $('.modal-content #security').val() },
            success: function(data){
                if (data.loggedin == true){
					var success = '<i class="md-verified-user"> </i> ' + data.message;
					$('.modal-content p.status').removeClass('wcp-red').html(success);
                    setTimeout(function(){ document.location.href = ajax_login_object.redirecturl}, 500);
                } else {
					var fail = '<i class="md-report-problem"> </i> ' + data.message;
					$('.modal-content p.status').addClass('wcp-red').html(fail);
				}
            }
        });
        e.preventDefault();
    }
	$(document).ready(function() {
		if (ajax_login_object.launchLogin == 'launch') {
			$('a#show_login').trigger("click");
		}
	});

});
