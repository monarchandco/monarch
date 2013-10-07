$ = jQuery;
$(document).ready(function(){
	$('.sub-menu li a').each(function(){$(this).prepend('<img src="/wp-content/themes/monarch/images/bullet.png" />')});
	$('#carousel').cycle({
		fx: 'scrollHorz',
		next: '#next',
		prev: '#prev',
		pager:  '#pager' 
	});
	$('#mla_gallery-1 ').cycle({
		fx: 'scrollHorz',
		next: '#next',
		prev: '#prev',
		pager:  '#pager' 
	});

});