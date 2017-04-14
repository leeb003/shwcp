jQuery(function ($) {  // use $ for jQuery
    "use strict";
	
	/* Search submit */
	$('.wcp-search-input').keypress(function (e) {
		if (e.which == 13) {
			var search = encodeURIComponent($(this).val());
			var searchFieldText = $(this).closest('div').find('.wcp-select-styled').text();
			var searchFieldVal = 'nomatch';
			$('.wcp-select-options li').each( function() {
				if ($(this).text() == searchFieldText) {
					searchFieldVal = $(this).attr('rel');
				}
			});
			window.location.href = '?wcp_search=true&s_field=' + searchFieldVal + '&q=' + search;
		}
    });

	$('.log-search-input').keypress(function (e) {
        if (e.which == 13) {
            var search = encodeURIComponent($(this).val());
            var searchFieldText = $('.wcp-select-styled').text();
            var searchFieldVal = 'nomatch';
            $('.wcp-select-options li').each( function() {
                if ($(this).text() == searchFieldText) {
                    searchFieldVal = $(this).attr('rel');
                }
            });
            window.location.href = '?wcp=logging&wcp_search=true&q=' + search;
        }
    });

	// click on search when filled go ahead and submit
	$('.wcp-search-input').on('click', function() {
		var search = encodeURIComponent($(this).val());
		if (search != '') {
			var searchFieldText = $(this).closest('div').find('.wcp-select-styled').text();
            var searchFieldVal = 'nomatch';
            $('.wcp-select-options li').each( function() {
                if ($(this).text() == searchFieldText) {
                    searchFieldVal = $(this).attr('rel');
                }
            });
            window.location.href = '?wcp_search=true&s_field=' + searchFieldVal + '&q=' + search;
		}
	});

	/* Searching, open and close */
	$(document).click(function(event) {
		if (!$(event.target).closest('.wcp-search').length
			&& !$(event.target).closest('.wcp-search-menu').length
		) {
			if($('.wcp-search-input').is(':visible')) {
				$('.wcp-search-input').css('width', '25px');
				$('.wcp-search-input').css('background', 'inherit');
				$('.wcp-select').hide();
				$('.second-menu').css('height', '0');
				$('.second-menu').hide();
			}
		}
	});
	$(document).on('click', '.wcp-search', function() {
		$('.wcp-search-input').css('width', '200px').css('height', '64px');
		$('.wcp-search-input').css('background-color', 'rgba(0,0,0,0.1)');
		$('.wcp-search-input').css('color', '#ffffff');
		$('.second-menu').find('.wcp-search-input').css('color', 'rgba(0,0,0,0.84)');
		$('.wcp-select').show();
	});

	// small screen menu
	$(document).on('click', '.wcp-search-menu', function() {
		$('.second-menu').css('height', 'auto');
		$('.second-menu .wcp-search-input').css('width', '200px');
        $('.second-menu .wcp-search-input').css('background', 'rgba(0,0,0,0.15)');
        $('.second-menu .wcp-search-input').css('color', 'rgba(0,0,0,0.54)');
		$('.second-menu').show();
        $('.second-menu .wcp-select').show();
	});

	$(document).click(function(event) {
        if (!$(event.target).closest('.log-search').length) {
            if($('.log-search-input').is(':visible')) {
                $('.log-search-input').css('width', '25px');
                $('.log-search-input').css('background', 'inherit');
            }
        }
    });
    $(document).on('click', '.log-search', function() {
        $('.log-search-input').css('width', '200px').css('height', '64px');
        $('.log-search-input').css('background', 'rgba(0,0,0,0.15)');
        $('.log-search-input').css('color', '#ffffff');
    });

	// click on search when filled go ahead and submit log search
    $('.log-search-input').on('click', function() {
        var search = $(this).val();
        if (search != '') {
			window.location.href = '?wcp=logging&wcp_search=true&q=' + search;
        }
    });

	/* Select search lists */
	$('.wcp-select').each(function(){
    	var $this = $(this), numberOfOptions = $(this).children('option').length;
  
    	$this.addClass('wcp-select-hidden'); 
    	$this.wrap('<div class="wcp-select"></div>');
    	$this.after('<div class="wcp-select-styled"></div>');

    	var $styledSelect = $this.next('div.wcp-select-styled');
    	// $styledSelect.text($this.children('option').eq(0).text());
  
    	var $list = $('<ul />', {
        	'class': 'wcp-select-options'
    	}).insertAfter($styledSelect);
  		var selectedItem = '';
    	for (var i = 0; i < numberOfOptions; i++) {
			if ($this.children('option').eq(i).is(':selected')) { 
				selectedItem = $this.children('option').eq(i).text(); 
			}
        	$('<li />', {
            	text: $this.children('option').eq(i).text(),
            	rel: $this.children('option').eq(i).val()
        	}).appendTo($list);
    	}
		if (selectedItem) {  // If we have a selected option, load it in the field
			$styledSelect.text(selectedItem);
		} else {
			$styledSelect.text($this.children('option').eq(0).text());
		}
  
    	var $listItems = $list.children('li');
  
    	$styledSelect.click(function(e) {
        	e.stopPropagation();
        	$('div.wcp-select-styled.active').each(function(){
            	$(this).removeClass('active').next('ul.wcp-select-options').hide();
        	});
        	$(this).toggleClass('active').next('ul.wcp-select-options').toggle();
    	});
  
    	$listItems.click(function(e) {
        	e.stopPropagation();
        	$styledSelect.text($(this).text()).removeClass('active');
        	$this.val($(this).attr('rel'));
        	$list.hide();
        	//console.log($this.val());
			// change focus to text input
			$('.wcp-search-input').focus();
    	});
  
    	$(document).click(function() {
        	$styledSelect.removeClass('active');
        	$list.hide();
    	});

	});
	$('.wcp-select').hide();

	/* Input Select items */
	$(document).ready(function() {
		selectFieldGenerate();
	});



	window.selectFieldGenerate = function(theClass) {
		theClass = theClass || 'input-select'; // this is so we can override for single select items
		$('.' + theClass).each(function(){
        	var $this = $(this), numberOfOptions = $(this).children('option').length;
			var disabled = $(this).prop('disabled');
			var disabledClass = '';
			if (disabled) {
				disabledClass = 'disabled';
			}

        	$this.addClass('input-select-hidden');
        	$this.wrap('<div class="input-select"></div>');
        	$this.after('<div class="input-select-styled ' + disabledClass + '"></div>');

        	var $styledSelect = $this.next('div.input-select-styled');
        	$styledSelect.text($this.children('option:selected').text());

        	var $list = $('<ul />', {
            	'class': 'input-select-options'
        	}).insertAfter($styledSelect);

        	for (var i = 0; i < numberOfOptions; i++) {
            	$('<li />', {
                	text: $this.children('option').eq(i).text(),
                	rel: $this.children('option').eq(i).val()
            	}).appendTo($list);
        	}

        	var $listItems = $list.children('li');

        	$styledSelect.click(function(e) {
				if (!$(this).closest('.input-select').find('.input-select-hidden').prop('disabled')) {
            	e.stopPropagation();
            	$('div.input-select-styled.active').each(function(){
                	$(this).removeClass('active').next('ul.input-select-options').fadeOut(200);
            	});
            	$(this).toggleClass('active').next('ul.input-select-options').fadeToggle(200);
				var currentVal = $(this).text();
				var inputSelect = $(this).closest('.input-select');
				inputSelect.find('li').each(function() {
					var textVal=$(this).text();
					if (textVal == currentVal) {
						$(this).addClass('selected');
					} else {
						$(this).removeClass('selected');
					}
				});
				}
        	});

        	$listItems.click(function(e) {
            	e.stopPropagation();
            	$styledSelect.text($(this).text()).removeClass('active');
            	$this.val($(this).attr('rel'));
            	$list.hide();
				$this.trigger('change');
            	//console.log($this.val());
        	});
			$(document).click(function() {
            	$styledSelect.removeClass('active');
            	$list.fadeOut(200);
        	});

    	});
	};

	// Submenu display
	$('.drawer-menu').on('click', '.wcp-submenu', function() {
		$(this).closest('li').find('.wcp-dropdown').slideToggle();
		return false;
	});

	if ($('.wcp-table').length) {
		// responsive table layouts - main
		var headertext = [],
		headers = document.querySelectorAll(".wcp-table th"),
		tablerows = document.querySelectorAll(".wcp-table th"),
		tablebody = document.querySelector(".wcp-table tbody");

		for(var i = 0; i < headers.length; i++) {
  			var current = headers[i];
  			headertext.push(current.textContent.replace(/\r?\n|\r/,""));
		}
		for (var i = 0, row; row = tablebody.rows[i]; i++) {
  			for (var j = 0, col; col = row.cells[j]; j++) {
    			col.setAttribute("data-th", headertext[j]);
  			}
		}
	}

	if ($('.log-entries').length) {
        // responsive table layouts - logging
        var headertext = [],
        headers = document.querySelectorAll(".log-entries th"),
        tablerows = document.querySelectorAll(".log-entries th"),
        tablebody = document.querySelector(".log-entries tbody");

       	for(var i = 0; i < headers.length; i++) {
           	var current = headers[i];
           	headertext.push(current.textContent.replace(/\r?\n|\r/,""));
       	}
       	for (var i = 0, row; row = tablebody.rows[i]; i++) {
           	for (var j = 0, col; col = row.cells[j]; j++) {
               	col.setAttribute("data-th", headertext[j]);
           	}
       	}
   	}	

	// Highlight labels on focus
	$(document).on('focus', '.input-field :input', function() {
		$(this).closest('.input-field').find("label").addClass("wcp-primary");
	});
	$(document).on('blur', '.input-field :input', function() {
		$(this).closest('.input-field').find("label").removeClass("wcp-primary");
	});

	// Import Export tabs
	$(function() {
		$('.wcp-tabs').tabs();
	});

});
