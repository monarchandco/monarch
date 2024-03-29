<?php
/**
 * Media Library Assistant extended List Table class
 *
 * @package Media Library Assistant
 * @since 1.40
 */

/* 
 * The WP_List_Table class isn't automatically available to plugins
 */
if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class MLA (Media Library Assistant) View List Table implements the "Views" admin settings tab
 *
 * Extends the core WP_List_Table class.
 *
 * @package Media Library Assistant
 * @since 1.40
 */
class MLA_View_List_Table extends WP_List_Table {
	/*
	 * These arrays define the table columns.
	 */
	
	/**
	 * Table column definitions
	 *
	 * This array defines table columns and titles where the key is the column slug (and class)
	 * and the value is the column's title text.
	 * 
	 * @since 1.40
	 *
	 * @var	array
	 */
	private static $default_columns = array(
		'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
		'name' => 'Slug',
		'specification'  => 'Specification',
		'post_mime_type' => 'Post Mime',
		'table_view' => 'Table View',
		'singular'  => 'Singular Name',
		'plural'  => 'Plural Name',
		'menu_order' => 'Order',
		'description' => 'Description'
	);
	
	/**
	 * Default values for hidden columns
	 *
	 * This array is used when the user-level option is not set, i.e.,
	 * the user has not altered the selection of hidden columns.
	 *
	 * The value on the right-hand side must match the column slug, e.g.,
	 * array(0 => 'ID_parent, 1 => 'title_name').
	 * 
	 * @since 1.40
	 *
	 * @var	array
	 */
	private static $default_hidden_columns	= array(
		// 'name',
		// 'specification',
		// 'post_mime_type',
		// 'table_view',
		'singular',
		// 'plural',
		'menu_order',
		'description'
	);
	
	/**
	 * Sortable column definitions
	 *
	 * This array defines the table columns that can be sorted. The array key
	 * is the column slug that needs to be sortable, and the value is database column
	 * to sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * The array value also contains a boolean which is 'true' if the data is currently
	 * sorted by that column. This is computed each time the table is displayed.
	 *
	 * @since 1.40
	 *
	 * @var	array
	 */
	private static $default_sortable_columns = array(
		'name' => array('slug',false),
		'specification' => array('specification',false),
		'post_mime_type' => array('post_mime_type',false),
		'table_view' => array('table_view',false),
		'singular' => array('singular',false),
		'plural' => array('plural',false),
		'menu_order' => array('menu_order',false),
		'description' => array('description',false)
        );

	/**
	 * Access the default list of hidden columns
	 *
	 * @since 1.40
	 *
	 * @return	array	default list of hidden columns
	 */
	private static function _default_hidden_columns( ) {
		return self::$default_hidden_columns;
	}
	
	/**
	 * Return the names and display values of the sortable columns
	 *
	 * @since 1.40
	 *
	 * @return	array	name => array( orderby value, heading ) for sortable columns
	 */
	public static function mla_get_sortable_columns( )
	{
		$results = array() ;
			
		foreach ( self::$default_sortable_columns as $key => $value ) {
			$value[1] = self::$default_columns[ $key ];
			$results[ $key ] = $value;
		}
		
		return $results;
	}
	
	/**
	 * Handler for filter 'get_user_option_managesettings_page_mla-settings-menu-viewcolumnshidden'
	 *
	 * Required because the screen.php get_hidden_columns function only uses
	 * the get_user_option result. Set when the file is loaded because the object
	 * is not created in time for the call from screen.php.
	 *
	 * @since 1.40
	 *
	 * @param	mixed	false or array with current list of hidden columns, if any
	 * @param	string	'managesettings_page_mla-settings-menucolumnshidden'
	 * @param	object	WP_User object, if logged in
	 *
	 * @return	array	updated list of hidden columns
	 */
	public static function mla_manage_hidden_columns_filter( $result, $option, $user_data ) {
		if ( $result )
			return $result;
		else
			return self::_default_hidden_columns();
	}
	
	/**
	 * Handler for filter 'manage_settings_page_mla-settings-menu_columns'
	 *
	 * This required filter dictates the table's columns and titles. Set when the
	 * file is loaded because the list_table object isn't created in time
	 * to affect the "screen options" setup.
	 *
	 * @since 1.40
	 *
	 * @return	array	list of table columns
	 */
	public static function mla_manage_columns_filter( )
	{
		return self::$default_columns;
	}
	
	/**
	 * Called in the admin_init action because the list_table object isn't
	 * created in time to affect the "screen options" setup.
	 *
	 * @since 1.40
	 *
	 * @return	void
	 */
	public static function mla_admin_init_action( )
	{
		if ( isset( $_REQUEST['mla_tab'] ) && $_REQUEST['mla_tab'] == 'view' ) {
			add_filter( 'get_user_option_managesettings_page_' . MLASettings::MLA_SETTINGS_SLUG . '-viewcolumnshidden', 'MLA_View_List_Table::mla_manage_hidden_columns_filter', 10, 3 );
			add_filter( 'manage_settings_page_' . MLASettings::MLA_SETTINGS_SLUG . '-view_columns', 'MLA_View_List_Table::mla_manage_columns_filter', 10, 0 );
		}
	}
	
