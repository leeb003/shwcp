jQuery(function ($) {  // use $ for jQuery
	"use strict";
	// Load events to calendar
    $(document).ready(function() {
		var date = new Date();
  		var d = date.getDate();
  		var m = ('0' + (date.getMonth() + 1)).slice(-2);
  		var y = date.getFullYear();
    	$('.shwcp-calendar').fullCalendar({
        	header: {
            	left: 'prev,next today',
                center: 'prevYear title nextYear',
                right: 'month,basicWeek,basicDay',
            },
			monthNames: WCP_Cal_Ajax.monthNames,
			monthNamesShort: WCP_Cal_Ajax.monthNamesShort,
			dayNames: WCP_Cal_Ajax.dayNames,
			dayNamesShort: WCP_Cal_Ajax.dayNamesShort,
			buttonText: {
				today: WCP_Cal_Ajax.today,
				month: WCP_Cal_Ajax.month,
				week: WCP_Cal_Ajax.week,
				day: WCP_Cal_Ajax.day,
			},
			eventLimit: true, // allow "more" link when too many events
			events: function(start, end, timezone, callback) {
				var current = $('.shwcp-calendar').fullCalendar('getDate');
				$.ajax({
					url: WCP_Cal_Ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ajax-wcpfrontend',
						frontUrl: WCP_Cal_Ajax.frontUrl,
                        nextNonce: WCP_Cal_Ajax.nextNonce,
                        postID: WCP_Cal_Ajax.postID,
						contactsColor: WCP_Cal_Ajax.contactsColor,
                        load_calendar: 'true',
						start: start.format(),
						end: end.format(),
						current: current.format()   // format's different but we have the current view month to work with
                    },
					success: function(response) {
						var events = [];
						$.each(response.matches, function(k,v) {
							events.push({
								title: v.title,
								start: v.creation_date,
								end: v.stop,
								color: v.color,
								textcolor: v.textcolor,
								url: v.url,
								class: v.class,
								description: v.description,
								editEvent: v.edit_event
							});
						});
						callback(events);
					},
					error: function() {
						alert('Something went wrong')
					}
				});
			},
			eventClick: function(event, jsEvent, view) {
				var cancelText = $(document).find('.cancel-text').text();
				var deleteText = $(document).find('.delete-text').text();
				var editText = $(document).find('.edit-text').text();
				var entryText = $(document).find('.entry-text').text();
				var canEdit = $(document).find('.can-edit').text();
				var currentAccess = $(document).find('.current-access').text();

				var buttons = '';
				if (event.class == 'lead-link') {
					buttons = '<button type="button" onclick="location.href=\'' + event.url 
						+ '\';" class="btn btn-primary lead-link">' + entryText + '</button>';
				} else if (event.class == 'modal-link') {
					if (canEdit != 'none'
					   && event.editEvent == 'yes'
					) {
						buttons =  '<button type="button" class="btn btn-default existing delete-event event-' 
							+ event.url + '">' + deleteText + '</button>'
							+ '<button type="button" class="btn btn-default existing add-edit-event edit-event-' 
							+ event.url + '">' + editText + '</button>';
					}
				}
                $('.wcp-modal').find('.modal-dialog').addClass('modal-lg');
                $('.wcp-modal').find('.modal-title').html(event.title);
                var description = '<div class="show-event row"><div class="col-md-12">' + event.description + '</div></div>';
                $('.wcp-modal').find('.modal-body').html(description);
                var footer = '<button type="button" class="btn btn-default" data-dismiss="modal">' + cancelText + '</button>'
                        + buttons;
                $('.wcp-modal').find('.modal-footer').html(footer);
                $('.wcp-modal').modal();
                return false;
            },
        })
    });

	// Delete an existing event
	var alertSent='no';
	$(document).on('click', '.delete-event', function() {
		var eventID = $(this).attr('class').split(' ')[4].split('-')[1];
		if (alertSent == 'no') {
			alertSent = 'yes';
			var deleteAlert = $(document).find('.delete-alert').text();
			alert(deleteAlert);
		} else if (alertSent == 'yes') {
			// delete it
			$.post(WCP_Cal_Ajax.ajaxurl, {
            	// wp ajax action
            	action: 'ajax-wcpfrontend',
            	// vars
            	delete_event  : 'true',
            	event_id      : eventID,
            	nextNonce     : WCP_Cal_Ajax.nextNonce,
            	postID        : WCP_Cal_Ajax.postID
        	}, function(response) {
				alertSent = 'no';  // reset it
				if (response.deleted) {
					$('.wcp-modal').modal('hide');
                	$('.shwcp-calendar').fullCalendar('refetchEvents');
				}
			});
		}
	});

	// Add new or edit existing general event
	$(document).on('click', '.add-edit-event', function() {
		var eventID = 'new';
		if ($(this).hasClass('existing')) {  // This is an edit
			eventID = $(this).attr('class').split(' ')[4].split('-')[2];
		}
			
        var modalBody = '';
        $.post(WCP_Cal_Ajax.ajaxurl, {
            // wp ajax action
            action: 'ajax-wcpfrontend',
            // vars
            add_event  : 'true',
            lead_id    : 'general',
			event_id   : eventID,
            nextNonce  : WCP_Cal_Ajax.nextNonce,
            postID     : WCP_Cal_Ajax.postID
        }, function(response) {
			var notifications = response.notifications;
            if (response.logged_in == 'false') {
                showLogInDiag(response.title, response.body, response.login_button, response.close);
                return false;
            }
			var eventEntry = '';
			var eventID = response.event_id;

			var start = '';
			var end = '';
			var title = '';
			var details = '';
			var repeat = '';
			var repeatEvery = '';
			var alertEnable = '';
			var notifyAt = '';
			var alertNotifyInc = '';
			var alertNotifySel = '';
			var alertTime = '';
			var notifyWho = {};
			var notifyEmailSent = '';
			var eventColor = '#ddc194';


			if (response.event_id != 'new') { // This is an existing event
				eventEntry = response.event_entry;

				start           = eventEntry.start;
				end             = eventEntry.end;
				title           = eventEntry.title;
				details         = eventEntry.details;
				repeat          = eventEntry.repeat;
				repeatEvery     = eventEntry.repeat_every;
				alertEnable     = eventEntry.alert_enable;
				notifyAt        = eventEntry.notify_at;
				alertNotifyInc  = eventEntry.alert_notify_inc;
				alertNotifySel  = eventEntry.alert_notify_sel;
				alertTime       = eventEntry.alert_time;
				notifyWho       = response.notify_who;
				notifyEmailSent = eventEntry.notify_email_sent;
				eventColor      = eventEntry.event_color;
				
			}
			
			var repeatChecked = (repeat=='1') ? ' checked="checked"' : '';
			var alertChecked  = (alertEnable=='1') ? ' checked="checked"' : '';
						

            modalBody += '<div class="wcp-edit-event row">'
					   + '<div class="col-md-12">'
					   + '<div class="input-field">'
					   + '<label class="" for="event-title">' + response.title_label + '</label>'
					   + '<input class="event-title" type="text" value="' + title + '" />'
					   + '</div></div>' 

					   + '<div class="col-md-6">'
					   + '<div class="input-field">'
					   + '<label for="event-start">' + response.start_label + '</label>'
					   + '<input class="event-start date-choice" type="text" value="' + start + '" />'
					   + '</div></div>'

					   + '<div class="col-md-6">'
                       + '<div class="input-field">'
                       + '<label for="event-stop">' + response.stop_label + '</label>'
                       + '<input class="event-stop date-choice" type="text" value="' + end + '" />'
                       + '</div></div>'

					   /* Repeating Event */
					   + '<div class="col-md-6">'
                       + '<div class="input-field">'
                       + '<label for="alert-repeat">' + response.repeat_label + '</label>'
                       + '<div class="row"><div class="col-xs-12">'
                       + '<input class="checkbox repeat-enable" id="repeat-enable" type="checkbox"' + repeatChecked + ' />'
                       + '<label for="repeat-enable">' + response.repeat_enable +  '</label></div>'

                       + '<div class="col-xs-12"><select class="repeat-sel input-select">';
            $.each(response.repeat_select, function(k,v) {
				var selected = '';
				if (k == repeatEvery) {
					selected = 'selected="selected"';
				}
                modalBody += '<option value="' + k + '" ' + selected + '>' + v + '</option>';
            });
                       modalBody += '</select></div>'

			var show_notify = 'style="display:none;"';
			if (response.notifications == 'email' || response.notifications == 'both') {
				show_notify = '';
			}
            modalBody += '</select>'
                       + '</div></div></div>'

					   /* End Repeating Event
			
					   /* Notifications */
					   + '<div class="col-md-6" ' + show_notify + '>'
					   + '<div class="input-field">'
					   + '<label for="alert-time">' + response.alert_label + '</label>'
					   + '<div class="row"><div class="col-xs-12">'
                       + '<input class="checkbox alert-enable" id="alert-enable" type="checkbox"' + alertChecked + ' />'
                       + '<label for="alert-enable">' + response.alert_enable +  '</label></div>'

					   + '<div class="col-xs-4"><select class="alert-notify-inc input-select">';
			for (var i = 0; i <= 60; i++) {
				var selected = '';
				if (i == alertNotifyInc) {
					selected = 'selected="selected"';
				}
				modalBody += '<option value="' + i + '" ' + selected + '>' + i + '</option>';
			}
					   modalBody += '</select></div>'

					   + '<div class="col-xs-4"><select class="alert-notify-sel input-select">';
			$.each(response.alert_select, function(k,v) {
				var selected = '';
				if (k == alertNotifySel) {
					selected = 'selected="selected"';
				}
				modalBody += '<option value="' + k + '" ' + selected + '>' + v + '</option>';
			});
		    modalBody += '</select></div>'

					   + '<div class="col-xs-4"><select class="alert-time input-select">';
	
			$.each(response.alert_options, function(k,v) {
				var selected = '';
				if (k == alertTime) {
					selected = 'selected="selected"';
				}
				modalBody += '<option value="' + k + '" ' + selected + '>' + v + '</option>';
			});
		
			var hideUsers = '';  // hide users if enable notification is not checked
			if (alertEnable != '1') {
				hideUsers = 'style="display:none;"';
			}
			if (response.notifications != 'email' && response.notifications != 'both') {
				hideUsers = 'style="display:none;"';
			}

			modalBody += '</select>'
					   + '</div></div></div></div>'
					   /* End Notifications */

					   /* Event Color */
					   + '<div class="col-md-6">'
                       + '<div class="input-field">'
                       + '<label for="event-color">' + response.color_label + '</label>'
                       + '<input class="event-color wp-color-picker" value="' + eventColor + '" type="text" />'
                       + '</div></div>'

					   /* End Event Color */

					   /* Who Receives Notification */
					   + '<div class="col-md-6"><div class="notify-user-holder" ' + hideUsers + '>'
					   + '<div class="input-field">'
					   + '<label for="notify-recip-sel">' + response.notify_recip + '</label>'
					   + '<div class="row">';
			$.each(response.notify_users, function(k,v) {
					var checked = '';
					$.each(notifyWho, function(k2,v2) {
						if (k2 == k) {
							checked = 'checked="checked"';
						}
					});
				modalBody += '<div class="col-xs-12 col-sm-6">'
					+ '<input class="checkbox notify-user" id="user-' + k + '" value="' + v.data.user_login + '" type="checkbox" ' + checked + ' />'
                       + '<label for="user-' + k + '">' + v.data.user_login + '</label></div>'

			});

			modalBody += '</div></div></div></div>'

					   /* End Who Recieves Notification */
					
					   + '<div class="col-md-12"><hr /></div>'

					   + '<div class="col-md-12">'
					   + '<textarea id="event-description">' + details + '</textarea>'
					   + '</div>'
					   + '</div>';

            // add large class to modal (remove for smaller ones)
            $('.wcp-modal').find('.modal-dialog').addClass('modal-lg');

            $('.wcp-modal').find('.modal-title').html(response.title);
            $('.wcp-modal').find('.modal-body').html(modalBody);
            var footer = '<button type="button" class="btn btn-default cancel-event" data-dismiss="modal">'
                        + response.cancel_button + '</button>'
                        + '<button type="button" class="btn btn-primary save-event event-' 
						+ eventID + ' lead-' + response.lead_id + '">'
                        + response.confirm_button + '</button>';
            $('.wcp-modal').find('.modal-footer').html(footer);
            $('.wcp-modal').modal();

			 // Initialize select fields
            selectFieldGenerate();

			// Initialize time picker
            $('.date-choice').datetimepicker({
				dateFormat : WCP_Cal_Ajax.dateFormat,
	            timeFormat : WCP_Cal_Ajax.timeFormat
            });

            // Initialize colorpicker
            // Add Color Picker to all inputs that have 'color-field' class
            $('.event-color').wpColorPicker();

			// initialize tinymce
			tinymce.EditorManager.editors = [];  // clear all tinymce instances
            tinyMCE.init({
                mode: 'exact',
                elements: 'event-description',
                menubar: false
            });
        });
        return false;
    });

	$(document).on('click', '.alert-enable', function() {
		if( $(this).is(':checked') ) {
			$('.notify-user-holder').show();
		} else {
			$('.notify-user-holder').hide();
		}
	});

	// Save the event
	$(document).on('click', '.save-event', function() {
		var eventID        = $(this).attr('class').split(' ')[3].split('-')[1];
		var title          = $('.event-title').val();
		var start          = $('.event-start').val();
		var stop           = $('.event-stop').val();
		var repeatEnable   = $('.repeat-enable').is(':checked') ? '1' : '0';
		var repeatSel      = $('.repeat-sel option:selected').val();
		var alertEnable    = $('.alert-enable').is(':checked') ? '1' : '0';
		var alertNotifyInc = $('.alert-notify-inc option:selected').val();
		var alertNotifySel = $('.alert-notify-sel option:selected').val();
		var alertTime      = $('.alert-time option:selected').val();
		var eventColor     = $('.event-color').val();
		var notifyUser = {};
        $('.notify-user:checked').each( function() {
            var id = $(this).attr('id').split('-')[1];
            var name = $(this).val();
            notifyUser[id] = name;
        });
		var description = tinyMCE.get('event-description');

		$.post(WCP_Cal_Ajax.ajaxurl, {
            // wp ajax action
            action: 'ajax-wcpfrontend',
            // vars
            save_event  : 'true',
            lead_id    : 'general',
			event_id   : eventID,
            nextNonce  : WCP_Cal_Ajax.nextNonce,
            postID     : WCP_Cal_Ajax.postID,

			title : title,
			start : start,
			stop  : stop,
			repeat_enable : repeatEnable,
			repeat_sel : repeatSel,
			alert_enable: alertEnable,
			alert_notify_inc: alertNotifyInc,
			alert_notify_sel: alertNotifySel,
			alert_time: alertTime,
			event_color: eventColor,
			notify_user: notifyUser,
			description: description.getContent(),
        }, function(response) {
            if (response.logged_in == 'false') {
                showLogInDiag(response.title, response.body, response.login_button, response.close);
                return false;
            }
			if (response.error == '1') {
				alert(response.error_msg);
			} else {
				$('.wcp-modal').modal('hide');
				$('.shwcp-calendar').fullCalendar('refetchEvents');
			}
		});
		return false;
	});

});
