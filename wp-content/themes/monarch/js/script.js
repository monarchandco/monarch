$ = jQuery;
$(document).ready(function(){
	$('.sub-menu li a').each(function(){$(this).prepend('<img src="/wp-content/themes/monarch/images/bullet.png" />')});
});