	/**
	 * Initializes some properties from $_REQUEST variables, then
	 * calls the parent constructor to set some default configs.
	 *
	 * @since 1.40
	 *
	 * @return	void
	 */
	function __construct( ) {
		//Set parent defaults
		parent::__construct( array(
			'singular' => 'post_mime_type', //singular name of the listed records
			'plural' => 'post_mime_types', //plural name of the listed records
			'ajax' => true, //does this table support ajax?
			'screen' => 'settings_page_' . MLASettings::MLA_SETTINGS_SLUG . '-view'
		) );
		
		/*
		 * NOTE: There is one add_action call at the end of this source file.
		 * NOTE: There are two add_filter calls at the end of this source file.
		 */
	}
	
	/**
	 * Supply a column value if no column-specific function has been defined
	 *
	 * Called when the parent class can't find a method specifically built for a
	 * given column. All columns should have a specific method, so this function
	 * returns a troubleshooting message.
	 *
	 * @since 1.40
	 *
	 * @param	array	A singular item (one full row's worth of data)
	 * @param	array	The name/slug of the column to be processed
	 * @return	string	Text or HTML to be placed inside the column
	 */
	function column_default( $item, $column_name ) {
		//Show the whole array for troubleshooting purposes
		return 'column_default: ' . $column_name . ', ' . print_r( $item, true );
	}
	
