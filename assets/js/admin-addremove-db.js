jQuery(function ($) {  // use $ for jQuery
    "use strict";
	/* Functionality for adding and removing Databases
	 * posts to class-wcp-ajax.php backend settings
	 *
	 */

	// Add Database
	$(document).on('click', '.add-shwcp-newdb', function() {
		var dbTransName = $(document).find('.shwcp-newdb-name').val();
		if (dbTransName == '') {
			return false;
		} else {
            $(document).find('.shwcp-newdb-name').prop("disabled", true);
            $.post(WCP_Ajax_DB_Admin.ajaxurl, {
                // wp ajax action
                action: 'ajax-wcpbackend',
                // vars
                new_db: 'true',
				db_trans_name: dbTransName,
                nextNonce : WCP_Ajax_DB_Admin.nextNonce

            }, function(response) {
                if (response.created == 'true') {
					var url = window.location.href;
					if (url.indexOf('?') > -1){
  						url += '&submitted=1'
					}else{
   						url += '?submitted=1'
					}
					window.location.href=url;
                }
            });
        }
        return false;
			
	});

	// Delete Database
	$(document).on('click', '.shwcp-deldb-submit', function() {
		var dbToDel = $(document).find('.shwcp-deldb-name option:selected').val();
		var dbNameToDel = $(document).find('.shwcp-deldb-name option:selected').text();
		if (dbToDel) {
			//alert(dbToDel);
			$.post(WCP_Ajax_DB_Admin.ajaxurl, {
                // wp ajax action
                action: 'ajax-wcpbackend',
                // vars
                delete_db: 'true',
                db_number: dbToDel,
                nextNonce : WCP_Ajax_DB_Admin.nextNonce

            }, function(response) {
                if (response.deleted) {
                    var url = window.location.href;
                    if (url.indexOf('?') > -1){
                        url += '&submitted=1&dbname=' + dbNameToDel
                    }else{
                        url += '?submitted=1&dbname=' + dbNameToDel
                    }
                    window.location.href=url;
                }
            });
		}
	});

	// Clone Database
	$(document).on('click', '.shwcp-clonedb-submit', function() {
        var dbToClone = $(document).find('.shwcp-clonedb-name option:selected').val();
        var dbNameToClone = $(document).find('.shwcp-clonedb-name option:selected').text();
        if (dbToClone) {
            //alert(dbToClone);
            $.post(WCP_Ajax_DB_Admin.ajaxurl, {
                // wp ajax action
                action: 'ajax-wcpbackend',
                // vars
                clone_db: 'true',
                db_number: dbToClone,
				db_name: dbNameToClone,
                nextNonce : WCP_Ajax_DB_Admin.nextNonce

            }, function(response) {
                if (response.cloned_number) {
                    var url = window.location.href;
                    if (url.indexOf('?') > -1){
                        url += '&submitted=1&dbname=' + dbNameToClone
                    }else{
                        url += '?submitted=1&dbname=' + dbNameToClone
                    }
                    window.location.href=url;
                }
            });
        }
    });	

});
