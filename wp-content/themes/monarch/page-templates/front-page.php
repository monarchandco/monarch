<?php
/**
 * Template Name: Front Page Template
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

	<div id="primary" class="site-content" style="margin-top:-21px;">
		<div id="content" role="main">
			<div id="carousel">
				<?php 
				$args = array('tag' =>'carousel', 'orderby' =>'title', 'order'=>'ASC');
				$thequery = get_posts($args);
				foreach ( $thequery as $post ) :setup_postdata($post);
					echo '<div>'.get_the_content().'</div>';
			 	endforeach;// end of the loop. ?>
			</div>
			<div id="prev" class="slidenav"><img src="wp-content/themes/monarch/images/leftarrow.png"></div>
			<div id="next" class="slidenav"><img src="wp-content/themes/monarch/images/rightarrow.png"></div>
			<div id="pager" class="slidenav"></div>
			<br>
			<div id="featured">
			<?php 
				$args = array('tag' =>'featured', 'orderby' =>'title', 'order'=>'ASC');
				$thequery = get_posts($args);
				foreach ( $thequery as $post ) :setup_postdata($post);
					echo '<div class="featureslide">'.get_the_content().'</div>';
			 	endforeach;// end of the loop. ?>
			</div>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'content', 'page' ); ?>
			<?php endwhile; // end of the loop. ?>
		</div><!-- #content -->
	</div><!-- #primary -->
<?php get_sidebar( 'front' ); ?>
<?php get_footer(); ?>