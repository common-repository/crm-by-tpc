<?php
if( !class_exists( 'WP_Users_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php' );
}

/**
 * TPC_CRM_Admin_WPUserListTable
 *
 * Extends the WP_Users_List_Table class so we can add our own custom filters.
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @version  1.0
 */
class TPC_CRM_Admin_WPUserListTable extends WP_Users_List_Table {
	/**
	 * List of extra info
	 * @var array
	 */
	private $extras = array( );

	/**
	 * Prepare the users list for display.
	 *
	 * @source WP_User_List_Table
	 * @since 3.1.0
	 * @access public
	 */
	function prepare_items( ) {
		global $role, $usersearch, $wpdb;

		// a nifty hack to fix the referrer
		$_SERVER['REQUEST_URI'] = admin_url( 'users.php' );

		$request        = new TPC_Helper_Array( $_REQUEST );
		$usersearch     = $request->get( "sSearch", $request->get( "s" ) );
		$role           = $request->get( "role" );
		$per_page       = ( $this->is_site_users ) ? 'site_users_network_per_page' : 'users_per_page';
		$users_per_page = $this->get_items_per_page( $per_page );
		$paged          = $this->get_pagenum();
		$number         = $request->get( "iDisplayLength", $users_per_page );
		$offset         = $request->get( "iDisplayStart", 0 );
		$totalRecords   = $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s`", $wpdb->users ) );

		// We only allow one sortable column
		list( $columns, $hidden, $sortable ) = $this->get_column_info( );
		$columnsArr                          = array_values( $columns );
		$sortColumnIndex                     = $request->get( "iSortCol_0", 1 );
		$sortColumnOrder                     = $request->get( "sSortDir_0", "asc" );
		$sortColumnName                      = $columnsArr[ $sortColumnIndex ];
		$sortColumnID                        = $columns[ $sortColumnIndex ];
		$isRefreshed                         = $request->get( "bRefreshed" );
		$this->extras[ "refreshed" ]         = $isRefreshed;
		$this->extras[ "columns" ]           = $columnsArr;
		$this->extras[ "sort_index" ]        = $sortColumnIndex;
		$this->extras[ "sort_id" ]           = $sortColumnID;
		$this->extras[ "sort_name" ]         = $sortColumnName;
		$this->extras[ "sort_order"]         = $sortColumnOrder;

		$_REQUEST[ "orderby" ]               = $sortColumnName;
		$_REQUEST[ "order" ]                 = $sortColumnOrder;

		switch( $sortColumnID ) {
			case "username":
				$orderby = "login";
				break;
			case "posts":
				$orderby = "post_count";
				break;
			default:
				$orderby = strtolower( $sortColumnID );
		}
		
		$args = array(
			'number'  => $number,
			'offset'  => $offset,
			'role'    => $role,
			// 'search'  => $usersearch,
			'orderby' => $orderby,
			'order'   => $_REQUEST[ "order" ],
			'fields'  => 'all_with_meta'
		);

		if ( '' !== $args['search'] )
			$args['search'] = '*' . $args['search'] . '*';

		if ( $this->is_site_users )
			$args['blog_id'] = $this->site_id;

		if ( isset( $_REQUEST['orderby'] ) )
			$args['orderby'] = $_REQUEST['orderby'];

		if ( isset( $_REQUEST['order'] ) )
			$args['order'] = $_REQUEST['order'];


		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( apply_filters( 'tpc_crm_user_query_args', $args ) );
		$this->items    = $wp_user_search->get_results();

		$this->extras[ "total_records" ] = $totalRecords;
		$this->extras[ "total_items" ]   = $wp_user_search->get_total( );
		$this->extras[ "number"]         = $number;
		$this->extras[ "offset" ]        = $offset;

		$this->set_pagination_args( array(
			'total_items' => $this->extras[ "total_items" ],
			'per_page'    => $users_per_page,
		) );
	}
	
	
	/**
	 * Extended function of WP_Users_List_Table
	 */
	function get_single_row( $userid, $user_object, &$colUmns, $i ) {
		global $wp_roles;
		// Query the post counts for this page
		if ( ! $this->is_site_users )
			$post_counts = count_many_users_posts( array_keys( array( $userid => $user_object ) ) );

		$editable_roles = array_keys( get_editable_roles() );

		$style = ''; 
		
			if ( count( $user_object->roles ) <= 1 ) {
				$role = reset( $user_object->roles );
			} elseif ( $roles = array_intersect( array_values( $user_object->roles ), $editable_roles ) ) {
				$role = reset( $roles );
			} else {
				$role = reset( $user_object->roles );
			}

			if ( is_multisite() && empty( $user_object->allcaps ) )
				return false;

			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			
			if ( !( is_object( $user_object ) && is_a( $user_object, 'WP_User' ) ) )
				$user_object = get_userdata( (int) $user_object );
			$user_object->filter = 'display';
			$email = $user_object->user_email;
	
			if ( $this->is_site_users )
				$url = "site-users.php?id={$this->site_id}&amp;";
			else
				$url = 'users.php?';
	
			$checkbox = '';
			// Check if the user for this row is editable
			if ( current_user_can( 'list_users' ) ) {
				// Set up the user editing link
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );
	
				// Set up the hover actions for this user
				$actions = array();
	
				if ( current_user_can( 'edit_user',  $user_object->ID ) ) {
					$edit = "<strong><a href=\"$edit_link\">$user_object->user_login</a></strong><br />";
					$actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
				} else {
					$edit = "<strong>$user_object->user_login</strong><br />";
				}
	
				if ( !is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'delete_user', $user_object->ID ) )
					$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . "</a>";
				if ( is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'remove_user', $user_object->ID ) )
					$actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url( $url."action=remove&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . "</a>";
	
				/**
				 * Filter the action links displayed under each user in the Users list table.
				 *
				 * @since 2.8.0
				 *
				 * @param array   $actions     An array of action links to be displayed.
				 *                             Default 'Edit', 'Delete' for single site, and
				 *                             'Edit', 'Remove' for Multisite.
				 * @param WP_User $user_object WP_User object for the currently-listed user.
				 */
				$actions = apply_filters( 'user_row_actions', $actions, $user_object );
				$editAction = $this->row_actions( $actions );
	
				// Set up the checkbox ( because the user is editable, otherwise it's empty )
				$checkbox = '<label class="screen-reader-text" for="cb-select-' . $user_object->ID . '">' . sprintf( __( 'Select %s' ), $user_object->user_login ) . '</label>'
							. "<input type='checkbox' name='users[]' id='user_{$user_object->ID}' class='$role' value='{$user_object->ID}' />";
	
			} else {
				$edit = '<strong>' . $user_object->user_login . '</strong>';
			}
			$role_name = isset( $wp_roles->role_names[$role] ) ? translate_user_role( $wp_roles->role_names[$role] ) : __( 'None' );
			$avatar = get_avatar( $user_object->ID, 32 );
				
			list( $columns, $hidden ) = $this->get_column_info();
			$j = 0;
			foreach ( $columns as $column_name => $column_display_name ) {
				$class = "class=\"$column_name column-$column_name\"";
	
				$style = '';
				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';
	
				$attributes = "$class$style";
				$count = '';
				
				switch ( $column_name ) {
					case 'cb':
						$colUmns[$i][$j] = $checkbox;
						break;
					case 'username':
						$colUmns[$i][$j] = $avatar.' '.$edit.' '.$editAction;
						break;
					case 'name':
						$colUmns[$i][$j] = $user_object->first_name.' '.$user_object->last_name;
						break;
					case 'email':
						$colUmns[$i][$j] = "<a href='mailto:$email' title='" . esc_attr( sprintf( __( 'E-mail: %s' ), $email ) ) . "'>$email</a>";
						break;
					case 'role':
						$colUmns[$i][$j] = $role_name;
						break;
					case 'posts':
						$attributes = 'class="posts column-posts num"' . $style;
						$numposts = intval($post_counts[$userid]);
						if ( $numposts > 0 ) {
							$count .= "<a href='edit.php?author=$user_object->ID' title='" . esc_attr__( 'View posts by this author' ) . "' class='edit'>";
							$count .= $numposts;
							$count .= '</a>';
						} else {
							$count .= 0;
						}
						$colUmns[$i][$j] = $count;
						break;
					default:
						$colUmns[$i][$j] = apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
				}
				$j++;
			}
			
			return $colUmns[$i];
	}

    /*
     * Alternative implmentation to optimize
     */

    function get_single_row_compressed( $userid, $user_object, &$colUmns, $i, $post_counts, $editable_roles ) {
		global $wp_roles;


		$style = '';

			if ( count( $user_object->roles ) <= 1 ) {
				$role = reset( $user_object->roles );
			} elseif ( $roles = array_intersect( array_values( $user_object->roles ), $editable_roles ) ) {
				$role = reset( $roles );
			} else {
				$role = reset( $user_object->roles );
			}


			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';

//			if ( !( is_object( $user_object ) && is_a( $user_object, 'WP_User' ) ) )
//				$user_object = get_userdata( (int) $user_object );
			$user_object->filter = 'display';
			$email = $user_object->user_email;

			if ( $this->is_site_users )
				$url = "site-users.php?id={$this->site_id}&amp;";
			else
				$url = 'users.php?';

			$checkbox = '';
			// Check if the user for this row is editable
			if ( current_user_can( 'list_users' ) ) {
				// Set up the user editing link
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );

				// Set up the hover actions for this user
				$actions = array();

				if ( current_user_can( 'edit_user',  $user_object->ID ) ) {
					$edit = "<strong><a href=\"$edit_link\">$user_object->user_login</a></strong><br />";
					$actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
				} else {
					$edit = "<strong>$user_object->user_login</strong><br />";
				}

				if ( !is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'delete_user', $user_object->ID ) )
					$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . "</a>";
				if ( is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'remove_user', $user_object->ID ) )
					$actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url( $url."action=remove&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . "</a>";

				/**
				 * Filter the action links displayed under each user in the Users list table.
				 *
				 * @since 2.8.0
				 *
				 * @param array   $actions     An array of action links to be displayed.
				 *                             Default 'Edit', 'Delete' for single site, and
				 *                             'Edit', 'Remove' for Multisite.
				 * @param WP_User $user_object WP_User object for the currently-listed user.
				 */
				$actions = apply_filters( 'user_row_actions', $actions, $user_object );
				$editAction = $this->row_actions( $actions );

				// Set up the checkbox ( because the user is editable, otherwise it's empty )
				$checkbox = '<label class="screen-reader-text" for="cb-select-' . $user_object->ID . '">' . sprintf( __( 'Select %s' ), $user_object->user_login ) . '</label>'
							. "<input type='checkbox' name='users[]' id='user_{$user_object->ID}' class='$role' value='{$user_object->ID}' />";

			} else {
				$edit = '<strong>' . $user_object->user_login . '</strong>';
			}
			$role_name = isset( $wp_roles->role_names[$role] ) ? translate_user_role( $wp_roles->role_names[$role] ) : __( 'None' );
			$avatar = get_avatar( $user_object->ID, 32 );

			list( $columns, $hidden ) = $this->get_column_info();
			$j = 0;
			foreach ( $columns as $column_name => $column_display_name ) {
				$class = "class=\"$column_name column-$column_name\"";

				$style = '';
				if ( in_array( $column_name, $hidden ) )
					$style = ' style="display:none;"';

				$attributes = "$class$style";
				$count = '';

				switch ( $column_name ) {
					case 'cb':
						$colUmns[$i][$j] = $checkbox;
						break;
					case 'username':
						$colUmns[$i][$j] = $avatar.' '.$edit.' '.$editAction;
						break;
					case 'name':
						$colUmns[$i][$j] = $user_object->first_name.' '.$user_object->last_name;
						break;
					case 'email':
						$colUmns[$i][$j] = "<a href='mailto:$email' title='" . esc_attr( sprintf( __( 'E-mail: %s' ), $email ) ) . "'>$email</a>";
						break;
					case 'role':
						$colUmns[$i][$j] = $role_name;
						break;
					case 'posts':
						$attributes = 'class="posts column-posts num"' . $style;
						$numposts = intval($post_counts[$userid]);
						if ( $numposts > 0 ) {
							$count .= "<a href='edit.php?author=$user_object->ID' title='" . esc_attr__( 'View posts by this author' ) . "' class='edit'>";
							$count .= $numposts;
							$count .= '</a>';
						} else {
							$count .= 0;
						}
						$colUmns[$i][$j] = $count;
						break;
					default:
						$colUmns[$i][$j] = apply_filters( 'manage_users_custom_column', '', $column_name, $user_object->ID );
				}
				$j++;
			}

			return $colUmns[$i];
	}
	
	

	/**
	 * Returns the hidden columns
	 * @return array
	 */
	public function getHiddenColumns( ) {
		list( $columns, $hidden ) = $this->get_column_info();
		$keys = array_keys( $columns );
		$hide = array( );
		foreach( $hidden as $h ) {
			$pos          = (string) array_search( $h, $keys );
			$hide[ $pos ] = $h;
		}

		return $hide;
	}

	public function getExtraInfo( ) {
		return $this->extras;
	}

	/**
	 * Return the rows and columns in an array format
	 * @return array 
	 */
	public function getRows( ) {
		ob_start( );
		$this->display_rows( );
		$rows    = ob_get_clean( );
		$columns = array( );
		$hidden  = $this->getHiddenColumns( );
		$hasNoLink = in_array( 'username', $hidden );
		
		preg_match_all( '/\<tr[^\>]*>(.*)\<\/tr\>/', $rows, $matches );
		foreach( $matches[1] as $i => $row ) {
			$row    = preg_replace( '/(\<(th|td)[^\>]*\>)/', '##newline##', $row );
			$row    = preg_replace( '/(\<\/(th|td)>)/', '', $row );
			$column = explode( '##newline##', $row );

			$cb     = array_shift( $column );

			if( $hasNoLink ) {
				if( preg_match( "/value='(\d+)\'/", $column[ 0 ], $value ) ) {
					$column[ 2 ] .= $this->getEditHtml( intval( $value[ 1 ] ) );
				}
			}
			$columns[ $i ] = $column;
		}

		return $columns;
	}

	/**
	 * Create the edit html
	 * @param  Integer|Object $user_object 
	 * @return String         
	 */
	public function getEditHtml( $user_object ) {
		if ( !( is_object( $user_object ) && is_a( $user_object, 'WP_User' ) ) )
			$user_object = get_userdata( (int) $user_object );
		$user_object->filter = 'display';
		$email = $user_object->user_email;

		if ( $this->is_site_users )
			$url = "site-users.php?id={$this->site_id}&amp;";
		else
			$url = 'users.php?';

		// Check if the user for this row is editable
		$edit    = '<div class="row-actions"><p>';
		$actions = array( );
		if ( current_user_can( 'list_users' ) ) {
			// Set up the user editing link
			$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_object->ID ) ) );

			if ( current_user_can( 'edit_user',  $user_object->ID ) ) {
				$actions[] .= '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
			}

			if ( !is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'delete_user', $user_object->ID ) )
				$actions[] .= "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . "</a>";
			if ( is_multisite() && get_current_user_id() != $user_object->ID && current_user_can( 'remove_user', $user_object->ID ) )
				$actions[] = "<a class='submitdelete' href='" . wp_nonce_url( $url."action=remove&amp;user=$user_object->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . "</a>";

			$edit .= implode( " | ", $actions );
		}
		$edit .= '</p></div>';
		return $edit;
	}
}