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

	$('#reanimator').mouseenter(function(){
		
		$('.headlinebar').clearQueue();
		$('.headlinebar').animate({
			marginTop: "-300px"
		}, 500, 
			function(){
				$('.solutionseach').animate({
					marginTop: "40px"
				}, 300);
				$('.hiddenpage').fadeIn('fast');
			}
		)
	})
	$('#reanimator').mouseleave(function(){
		$('.hiddenpage').fadeOut('fast');
		$('.headlinebar').clearQueue();
		$('.headlinebar').animate({
			marginTop: "-10px"
		}, 500, 
			function(){
				$('.solutionseach').animate({
					marginTop: "10px"
				}, 300);

			}
		)
	});

	$('.main-navigation ul li').mouseenter(function(){
		var child = 0;
		$(this).children().each(function(){
			if($(this).attr('class') == 'sub-menu'){
				$('#content').animate({
					marginTop: '00px'
				}, 500);
			}
		});
	});

	$('.main-navigation ul li ul').mouseleave(function(){
		$('#content').clearQueue();
		$('#content').animate({
			marginTop: '-39px'
		}, 500);
	});
	$('.main-navigation').mouseleave(function(){
		$('#content').clearQueue();
		$('#content').animate({
			marginTop: '-39px'
		}, 500);
	});
});