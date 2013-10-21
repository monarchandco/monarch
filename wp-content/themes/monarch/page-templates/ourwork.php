<?php
/**
 * Template Name: Our Work
 *
 * Description: A page template that provides a key component of WordPress as a CMS
 * by meeting the need for a carefully crafted introductory page. The front page template
 * in Twenty Twelve consists of a page content area for adding text, images, video --
 * anything you'd like -- followed by front-page-only widgets in one or two columns.
 *
 * @package Wind Up Pixel
 * @subpackage Monarch
 * @since Monarch 1.0
 */

get_header(); ?>
	<div id="primary" class="inner site-content">
		<div id="content" role="main">
			<div id="ourwork">
				<h3 class="orange headlinebar">Our Work</h3>
				<div id="ourwork-content"> <div id="closer"><img src="/wp-content/themes/monarch/images/close.png" /></div> <div class="image"></div>  <div class="right"><h2 class="header orange"></h2>  <div class="lightbox"></div></div></div>
				<?php $my_query = new WP_Query('category_name=work&posts_per_page=20');
				  while ($my_query->have_posts()) : $my_query->the_post();
				  	$do_not_duplicate[] = $post->ID;
				  	echo "<div class='ourworkeach' onclick='javascript:null;' id='" .basename(get_permalink()). "'>";
					if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
					echo "<a  href='#' >" .  get_the_post_thumbnail() ."</a>";
					} 
				  	echo "<span class='overlay'>".get_the_title()."</span></div>";
				  endwhile; ?>
				  <div id="logos">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php the_content(); ?>
					<?php endwhile; // end of the loop. ?>
				  <div>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar( 'front' ); ?>
<?php get_footer(); ?>