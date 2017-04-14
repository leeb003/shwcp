jQuery(function ($) {  // use $ for jQuery
    "use strict";

	function startIntro(){
        var intro = introJs();
          intro.setOptions({
            steps: [
              { 
                intro: "<h2>Welcome!</h2>  This is a quick tour to let you know some of the amazing features available with WP Contacts that you might otherwise miss out on."
              },
			  {
                intro: "<h2>WP Contacts</h2> Can be set for access on any page you assign it to.  You can have multiple databases in WP Contacts each with their own permissions, purpose, settings and separate pages for access.  <p>We use a page for the frontend access since it gives many freedoms that the backend of WordPress is not quite capable of.  You'll notice that WP Contacts is completely responsive, so you can manage it on all of your favorite devices.</p>"
              },
              {
			  	element: '.header-row',
                intro: "<p>You can create as many fields (or columns in this view) as you need to work with.  Each field can be purposed to be a different field type or you can have multiples of each.</p><p>  Some included field types we have are text fields, date fields, rating fields, dropdown select fields and quite a few more.</p><p> Entries can also be set to have images, files and notes as well.</p>"
              },
			  {
				element: 'th.edit-header',
				intro: "The Quick Edit bar lets you quicky update entries, delete, or delete multiple entries at once.  There's also a backend setting to allow it to \"float\" instead of being set statically in the data.",
				position: 'left'
			  },
			  {
				element: document.querySelectorAll('.wcp-table tr') [4],
				intro: "<p>These are your entries.  Some fields can be set to link to Google maps or used to make call with Skype or cellphones, but most other fields types will link to the individual entry view.</p>",
				position: 'top'
			  },
              {
                element: '.header-row',
                intro: "These columns displayed on this page can be set to show a subset of your fields and can be set in the order you like separate from the individual entry page.  Use the Front Page Settings for setting this and filters up.",
                position: 'bottom-middle'
              },
              {
                element: '.sst-select',
                intro: 'These are your filters.  You can have as many filters as you would like to help you filter your entries to specific views. Filters can be combined with each other and searching to quickly find exactly what you are looking for.',
                position: 'right'
              },
              {
                element: '.wcp-toolbar',
                intro: "This is the toolbar.  In this area you can use the menu on the left to go to different areas, search entries, add entries with the \"+\" and login/logout.",
                position: 'bottom'
              },
			  {
                intro: "You can Import, Export (csv and xlsx), Sync with Mailchimp, use our API, or get our Zapier extension to manage entries and get data in and out easily. It also integrates directly with Contact Form 7, Gravity Forms, and Ninja Forms as well."
              },
              {
                intro: 'In the backend settings, we have powerful features to allow you to fine tune most things.  We have a built-in database backup, restore and reset, and we have user access and permissions for using WP Contacts.'
              },
			  {
			    intro: 'It is the smartest tool available for you to manage your leads, contacts, inventory, class members, soccer club...or just about anything you want all in your own WordPress installation!'
			  }
            ],
			showProgress: true
          });
          intro.start();
      }

	$(document).ready(function() { startIntro(); });
});
