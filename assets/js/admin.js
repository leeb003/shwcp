jQuery(function ($) {  // use $ for jQuery
    "use strict";
	/* Only loaded on shwcp_options page in admin */

	// Adjust current link highlighting in menu
	$(document).ready( function() {
		var db = $.QueryString["db"];
		if (!db) {  // match tab if db query isn't set
			var tab = $.QueryString["tab"];
			if (tab) {
				tab = tab.split("_");
				tab = tab[tab.length-1]; // get the last match
				db=tab;
			}
		}
		if ( $.isNumeric(db) ) {
			var $wrap = $(document).find('#toplevel_page_shwcp_options .wp-submenu-wrap');
        	$wrap.find('.current').removeClass('current');
			$wrap.find('a[href$="db=' + db + '"]').addClass('current').closest('li').addClass('current');
		}
	});

	// Url parameter handling
	$.QueryString = (function(a) {
        if (a == "") return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p=a[i].split('=');
            if (p.length != 2) continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    })(window.location.search.substr(1).split('&'))


	// Add Color Picker to all inputs that have 'color-field' class
    $(function() {
        $('.color-field').wpColorPicker();
    });


	// Detect current tab and show save button on pages that use it (css hidden by default)
	$(function() {
      	var current_tab = $("form input[name='option_page']").val();
		if (current_tab.indexOf("shwcp_db") == -1
			&& current_tab.indexOf("shwcp_info") == -1
		) {
			$('form #submit').show();
		}
    });

	/* First Tab Settings */
	// Hide pagination results if use pagination is false
	$(document).ready( function() {
		checkPaging();
		checkDefaultImage();
	});
	$(document).on('change', '.wcp-page-page', function() {
		checkPaging();
	});
	$(document).on('change', '.wcp-contact-image', function() {
		checkDefaultImage();
	});

	function checkPaging() {
		var paging = $('.wcp-page-page').val();
		if (paging == 'false') {
            $('.wcp-page-page-count').closest('tr').hide();
        } else {
            $('.wcp-page-page-count').closest('tr').show();
        }
	}

	function checkDefaultImage() {
		var contactImage = $('.wcp-contact-image option:selected').val();
        if (contactImage == 'true') {
            $(document).find('#upload_now2').closest('tr').show();
            $(document).find('.thumbnail-size').closest('tr').show();
        } else {
            $(document).find('#upload_now2').closest('tr').hide();
            $(document).find('.thumbnail-size').closest('tr').hide();
        }
	}

	

	// Hide / Show Calendar notifications
	$(document).ready( function() {
		checkNotify();
	});
	$(document).on('change', '.wcp-events', function() {
		checkNotify();
	});

	function checkNotify() {
		var events = $('.wcp-events').val();
		if (events == 'false') {
			$('.wcp-event-notify').closest('tr').hide();
			$('.calendar-entries-new').closest('tr').hide();
			$('.calendar-entries-date').closest('tr').hide();
		} else {
			$('.wcp-event-notify').closest('tr').show();
			$('.calendar-entries-new').closest('tr').show();
			$('.calendar-entries-date').closest('tr').show();
		}
	}

	// logo image upload
	$(document).on('click', '.logo_clear', function() {
		$('.custom_media_url').val('');
		$('.custom_media_id').val('');
		$('.wcp_logo_image').hide();
		return false;
	});
	$(document).ready( function() {
		if ($('.wcp_logo_image').length) {
			var image_url = $('.wcp_logo_image').attr('src');
			if (image_url.length) {  // show it if it's got it
				$('.wcp_logo_image').show();
			}
		}
	});
	$('.custom_media_upload').click(function(e) {
    	e.preventDefault();

    	var custom_uploader = wp.media({
        	title: 'Logo',
        	button: {
            	text: 'Set Logo'
        	},
        	multiple: false  // Set this to true to allow multiple files to be selected
    	})
    	.on('select', function() {
        	var attachment = custom_uploader.state().get('selection').first().toJSON();
        	$('.wcp_logo_image').attr('src', attachment.url);
			$('.wcp_logo_image').show();
        	$('.custom_media_url').val(attachment.url);
        	$('.custom_media_id').val(attachment.id);
    	})
    	.open();
	});

	// Entry Image Upload
	$(document).on('click', '.contact_image_clear', function() {
        $('.custom_contact_url').val('');
        $('.custom_contact_id').val('');
        $('.wcp_contact_image').hide();
        return false;
    });
    $(document).ready( function() {
        if ($('.wcp_contact_image').length) {
            var image_url = $('.wcp_contact_image').attr('src');
            if (image_url.length) {  // show it if it's got it
                $('.wcp_contact_image').show();
            }
        }
    });
    $('.custom_contact_upload').click(function(e) {
        e.preventDefault();

        var custom_uploader2 = wp.media({
            title: 'Entry Image',
            button: {
                text: 'Set Image'
            },
            multiple: false  // Set this to true to allow multiple files to be selected
        })
        .on('select', function() {
            var attachment = custom_uploader2.state().get('selection').first().toJSON();
            $('.wcp_contact_image').attr('src', attachment.url);
            $('.wcp_contact_image').show();
            $('.custom_contact_url').val(attachment.url);
            $('.custom_contact_id').val(attachment.id);
        })
        .open();
    });

	// Show / Hide the Entry Image Upload fields on Entry Image Change
/*
	$(document).on('change', '.wcp-contact-image', function() {
		var selected = $('.wcp-contact-image option:selected').val();
		if (selected == 'true') {
			$(document).find('#upload_now2').closest('tr').show();
			$(document).find('.thumbnail-size').closest('tr').show();
		} else {
			$(document).find('#upload_now2').closest('tr').hide();
			$(document).find('.thumbnail-size').closest('tr').hide();
		}
	});
*/
	// Add custom links
	$(document).on('click', '.add-custom-menu-link', function() {
		var linkText = $(document).find('.wcp-cust-link-text').text();
		var urlText = $(document).find('.wcp-cust-link-url').text();
		var openText = $(document).find('.wcp-cust-open-text').text();
		var deleteText = $(document).find('.wcp-cust-del-text').text();
		var currentDB = $(document).find('input[name=option_page]').val();
		// alert(currentDB);  need to get current db dynamically to set names

		var currRows = $('.wcp-custom-links tr').length;
		var nextRow = currRows + 1;

		var addLink = '<tr><td><input class="wcp-cust-link" name="' + currentDB + '[custom_links][' + nextRow + '][link]" '
					+ 'placeholder="' + linkText + '" /></td>'
					+ '<td><input class="wcp-cust-url" name="' + currentDB + '[custom_links][' + nextRow + '][url]" '
				    + 'placeholder="' + urlText + '" /> '
					+ openText + ' <input type="checkbox" class="wcp-cust-url-open" '
					+ 'name="' + currentDB + '[custom_links][' + nextRow + '][open]" /></td>'
					+ '<td><a class="wcp-cust-delete">' + deleteText +  '</a> <i class="wcp-md md-sort"></i></td></tr>';

		$('.wcp-custom-links').prepend(addLink);
		return false;

	});

	// Custom Link sorting
	$(function() {
        $('.wcp-custom-links tbody').sortable({});;
    });

	// Delete custom link
	$(document).on('click', '.wcp-cust-delete', function() {
		$(this).closest('tr').remove();
	});

	/* End First Tab Settings */

	/* Second Tab (Fields) Settings */

	// Add custom role
	// copy hidden table of options and modify names for saving
	$(document).on('click', '.wcp-custom-role', function() {
		var roleName       = $(document).find('.wcp-role-name').text();
		var roleLabel      = $(document).find('.wcp-role-label').text();
		var removeRoleText = $(document).find('.remove-role-text').text();
		var currentDB      = $(document).find('input[name=option_page]').val();
		var uniqueString   = 'Custom-' + Math.floor(Math.random() * 26) + Date.now();
		var currRows = 0; // default start
		$('.cust-role-row').each(function() {
			var tempRow = parseInt($(this).attr('class').split(' ')[1].split('-')[2]);
			if (tempRow > currRows) {
				currRows = tempRow;
			}
		});
		var nextRow = currRows + 1;
		// clone hidden table
		var optionsClone = $('.wcp-access-options').find('.wcp-table-access-options').clone();
		// modify names
		optionsClone.find('td').each(function() {
			if ($(this).hasClass('option-name')) {
				var entryName = $(this).attr('class').split(' ')[1];
				$(this).find('input').each(function() {
					var inputName = currentDB + '[custom_roles][' + nextRow + '][' + entryName + ']';
					$(this).attr('name', inputName);
				});
			}
		});

		// modify names for fields
		optionsClone.find('.field_override_fields td').each(function() {
			var fieldName = $(this).find('.fo_orig_name').text();
			//alert(fieldName);
			$(this).find('input').each(function() {
				var inputName = currentDB + '[custom_roles][' + nextRow + '][field_val][' + fieldName + ']';
				$(this).attr('name', inputName);
			});
		});
	
		var addRole = '<tr class="cust-role-row child-wcprow-' + nextRow + ' row-' + nextRow + '"><td class="role-name">'
					+ '<p class="role-title">' + roleLabel + '</p>'
					+ '<input class="wcp-cust-role" name="' 
					+ currentDB + '[custom_roles][' + nextRow + '][name]" '
				    + 'placeholder="' + roleName + '" />'
					+ '<input class="wcp-cust-unique hide-me" name="' + currentDB + '[custom_roles][' + nextRow + '][unique]"'
                    + ' value="' + uniqueString + '" />'
					+ '</td>'
					+ '<td class="role-access"></td>'
					+ '<td class="remove-cust"><div class="remove-cont">'
					+ '<div class="remove-button" title="' + removeRoleText + '"><i class="md-clear"> </i>'
					+ '</div></div></td></tr>';

		$('.wcp-user-roles').prepend(addRole);
		$('.row-' + nextRow + ' .role-access').append(optionsClone);
		
		return false;
	});

	// delete custom role
	$(document).on('click', '.remove-cust .remove-button', function() {
		var thisTR = $(this).closest('.cust-role-row');
		var rowNumb = parseInt($(thisTR).attr('class').split(' ')[1].split('-')[2]);
		$(thisTR).closest('.cust-role-row').fadeOut().remove();
		$('#wcprow-' + rowNumb).fadeOut().remove();
		return false;
	});

	// Set dependent fields disabled for Edit Entries if no access
	$(document).ready(function() {
		$('.entries_edit').each(function() {
			var editChecked = $(this).find('.entries_edit_option:checked').val();
			if (editChecked == 'none') {
				$(this).closest('tr').find('.entries_ownership, .manage_entry_files, .manage_entry_photo')
				.addClass('wcp-disabled'); //.find('input').prop('disabled', true);
			}
		});
	});
	$(document).on('change', '.entries_edit', function() {
		var editChecked = $(this).find('.entries_edit_option:checked').val();
		if (editChecked == 'none') {
            $(this).closest('tr').find('.entries_ownership, .manage_entry_files, .manage_entry_photo')
            .addClass('wcp-disabled'); //.find('input').prop('disabled', true);
        } else {
			$(this).closest('tr').find('.entries_ownership, .manage_entry_files, .manage_entry_photo')
            .removeClass('wcp-disabled'); //.find('input').prop('disabled', false);
		}
	});

    // Custom roles contract expand settings
	$(document).ready(function () {  
		var custRoleMsg = $(document).find('.cust-role-msg').text();
		$('tr.cust-role-row').hide().children('td');
        $('tr.wcp-parent')  
            .css("cursor", "pointer")  
            .attr("title", custRoleMsg)  
            .click(function () {  
                $(this).siblings('.child-' + this.id).fadeToggle();  
            });  
    });
	// Custom roles enable Field Overrides
	$(document).on('change', '.field_override_enable', function() {
		var enable = $(this).val();
		if (enable == 'no') {
			$(this).closest('tr').next('tr').find('.field_override_fields').fadeOut();
		} else {
			$(this).closest('tr').next('tr').find('.field_override_fields').fadeIn();
		}
	});

	/* End Second Tab Settings */

	/* Third Tab (Permissions) Settings */



	/* End Third Tab Settings */

	/* Fourth Tab Settings */
	// set checkboxes to empty on load
	$(document).ready( function() {
		$('.reset-db-check').removeAttr('checked');
	});
	// display reset database button
	$(document).on('click', '.reset-db-check', function() {
		var reset = $(this).is(':checked');
		if (reset) {
			$('.reset-db-confirm').show();
		} else {
			$('.reset-db-confirm').hide();
		}
	});

	// Submit database reset
	$(document).on('click', '.reset-db-confirm', function() {
		// just to make sure checkmark is checked
		var reset = $('.reset-db-check').is(':checked');
		var database = $('.current-working-database').text();
		if (reset) {
			$(this).prop("disabled", true);
			$.post(WCP_Ajax_Admin.ajaxurl, {
            	// wp ajax action
            	action: 'ajax-wcpbackend',
            	// vars
            	reset_db: 'true',
				database: database,
            	nextNonce : WCP_Ajax_Admin.nextNonce

        	}, function(response) {
            	if (response.reset == 'true') {
					var success = $('.reset-success').html();
					$('.reset-message').html(success).fadeIn();
					$('.reset-db-check').removeAttr('checked');
					$('.reset-db-confirm').hide();
            	}
        	});
		}
		return false;
	});

	// display backup database button
	$(document).on('click', '.backup-db-check', function() {
		var backup = $(this).is(':checked');
		if (backup) {
			$('.backup-db-confirm').show();
		} else {
			$('.backup-db-confirm').hide();
		}
	});
	// Submit backup request
	$(document).on('click', '.backup-db-confirm', function() {
        // just to make sure checkmark is checked
        var backup = $('.backup-db-check').is(':checked');
		var database = $('.current-working-database').text();
        if (backup) {
            $(this).prop("disabled", true);
			$('.backup-wait').show();
            $.post(WCP_Ajax_Admin.ajaxurl, {
                // wp ajax action
                action: 'ajax-wcpbackend',
                // vars
                backup_db: 'true',
				database: database,
                nextNonce : WCP_Ajax_Admin.nextNonce

            }, function(response) {
                if (response.backup == 'true') {
                    var success = $('.backup-success').html();
					$('.backup-wait').hide();
                    $('.backup-message').html(success).fadeIn();
                    $('.backup-db-check').removeAttr('checked');
                    $('.backup-db-confirm').hide();
					window.setTimeout(function(){location.reload(true)},3000);
                }
            });
        }
        return false;
    });

	// delete backup and refresh page
	$(document).on('click', '.remove-backup', function() {
		var backupEntry = $(this).closest('li').find('.backup-entry').text();
		$.post(WCP_Ajax_Admin.ajaxurl, {
            // wp ajax action
            action: 'ajax-wcpbackend',
            // vars
            remove_backup: 'true',
			backup: backupEntry,
            nextNonce : WCP_Ajax_Admin.nextNonce

        }, function(response) {
            if (response.removed == 'true') {
				location.reload(true); 
            }
        });
		return false;
	});

	// download backup
	// generates a form appended to body, submits and removes after submit
	$(document).on('click', '.download-backup', function() {
		var downloadUrl = $(document).find('.download-backup-url').text();
		var backupEntry = $(this).closest('li').find('.backup-entry').text();
		var database = $('.current-working-database').text();
		var nonce = $('#wcp_dlb_nonce').val();
		//var backupForm = '<form action="' + downloadUrl + '?file=' + backupEntry + '" method="post" '
		var backupForm = '<form action="' + downloadUrl + '" method="post" '
					   + 'class="hidden" id="bf_form_' + backupEntry + '">'
		               + '<input type="hidden" name="file" value="' + backupEntry + '"/>'
					   + '<input type="hidden" name="action" value="wcpdlbackups">'
					   + '<input type="hidden" name="wcp_dlb_nonce" value="' + nonce + '" />'
					   + '<input type="submit" name="dl_submit" id="dl_submit" value="submit"  />'
					   + '</form>';
		$(backupForm).appendTo('body').submit().remove();
        return false;
    });	

	// display restore backup selection and button
	$(document).on('click', '.restore-db-check', function() {
		var restore = $(this).is(':checked');
		if (restore) {
			$('.restore-db-confirm').show();
			$('.restore-db-file').show();
		} else {
			$('.restore-db-confirm').hide();
			$('.restore-db-file').hide();
		}
	});

	// Confirm Database Restore
	$(document).on('click', '.restore-db-confirm', function() {
		var backup = $('.restore-db-file :selected').val();
		var restore = $('.restore-db-check').is(':checked');
		var database = $('.current-working-database').text();
		if (restore) {  // just in case
			$(this).prop("disabled", true);
            $('.restore-wait').show();
			$.post(WCP_Ajax_Admin.ajaxurl, {
               	// wp ajax action
               	action: 'ajax-wcpbackend',
               	// vars
               	restore_backup: 'true',
               	backup: backup,
				database: database,
               	nextNonce : WCP_Ajax_Admin.nextNonce

           	}, function(response) {
               	if (response.restored == 'true') {
					var success = $('.restore-success').html();
					$('.restore-wait').hide();
                    $('.restore-message').html(success).fadeIn();
                    $('.restore-db-check').removeAttr('checked');
                    $('.restore-db-confirm').hide();
               	} else if (response.error == 'true') {
					alert(response.errormsg);
					$('.restore-db-confirm').removeAttr('disabled');
					
				}
           	});
		}
		return false;
	});

	/* End Fourth Tab Settings */

});
