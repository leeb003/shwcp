jQuery(function ($) {  // use $ for jQuery
    "use strict";

	/* Meta Box hide / show  pre-gutenberg 5.0 */
    $(document).ready(function() {
        if ($('#page_template').length) {
            var selected = $('#page_template option:selected').val();
            //alert(selected);
			if (selected == 'wcp-fullpage-template.php') {
				$(document).find('#wcp_db_options').show();
			} else {
				$(document).find('#wcp_db_options').hide();
			}
        }
    });

	/* On change Meta Box hide / show  pre-gutenberg 5.0 */
	$(document).on('change', '#page_template', function() {
		var selected = $('#page_template option:selected').val();
        //alert(selected);
        if (selected == 'wcp-fullpage-template.php') {
            $(document).find('#wcp_db_options').show();
        } else {
            $(document).find('#wcp_db_options').hide();
        }
	});
});
