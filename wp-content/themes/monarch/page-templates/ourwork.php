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

	<div id="primary" class="site-content">
		<div id="content" role="main">
			<div>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'content', 'page' ); ?>
			<?php endwhile; // end of the loop. ?>
				<?php $my_query = new WP_Query('category_name=work&posts_per_page=2');
				  while ($my_query->have_posts()) : $my_query->the_post();
				  	$do_not_duplicate[] = $post->ID;
				  	echo "<a href='".get_permalink()."'>".get_the_title()."</a>";
				  endwhile; ?>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar( 'front' ); ?>
<?php get_footer(); ?>