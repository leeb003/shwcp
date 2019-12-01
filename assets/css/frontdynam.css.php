<?php
/* Frontend dynamic css generation
 *
 */
$postid = intval($_GET['postid']);
$db = '';
$database = get_post_meta($postid, 'wcp_db_select', true);
if ($database && $database != 'default') {
	$db = '_' . $database;
}

$wcp_settings = get_option('shwcp_main_settings' . $db);
$primary_color = $wcp_settings['page_color'];
$logo = $wcp_settings['logo_attachment_url'];
$contact_image_thumbsize = $wcp_settings['contact_image_thumbsize'];
$custom_css = trim($wcp_settings['custom_css']);

//test for admin bar and adjust elements as necessary
$admin_adjust = '';
if ($wcp_settings['show_admin'] == 'true'
	&& is_user_logged_in()	
) {

	$admin_adjust= <<<EOC
.wcp-toolbar,
.pure-toggle-label {
	/* top: 32px; */
}
.pure-drawer,
.pure-overlay {
	/*top: 96px;*/
}
@media screen and (max-width: 767px) {
	.wcp-toolbar,
	.pure-toggle-label {
		top: 0px;
		position:absolute;
	}
	.pure-drawer,
	.pure-overlay {
		top: 64px;
		position: absolute;
	}
}
EOC;
}

header('Content-type: text/css');
?>
/* Dynamic Styles For WP Contacts */
.wcp-toolbar, 
.wcp-select-options {
	background-color: <?php echo $primary_color; ?>
}
.wcp-primary {
	color: <?php echo $primary_color; ?>;
}

.tab-select li.ui-state-active a {
    box-shadow: inset 0 2px 0 <?php echo $primary_color; ?>;
}
.tab-select li.ui-state-active i {
	color: <?php echo $primary_color; ?>;
}
.drawer-menu li a:hover,
.drawer-menu li a:focus {
	color: <?php echo $primary_color; ?>;
}

.ui-datepicker td .ui-state-active,
.ui-datepicker td .ui-state-hover {
	background-color: <?php echo $primary_color; ?>;
}

.ui-datepicker-header {
    background-color: <?php echo $primary_color; ?>;
}

.input-select-options {
	color: <?php echo $primary_color; ?>;
}

.wcp-button {
	color: <?php echo $primary_color; ?>;
}

.wcp-container [type="checkbox"]:checked + label:before,
.wcp-modal [type="checkbox"]:checked + label:before {
  border-right: 2px solid <?php echo $primary_color; ?>;
  border-bottom: 2px solid <?php echo $primary_color; ?>;
}

input[type=text]:focus:not([readonly]), 
input[type=password]:focus:not([readonly]),
textarea.materialize-textarea:focus:not([readonly]) {
    border-bottom: 1px solid <?php echo $primary_color; ?>;
    box-shadow: 0 1px 0 0 <?php echo $primary_color; ?>; 
}
textarea.materialize-textarea:focus:not([readonly]) {
	min-height: 90px;
}

.fields-grouptitle {
    min-height: 100px;
    width: 100%;
    margin-top: 30px;
}
.fields-grouptitle h3 {
	text-align: center;
    padding: 5px 0 15px;
    border-bottom: 1px solid <?php echo $primary_color; ?>;
    box-shadow: 0 2px 0 0 <?php echo $primary_color; ?>; 
}

.btn-default, .btn-default:hover, 
.btn-default:active, .btn-default:focus,
.btn-primary, .btn-primary:hover,
.btn-primary:active, .btn-default:focus {
	color: <?php echo $primary_color; ?>;
	font-weight: 600;
	outline:none;
	border: 0;
}

.wcp-red {
	color: #f44336;
}
.wcp-white {
	color: #ffffff;
}

.wcp-footer {
	color: <?php echo $primary_color; ?>;
}
.left-col-width .ui-slider-range {
	background: <?php echo $primary_color; ?>;
	opacity: 0.5;
}
.left-col-width.ui-slider > .ui-slider-handle {
	background: <?php echo $primary_color; ?>;
}
.image-td a {
    width: <?php echo $contact_image_thumbsize; ?>px;
    height: <?php echo $contact_image_thumbsize; ?>px;
}

<?php echo $admin_adjust; ?>
/* Custom CSS Below */
<?php echo $custom_css;?>
