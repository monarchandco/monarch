$ = jQuery;
$(document).ready(function(){
	var content='';
	var element='';

	function load_content(search, selector) 
	{
		$(selector).fadeOut('fast');
	   	$.ajax({
		    url: ajax_runner.ajaxurl,
		    type: "POST",
		    data: {
		    	action : 'run_content',
		    	search: search
		    },
		    success: function(data) {
		    	parsed = $.parseJSON(data);
		    	$(selector + ' .header').html(parsed[0]);
		    	$(selector + ' .lightbox').html(parsed[1]);
		    	$(selector + ' .image').html(parsed[2]);
		    	$('#dim').fadeIn('fast');
		    	$(selector).fadeIn('fast');
		    	$('#dim').click(function(){
		    		$(selector).fadeOut('fast');
		    		$('#dim').fadeOut('fast');
		    	})
		    	$('#closer').click(function(){
		    		$(selector).fadeOut('fast');
		    		$('#dim').fadeOut('fast');
		    	})
		    	// $(selector).fadeOut('fast')
		    	// .fadeIn('fast');
		     	// $(selector).html(data);
		    }
	    });
	}

	$('.ourworkeach').click(
		function(){
			if(element != $(this).attr('id')){
				load_content($(this).attr('id'), '#ourwork-content');
			}
			
			load_content($(this).attr('id'), '#our-workcontent');

		}
	);



});