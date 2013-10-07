<?php
/**
 * Template Name: Solutions
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
			<div id="solutions">
				<div id="featuredimage" style="background: url(<?php echo wp_get_attachment_image_src( get_post_thumbnail_id( 11, 'full' ), 'single-post-thumbnail' )[0]; ?>) center; ">
				</div>
				<h3><?php the_title(); ?></h3>
				<div class="solutionseach">
					 <img src="/wp-content/themes/monarch/images/solutions_branded_merch.png">
				</div>
				<div class="solutionseach">
					 <img src="/wp-content/themes/monarch/images/solutions_corporate_apparel.png">
				</div>
				<div class="solutionseach">
					 <img src="/wp-content/themes/monarch/images/solutions_printing_services.png">
				</div>
				<div class="solutionseach">
					 <img src="/wp-content/themes/monarch/images/solutions_graphic_design.png">
				</div>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar( 'front' ); ?>
<?php get_footer(); ?>