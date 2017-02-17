jQuery(function ($) {  // use $ for jQuery
    "use strict";

	function startIntro(){
        var intro = introJs();
          intro.setOptions({
            steps: [
              { 
                intro: "<h2>Welcome!</h2>  This is a quick tour to let you know some of the amazing features available with WP Contacts that you might otherwise miss."
              },
			  {
                intro: "<h2>WP Contacts</h2> can be set for access on any page you assign it to.  You can have multiple databases in WP Contacts each with their own permissions, settings and separate page for access.  <p>We use a page for the frontend access since it gives many freedoms that the backend of WordPress is not quite capable of.  You'll notice that WP Contacts is completely responsive, so you can manage it on all of your favorite devices.  It's also secured with the same login functionality as WordPress itself and only users you decide on can access it.</p>"
              },
              {
			  	element: '.header-row',
                intro: "<p>You can create as many fields (or columns in this view) as you need to work with.  Each field can be purposed to be a different field type or you can have multiples of each.</p><p>  Some included field types we have are text fields, date fields, rating fields, dropdown select fields and quite a few more.</p><p> Entries can also have images and files uploaded to them.</p>"
              },
			  {
				element: '.wcp-table tr:last-child',
				intro: "<p>These are your entries, some fields can be set to link to maps or used to call with skype or cellphones, but most other fields types will link to the individual entry view.</p><p>You can also set how many results per page to show in the settings.</p>",
				position: 'top'
			  },
              {
                element: '.header-row',
                intro: "You are currently viewing the <i>front page</i> of all of your entries, and these columns displayed on this page can be set to show a subset of your fields and can be set in the order you like separate from the individual entry page.",
                position: 'bottom-middle'
              },
              {
                element: '.sst-select',
                intro: 'These are your filters.  With our latest version of WP Contacts, you can have as many filters as you would like to help you filter your entries to specific views. Filters can be combined with each other and searching to find exactly what you are looking for.',
                position: 'right'
              },
              {
                element: '.bar-tools',
                intro: "In this area you can search your entries by field (we even have a setting to search all at once) and add new entries with the \"+\".",
                position: 'left'
              },
			  {
                intro: "<h2>Extensable and customizable</h2>  In the backend settings there are many options to set colors, logos, preferences and settings including custom CSS and JS."
              },
              {
                element: '#step5',
                intro: 'Get it, use it.'
              }
            ],
			showProgress: true
          });
          intro.start();
      }

	$(document).ready(function() { startIntro(); });
});