	/**
	 * Displays checkboxes for using bulk actions. The 'cb' column
	 * is given special treatment when columns are processed.
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_cb( $item )
	{
		return sprintf( '<input type="checkbox" name="cb_mla_item_ID[]" value="%1$s" />',
		/*%1$s*/ $item->post_ID
		);
	}
	
	/**
	 * Add rollover actions to a table column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @param	string	Current column name
	 *
	 * @return	array	Names and URLs of row-level actions
	 */
	private function _build_rollover_actions( $item, $column ) {
		$actions = array();
		
		/*
		 * Compose view arguments
		 */
		
		$view_args = array(
			'page' => MLASettings::MLA_SETTINGS_SLUG . '-view',
			'mla_tab' => 'view',
			'mla_item_slug' => urlencode( $item->slug )
		);

		if ( isset( $_REQUEST['paged'] ) )
			$view_args['paged'] = $_REQUEST['paged'];
		
		if ( isset( $_REQUEST['order'] ) )
			$view_args['order'] = $_REQUEST['order'];
		
		if ( isset( $_REQUEST['orderby'] ) )
			$view_args['orderby'] = $_REQUEST['orderby'];
			
		/*
		 * Get the standard and custom types
		 */
		$mla_types = MLAOptions::mla_get_option( MLAOptions::MLA_POST_MIME_TYPES, true );
		if ( ! is_array( $mla_types ) )
			$mla_types = array ();
		
		$custom_types = MLAOptions::mla_get_option( MLAOptions::MLA_POST_MIME_TYPES, false, true );
		if ( ! is_array( $custom_types ) )
			$custom_types = array ();
		
		$actions['edit'] = '<a href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_EDIT_DISPLAY, MLA::MLA_ADMIN_NONCE ) ) . '" title="Edit this item">Edit</a>';

		$actions['inline hide-if-no-js'] = '<a class="editinline" href="#" title="Edit this item inline">Quick Edit</a>';
			
			if ( isset( $custom_types[ $item->slug ] ) ) {
				if ( isset( $mla_types[ $item->slug ] ) )
					$actions['delete'] = '<a class="delete-tag"' . ' href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_DELETE, MLA::MLA_ADMIN_NONCE ) ) . '" title="Revert to standard item">Revert to Standard</a>';
				else
					$actions['delete'] = '<a class="delete-tag"' . ' href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_DELETE, MLA::MLA_ADMIN_NONCE ) ) . '" title="Delete this item permanently">Delete Permanently</a>';
			} // custom type

		return $actions;
	}
	
	/**
	 * Add hidden fields with the data for use in the inline editor
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 *
	 * @return	string	HTML <div> with row data
	 */
	private function _build_inline_data( $item ) {
		$inline_data = "\r\n" . '<div class="hidden" id="inline_' . $item->post_ID . "\">\r\n";
		$inline_data .= '	<div class="original_slug">' . esc_attr( $item->slug ) . "</div>\r\n";
		$inline_data .= '	<div class="slug">' . esc_attr( $item->slug ) . "</div>\r\n";
		$inline_data .= '	<div class="singular">' . esc_attr( $item->singular ) . "</div>\r\n";
		$inline_data .= '	<div class="plural">' . esc_attr( $item->plural ) . "</div>\r\n";
		$inline_data .= '	<div class="specification">' . esc_attr( $item->specification ) . "</div>\r\n";
		$inline_data .= '	<div class="post_mime_type">' . esc_attr( $item->post_mime_type ) . "</div>\r\n";
		$inline_data .= '	<div class="table_view">' . esc_attr( $item->table_view ) . "</div>\r\n";
		$inline_data .= '	<div class="menu_order">' . esc_attr( $item->menu_order ) . "</div>\r\n";
		$inline_data .= '	<div class="description">' . esc_attr( $item->description ) . "</div>\r\n";
		$inline_data .= "</div>\r\n";
		return $inline_data;
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_name( $item ) {
		$row_actions = self::_build_rollover_actions( $item, 'name' );
		$slug = esc_attr( $item->slug );
		return sprintf( '%1$s<br>%2$s%3$s', /*%1$s*/ $slug, /*%2$s*/ $this->row_actions( $row_actions ), /*%3$s*/ $this->_build_inline_data( $item ) );
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_specification( $item ) {
		return esc_attr( $item->specification );
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_post_mime_type( $item ) {
		if ( $item->post_mime_type )
			return 'yes';
		else
			return 'no';
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_table_view( $item ) {
		if ( $item->table_view )
			return 'yes';
		else
			return 'no';
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_singular( $item ) {
		return esc_attr( $item->singular );
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_plural( $item ) {
		return esc_attr( $item->plural );
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_menu_order( $item ) {
		return (string) $item->menu_order;
	}
	
	/**
	 * Supply the content for a custom column
	 *
	 * @since 1.40
	 * 
	 * @param	object	An MLA post_mime_type object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_description( $item ) {
		return esc_attr( $item->description );
	}
	
	/**
	 * This method dictates the table's columns and titles
	 *
	 * @since 1.40
	 * 
	 * @return	array	Column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns( ) {
		return $columns = MLA_View_List_Table::mla_manage_columns_filter();
	}
	
	/**
	 * Returns the list of currently hidden columns from a user option or
	 * from default values if the option is not set
	 *
	 * @since 1.40
	 * 
	 * @return	array	Column information,e.g., array(0 => 'ID_parent, 1 => 'title_name')
	 */
	function get_hidden_columns( )
	{
		$columns = get_user_option( 'managesettings_page_' . MLASettings::MLA_SETTINGS_SLUG . '-viewcolumnshidden' );

		if ( is_array( $columns ) ) {
			return $columns;
		}
		else
			return self::_default_hidden_columns();
	}
	
	/**
	 * Returns an array where the  key is the column that needs to be sortable
	 * and the value is db column to sort by. Also notes the current sort column,
	 * if set.
	 *
	 * @since 1.40
	 * 
	 * @return	array	Sortable column information,e.g.,
	 * 					'slugs'=>array('data_values',boolean)
	 */
	function get_sortable_columns( ) {
		$columns = self::$default_sortable_columns;
		
		if ( isset( $_REQUEST['orderby'] ) ) {
			$needle = array(
				 $_REQUEST['orderby'],
				false 
			);
			$key = array_search( $needle, $columns );
			if ( $key )
				$columns[ $key ][ 1 ] = true;
		} else {
			$columns['menu_order'][ 1 ] = true;
		}

		return $columns;
	}
	
	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 1.40
	 * 
	 * @return	array	Contains all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function get_bulk_actions( )
	{
		$actions = array();

		$actions['edit'] = 'Edit';
		$actions['delete'] = 'Delete Permanently';
		
		return $actions;
	}
	
	/**
	 * Prepares the list of items for displaying
	 *
	 * This is where you prepare your data for display. This method will usually
	 * be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args().
	 *
	 * @since 1.40
	 *
	 * @return	void
	 */
	function prepare_items( ) {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns() 
		);
		
		/*
		 * REQUIRED for pagination.
		 */
		$total_items = MLAMime::mla_count_view_items( $_REQUEST );
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );
		if ( is_string( $option ) )
			$per_page = get_user_meta( $user, $option, true );
		else
			$per_page = 10;
			
		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $screen->get_option( 'per_page', 'default' );
		
		/*
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page, 
			'total_pages' => ceil( $total_items / $per_page )
		) );

		$current_page = $this->get_pagenum();

		/*
		 * REQUIRED. Assign sorted and paginated data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = MLAMime::mla_query_view_items( $_REQUEST, ( ( $current_page - 1 ) * $per_page ), $per_page );
	}
	
	/**
	 * Generates (echoes) content for a single row of the table
	 *
	 * @since 1.40
	 *
	 * @param object the current item
	 *
	 * @return void Echoes the row HTML
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr id="view-' . $item->post_ID . '"' . $row_class . '>';
		echo parent::single_row_columns( $item );
		echo '</tr>';
	}
} // class MLA_View_List_Table

/*
 * Actions are added here, when the source file is loaded, because the MLA_View_List_Table
 * object is created too late to be useful.
 */
add_action( 'admin_init', 'MLA_View_List_Table::mla_admin_init_action' );
?>