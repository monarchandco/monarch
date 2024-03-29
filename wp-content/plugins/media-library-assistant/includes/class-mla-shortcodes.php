<?php
/**
 * Media Library Assistant Shortcode handler(s)
 *
 * @package Media Library Assistant
 * @since 0.1
 */

/**
 * Class MLA (Media Library Assistant) Shortcodes defines the shortcodes available to MLA users
 *
 * @package Media Library Assistant
 * @since 0.20
 */
class MLAShortcodes {
	/**
	 * Initialization function, similar to __construct()
	 *
	 * @since 0.20
	 *
	 * @return	void
	 */
	public static function initialize() {
		add_shortcode( 'mla_attachment_list', 'MLAShortcodes::mla_attachment_list_shortcode' );
		add_shortcode( 'mla_gallery', 'MLAShortcodes::mla_gallery_shortcode' );
	}

	/**
	 * WordPress Shortcode; renders a complete list of all attachments and references to them
	 *
	 * @since 0.1
	 *
	 * @return	void	echoes HTML markup for the attachment list
	 */
	public static function mla_attachment_list_shortcode( /* $atts */ ) {
		global $wpdb;
		
		/*	extract(shortcode_atts(array(
		'item_type'=>'attachment',
		'organize_by'=>'title',
		), $atts)); */
		
		/*
		 * Process the where-used settings option
		 */
		if ('checked' == MLAOptions::mla_get_option( MLAOptions::MLA_EXCLUDE_REVISIONS ) )
			$exclude_revisions = "(post_type <> 'revision') AND ";
		else
			$exclude_revisions = '';
				
		$attachments = $wpdb->get_results(
				"
				SELECT ID, post_title, post_name, post_parent
				FROM {$wpdb->posts}
				WHERE {$exclude_revisions}post_type = 'attachment' 
				"
		);
		
		foreach ( $attachments as $attachment ) {
			$references = MLAData::mla_fetch_attachment_references( $attachment->ID, $attachment->post_parent );
			
			echo '&nbsp;<br><h3>' . $attachment->ID . ', ' . esc_attr( $attachment->post_title ) . ', Parent: ' . $attachment->post_parent . '<br>' . esc_attr( $attachment->post_name ) . '<br>' . esc_html( $references['base_file'] ) . "</h3>\r\n";
			
			/*
			 * Look for the "Featured Image(s)"
			 */
			if ( empty( $references['features'] ) ) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;not featured in any posts.<br>\r\n";
			} else {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;Featured in<br>\r\n";
				foreach ( $references['features'] as $feature_id => $feature ) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					
					if ( $feature_id == $attachment->post_parent ) {
						echo 'PARENT ';
						$found_parent = true;
					}
					
					echo $feature_id . ' (' . $feature->post_type . '), ' . esc_attr( $feature->post_title ) . "<br>\r\n";
				}
			}
			
			/*
			 * Look for item(s) inserted in post_content
			 */
			if ( empty( $references['inserts'] ) ) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;no inserts in any post_content.<br>\r\n";
			} else {
				foreach ( $references['inserts'] as $file => $inserts ) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $file . " inserted in<br>\r\n";
					foreach ( $inserts as $insert ) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						
						if ( $insert->ID == $attachment->post_parent ) {
							echo 'PARENT ';
							$found_parent = true;
						}
						
						echo $insert->ID . ' (' . $insert->post_type . '), ' . esc_attr( $insert->post_title ) . "<br>\r\n";
					} // foreach $insert
				} // foreach $file
			}
			
			$errors = '';
			
			if ( !$references['found_reference'] )
				$errors .= '(ORPHAN) ';
			
			if ( $references['is_unattached'] )
				$errors .= '(UNATTACHED) ';
			else {
				if ( !$references['found_parent'] ) {
					if ( isset( $references['parent_title'] ) )
						$errors .= '(BAD PARENT) ';
					else
						$errors .= '(INVALID PARENT) ';
				}
			}
			
			if ( !empty( $errors ) )
				echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $errors . "<br>\r\n";
		} // foreach attachment
		
		echo "<br>----- End of Report -----\r\n";
	}
	
	/**
	 * Accumulates debug messages
	 *
	 * @since 0.60
	 *
	 * @var	string
	 */
	public static $mla_debug_messages = '';
	
	/**
	 * Turn debug collection and display on or off
	 *
	 * @since 0.70
	 *
	 * @var	boolean
	 */
	private static $mla_debug = false;
	
	/**
	 * The MLA Gallery shortcode.
	 *
	 * This is a superset of the WordPress Gallery shortcode for displaying images on a post,
	 * page or custom post type. It is adapted from /wp-includes/media.php gallery_shortcode.
	 * Enhancements include many additional selection parameters and full taxonomy support.
	 *
	 * @since .50
	 *
	 * @param array $attr Attributes of the shortcode.
	 *
	 * @return string HTML content to display gallery.
	 */
	public static function mla_gallery_shortcode( $attr ) {
		global $post;

		/*
		 * Some do_shortcode callers may not have a specific post in mind
		 */
		if ( ! is_object( $post ) )
			$post = (object) array( 'ID' => 0 );
		
		/*
		 * Make sure $attr is an array, even if it's empty
		 */
		if ( empty( $attr ) )
			$attr = array();
		elseif ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );

		/*
		 * Special handling of the mla_paginate_current parameter to make
		 * "MLA pagination" easier. Look for this parameter in $_REQUEST
		 * if it's not present in the shortcode itself.
		 */
		if ( isset( $_REQUEST['mla_paginate_current'] ) && ! isset( $attr['mla_paginate_current'] ) )
			$attr['mla_paginate_current'] = $_REQUEST['mla_paginate_current'];
		 
		/*
		 * These are the parameters for gallery display
		 */
		$mla_arguments = array(
			'mla_output' => 'gallery',
			'mla_style' => MLAOptions::mla_get_option('default_style'),
			'mla_markup' => MLAOptions::mla_get_option('default_markup'),
			'mla_float' => is_rtl() ? 'right' : 'left',
			'mla_itemwidth' => MLAOptions::mla_get_option('mla_gallery_itemwidth'),
			'mla_margin' => MLAOptions::mla_get_option('mla_gallery_margin'),
			'mla_link_attributes' => '',
			'mla_link_class' => '',
			'mla_link_href' => '',
			'mla_link_text' => '',
			'mla_nolink_text' => '',
			'mla_rollover_text' => '',
			'mla_image_class' => '',
			'mla_image_alt' => '',
			'mla_image_attributes' => '',
			'mla_caption' => '',
			'mla_target' => '',
			'mla_debug' => false,
			'mla_viewer' => false,
			'mla_viewer_extensions' => 'doc,xls,ppt,pdf,txt',
			'mla_viewer_page' => '1',
			'mla_viewer_width' => '150',
			'mla_alt_shortcode' => NULL,
			'mla_alt_ids_name' => 'ids',
			// paginate_links arguments
			'mla_end_size'=> 1,
			'mla_mid_size' => 2,
			'mla_prev_text' => '&laquo; Previous',
			'mla_next_text' => 'Next &raquo;',
			'mla_paginate_type' => 'plain'
		);
		
		$default_arguments = array_merge( array(
			'size' => 'thumbnail', // or 'medium', 'large', 'full' or registered size
			'itemtag' => 'dl',
			'icontag' => 'dt',
			'captiontag' => 'dd',
			'columns' => MLAOptions::mla_get_option('mla_gallery_columns'),
			'link' => 'permalink', // or 'post' or file' or a registered size
			// Photonic-specific
			'id' => NULL,
			'style' => NULL,
			'type' => 'default', // also used by WordPress.com Jetpack!
			'thumb_width' => 75,
			'thumb_height' => 75,
			'thumbnail_size' => 'thumbnail',
			'slide_size' => 'large',
			'slideshow_height' => 500,
			'fx' => 'fade',
			'timeout' => 4000,
			'speed' => 1000,
			'pause' => NULL),
			$mla_arguments
		);
			
		/*
		 * Look for 'request' substitution parameters,
		 * which can be added to any input parameter
		 */
		foreach ( $attr as $attr_key => $attr_value ) {
			/*
			 * attachment-specific Gallery Display Content parameters must be evaluated
			 * later, when all of the information is available.
			 */
			if ( in_array( $attr_key, array( 'mla_link_attributes', 'mla_link_class', 'mla_link_href', 'mla_link_text', 'mla_nolink_text', 'mla_rollover_text', 'mla_image_class', 'mla_image_alt', 'mla_image_attributes', 'mla_caption' ) ) )
				continue;
				
			$attr_value = str_replace( '{+', '[+', str_replace( '+}', '+]', $attr_value ) );
			$replacement_values = MLAData::mla_expand_field_level_parameters( $attr_value );

			if ( ! empty( $replacement_values ) )
				$attr[ $attr_key ] = MLAData::mla_parse_template( $attr_value, $replacement_values );
		}
		
		/*
		 * Merge gallery arguments with defaults, pass the query arguments on to mla_get_shortcode_attachments.
		 */
		 
		$arguments = shortcode_atts( $default_arguments, $attr );
		self::$mla_debug = !empty( $arguments['mla_debug'] ) && ( 'true' == strtolower( $arguments['mla_debug'] ) );

		/*
		 * Determine output type
		 */
		$output_parameters = array_map( 'strtolower', array_map( 'trim', explode( ',', $arguments['mla_output'] ) ) );
		$is_gallery = 'gallery' == $output_parameters[0];
		$is_pagination = in_array( $output_parameters[0], array( 'previous_page', 'next_page', 'paginate_links' ) ); 
		
		$attachments = self::mla_get_shortcode_attachments( $post->ID, $attr, $is_pagination );
			
		if ( is_string( $attachments ) )
			return $attachments;
			
		if ( empty($attachments) ) {
			if ( self::$mla_debug ) {
				$output = '<p><strong>mla_debug empty gallery</strong>, query = ' . var_export( $attr, true ) . '</p>';
				$output .= self::$mla_debug_messages;
				self::$mla_debug_messages = '';
			}
			else {
				$output =  '';
			}
			
			$output .= $arguments['mla_nolink_text'];
			return $output;
		} // empty $attachments
	
		/*
		 * Look for user-specified alternate gallery shortcode
		 */
		if ( is_string( $arguments['mla_alt_shortcode'] ) ) {
			/*
			 * Replace data-selection parameters with the "ids" list
			 */
			$blacklist = array_merge( $mla_arguments, self::$data_selection_parameters );
			$new_args = '';
			foreach ( $attr as $key => $value ) {
				if ( array_key_exists( $key, $blacklist ) ) {
					continue;
				}
				
				$slashed = addcslashes( $value, chr(0).chr(7).chr(8)."\f\n\r\t\v\"\\\$" );
				if ( ( false !== strpos( $value, ' ' ) ) || ( false !== strpos( $value, '\'' ) ) || ( $slashed != $value ) ) {
					$value = '"' . $slashed . '"';
				}
				
				$new_args .= empty( $new_args ) ? $key . '=' . $value : ' ' . $key . '=' . $value;
			} // foreach $attr
			
			$new_ids = '';
			foreach ( $attachments as $value ) {
				$new_ids .= empty( $new_ids ) ? (string) $value->ID : ',' . $value->ID;
			} // foreach $attachments

			$new_ids = $arguments['mla_alt_ids_name'] . '="' . $new_ids . '"';
			
			if ( self::$mla_debug ) {
				$output = self::$mla_debug_messages;
				self::$mla_debug_messages = '';
			}
			else
				$output = '';
			/*
			 * Execute the alternate gallery shortcode with the new parameters
			 */
			return $output . do_shortcode( sprintf( '[%1$s %2$s %3$s]', $arguments['mla_alt_shortcode'], $new_ids, $new_args ) );
		} // mla_alt_shortcode

		/*
		 * Look for Photonic-enhanced gallery
		 */
		global $photonic;
		
		if ( is_object( $photonic ) && ! empty( $arguments['style'] ) ) {
			if ( 'default' != strtolower( $arguments['type'] ) ) 
				return '<p><strong>Photonic-enhanced [mla_gallery]</strong> type must be <strong>default</strong>, query = ' . var_export( $attr, true ) . '</p>';

			$images = array();
			foreach ($attachments as $key => $val) {
				$images[$val->ID] = $attachments[$key];
			}
			
			if ( isset( $arguments['pause'] ) && ( 'false' == $arguments['pause'] ) )
				$arguments['pause'] = NULL;

			$output = $photonic->build_gallery( $images, $arguments['style'], $arguments );
			return $output;
		}
		
		$size = $size_class = $arguments['size'];
		if ( 'icon' == strtolower( $size) ) {
			if ( 'checked' == MLAOptions::mla_get_option( MLAOptions::MLA_ENABLE_MLA_ICONS ) )
				$size = array( 64, 64 );
			else
				$size = array( 60, 60 );
				
			$show_icon = true;
		}
		else
			$show_icon = false;
		
		/*
		 * Feeds such as RSS, Atom or RDF do not require styled and formatted output
		 */
		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment )
				$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
			return $output;
		}

		/*
		 * Check for Google File Viewer arguments
		 */
		$arguments['mla_viewer'] = !empty( $arguments['mla_viewer'] ) && ( 'true' == strtolower( $arguments['mla_viewer'] ) );
		if ( $arguments['mla_viewer'] ) {
			$arguments['mla_viewer_extensions'] = array_filter( array_map( 'trim', explode( ',', $arguments['mla_viewer_extensions'] ) ) );
			$arguments['mla_viewer_page'] = absint( $arguments['mla_viewer_page'] );
			$arguments['mla_viewer_width'] = absint( $arguments['mla_viewer_width'] );
		}
			
		// $instance supports multiple galleries in one page/post	
		static $instance = 0;
		$instance++;

		/*
		 * The default MLA style template includes "margin: 1.5%" to put a bit of
		 * minimum space between the columns. "mla_margin" can be used to change
		 * this. "mla_itemwidth" can be used with "columns=0" to achieve a "responsive"
		 * layout.
		 */
		 
		$columns = absint( $arguments['columns'] );
		$margin_string = strtolower( trim( $arguments['mla_margin'] ) );
		
		if ( is_numeric( $margin_string ) && ( 0 != $margin_string) )
			$margin_string .= '%'; // Legacy values are always in percent
		
		if ( '%' == substr( $margin_string, -1 ) )
			$margin_percent = (float) substr( $margin_string, 0, strlen( $margin_string ) - 1 );
		else
			$margin_percent = 0;
		
		$width_string = strtolower( trim( $arguments['mla_itemwidth'] ) );
		if ( 'none' != $width_string ) {
			switch ( $width_string ) {
				case 'exact':
					$margin_percent = 0;
					/* fallthru */
				case 'calculate':
					$width_string = $columns > 0 ? (floor(1000/$columns)/10) - ( 2.0 * $margin_percent ) : 100 - ( 2.0 * $margin_percent );
					/* fallthru */
				default:
					if ( is_numeric( $width_string ) && ( 0 != $width_string) )
						$width_string .= '%'; // Legacy values are always in percent
			}
		} // $use_width
		
		$float = strtolower( $arguments['mla_float'] );
		if ( ! in_array( $float, array( 'left', 'none', 'right' ) ) )
			$float = is_rtl() ? 'right' : 'left';
		
		$style_values = array(
			'mla_style' => $arguments['mla_style'],
			'mla_markup' => $arguments['mla_markup'],
			'instance' => $instance,
			'id' => $post->ID,
			'itemtag' => tag_escape( $arguments['itemtag'] ),
			'icontag' => tag_escape( $arguments['icontag'] ),
			'captiontag' => tag_escape( $arguments['captiontag'] ),
			'columns' => $columns,
			'itemwidth' => $width_string,
			'margin' => $margin_string,
			'float' => $float,
			'selector' => "mla_gallery-{$instance}",
			'size_class' => sanitize_html_class( $size_class )
		);

		$style_template = $gallery_style = '';
		$use_mla_gallery_style = ( 'none' != strtolower( $style_values['mla_style'] ) );
		if ( apply_filters( 'use_mla_gallery_style', $use_mla_gallery_style, $style_values['mla_style'] ) ) {
			$style_template = MLAOptions::mla_fetch_gallery_template( $style_values['mla_style'], 'style' );
			if ( empty( $style_template ) ) {
				$style_values['mla_style'] = 'default';
				$style_template = MLAOptions::mla_fetch_gallery_template( 'default', 'style' );
			}
				
			if ( ! empty ( $style_template ) ) {
				/*
				 * Look for 'query' and 'request' substitution parameters
				 */
				$style_values = MLAData::mla_expand_field_level_parameters( $style_template, $attr, $style_values );

				/*
				 * Clean up the template to resolve width or margin == 'none'
				 */
				if ( 'none' == $margin_string ) {
					$style_values['margin'] = '0';
					$style_template = preg_replace( '/margin:[\s]*\[\+margin\+\][\%]*[\;]*/', '', $style_template );
				}

				if ( 'none' == $width_string ) {
					$style_values['itemwidth'] = 'auto';
					$style_template = preg_replace( '/width:[\s]*\[\+itemwidth\+\][\%]*[\;]*/', '', $style_template );
				}
				$gallery_style = MLAData::mla_parse_template( $style_template, $style_values );
				
				/*
				 * Clean up the styles to resolve extra "%" suffixes on width or margin (pre v1.42 values)
				 */
				$preg_pattern = array( '/([margin|width]:[^\%]*)\%\%/', '/([margin|width]:.*)auto\%/', '/([margin|width]:.*)inherit\%/' );
				$preg_replacement = array( '${1}%', '${1}auto', '${1}inherit',  );
				$gallery_style = preg_replace( $preg_pattern, $preg_replacement, $gallery_style );
			} // !empty template
		} // use_mla_gallery_style
		
		$upload_dir = wp_upload_dir();
		$markup_values = $style_values;
		$markup_values['site_url'] = site_url();
		$markup_values['base_url'] = $upload_dir['baseurl'];
		$markup_values['base_dir'] = $upload_dir['basedir'];

		$open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-open', 'markup' );
		if ( false === $open_template ) {
			$markup_values['mla_markup'] = 'default';
			$open_template = MLAOptions::mla_fetch_gallery_template( 'default-open', 'markup' );
		}
		if ( empty( $open_template ) )
			$open_template = '';

		$row_open_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-open', 'markup' );
		if ( empty( $row_open_template ) )
			$row_open_template = '';
				
		$item_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-item', 'markup' );
		if ( empty( $item_template ) )
			$item_template = '';

		$row_close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-row-close', 'markup' );
		if ( empty( $row_close_template ) )
			$row_close_template = '';
			
		$close_template = MLAOptions::mla_fetch_gallery_template( $markup_values['mla_markup'] . '-close', 'markup' );
		if ( empty( $close_template ) )
			$close_template = '';

		/*
		 * Look for gallery-level markup substitution parameters
		 */
		$new_text = $open_template . $row_open_template . $row_close_template . $close_template;
		
		$markup_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $markup_values );

		if ( self::$mla_debug ) {
			$output = self::$mla_debug_messages;
			self::$mla_debug_messages = '';
		}
		else
			$output = '';

		if ($is_gallery ) {
			if ( empty( $open_template ) )
				$gallery_div = '';
			else
				$gallery_div = MLAData::mla_parse_template( $open_template, $markup_values );
	
			$output .= apply_filters( 'mla_gallery_style', $gallery_style . $gallery_div, $style_values, $markup_values, $style_template, $open_template );
		}
		else {
			if ( ! isset( $attachments['found_rows'] ) )
				$attachments['found_rows'] = 0;
	
			/*
			 * Handle 'previous_page', 'next_page', and 'paginate_links'
			 */
			$pagination_result = self::_process_pagination_output_types( $output_parameters, $markup_values, $arguments, $attr, $attachments['found_rows'], $output );
			if ( false !== $pagination_result )
				return $pagination_result;
				
			unset( $attachments['found_rows'] );
		}
		
		/*
		 * For "previous_link" and "next_link", discard all of the $attachments except the appropriate choice
		 */
		if ( ! $is_gallery ) {
			$is_previous = 'previous_link' == $output_parameters[0];
			$is_next = 'next_link' == $output_parameters[0];
			
			if ( ! ( $is_previous || $is_next ) )
				return ''; // unknown outtput type
			
			$is_wrap = isset( $output_parameters[1] ) && 'wrap' == $output_parameters[1];
			$current_id = empty( $arguments['id'] ) ? $markup_values['id'] : $arguments['id'];
				
			foreach ( $attachments as $id => $attachment ) {
				if ( $attachment->ID == $current_id )
					break;
			}
		
			$target_id = $is_previous ? $id - 1 : $id + 1;
			if ( isset( $attachments[ $target_id ] ) ) {
				$attachments = array( $attachments[ $target_id ] );
			}
			elseif ( $is_wrap ) {
				if ( $is_next )
					$attachments = array( array_shift( $attachments ) );
				else
					$attachments = array( array_pop( $attachments ) );
			} // is_wrap
			elseif ( ! empty( $arguments['mla_nolink_text'] ) )
				return self::_process_shortcode_parameter( $arguments['mla_nolink_text'], $markup_values ) . '</a>';
			else
				return '';
		} // ! is_gallery
		
		$column_index = 0;
		foreach ( $attachments as $id => $attachment ) {
			$item_values = $markup_values;
			
			/*
			 * fill in item-specific elements
			 */
			$item_values['index'] = (string) 1 + $column_index;

			$item_values['excerpt'] = wptexturize( $attachment->post_excerpt );
			$item_values['attachment_ID'] = $attachment->ID;
			$item_values['mime_type'] = $attachment->post_mime_type;
			$item_values['menu_order'] = $attachment->menu_order;
			$item_values['date'] = $attachment->post_date;
			$item_values['modified'] = $attachment->post_modified;
			$item_values['parent'] = $attachment->post_parent;
			$item_values['parent_title'] = '(unattached)';
			$item_values['parent_type'] = '';
			$item_values['parent_date'] = '';
			$item_values['title'] = wptexturize( $attachment->post_title );
			$item_values['slug'] = wptexturize( $attachment->post_name );
			$item_values['width'] = '';
			$item_values['height'] = '';
			$item_values['image_meta'] = '';
			$item_values['image_alt'] = '';
			$item_values['base_file'] = '';
			$item_values['path'] = '';
			$item_values['file'] = '';
			$item_values['description'] = wptexturize( $attachment->post_content );
			$item_values['file_url'] = wptexturize( $attachment->guid );
			$item_values['author_id'] = $attachment->post_author;
		
			$user = get_user_by( 'id', $attachment->post_author );
			if ( isset( $user->data->display_name ) )
				$item_values['author'] = wptexturize( $user->data->display_name );
			else
				$item_values['author'] = 'unknown';

			$post_meta = MLAData::mla_fetch_attachment_metadata( $attachment->ID );
			$base_file = $post_meta['mla_wp_attached_file'];
			$sizes = isset( $post_meta['mla_wp_attachment_metadata']['sizes'] ) ? $post_meta['mla_wp_attachment_metadata']['sizes'] : array();

			if ( !empty( $post_meta['mla_wp_attachment_metadata']['width'] ) )
				$item_values['width'] = $post_meta['mla_wp_attachment_metadata']['width'];
			if ( !empty( $post_meta['mla_wp_attachment_metadata']['height'] ) )
				$item_values['height'] = $post_meta['mla_wp_attachment_metadata']['height'];
			if ( !empty( $post_meta['mla_wp_attachment_metadata']['image_meta'] ) )
				$item_values['image_meta'] = wptexturize( var_export( $post_meta['mla_wp_attachment_metadata']['image_meta'], true ) );
			if ( !empty( $post_meta['mla_wp_attachment_image_alt'] ) )
				$item_values['image_alt'] = wptexturize( $post_meta['mla_wp_attachment_image_alt'] );

			if ( ! empty( $base_file ) ) {
				$last_slash = strrpos( $base_file, '/' );
				if ( false === $last_slash ) {
					$file_name = $base_file;
					$item_values['base_file'] = wptexturize( $base_file );
					$item_values['file'] = wptexturize( $base_file );
				}
				else {
					$file_name = substr( $base_file, $last_slash + 1 );
					$item_values['base_file'] = wptexturize( $base_file );
					$item_values['path'] = wptexturize( substr( $base_file, 0, $last_slash + 1 ) );
					$item_values['file'] = wptexturize( $file_name );
				}
			}
			else
				$file_name = '';

			$parent_info = MLAData::mla_fetch_attachment_parent_data( $attachment->post_parent );
			if ( isset( $parent_info['parent_title'] ) )
				$item_values['parent_title'] = wptexturize( $parent_info['parent_title'] );
				
			if ( isset( $parent_info['parent_date'] ) )
				$item_values['parent_date'] = wptexturize( $parent_info['parent_date'] );
				
			if ( isset( $parent_info['parent_type'] ) )
				$item_values['parent_type'] = wptexturize( $parent_info['parent_type'] );
				
			/*
			 * Add attachment-specific field-level substitution parameters
			 */
			$new_text = $item_template . str_replace( '{+', '[+', str_replace( '+}', '+]', $arguments['mla_link_attributes'] . $arguments['mla_link_class'] . $arguments['mla_link_href'] . $arguments['mla_link_text'] . $arguments['mla_nolink_text'] . $arguments['mla_rollover_text'] . $arguments['mla_image_class'] . $arguments['mla_image_alt'] . $arguments['mla_image_attributes'] . $arguments['mla_caption'] ) );
		
			$item_values = MLAData::mla_expand_field_level_parameters( $new_text, $attr, $item_values, $attachment->ID );

			if ( $item_values['captiontag'] ) {
				$item_values['caption'] = wptexturize( $attachment->post_excerpt );
				if ( ! empty( $arguments['mla_caption'] ) )
					$item_values['caption'] = wptexturize( self::_process_shortcode_parameter( $arguments['mla_caption'], $item_values ) );
			}
			else
				$item_values['caption'] = '';
			
			if ( ! empty( $arguments['mla_link_text'] ) )
				$link_text = self::_process_shortcode_parameter( $arguments['mla_link_text'], $item_values );
			else
				$link_text = false;

			$item_values['pagelink'] = wp_get_attachment_link($attachment->ID, $size, true, $show_icon, $link_text);
			$item_values['filelink'] = wp_get_attachment_link($attachment->ID, $size, false, $show_icon, $link_text);

			/*
			 * Apply the Gallery Display Content parameters.
			 * Note that $link_attributes and $rollover_text
			 * are used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_target'] ) )
				$link_attributes = 'target="' . $arguments['mla_target'] . '" ';
			else
				$link_attributes = '';
				
			if ( ! empty( $arguments['mla_link_attributes'] ) )
				$link_attributes .= self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $item_values ) . ' ';

			if ( ! empty( $arguments['mla_link_class'] ) )
				$link_attributes .= 'class="' . self::_process_shortcode_parameter( $arguments['mla_link_class'], $item_values ) . '" ';

			if ( ! empty( $link_attributes ) ) {
				$item_values['pagelink'] = str_replace( '<a href=', '<a ' . $link_attributes . 'href=', $item_values['pagelink'] );
				$item_values['filelink'] = str_replace( '<a href=', '<a ' . $link_attributes . 'href=', $item_values['filelink'] );
			}
			
			if ( ! empty( $arguments['mla_rollover_text'] ) ) {
				$rollover_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $item_values ) );
				
				/*
				 * Replace single- and double-quote delimited values
				 */
				$item_values['pagelink'] = preg_replace('# title=\'([^\']*)\'#', " title='{$rollover_text}'", $item_values['pagelink'] );
				$item_values['pagelink'] = preg_replace('# title=\"([^\"]*)\"#', " title=\"{$rollover_text}\"", $item_values['pagelink'] );
				$item_values['filelink'] = preg_replace('# title=\'([^\']*)\'#', " title='{$rollover_text}'", $item_values['filelink'] );
				$item_values['filelink'] = preg_replace('# title=\"([^\"]*)\"#', " title=\"{$rollover_text}\"", $item_values['filelink'] );
			}
			else
				$rollover_text = $item_values['title'];

			/*
			 * Process the <img> tag, if present
			 * Note that $image_attributes, $image_class and $image_alt
			 * are used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_image_attributes'] ) )
				$image_attributes = self::_process_shortcode_parameter( $arguments['mla_image_attributes'], $item_values ) . ' ';
			else
				$image_attributes = '';
				
			if ( ! empty( $arguments['mla_image_class'] ) )
				$image_class = esc_attr( self::_process_shortcode_parameter( $arguments['mla_image_class'], $item_values ) );
			else
				$image_class = '';

				if ( ! empty( $arguments['mla_image_alt'] ) )
					$image_alt = esc_attr( self::_process_shortcode_parameter( $arguments['mla_image_alt'], $item_values ) );
				else
					$image_alt = '';

			if ( false !== strpos( $item_values['pagelink'], '<img ' ) ) {
				if ( ! empty( $image_attributes ) ) {
					$item_values['pagelink'] = str_replace( '<img ', '<img ' . $image_attributes, $item_values['pagelink'] );
					$item_values['filelink'] = str_replace( '<img ', '<img ' . $image_attributes, $item_values['filelink'] );
				}
				
				/*
				 * Extract existing class values and add to them
				 */
				if ( ! empty( $image_class ) ) {
					$match_count = preg_match_all( '# class=\"([^\"]+)\" #', $item_values['pagelink'], $matches, PREG_OFFSET_CAPTURE );
					if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
						$class = $matches[1][0][0] . ' ' . $image_class;
					}
					else
						$class = $image_class;
					
					$item_values['pagelink'] = preg_replace('# class=\"([^\"]*)\"#', " class=\"{$class}\"", $item_values['pagelink'] );
					$item_values['filelink'] = preg_replace('# class=\"([^\"]*)\"#', " class=\"{$class}\"", $item_values['filelink'] );
				}
				
				if ( ! empty( $image_alt ) ) {
					$item_values['pagelink'] = preg_replace('# alt=\"([^\"]*)\"#', " alt=\"{$image_alt}\"", $item_values['pagelink'] );
					$item_values['filelink'] = preg_replace('# alt=\"([^\"]*)\"#', " alt=\"{$image_alt}\"", $item_values['filelink'] );
				}
			} // process <img> tag
			
			switch ( $arguments['link'] ) {
				case 'permalink':
				case 'post':
					$item_values['link'] = $item_values['pagelink'];
					break;
				case 'file':
				case 'full':
					$item_values['link'] = $item_values['filelink'];
					break;
				default:
					$item_values['link'] = $item_values['filelink'];

					/*
					 * Check for link to specific (registered) file size
					 */
					if ( array_key_exists( $arguments['link'], $sizes ) ) {
						$target_file = $sizes[ $arguments['link'] ]['file'];
						$item_values['link'] = str_replace( $file_name, $target_file, $item_values['filelink'] );
					}
			} // switch 'link'
			
			/*
			 * Extract target and thumbnail fields
			 */
			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['pagelink'], $matches, PREG_OFFSET_CAPTURE );
 			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['pagelink_url'] = $matches[1][0][0];
			}
			else
				$item_values['pagelink_url'] = '';

			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['filelink'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['filelink_url'] = $matches[1][0][0];
			}
			else
				$item_values['filelink_url'] = '';

			$match_count = preg_match_all( '#href=\'([^\']+)\'#', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['link_url'] = $matches[1][0][0];
			}
			else
				$item_values['link_url'] = '';

			/*
			 * Override the link value; leave filelink and pagelink unchanged
			 * Note that $link_href is used in the Google Viewer code below
			 */
			if ( ! empty( $arguments['mla_link_href'] ) ) {
				$link_href = self::_process_shortcode_parameter( $arguments['mla_link_href'], $item_values );

				/*
				 * Replace single- and double-quote delimited values
				 */
				$item_values['link'] = preg_replace('# href=\'([^\']*)\'#', " href='{$link_href}'", $item_values['link'] );
				$item_values['link'] = preg_replace('# href=\"([^\"]*)\"#', " href=\"{$link_href}\"", $item_values['link'] );
			}
			else
				$link_href = '';
			
			$match_count = preg_match_all( '#\<a [^\>]+\>(.*)\</a\>#', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['thumbnail_content'] = $matches[1][0][0];
			}
			else
				$item_values['thumbnail_content'] = '';

			$match_count = preg_match_all( '# width=\"([^\"]+)\" height=\"([^\"]+)\" src=\"([^\"]+)\" #', $item_values['link'], $matches, PREG_OFFSET_CAPTURE );
			if ( ! ( ( $match_count == false ) || ( $match_count == 0 ) ) ) {
				$item_values['thumbnail_width'] = $matches[1][0][0];
				$item_values['thumbnail_height'] = $matches[2][0][0];
				$item_values['thumbnail_url'] = $matches[3][0][0];
			}
			else {
				$item_values['thumbnail_width'] = '';
				$item_values['thumbnail_height'] = '';
				$item_values['thumbnail_url'] = '';
			}

			/*
			 * Check for Google file viewer substitution, uses above-defined
			 * $link_attributes (includes target), $rollover_text, $link_href (link only),
			 * $image_attributes, $image_class, $image_alt
			 */
			if ( $arguments['mla_viewer'] && empty( $item_values['thumbnail_url'] ) ) {
				$last_dot = strrpos( $item_values['file'], '.' );
				if ( !( false === $last_dot) ) {
					$extension = substr( $item_values['file'], $last_dot + 1 );
					if ( in_array( $extension, $arguments['mla_viewer_extensions'] ) ) {
						/*
						 * <img> tag (thumbnail_text)
						 */
						if ( ! empty( $image_class ) )
							$image_class = ' class="' . $image_class . '"';
							
						if ( ! empty( $image_alt ) )
							$image_alt = ' alt="' . $image_alt . '"';
						elseif ( ! empty( $item_values['caption'] ) )
							$image_alt = ' alt="' . $item_values['caption'] . '"';

						$item_values['thumbnail_content'] = sprintf( '<img %1$ssrc="http://docs.google.com/viewer?url=%2$s&a=bi&pagenumber=%3$d&w=%4$d"%5$s%6$s>', $image_attributes, $item_values['filelink_url'], $arguments['mla_viewer_page'], $arguments['mla_viewer_width'], $image_class, $image_alt );
						
						/*
						 * Filelink, pagelink and link
						 */
						$item_values['pagelink'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $item_values['pagelink_url'], $rollover_text, $item_values['thumbnail_content'] );
						$item_values['filelink'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $item_values['filelink_url'], $rollover_text, $item_values['thumbnail_content'] );

						if ( ! empty( $link_href ) )
							$item_values['link'] = sprintf( '<a %1$shref="%2$s" title="%3$s">%4$s</a>', $link_attributes, $link_href, $rollover_text, $item_values['thumbnail_content'] );
						elseif ( 'permalink' == $arguments['link'] )
							$item_values['link'] = $item_values['pagelink'];
						else
							$item_values['link'] = $item_values['filelink'];
					} // viewer extension
				} // has extension
			} // mla_viewer
			
			if ($is_gallery ) {
				/*
				 * Start of row markup
				 */
				if ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 )
					$output .= MLAData::mla_parse_template( $row_open_template, $markup_values );
				
				/*
				 * item markup
				 */
				$column_index++;
				if ( $item_values['columns'] > 0 && $column_index % $item_values['columns'] == 0 )
					$item_values['last_in_row'] = 'last_in_row';
				else
					$item_values['last_in_row'] = '';

				$output .= MLAData::mla_parse_template( $item_template, $item_values );
	
				/*
				 * End of row markup
				 */
				if ( $markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 )
					$output .= MLAData::mla_parse_template( $row_close_template, $markup_values );
			} // is_gallery
			elseif ( ( $is_previous || $is_next ) )
				return $item_values['link'];
		} // foreach attachment
	
		if ($is_gallery ) {
			/*
			 * Close out partial row
			 */
			if ( ! ($markup_values['columns'] > 0 && $column_index % $markup_values['columns'] == 0 ) )
				$output .= MLAData::mla_parse_template( $row_close_template, $markup_values );
				
			$output .= MLAData::mla_parse_template( $close_template, $markup_values );
		} // is_gallery
	
		return $output;
	}

	/**
	 * Handles brace/bracket escaping and parses template for a shortcode parameter
	 *
	 * @since 1.14
	 *
	 * @param string raw shortcode parameter, e.g., "text {+field+} {brackets} \\{braces\\}"
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 *
	 * @return string query specification with HTML escape sequences and line breaks removed
	 */
	private static function _process_shortcode_parameter( $text, $markup_values ) {
		$new_text = str_replace( '{', '[', str_replace( '}', ']', $text ) );
		$new_text = str_replace( '\[', '{', str_replace( '\]', '}', $new_text ) );
		return MLAData::mla_parse_template( $new_text, $markup_values );
	}
	
	/**
	 * Handles pagnation output types 'previous_page', 'next_page', and 'paginate_links'
	 *
	 * @since 1.42
	 *
	 * @param array	value(s) for mla_output_type parameter
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 * @param string merged default and passed shortcode parameter values
	 * @param integer number of attachments in the gallery, without pagination
	 * @param string output text so far, may include debug values
	 *
	 * @return mixed	false or string with HTML for pagination output types
	 */
	private static function _paginate_links( $output_parameters, $markup_values, $arguments, $found_rows, $output = '' ) {
		if ( 2 > $markup_values['last_page'] )
			return '';
			
		$show_all = $prev_next = false;
		
		if ( isset ( $output_parameters[1] ) )
				switch ( $output_parameters[1] ) {
				case 'show_all':
					$show_all = true;
					break;
				case 'prev_next':
					$prev_next = true;
			}

		$current_page = $markup_values['current_page'];
		$last_page = $markup_values['last_page'];
		$end_size = absint( $arguments['mla_end_size'] );
		$mid_size = absint( $arguments['mla_mid_size'] );
		$posts_per_page = $markup_values['posts_per_page'];
		
		$new_target = ( ! empty( $arguments['mla_target'] ) ) ? 'target="' . $arguments['mla_target'] . '" ' : '';
		
		/*
		 * these will add to the default classes
		 */
		$new_class = ( ! empty( $arguments['mla_link_class'] ) ) ? ' ' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_class'], $markup_values ) ) : '';

		$new_attributes = ( ! empty( $arguments['mla_link_attributes'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $markup_values ) ) . ' ' : '';

		$new_base =  ( ! empty( $arguments['mla_link_href'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_href'], $markup_values ) ) : $markup_values['new_url'];

		/*
		 * Build the array of page links
		 */
		$page_links = array();
		$dots = false;
		
		if ( $prev_next && $current_page && 1 < $current_page ) {
			$markup_values['new_page'] = $current_page - 1;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			$new_url = add_query_arg( array( 'mla_paginate_current' => $current_page - 1 ), $new_base );
			$prev_text = ( ! empty( $arguments['mla_prev_text'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_prev_text'], $markup_values ) ) : '&laquo; Previous';
			$page_links[] = sprintf( '<a %1$sclass="prev page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
				/* %1$s */ $new_target,
				/* %2$s */ $new_class,
				/* %3$s */ $new_attributes,
				/* %4$s */ $new_title,
				/* %5$s */ $new_url,
				/* %6$s */ $prev_text );
		}
		
		for ( $new_page = 1; $new_page <= $last_page; $new_page++ ) {
			$new_page_display = number_format_i18n( $new_page );
			$markup_values['new_page'] = $new_page;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			
			if ( $new_page == $current_page ) {
				// build current page span
				$page_links[] = sprintf( '<span class="page-numbers current%1$s">%2$s</span>',
					/* %1$s */ $new_class,
					/* %2$s */ $new_page_display );
				$dots = true;
			}
			else {
				if ( $show_all || ( $new_page <= $end_size || ( $current_page && $new_page >= $current_page - $mid_size && $new_page <= $current_page + $mid_size ) || $new_page > $last_page - $end_size ) ) {
					// build link
					$new_url = add_query_arg( array( 'mla_paginate_current' => $new_page ), $new_base );
					$page_links[] = sprintf( '<a %1$sclass="page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
						/* %1$s */ $new_target,
						/* %2$s */ $new_class,
						/* %3$s */ $new_attributes,
						/* %4$s */ $new_title,
						/* %5$s */ $new_url,
						/* %6$s */ $new_page_display );
					$dots = true;
				}
				elseif ( $dots && ! $show_all ) {
					// build link
					$page_links[] = sprintf( '<span class="page-numbers dots%1$s">&hellip;</span>',
						/* %1$s */ $new_class );
					$dots = false;
				}
			} // ! current
		} // for $new_page
		
		if ( $prev_next && $current_page && ( $current_page < $last_page || -1 == $last_page ) ) {
			// build next link
			$markup_values['new_page'] = $current_page + 1;
			$new_title = ( ! empty( $arguments['mla_rollover_text'] ) ) ? 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ' : '';
			$new_url = add_query_arg( array( 'mla_paginate_current' => $current_page + 1 ), $new_base );
			$next_text = ( ! empty( $arguments['mla_next_text'] ) ) ? esc_attr( self::_process_shortcode_parameter( $arguments['mla_next_text'], $markup_values ) ) : 'Next &raquo;';
			$page_links[] = sprintf( '<a %1$sclass="next page-numbers%2$s" %3$s%4$shref="%5$s">%6$s</a>',
				/* %1$s */ $new_target,
				/* %2$s */ $new_class,
				/* %3$s */ $new_attributes,
				/* %4$s */ $new_title,
				/* %5$s */ $new_url,
				/* %6$s */ $next_text );
		}

		switch ( strtolower( trim( $arguments['mla_paginate_type'] ) ) ) {
			case 'list':
				$results = "<ul class='page-numbers'>\n\t<li>";
				$results .= join("</li>\n\t<li>", $page_links);
				$results .= "</li>\n</ul>\n";
				break;
			default:
				$results = join("\n", $page_links);
		} // mla_paginate_type
	
		return $output . $results;
	}
	
	/**
	 * Handles pagnation output types 'previous_page', 'next_page', and 'paginate_links'
	 *
	 * @since 1.42
	 *
	 * @param array	value(s) for mla_output_type parameter
	 * @param string template substitution values, e.g., ('instance' => '1', ...  )
	 * @param string merged default and passed shortcode parameter values
	 * @param string raw passed shortcode parameter values
	 * @param integer number of attachments in the gallery, without pagination
	 * @param string output text so far, may include debug values
	 *
	 * @return mixed	false or string with HTML for pagination output types
	 */
	private static function _process_pagination_output_types( $output_parameters, $markup_values, $arguments, $attr, $found_rows, $output = '' ) {
		if ( ! in_array( $output_parameters[0], array( 'previous_page', 'next_page', 'paginate_links' ) ) )
			return false;
			
		/*
		 * Add data selection parameters to gallery-specific and mla_gallery-specific parameters
		 */
		$arguments = array_merge( $arguments, shortcode_atts( self::$data_selection_parameters, $attr ) );
		$posts_per_page = absint( $arguments['posts_per_page'] );
			
		if ( 0 == $posts_per_page )
			$posts_per_page = absint( $arguments['numberposts'] );
			
		if ( 0 == $posts_per_page )
			$posts_per_page = absint( get_option('posts_per_page') );

			if ( 0 < $posts_per_page ) {
				$max_page = floor( $found_rows / $posts_per_page );
				if ( $max_page < ( $found_rows / $posts_per_page ) )
					$max_page++;
			}
			else
				$max_page = 1;

		if ( isset( $arguments['mla_paginate_total'] )  && $max_page > absint( $arguments['mla_paginate_total'] ) )
			$max_page = absint( $arguments['mla_paginate_total'] );
			
		if ( isset( $arguments['mla_paginate_current'] ) )
			$paged = absint( $arguments['mla_paginate_current'] );
		else
			$paged = absint( $arguments['paged'] );
		
		if ( 0 == $paged )
			$paged = 1;

		if ( $max_page < $paged )
			$paged = $max_page;

		switch ( $output_parameters[0] ) {
			case 'previous_page':
				if ( 1 < $paged )
					$new_page = $paged - 1;
				else {
					$new_page = 0;

					if ( isset ( $output_parameters[1] ) )
						switch ( $output_parameters[1] ) {
							case 'wrap':
								$new_page = $max_page;
								break;
							case 'first':
								$new_page = 1;
						}
				}
				
				break;
			case 'next_page':
				if ( $paged < $max_page )
					$new_page = $paged + 1;
				else {
					$new_page = 0;

					if ( isset ( $output_parameters[1] ) )
						switch ( $output_parameters[1] ) {
							case 'last':
								$new_page = $max_page;
								break;
							case 'wrap':
								$new_page = 1;
						}
				}
					
				break;
			case 'paginate_links':
				$new_page = 0;
				$new_text = '';
		}
		
		$markup_values['current_page'] = $paged;
		$markup_values['new_page'] = $new_page;
		$markup_values['last_page'] = $max_page;
		$markup_values['posts_per_page'] = $posts_per_page;
		$markup_values['found_rows'] = $found_rows;

		if ( $paged )
			$markup_values['current_offset'] = ( $paged - 1 ) * $posts_per_page;
		else
			$markup_values['current_offset'] = 0;
			
		if ( $new_page )
			$markup_values['new_offset'] = ( $new_page - 1 ) * $posts_per_page;
		else
			$markup_values['new_offset'] = 0;
			
		$markup_values['current_page_text'] = 'mla_paginate_current="[+current_page+]"';
		$markup_values['new_page_text'] = 'mla_paginate_current="[+new_page+]"';
		$markup_values['last_page_text'] = 'mla_paginate_total="[+last_page+]"';
		$markup_values['posts_per_page_text'] = 'posts_per_page="[+posts_per_page+]"';
		
		if ( 'HTTPS' == substr( $_SERVER["SERVER_PROTOCOL"], 0, 5 ) )
			$markup_values['scheme'] = 'https://';
		else
			$markup_values['scheme'] = 'http://';
			
		$markup_values['http_host'] = $_SERVER['HTTP_HOST'];
		$markup_values['request_uri'] = add_query_arg( array( 'mla_paginate_current' => $new_page ), $_SERVER['REQUEST_URI'] );	
		$markup_values['new_url'] = set_url_scheme( $markup_values['scheme'] . $markup_values['http_host'] . $markup_values['request_uri'] );

		/*
		 * Build the new link, applying Gallery Display Content parameters
		 */
		if ( 'paginate_links' == $output_parameters[0] )
			return self::_paginate_links( $output_parameters, $markup_values, $arguments, $found_rows, $output );
			
		if ( 0 == $new_page ) {
			if ( ! empty( $arguments['mla_nolink_text'] ) )
				return self::_process_shortcode_parameter( $arguments['mla_nolink_text'], $markup_values );
			else
				return '';
		}
		
		$new_link = '<a ';
		
		if ( ! empty( $arguments['mla_target'] ) )
			$new_link .= 'target="' . $arguments['mla_target'] . '" ';
		
		if ( ! empty( $arguments['mla_link_class'] ) )
			$new_link .= 'class="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_class'], $markup_values ) ) . '" ';

		if ( ! empty( $arguments['mla_rollover_text'] ) )
			$new_link .= 'title="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_rollover_text'], $markup_values ) ) . '" ';

		if ( ! empty( $arguments['mla_link_attributes'] ) )
			$new_link .= esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_attributes'], $markup_values ) ) . ' ';

		if ( ! empty( $arguments['mla_link_href'] ) )
			$new_link .= 'href="' . esc_attr( self::_process_shortcode_parameter( $arguments['mla_link_href'], $markup_values ) ) . '" >';
		else
			$new_link .= 'href="' . $markup_values['new_url'] . '" >';
		
		if ( ! empty( $arguments['mla_link_text'] ) )
			$new_link .= self::_process_shortcode_parameter( $arguments['mla_link_text'], $markup_values ) . '</a>';
		else {
			if ( 'previous_page' == $output_parameters[0] ) {
				if ( isset( $arguments['mla_prev_text'] ) )
					$new_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_prev_text'], $markup_values ) );
				else
					$new_text = '&laquo; Previous';
			}
			else {
				if ( isset( $arguments['mla_next_text'] ) )
					$new_text = esc_attr( self::_process_shortcode_parameter( $arguments['mla_next_text'], $markup_values ) );
				else
					$new_text = 'Next &raquo;';
			}
		
			$new_link .= $new_text . '</a>';
		}
		
		return $new_link;
	}
	
	/**
	 * WP_Query filter "parameters"
	 *
	 * This array defines parameters for the query's where and orderby filters,
	 * mla_shortcode_query_posts_where_filter and mla_shortcode_query_posts_orderby_filter.
	 * The parameters are set up in the mla_get_shortcode_attachments function, and
	 * any further logic required to translate those values is contained in the filter.
	 *
	 * Array index values are: orderby, post_parent
	 *
	 * @since 1.13
	 *
	 * @var	array
	 */
	private static $query_parameters = array();

	/**
	 * Cleans up damage caused by the Visual Editor to the tax_query and meta_query specifications
	 *
	 * @since 1.14
	 *
	 * @param string query specification; PHP nested arrays
	 *
	 * @return string query specification with HTML escape sequences and line breaks removed
	 */
	private static function _sanitize_query_specification( $specification ) {
		$specification = wp_specialchars_decode( $specification );
		$specification = str_replace( array( '<br />', '<p>', '</p>', "\r", "\n" ), ' ', $specification );
		return $specification;
	}
	
	/**
	 * Translates query parameters to a valid SQL order by clause.
	 *
	 * Accepts one or more valid columns, with or without ASC/DESC.
	 * Enhanced version of /wp-includes/formatting.php function sanitize_sql_orderby().
	 *
	 * @since 1.20
	 *
	 * @param array Validated query parameters
	 * @return string|bool Returns the orderby clause if present, false otherwise.
	 */
	private static function _validate_sql_orderby( $query_parameters ){
		global $wpdb;

		$results = array ();
		$order = isset( $query_parameters['order'] ) ? ' ' . $query_parameters['order'] : '';
		$orderby = isset( $query_parameters['orderby'] ) ? $query_parameters['orderby'] : '';
		$meta_key = isset( $query_parameters['meta_key'] ) ? $query_parameters['meta_key'] : '';
		$post__in = isset( $query_parameters['post__in'] ) ? implode(',', array_map( 'absint', $query_parameters['post__in'] )) : '';

		if ( empty( $orderby ) ) {
			$orderby = "$wpdb->posts.post_date " . $order;
		} elseif ( 'none' == $orderby ) {
			return '';
		} elseif ( $orderby == 'post__in' && ! empty( $post__in ) ) {
			$orderby = "FIELD( {$wpdb->posts}.ID, {$post__in} )";
		} else {
			$allowed_keys = array('ID', 'author', 'date', 'description', 'content', 'title', 'caption', 'excerpt', 'slug', 'name', 'modified', 'parent', 'menu_order', 'mime_type', 'comment_count', 'rand');
			if ( ! empty( $meta_key ) ) {
				$allowed_keys[] = $meta_key;
				$allowed_keys[] = 'meta_value';
				$allowed_keys[] = 'meta_value_num';
			}
		
			$obmatches = preg_split('/\s*,\s*/', trim($query_parameters['orderby']));
			foreach ( $obmatches as $index => $value ) {
				$count = preg_match('/([a-z0-9_]+)(\s+(ASC|DESC))?/i', $value, $matches);

				if ( $count && ( $value == $matches[0] ) && in_array( $matches[1], $allowed_keys ) ) {
					if ( 'rand' == $matches[1] )
							$results[] = 'RAND()';
					else {
						switch ( $matches[1] ) {
							case 'ID':
								$matches[1] = "$wpdb->posts.ID";
								break;
							case 'description':
								$matches[1] = "$wpdb->posts.post_content";
								break;
							case 'caption':
								$matches[1] = "$wpdb->posts.post_excerpt";
								break;
							case 'slug':
								$matches[1] = "$wpdb->posts.post_name";
								break;
							case 'menu_order':
								$matches[1] = "$wpdb->posts.menu_order";
								break;
							case 'comment_count':
								$matches[1] = "$wpdb->posts.comment_count";
								break;
							case $meta_key:
							case 'meta_value':
								$matches[1] = "$wpdb->postmeta.meta_value";
								break;
							case 'meta_value_num':
								$matches[1] = "$wpdb->postmeta.meta_value+0";
								break;
							default:
								$matches[1] = "$wpdb->posts.post_" . $matches[1];
						} // switch $matches[1]
	
						$results[] = isset( $matches[2] ) ? $matches[1] . $matches[2] : $matches[1] . $order;
					} // not 'rand'
				} // valid column specification
			} // foreach $obmatches

			$orderby = implode( ', ', $results );
			if ( empty( $orderby ) )
				return false;
		} // else filter by allowed keys, etc.

		return $orderby;
	}

	/**
	 * Data selection parameters for the WP_Query in [mla_gallery]
	 *
	 * @since 1.30
	 *
	 * @var	array
	 */
	private static $data_selection_parameters = array(
			'order' => 'ASC', // or 'DESC' or 'RAND'
			'orderby' => 'menu_order,ID',
			'id' => NULL,
			'ids' => array(),
			'include' => array(),
			'exclude' => array(),
			// MLA extensions, from WP_Query
			// Force 'get_children' style query
			'post_parent' => NULL, // post/page ID or 'current' or 'all'
			// Author
			'author' => NULL,
			'author_name' => '',
			// Category
			'cat' => 0,
			'category_name' => '',
			'category__and' => array(),
			'category__in' => array(),
			'category__not_in' => array(),
			// Tag
			'tag' => '',
			'tag_id' => 0,
			'tag__and' => array(),
			'tag__in' => array(),
			'tag__not_in' => array(),
			'tag_slug__and' => array(),
			'tag_slug__in' => array(),
			// Taxonomy parameters are handled separately
			// {tax_slug} => 'term' | array ( 'term, 'term, ... )
			// 'tax_query' => ''
			'tax_operator' => '',
			'tax_include_children' => true,
			// Post 
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_mime_type' => 'image',
			// Pagination - no default for most of these
			'nopaging' => true,
			'numberposts' => 0,
			'posts_per_page' => 0,
			'posts_per_archive_page' => 0,
			'paged' => NULL, // page number or 'current'
			'offset' => NULL,
			'mla_paginate_current' => NULL,
			'mla_paginate_total' => NULL,
			// TBD Time
			// Custom Field
			'meta_key' => '',
			'meta_value' => '',
			'meta_value_num' => NULL,
			'meta_compare' => '',
			'meta_query' => '',
			// Search
			's' => ''
		);

	/**
	 * Parses shortcode parameters and returns the gallery objects
	 *
	 * @since .50
	 *
	 * @param int Post ID of the parent
	 * @param array Attributes of the shortcode
	 * @param boolean true to calculate and return ['found_posts'] as an array element
	 *
	 * @return array List of attachments returned from WP_Query
	 */
	public static function mla_get_shortcode_attachments( $post_parent, $attr, $return_found_rows = false ) {
		global $wp_query;

		/*
		 * Parameters passed to the where and orderby filter functions
		 */
		self::$query_parameters = array();

		/*
		 * Merge input arguments with defaults, then extract the query arguments.
		 */
		 
		if ( is_string( $attr ) )
			$attr = shortcode_parse_atts( $attr );
			
		$arguments = shortcode_atts( self::$data_selection_parameters, $attr );

		/*
		 * 'RAND' is not documented in the codex, but is present in the code.
		 */
		if ( 'RAND' == strtoupper( $arguments['order'] ) ) {
			$arguments['orderby'] = 'none';
			unset( $arguments['order'] );
		}

		if ( !empty( $arguments['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) )
				$arguments['orderby'] = 'post__in';

			$arguments['include'] = $arguments['ids'];
		}
		unset( $arguments['ids'] );
	
		/*
		 * Extract taxonomy arguments
		 */
		$taxonomies = get_taxonomies( array ( 'show_ui' => true ), 'names' ); // 'objects'
		$query_arguments = array();
		if ( ! empty( $attr ) ) {
			foreach ( $attr as $key => $value ) {
				if ( 'tax_query' == $key ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else {
						$tax_query = NULL;
						$value = self::_sanitize_query_specification( $value );
						$function = @create_function('', 'return ' . $value . ';' );
						if ( is_callable( $function ) )
							$tax_query = $function();

						if ( is_array( $tax_query ) )						
							$query_arguments[ $key ] = $tax_query;
						else
							return '<p>ERROR: invalid mla_gallery tax_query = ' . var_export( $value, true ) . '</p>';
					} // not array
				}  // tax_query
				elseif ( array_key_exists( $key, $taxonomies ) ) {
					$query_arguments[ $key ] = implode(',', array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
					
					if ( 'false' == strtolower( trim( $arguments['tax_include_children'] ) ) ) {
						$arguments['tax_include_children'] = false;
						
						if ( '' == $arguments['tax_operator'] )
						 $arguments['tax_operator'] = 'OR';
					}
					else
						$arguments['tax_include_children'] = true;
					
					if ( in_array( strtoupper( $arguments['tax_operator'] ), array( 'OR', 'IN', 'NOT IN', 'AND' ) ) ) {
						$query_arguments['tax_query'] =	array( array( 'taxonomy' => $key, 'field' => 'slug', 'terms' => explode( ',', $query_arguments[ $key ] ), 'operator' => strtoupper( $arguments['tax_operator'] ), 'include_children' => $arguments['tax_include_children'] ) );
						unset( $query_arguments[ $key ] );
					}
				} // array_key_exists
			} //foreach $attr
		} // ! empty
		unset( $arguments['tax_operator'] );
		
		/*
		 * $query_arguments has been initialized in the taxonomy code above.
		 */
		$use_children = empty( $query_arguments );
		foreach ($arguments as $key => $value ) {
			/*
			 * There are several "fallthru" cases in this switch statement that decide 
			 * whether or not to limit the query to children of a specific post.
			 */
			$children_ok = true;
			switch ( $key ) {
			case 'post_parent':
				switch ( strtolower( $value ) ) {
				case 'all':
					$value = NULL;
					$use_children = false;
					break;
				case 'any':
					self::$query_parameters['post_parent'] = 'any';
					$value = NULL;
					$use_children = false;
					break;
				case 'current':
					$value = $post_parent;
					break;
				case 'none':
					self::$query_parameters['post_parent'] = 'none';
					$value = NULL;
					$use_children = false;
					break;
				}
				// fallthru
			case 'id':
				if ( is_numeric( $value ) ) {
					$query_arguments[ $key ] = intval( $value );
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'numberposts':
			case 'posts_per_page':
			case 'posts_per_archive_page':
				if ( is_numeric( $value ) ) {
					$value =  intval( $value );
					if ( ! empty( $value ) ) {
						$query_arguments[ $key ] = $value;
					}
				}
				unset( $arguments[ $key ] );
				break;
			case 'meta_value_num':
				$children_ok = false;
				// fallthru
			case 'offset':
				if ( is_numeric( $value ) ) {
					$query_arguments[ $key ] = intval( $value );
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'paged':
				if ( 'current' == strtolower( $value ) ) {
					/*
					 * Note: The query variable 'page' holds the pagenumber for a single paginated
					 * Post or Page that includes the <!--nextpage--> Quicktag in the post content. 
					 */
					if ( get_query_var('page') )
						$query_arguments[ $key ] = get_query_var('page');
					else
						$query_arguments[ $key ] = (get_query_var('paged')) ? get_query_var('paged') : 1;
				}
				elseif ( is_numeric( $value ) )
					$query_arguments[ $key ] = intval( $value );
				elseif ( '' === $value )
					$query_arguments[ $key ] = 1;
				unset( $arguments[ $key ] );
				break;
			case 'mla_paginate_current':
			case 'mla_paginate_total':
				if ( is_numeric( $value ) )
					$query_arguments[ $key ] = intval( $value );
				elseif ( '' === $value )
					$query_arguments[ $key ] = 1;
				unset( $arguments[ $key ] );
				break;
			case 'author':
			case 'cat':
			case 'tag_id':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = array_filter( $value );
					else
						$query_arguments[ $key ] = array_filter( array_map( 'intval', explode( ",", $value ) ) );
						
					if ( 1 == count( $query_arguments[ $key ] ) )
						$query_arguments[ $key ] = $query_arguments[ $key ][0];
					else
						$query_arguments[ $key ] = implode(',', $query_arguments[ $key ] );

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'category__and':
			case 'category__in':
			case 'category__not_in':
			case 'tag__and':
			case 'tag__in':
			case 'tag__not_in':
			case 'include':
				$children_ok = false;
				// fallthru
			case 'exclude':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = array_filter( $value );
					else
						$query_arguments[ $key ] = array_filter( array_map( 'intval', explode( ",", $value ) ) );
						
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'tag_slug__and':
			case 'tag_slug__in':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else
						$query_arguments[ $key ] = array_filter( array_map( 'trim', explode( ",", $value ) ) );

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'nopaging': // boolean
				if ( ! empty( $value ) && ( 'false' != strtolower( $value ) ) )
					$query_arguments[ $key ] = true;
				unset( $arguments[ $key ] );
				break;
			case 'author_name':
			case 'category_name':
			case 'tag':
			case 'meta_key':
			case 'meta_value':
			case 'meta_compare':
			case 's':
				$children_ok = false;
				// fallthru
			case 'post_type':
			case 'post_status':
			case 'post_mime_type':
			case 'orderby':
				if ( ! empty( $value ) ) {
					$query_arguments[ $key ] = $value;
					
					if ( ! $children_ok )
						$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			case 'order':
				if ( ! empty( $value ) ) {
					$value = strtoupper( $value );
					if ( in_array( $value, array( 'ASC', 'DESC' ) ) )
						$query_arguments[ $key ] = $value;
				}
				unset( $arguments[ $key ] );
				break;
			case 'meta_query':
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) )
						$query_arguments[ $key ] = $value;
					else {
						$meta_query = NULL;
						$value = self::_sanitize_query_specification( $value );
						$function = @create_function('', 'return ' . $value . ';' );
						if ( is_callable( $function ) )
							$meta_query = $function();
						
						if ( is_array( $meta_query ) )
							$query_arguments[ $key ] = $meta_query;
						else
							return '<p>ERROR: invalid mla_gallery meta_query = ' . var_export( $value, true ) . '</p>';
					} // not array

					$use_children = false;
				}
				unset( $arguments[ $key ] );
				break;
			default:
				// ignore anything else
			} // switch $key
		} // foreach $arguments 

		/*
		 * Decide whether to use a "get_children" style query
		 */
		if ( $use_children && ! isset( $query_arguments['post_parent'] ) ) {
			if ( ! isset( $query_arguments['id'] ) )
				$query_arguments['post_parent'] = $post_parent;
			else				
				$query_arguments['post_parent'] = $query_arguments['id'];

			unset( $query_arguments['id'] );
		}

		if ( isset( $query_arguments['numberposts'] ) && ! isset( $query_arguments['posts_per_page'] )) {
			$query_arguments['posts_per_page'] = $query_arguments['numberposts'];
		}
		unset( $query_arguments['numberposts'] );

		/*
		 * MLA pagination will override WordPress pagination
		 */
		if ( isset( $query_arguments['mla_paginate_current'] ) ) {
			unset( $query_arguments['nopaging'] );
			unset( $query_arguments['offset'] );
			unset( $query_arguments['paged'] );
			
			if ( isset( $query_arguments['mla_paginate_total'] ) && ( $query_arguments['mla_paginate_current'] > $query_arguments['mla_paginate_total'] ) )
				$query_arguments['offset'] = 0x7FFFFFFF; // suppress further output
			else
				$query_arguments['paged'] = $query_arguments['mla_paginate_current'];
		}
		else {
			if ( isset( $query_arguments['posts_per_page'] ) || isset( $query_arguments['posts_per_archive_page'] ) ||
				isset( $query_arguments['paged'] ) || isset( $query_arguments['offset'] ) ) {
				unset( $query_arguments['nopaging'] );
			}
		}
		unset( $query_arguments['mla_paginate_current'] );
		unset( $query_arguments['mla_paginate_total'] );

		if ( isset( $query_arguments['post_mime_type'] ) && ('all' == strtolower( $query_arguments['post_mime_type'] ) ) )
			unset( $query_arguments['post_mime_type'] );

		if ( ! empty($query_arguments['include']) ) {
			$incposts = wp_parse_id_list( $query_arguments['include'] );
			$query_arguments['posts_per_page'] = count($incposts);  // only the number of posts included
			$query_arguments['post__in'] = $incposts;
		} elseif ( ! empty($query_arguments['exclude']) )
			$query_arguments['post__not_in'] = wp_parse_id_list( $query_arguments['exclude'] );
	
		$query_arguments['ignore_sticky_posts'] = true;
		$query_arguments['no_found_rows'] = ! $return_found_rows;
	
		/*
		 * We will always handle "orderby" in our filter
		 */ 
		self::$query_parameters['orderby'] = self::_validate_sql_orderby( $query_arguments );
		if ( false === self::$query_parameters['orderby'] )
			unset( self::$query_parameters['orderby'] );
			
		unset( $query_arguments['orderby'] );
		unset( $query_arguments['order'] );
	
		if ( self::$mla_debug ) {
			add_filter( 'posts_clauses', 'MLAShortcodes::mla_shortcode_query_posts_clauses_filter', 0x7FFFFFFF, 1 );
			add_filter( 'posts_clauses_request', 'MLAShortcodes::mla_shortcode_query_posts_clauses_request_filter', 0x7FFFFFFF, 1 );
		}
		
		add_filter( 'posts_orderby', 'MLAShortcodes::mla_shortcode_query_posts_orderby_filter', 0x7FFFFFFF, 1 );
		add_filter( 'posts_where', 'MLAShortcodes::mla_shortcode_query_posts_where_filter', 0x7FFFFFFF, 1 );

		if ( self::$mla_debug ) {
			global $wp_filter;
			self::$mla_debug_messages .= '<p><strong>mla_debug $wp_filter[posts_where]</strong> = ' . var_export( $wp_filter['posts_where'], true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug $wp_filter[posts_orderby]</strong> = ' . var_export( $wp_filter['posts_orderby'], true ) . '</p>';
		}
		
		$get_posts = new WP_Query;
		$attachments = $get_posts->query($query_arguments);
		
		if ( $return_found_rows ) {
			$attachments['found_rows'] = $get_posts->found_posts;
		}
			
		remove_filter( 'posts_where', 'MLAShortcodes::mla_shortcode_query_posts_where_filter', 0x7FFFFFFF, 1 );
		remove_filter( 'posts_orderby', 'MLAShortcodes::mla_shortcode_query_posts_orderby_filter', 0x7FFFFFFF, 1 );
		
		if ( self::$mla_debug ) {
			remove_filter( 'posts_clauses', 'MLAShortcodes::mla_shortcode_query_posts_clauses_filter', 0x7FFFFFFF, 1 );
			remove_filter( 'posts_clauses_request', 'MLAShortcodes::mla_shortcode_query_posts_clauses_request_filter', 0x7FFFFFFF, 1 );

			self::$mla_debug_messages .= '<p><strong>mla_debug query</strong> = ' . var_export( $query_arguments, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug request</strong> = ' . var_export( $get_posts->request, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug query_vars</strong> = ' . var_export( $get_posts->query_vars, true ) . '</p>';
			self::$mla_debug_messages .= '<p><strong>mla_debug post_count</strong> = ' . var_export( $get_posts->post_count, true ) . '</p>';
		}
		
		return $attachments;
	}

	/**
	 * Filters the WHERE clause for shortcode queries
	 * 
	 * Captures debug information. Adds whitespace to the post_type = 'attachment'
	 * phrase to circumvent subsequent Role Scoper modification of the clause.
	 * Handles post_parent "any" and "none" cases.
	 * Defined as public because it's a filter.
	 *
	 * @since 0.70
	 *
	 * @param	string	query clause before modification
	 *
	 * @return	string	query clause after modification
	 */
	public static function mla_shortcode_query_posts_where_filter( $where_clause ) {
		global $table_prefix;

		if ( self::$mla_debug ) {
			$old_clause = $where_clause;
			self::$mla_debug_messages .= '<p><strong>mla_debug WHERE filter</strong> = ' . var_export( $where_clause, true ) . '</p>';
		}
		
		if ( strpos( $where_clause, "post_type = 'attachment'" ) ) {
			$where_clause = str_replace( "post_type = 'attachment'", "post_type  =  'attachment'", $where_clause );
		}

		if ( isset( self::$query_parameters['post_parent'] ) ) {
			switch ( self::$query_parameters['post_parent'] ) {
			case 'any':
				$where_clause .= " AND {$table_prefix}posts.post_parent > 0";
				break;
			case 'none':
				$where_clause .= " AND {$table_prefix}posts.post_parent < 1";
				break;
			}
		}

		if ( self::$mla_debug && ( $old_clause != $where_clause ) ) 
			self::$mla_debug_messages .= '<p><strong>mla_debug modified WHERE filter</strong> = ' . var_export( $where_clause, true ) . '</p>';

		return $where_clause;
	}

	/**
	 * Filters the ORDERBY clause for shortcode queries
	 * 
	 * This is an enhanced version of the code found in wp-includes/query.php, function get_posts.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.20
	 *
	 * @param	string	query clause before modification
	 *
	 * @return	string	query clause after modification
	 */
	public static function mla_shortcode_query_posts_orderby_filter( $orderby_clause ) {
		global $wpdb;

		if ( self::$mla_debug ) {
			self::$mla_debug_messages .= '<p><strong>mla_debug ORDER BY filter, incoming</strong> = ' . var_export( $orderby_clause, true ) . '<br>Replacement ORDER BY clause = ' . var_export( self::$query_parameters['orderby'], true ) . '</p>';
		}

		if ( isset( self::$query_parameters['orderby'] ) )
			return self::$query_parameters['orderby'];
		else
			return $orderby_clause;
	}

	/**
	 * Filters all clauses for shortcode queries, pre caching plugins
	 * 
	 * This is for debug purposes only.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.30
	 *
	 * @param	array	query clauses before modification
	 *
	 * @return	array	query clauses after modification (none)
	 */
	public static function mla_shortcode_query_posts_clauses_filter( $pieces ) {
		self::$mla_debug_messages .= '<p><strong>mla_debug posts_clauses filter</strong> = ' . var_export( $pieces, true ) . '</p>';

		return $pieces;
	}

	/**
	 * Filters all clauses for shortcode queries, post caching plugins
	 * 
	 * This is for debug purposes only.
	 * Defined as public because it's a filter.
	 *
	 * @since 1.30
	 *
	 * @param	array	query clauses before modification
	 *
	 * @return	array	query clauses after modification (none)
	 */
	public static function mla_shortcode_query_posts_clauses_request_filter( $pieces ) {
		self::$mla_debug_messages .= '<p><strong>mla_debug posts_clauses_request filter</strong> = ' . var_export( $pieces, true ) . '</p>';

		return $pieces;
	}
} // Class MLAShortcodes
?>