<?php
/**
 * TPC_CRM_Admin_Users
 *
 * Adds hooks to the WP Users Admin Page
 *
 * @package  : Customer Relationship Manager (Free)
 */
class TPC_CRM_Admin_Users implements TPC_Interface_Runnable {
	/**
	 * Container for the TPC_CRM_Admin_UsersListTable
	 * @var object
	 */
	private $usersTable;

	/**
	 * List of visible columns
	 * @var array
	 */
	private $visibleColumns = array( );

	/**
	 * List of user custom SQL
	 * @var Array
	 */
	private $customSQL;

	/**
	 * List of columns' data type
	 * @var array
	 */
	private $columnsDataType = array( );

	/**
	 * Let's initialize this object
	 */
	public function __construct( ) {
		$usersTable            = new TPC_CRM_Admin_UsersListTable( );
		$this->usersTable      = apply_filters( 'tpc_crm_users_table_columns', $usersTable );
		$this->visibleColumns  = $this->usersTable->getVisibleColumns( );
		$this->columnsDataType = $this->usersTable->getColumnsDataType( $this->visibleColumns );
	}

	/**
	 * Runs the hooks
	 */
	public function run( ) {
		// Run the Users List Table Hooks
		$this->usersTable->run();

		add_action( 'admin_init', array( $this, 'adminInit' ) );
		add_action( 'admin_menu', array( $this, 'adminMenu' ) );
		add_action( 'wp_ajax_draw_user_table', array( $this, 'drawUserTable' ) );
		add_action( 'wp_ajax_tpc_get_column_autocomplete', array( $this, 'getColumnAutoComplete' ) );
		add_action( 'wp_ajax_get_all_users', array( $this, 'getAllUsersInit' ) );

		add_filter( 'pre_user_query', array( $this, 'userQuery' ) );

		/**
		 * We don't need this since WP already has checkboxes for User Table's Columns
		 */
		// add_filter( 'screen_settings', array( $this, 'screenSettings' ), 10, 2 );
	}


	/**
	 * Ajax reply to get all users
	 */
	public function getAllUsers( ) {
		header( "Content-type: application/json" );
		$wp_list_table = new TPC_CRM_Admin_WPUserListTable( array(
				"screen" => "users"
			) );

		$users = get_users();

		$items = array(); $rows = array(); $i = 0;
		foreach($users as $user)
		{
			$userid = $user->ID;
			$post_counts = count_user_posts( $userid );
			$numposts = intval($post_counts );
			$meta_data = get_user_meta($user->ID);
			$html = $wp_list_table->get_single_row( $user->ID, $user, $rows, $i );
			$index_fields = array('login' => strtolower($user->user_login),
								  'email' => (!empty($user->user_email)) ? strtolower($user->user_email) : "",
								  'firstname' => (!empty($user->first_name)) ? strtolower($user->first_name) : "",
								  'lastname' => (!empty($user->last_name)) ? strtolower($user->last_name) : "",
								  'date_registered' => date("m-d-Y", strtotime($user->user_registered)),
				                  'website' =>  (!empty($user->user_url)) ? strtolower($user->user_url) : "",
								  'posts' => $numposts,
								  'html' => $html);
            foreach($meta_data as $userMetaKey => $metaValue) {
				$index_fields[$userMetaKey] = $metaValue[0];
			}
			if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
				$roles = "";
				for($x=0; $x<count($user->roles); $x++)
					$roles .= ($x === 0) ? strtolower($user->roles[$x]) : '_' . strtolower($user->roles[$x]);

				$index_fields['roles'] = $roles;
			}

			$data_index = "";
			foreach($index_fields as $key => $value)
				$data_index .= (empty($data_index)) ? $value : '_' . $value;


			$index_fields['index'] = $data_index;
			$items []= (object)$index_fields;
			$i++;
		}

		$output = array( 'items' => $items,
						 'rows' => $rows,
						 'total_items' => $i,
						 'hidden_columns' => $wp_list_table->getHiddenColumns( ),
						 'extras' => array('total_records' => $i,
										   'total_items' => $i,
										   'number' => 0,
										   'offset' => 0));

		echo json_encode( $output );
		exit( 0 );
	}

    /**
	 * List of all users in JSON
	 */
	public function getAllUsersInit( ) {
        header( "Content-type: application/json" );
		$wp_list_table = new TPC_CRM_Admin_WPUserListTable( array(
				"screen" => "users"
			) );

        $users = get_users();

        $items = array();
        $rows = array();
        $i = 0;

        $user_ids = array();
        foreach($users as $user) {
            $user_ids[] = $user->ID;
        }
        $post_counts = count_many_users_posts( $user_ids );
        $editable_roles = array_keys(get_editable_roles());

        foreach ($users as $user) {
            $userid = $user->ID;
            $numposts = intval($post_counts[$userid]);
			$meta_data = get_user_meta($user->ID);
            $html = $wp_list_table->get_single_row_compressed($user->ID, $user, $rows, $i, $numposts, $editable_roles);
            $index_fields = array('login' => strtolower($user->user_login),
                'email' => (!empty($user->user_email)) ? strtolower($user->user_email) : "",
                'firstname' => (!empty($user->first_name)) ? strtolower($user->first_name) : "",
                'lastname' => (!empty($user->last_name)) ? strtolower($user->last_name) : "",
                'date_registered' => date("m-d-Y", strtotime($user->user_registered)),
				'website' => (!empty($user->user_url) ? strtolower($user->user_url) : ""),
                'posts' => $numposts,
                'html' => $html);

			foreach($meta_data as $userMetaKey => $metaValue) {
				$index_fields[$userMetaKey] = $metaValue[0];
			}
            if (!empty($user->roles) && is_array($user->roles)) {
                $roles = "";
                for ($x = 0; $x < count($user->roles); $x++)
                    $roles .= ($x === 0) ? strtolower($user->roles[$x]) : '_' . strtolower($user->roles[$x]);

                $index_fields['roles'] = $roles;
            }

            $data_index = "";
            foreach ($index_fields as $key => $value)
                $data_index .= (empty($data_index)) ? $value : '_' . $value;


            $index_fields['index'] = $data_index;
            $items [] = (object)$index_fields;
            $i++;
        }

        $output = array('items' => $items,
            'rows' => $rows,
            'total_items' => $i,
            'hidden_columns' => array(),//$wp_list_table->getHiddenColumns( ),
            'extras' => array('total_records' => $i,
                'total_items' => $i,
                'number' => 0,
                'offset' => 0));



        echo json_encode($output);
        exit(0);
	}


	/**
	 * Actions to be done during initialization
	 */
	public function adminInit( ) {
		/**
		 * jQuery Select Chosen
		 */
		wp_register_style( 'jquery-select-chosen-css'  , TPC_CRM_URL . '/assets/css/jquery-select-chosen.css' );
		wp_register_script( 'jquery-select-chosen'	   , TPC_CRM_URL . '/assets/js/jquery-select-chosen.min.js' );

		/**
		 * Animations
		 */
		wp_register_style( 'animations-css'				, TPC_CRM_URL . '/assets/css/animations.css' );

		/**
		 * Bootstrap
		 */
		wp_register_style( 'bootstrap-datepicker-css'	, TPC_CRM_URL . '/assets/css/bootstrap-datepicker.css' );
		wp_register_script( 'jquery-bootstrap'			, TPC_CRM_URL . '/assets/js/bootstrap.3.1.min.js' );
		wp_register_script( 'bootstrap-datepicker'		, TPC_CRM_URL . '/assets/js/bootstrap-datepicker.min.js');

		/**
		 * Data Tables
		 */
		wp_register_script( 'jquery-datatable'			, TPC_CRM_URL . '/assets/js/jquery-datatable.min.js', array(), false, true );


		/**
		 * CRM styles/scripts
		 */
		wp_register_style( 'tpc-crm-admin-users-css'	, TPC_CRM_URL . '/assets/css/admin-users.css' );
		wp_register_script( 'tpc-crm-admin-users-js'	, TPC_CRM_URL . '/assets/js/admin-users.js', array( 'jquery', 'jquery-select-chosen', 'jquery-bootstrap', 'bootstrap-datepicker', 'jquery-datatable', 'jquery-ui-autocomplete', 'jquery-ui-position' ), false, true );

	}

	/**
	 * Register admin menu
	 */
	public function adminMenu( ) {
		add_action( 'admin_print_styles-users.php', array( $this, 'adminStyles' ) );
		add_action( 'admin_print_scripts-users.php', array( $this, 'adminScripts' ) );
		add_action( 'admin_head-users.php', array( $this, 'head' ) );
		add_action( 'admin_footer-users.php', array( $this, 'foot' ) );
	}

	/**
	 * Add styles
	 */
	public function adminStyles( ) {
		wp_enqueue_style( 'animations-css' 			 );
		wp_enqueue_style( 'jquery-select-chosen-css' );
		wp_enqueue_style( 'tpc-crm-admin-users-css'  );
		wp_enqueue_style( 'bootstrap-datepicker-css' );
	}

	/**
	 * Add scripts
	 */
	public function adminScripts( ) {
		wp_enqueue_script( 'jquery-select-chosen' );
		wp_enqueue_script( 'jquery-bootstrap' );
		wp_enqueue_script( 'bootstrap-datepicker' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'jquery-ui-position' );
		wp_enqueue_script( 'jquery-datatable' );
		wp_enqueue_script( 'tpc-crm-admin-users-js' );
		wp_localize_script( 'tpc-crm-admin-users-js', 'TPC_CRM', array(
				"ajax"   => admin_url( "admin-ajax.php" ),
				"url"    => TPC_CRM_URL,
				"slug"   => TPC_CRM_SLUG,
				'hidden' => get_user_meta( get_current_user_id(), 'manageuserscolumnshidden', true )
			) );
	}

	/**
	 * Add header data
	 */
	public function head( ) {
	}

	/**
	 * Add the footer script
	 */
	public function foot( ) {
		?>
			<script type="text/javascript">
				( function( w, $ ) {
					usersCustomListTable.init( {
							columns  : <?php echo json_encode( $this->usersTable->getColumnsDataTypes( ) ); ?>,
							filters  : <?php echo json_encode( $this->getFilters( ) ); ?>,
							requests : <?php echo json_encode( $_GET ); ?>
						} );
				} )( window, jQuery );
			</script>
		<?php
	}

	/**
	 * Get Filters and Presets
	 * @return array
	 */
	public function getFilters( ) {
		$filters                         = array( );
		$filters[ 'between_dates' ]            = __( "Between mm-dd-yyyy and mm-dd-yyyy"                    , TPC_CRM_SLUG );
		$filters[ 'before_date' ]              = __( "Before mm-dd-yyyy"				                    , TPC_CRM_SLUG );
		$filters[ 'after_date' ]               = __( "After mm-dd-yyyy"				                        , TPC_CRM_SLUG );
		$filters[ 'field_lesser_than' ]        = __( "Lesser than field"				                    , TPC_CRM_SLUG );
		$filters[ 'field_greater_than' ]       = __( "Greater than field"				                    , TPC_CRM_SLUG );
		$filters[ 'field_equal' ]              = __( "Equal to field"					                    , TPC_CRM_SLUG );
		$filters[ 'field_contains' ]           = __( "Field contains"					                    , TPC_CRM_SLUG );
		$filters[ 'field_not_equal' ]          = __( "Not equal to field"                                   , TPC_CRM_SLUG );
		$filters[ 'field_not_contains' ]       = __( "Field does not contain"		                        , TPC_CRM_SLUG );
		$filters[ 'not_before_date' ]          = __( "Not before mm-dd-yyyy"                                , TPC_CRM_SLUG );
		$filters[ 'not_after_date' ]           = __( "Not after mm-dd-yyyy"                                 , TPC_CRM_SLUG );
		$filters[ 'not_between_dates' ]        = __( "Not between mm-dd-yyyy and mm-dd-yyyy"                , TPC_CRM_SLUG );
		$filters[ 'field_not_lesser_than' ]    = __( "Not lesser than field"                                , TPC_CRM_SLUG );
		$filters[ 'field_not_greater_than' ]   = __( "Not greater than field"                               , TPC_CRM_SLUG );

		return apply_filters( 'tpc_crm_usrs_table_filters', $filters );
	}

	/**
	 * Process our custom filters queries
	 * @param  object $userQuery
	 * @return object
	 */
	public function userQuery( &$userQuery ) {
		global $wpdb, $pagenow;

		if( $pagenow !== "admin-ajax.php" && $pagenow !== "users.php" ) return $userQuery;

		$customQuery = array (
				"select"  => sprintf( "DISTINCT `%s`.`ID`", $wpdb->users ),
				"joins"   => array(
						"default" => array(
							$wpdb->usermeta,
							sprintf( "`%s`.`ID` = `%s`.`user_id`", $wpdb->users, $wpdb->usermeta )
						)
					),
				"wheres"  => array( ),
				"orderby" => ""
			);

		$arrayHelper = new TPC_Helper_Array( $_GET );
		$args        = $arrayHelper->get( array (
				"sSearch", "between-dates", "before-date", "after-date",
				"lesser-than", "greater-than", "equals", "contains"
			) );

		// Global search Query
		if( trim( $args[ "sSearch" ] ) ) {
			$searchSQL  = "";
			$searchSQLs = array( );
			$searchStr  = '%' . $args[ "sSearch" ] . '%';
			$searchType = new TPC_Helper_DataType( $args[ "sSearch" ] );

			$searchSQLs[] = sprintf( "(`%s`.`user_login` LIKE '%s')", $wpdb->users, $searchStr );
			$searchSQLs[] = sprintf( "(`%s`.`user_email` LIKE '%s')", $wpdb->users, $searchStr );
			$searchSQL    = sprintf( "(`ID` IN (SELECT `user_id` FROM `%s` ", $wpdb->usermeta );
			$searchSQL   .= "WHERE `meta_key` = 'first_name' OR `meta_key` = 'last_name' GROUP BY `user_id` ";
			$searchSQL   .=  sprintf( "HAVING GROUP_CONCAT( `meta_value` SEPARATOR ' ') LIKE '%s'))", $searchStr );
			$searchSQLs[] = $searchSQL;

			/*
			foreach( $this->visibleColumns as $id => $title ) {
				switch( $id ) {
					case "username":
						if( $searchType->isString( ) ) {
							$searchSQL    = sprintf( "(`%s`.`user_login` LIKE '%s')", $wpdb->users, $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "email":
						if( $searchType->isString( ) ) {
							$searchSQL    = sprintf( "(`%s`.`user_email` LIKE '%s')", $wpdb->users, $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "website":
						if( $searchType->isString( ) ) {
							$searchSQL    = sprintf( "(`%s`.`user_url` LIKE '%s')", $wpdb->users, $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "date_registered":
						if( $searchType->isDate( ) ) {
							$searchSQL = sprintf( "(MONTH(`%s`.`user_registered`) = MONTH('%s'))", $wpdb->users, date( 'Y-m-d', strtotime( $args[ 'sSearch'] ) ) );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "name":
						if( $searchType->isString( ) ) {
							$searchSQL    = sprintf( "(`ID` IN (SELECT `user_id` FROM `%s` ", $wpdb->usermeta );
							$searchSQL   .= "WHERE `meta_key` = 'first_name' OR `meta_key` = 'last_name' GROUP BY `user_id` ";
							$searchSQL   .=  sprintf( "HAVING GROUP_CONCAT( `meta_value` SEPARATOR ' ') LIKE '%s'))", $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "role":
						if( $searchType->isString( ) ) {
							$searchSQL    = sprintf( "(`%s`.`meta_key` = 'wp_capabilities' AND `meta_value` LIKE '%s')", $wpdb->usermeta, $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
					case "posts":
						if( $searchType->isNumeric( ) ) {
							$searchSQL    = sprintf( "(SELECT COUNT(*) FROM `%s` WHERE post_author = `%s`.ID AND post_status='publish' AND post_type='post') = %d", $wpdb->posts, $wpdb->users, $args[ "sSearch" ] );
							$searchSQLs[] = $searchSQL;
						}
						break;
					default:
						if( $searchType->isDate( ) ) {
							$searchSQL    = sprintf( "(`%s`.`meta_key` = '%s' AND MONTH(`meta_value`) = MONTH('%s'))", $wpdb->usermeta, $id, date( 'Y-m-d', strtotime( $args[ 'sSearch' ] ) ) );
							$searchSQLs[] = $searchSQL;
						} elseif( $searchType->isNumeric( ) ) {
							$searchSQL    = sprintf( "(`%s`.`meta_key` = '%s' AND `meta_value` = %d)", $wpdb->usermeta, $id, $args[ "sSearch" ] );
							$searchSQLs[] = $searchSQL;
						} else {
							$searchSQL    = sprintf( "(`%s`.`meta_key` = '%s' AND `meta_value` LIKE '%s')", $wpdb->usermeta, $id, $searchStr );
							$searchSQLs[] = $searchSQL;
						}
						break;
				}
			}
			*/

			$globalSearchWhere = implode( " OR ", $searchSQLs );

			$customQuery[ 'wheres' ][ 'search' ] = apply_filters( "tpc_crm_search_where_sql", $globalSearchWhere );
		}

		// Between dates
		if( $args[ "between-dates" ] && isset( $args[ "between-dates" ][ "field" ] ) && isset( $args[ "between-dates" ][ "from" ] ) && isset( $args[ "between-dates" ][ "to" ] ) ) {
			$searchQuery = "";
			switch ( $args[ "between-dates" ][ "field" ] ) {
				case "date_registered":
					$searchQuery = sprintf( "`%s`.`user_registered` BETWEEN '%s' AND '%s'",
							$wpdb->users, date( 'Y-m-d H:i:s', strtotime( $args[ "between-dates" ][ "from" ] ) ),
							date( 'Y-m-d H:i:s', strtotime( $args[ "between-dates" ][ "to" ] ) ) );
					break;
					default:
						$searchQuery = sprintf( "`%s`.`meta_key` = '%s' AND STR_TO_DATE(`%s`.`meta_value`, '%s') BETWEEN DATE('%s') AND ('%s')",
								$wpdb->usermeta, $args[ "between-dates" ][ "field" ],
								$wpdb->usermeta, '%m/%d/%Y', date( 'Y-m-d H:i:s',
								strtotime( $args[ "between-dates" ][ "from" ] ) ),
								date( 'Y-m-d H:i:s', strtotime( $args[ "between-dates" ][ "to" ] ) ) );
						break;
			}
			$customQuery[ 'wheres' ][ ] = apply_filters( "tpc_crm_between_dates_sql", $searchQuery );
		}

		// Before date
		if( $args[ "before-date" ] && isset( $args[ "before-date" ][ "field" ] ) ) {
			$beforeDateQuery = "";

			switch( $args[ "before-date" ][ "field" ] ) {
				case "date_registered":
					$beforeDateQuery = sprintf( "`%s`.`user_registered` < '%s'", $wpdb->users, date( 'Y-m-d H:i:s', strtotime( $args[ "before-date" ][ 'value' ] ) ) );
					break;
				default:
					$beforeDateQuery = sprintf( "`%s`.`meta_key` = '%s' AND STR_TO_DATE(`%s`.`meta_value`, '%s') < DATE('%s')",
						$wpdb->usermeta, $args[ "before-date" ][ "field" ], $wpdb->usermeta,
						'%m/%d/%Y', date( 'Y-m-d H:i:s', strtotime( $args[ "before-date" ][ 'value' ] ) ) );
					break;
			}

			$customQuery[ 'wheres' ][ 'before-date' ] = apply_filters( "tpc_crm_before_date_sql", $beforeDateQuery );
		}

		// After date
		if( $args[ "after-date" ]  && isset( $args[ "after-date" ][ "field" ] ) ) {
			$afterDateQuery = "";

			switch( $args[ "after-date" ][ "field" ] ) {
				case "date_registered":
					$afterDateQuery = sprintf( "`%s`.`user_registered` > '%s'", $wpdb->users, date( 'Y-m-d H:i:s', strtotime( $args[ "after-date" ][ 'value' ] ) ) );
					break;
				default:
					$afterDateQuery = sprintf( "`%s`.`meta_key` = '%s' AND STR_TO_DATE(`%s`.`meta_value`, '%s') > DATE('%s')",
						$wpdb->usermeta, $args[ "after-date" ][ "field" ], $wpdb->usermeta,
						'%m/%d/%Y', date( 'Y-m-d H:i:s', strtotime( $args[ "after-date" ][ 'value' ] ) ) );
					break;
			}

			$customQuery[ 'wheres' ][ 'after-date' ] = apply_filters( "tpc_crm_after_date_sql", $afterDateQuery );
		}

		// Lesser than field
		if( $args[ "lesser-than" ] && isset( $args[ "lesser-than" ][ "field" ] ) && isset( $args[ "lesser-than" ][ "value" ] ) ) {
			$lesserQuery = array(
					"wheres" => array( ),
					"values" => array( )
				);

			foreach( $args[ "lesser-than" ][ "field" ] as $index => $value ) {
				$fieldID                            = $value;
				$fieldVal                           = $args[ "lesser-than" ][ "value" ][ $index ];
				$lesserQuery[ "values" ][ "field" ] = $fieldVal;
				$type                               = new TPC_Helper_DataType( $fieldVal );

				if( $type->isNumeric( ) || $type->isDate( ) ) {
					// Remove duplicate queries
					switch( $fieldID ) {
						case "date_registered":
							unset( $customQuery[ "wheres" ][ "before-date" ] );
							$lesserQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_registered` < '%s'", $wpdb->users, date( 'Y-m-d H:i:s', strtotime( $fieldVal ) ) );
							break;
						case "posts":
							$lesserQuery[ "wheres" ][ $fieldID ] = sprintf( "(SELECT COUNT(*) FROM `%s` WHERE post_author = `%s`.ID AND post_status='publish' AND post_type='post') < %d",
								$wpdb->posts, $wpdb->users, $fieldVal );
							break;
						default:
							if( $type->isNumeric( ) ) {
								$lesserQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND `%s`.`meta_value` > '%d'",
										$wpdb->usermeta, $fieldID, $wpdb->usermeta,  $fieldVal
									);
							} else {
								$lesserQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND STR_TO_DATE(`%s`.`meta_value`, '%s') > DATE('%s')",
										$wpdb->usermeta, $fieldID, $wpdb->usermeta, '%m/%d/%Y', date( 'Y-m-d H:i:s', strtotime( $fieldVal ) )
									);
							}
							break;
					}
				}

			}

			$lesserQuery  = apply_filters( "tpc_crm_lesser_than_sql", $lesserQuery );
			$lesserWheres = array_values( $lesserQuery[ "wheres"] );
			$customQuery[ "wheres" ][ "lesser-than" ] = "( " . implode( " AND ", $lesserWheres ) . " )";
		}

		// Greater than field
		if( $args[ "greater-than" ] && isset( $args[ "greater-than" ][ "field" ] ) && isset( $args[ "greater-than" ][ "value" ] ) ) {
			$greaterQuery = array(
					"wheres" => array( ),
					"values" => array( )
				);

			foreach( $args[ "greater-than" ][ "field" ] as $index => $value ) {
				$fieldID                             = $value;
				$fieldVal                            = $args[ "greater-than" ][ "value" ][ $index ];
				$greaterQuery[ "values" ][ "field" ] = $fieldVal;
				$type                                = new TPC_Helper_DataType( $fieldVal );

				if( $type->isNumeric( ) || $type->isDate( ) ) {
					// Remove duplicate queries
					switch( $fieldID ) {
						case "date_registered":
							unset( $customQuery[ "wheres" ][ "after-date" ] );
							$greaterQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_registered` > '%s'", $wpdb->users, date( 'Y-m-d H:i:s', strtotime( $fieldVal ) ) );
							break;
						case "posts":
							$greaterQuery[ "wheres" ][ $fieldID ] = sprintf( "(SELECT COUNT(*) FROM `%s` WHERE post_author = `%s`.ID AND post_status='publish' AND post_type='post') > %d",
								$wpdb->posts, $wpdb->users, $fieldVal );
							break;
						default:
							if( $type->isNumeric( ) ) {
								$greaterQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND `%s`.`meta_value` > '%d'",
										$wpdb->usermeta, $fieldID, $wpdb->usermeta,  $fieldVal
									);
							} else {
								$greaterQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND STR_TO_DATE(`%s`.`meta_value`, '%s') > DATE('%s')",
										$wpdb->usermeta, $fieldID, $wpdb->usermeta, '%m/%d/%Y', date( 'Y-m-d H:i:s', strtotime( $fieldVal ) )
									);
							}
							break;
					}
				}
			}

			$greaterQuery  = apply_filters( "tpc_crm_greater_than_sql", $greaterQuery );
			$greaterWheres = array_values( $greaterQuery[ "wheres"] );
			$customQuery[ "wheres" ][ "greater-than" ] = "( " . implode( " AND ", $greaterWheres ) . " )";
		}

		// Equals a field
		if( isset( $args[ "equals" ] ) && isset( $args[ "equals" ][ "field" ] ) ) {
			$equalsQuery = array(
					"wheres" => array( ),
					"values" => array( )
				);

			foreach( $args[ "equals" ][ "field" ] as $index => $value ) {
				$fieldID                            = $value;
				$fieldVal                           = $args[ "equals" ][ "value" ][ $index ];
				$equalsQuery[ "values" ][ "field" ] = $fieldVal;

				// Remove duplicate queries
				switch( $fieldID ) {
					case "name":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "(`%s`.`meta_key` = 'first_name' AND `%s`.`meta_value` LIKE '%s' OR `%s`.`meta_key` = 'last_name' AND `%s`.`meta_value` LIKE '%s')",
								$wpdb->usermeta,  $wpdb->usermeta,  '%' . $fieldVal . '%',
								$wpdb->usermeta,  $wpdb->usermeta,  '%' . $fieldVal . '%'
							);
						break;
					case "first_name":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'first_name' AND `%s`.`meta_value` = '%s'",
								$wpdb->usermeta,  $wpdb->usermeta,  $fieldVal
							);
						break;
					case "last_name":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'last_name' AND `%s`.`meta_value` = '%s'",
								$wpdb->usermeta,  $wpdb->usermeta,  $fieldVal
							);
						break;
					case "nickname":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'nickname' AND `%s`.`meta_value` = '%s'",
								$wpdb->usermeta, $fieldVal
							);
						break;
					case "username":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_login` = '%s'", $wpdb->users, $fieldVal );
						break;
					case "email":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_email` = '%s'", $wpdb->users, $fieldVal );
						break;
					case "role":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'wp_capabilities' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, $wpdb->usermeta, '%' . $fieldVal . '%'
							);
						break;
					case "website":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_url` = '%s'", $wpdb->users, $fieldVal );
						break;
					case "date_registered":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_registered` = '%s'", $wpdb->users, date( 'Y-m-d H:i:s', strtotime( $fieldVal ) ) );
						break;
					case "posts":
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "(SELECT COUNT(*) FROM `%s` WHERE post_author = `%s`.ID AND post_status='publish' AND post_type='post') = %d",
							$wpdb->posts, $wpdb->users, $fieldVal );
						break;
					default:
						$equalsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND `%s`.`meta_value` = '%s'",
								$wpdb->usermeta, $fieldID, $wpdb->usermeta,  $fieldVal
							);
						break;
				}
			}

			$equalsQuery  = apply_filters( "tpc_crm_equals_sql", $equalsQuery );
			$equalsWheres = array_values( $equalsQuery[ "wheres"] );
			$customQuery[ "wheres" ][ "equals" ] = "( " . implode( " AND ", $equalsWheres ) . " )";
		}

		// Contains the value
		if( isset( $args[ "contains" ] ) && isset( $args[ "contains" ][ "field" ] ) ) {
			$containsQuery = array(
					"wheres" => array( ),
					"values" => array( )
				);

			foreach( $args[ "contains" ][ "field" ] as $index => $value ) {
				$fieldID                            = $value;
				$fieldVal                           = $args[ "contains" ][ "value" ][ $index ];
				$containsQuery[ "values" ][ "field" ] = $fieldVal;

				// Remove duplicate queries
				switch( $fieldID ) {
					case "name":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "(`%s`.`meta_key` = 'first_name' AND `%s`.`meta_value` LIKE '%s' OR `%s`.`meta_key` = 'last_name' AND `%s`.`meta_value` LIKE '%s')",
								$wpdb->usermeta,  $wpdb->usermeta, '%' . $fieldVal . '%',
								$wpdb->usermeta,  $wpdb->usermeta,  '%' . $fieldVal . '%'
							);
						break;
					case "first_name":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'first_name' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, $wpdb->usermeta, '%' . $fieldVal . '%'
							);
						break;
					case "last_name":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'last_name' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, $wpdb->usermeta, '%' . $fieldVal . '%'
							);
						break;
					case "nickname":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'nickname' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, '%' . $fieldVal . '%'
							);
						break;
					case "username":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_login` LIKE '%s'", $wpdb->users, '%' . $fieldVal . '%' );
						break;
					case "email":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_email` LIKE '%s'", $wpdb->users, '%' . $fieldVal . '%' );
						break;
					case "role":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = 'wp_capabilities' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, $wpdb->usermeta, '%' . $fieldVal . '%'
							);
						break;
					case "website":
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`user_url` LIKE '%s'", $wpdb->users, '%' . $fieldVal . '%' );
						break;
					default:
						$containsQuery[ "wheres" ][ $fieldID ] = sprintf( "`%s`.`meta_key` = '%s' AND `%s`.`meta_value` LIKE '%s'",
								$wpdb->usermeta, $fieldID, $wpdb->usermeta,  '%' . $fieldVal . '%'
							);
						break;
				}

			}

			$containsQuery  = apply_filters( "tpc_crm_contains_sql", $containsQuery );
			$containsWheres = array_values( $containsQuery[ "wheres"] );
			$customQuery[ "wheres" ][ "contains" ] = "( " . implode( " AND ", $containsWheres ) . " )";
		}
		$this->customSQL = $customQuery;

		$customQuery = apply_filters( "tpc_crm_pre_user_query", $customQuery );
		if( count( $customQuery[ 'wheres' ] ) ) {
			$sql         = sprintf( "SELECT %s FROM %s", $customQuery[ "select" ], $wpdb->users );
			$joinTables  = array( );
			$joinColumns = array( );
			$wheres      = array( );

			// Prepare the join statements
			foreach( $customQuery[ "joins" ] as $filter => $j ) {
				list( $jt, $jc ) = $j;
				$joinTables[]    = $jt;
				$joinColumns[]   = $jc;
			}

			if( count( $joinTables ) ) {
				$sql .= sprintf( " LEFT JOIN( %s ) ON ( %s )", implode( ", ", $joinTables ), implode( " AND ", $joinColumns ) );
			}

			// prepare the where statements
			foreach( $customQuery[ "wheres" ] as $filter => $w ) {
				$wheres[ ] = $w;
			}
			$sql .= sprintf( " WHERE %s", implode( " AND ", $wheres ) );

			$userQuery->query_where .= sprintf( " AND `%s`.`ID` IN ( %s )", $wpdb->users, $sql );
		}

		return $userQuery;
	}

	/**
	 * Ajax reply to draw the user table
	 */
	public function drawUserTable( ) {
		header( "Content-type: application/json" );
		$wp_list_table = new TPC_CRM_Admin_WPUserListTable( array(
				"screen" => "users"
			) );
		$columns       = array( );
		$wp_list_table->prepare_items( );

		$pagenum       = $wp_list_table->get_pagenum() ;
		$total_pages   = $wp_list_table->get_pagination_arg( 'total_pages' );
		$total_items   = $wp_list_table->get_pagination_arg( 'total_items' );
		$rows          = $wp_list_table->getRows( );
		$extras        = $wp_list_table->getExtraInfo( );

		$output = array(
			'sEcho'                => ( int ) 0,
			'iTotalRecords'        => ( int ) $extras[ "total_records" ],
			'iTotalDisplayRecords' => ( int ) $total_items,
			'aaData'               => $rows,
			'hidden' 			   => $wp_list_table->getHiddenColumns( ),
			'extras'			   => $extras,
			'debug'                => $this->customSLQ
		);

		echo json_encode( $output );
		exit( 0 );
	}

	/**
	 * Adds more fields on the screen settings
	 * @param  string $current 		Content for the screen wrapper
	 * @param  WP_Screen $screen  	An object with all the information about the current screen
	 * @return string             	New Content
	 */
	public function screenSettings( $current, $screen ) {
		if( $screen->parent_file == "users.php" ) {
			// custom content here
			$current .= " ";
		}
		return $current;
	}

	public function getColumnAutoComplete( ) {
		global $wpdb, $wp_roles;

		header( "Content-type: application/json" );
		$request = new TPC_Helper_Array( $_REQUEST );
		$column  = $request->get( 'column' );
		$row     = array( );
		$col     = array( );
		$data    = array( "arr" => array( ), "assoc" => array( ) );

		switch( $column ) {
			case "username":
				$col = $wpdb->get_col( "SELECT DISTINCT `user_login` FROM `{$wpdb->users}`" );
				break;
			case "name":
				$col = $wpdb->get_col( "SELECT DISTINCT GROUP_CONCAT(`meta_value` SEPARATOR ' ') FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'first_name' OR `meta_key` = 'last_name' GROUP BY `user_id`" );
				break;
			case "email":
				$col = $wpdb->get_col( "SELECT DISTINCT `user_email` FROM `{$wpdb->users}`" );
				break;
			case "role":
				$col = array_keys( $wp_roles->get_names( ) );
				break;
			case "posts":
				$col = $wpdb->get_col( "SELECT DISTINCT COUNT(*) FROM `{$wpdb->posts}` WHERE `post_type`='post' AND `post_status`='publish' GROUP BY `post_author`" );
				break;
			case "date_registered":
				$col = $wpdb->get_col( "SELECT DISTINCT `user_registered` FROM `{$wpdb->users}`" );
				break;
			case "website":
				$col = $wpdb->get_col( "SELECT DISTINCT `user_url` FROM `{$wpdb->users}`" );
				break;
			default:
				if( preg_match( '/[a-zA-Z0-9\-\_ ]+/', $column) ) {
					$col = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT `meta_value` FROM `{$wpdb->usermeta}` WHERE `meta_key`=%s", $column ) );
				}
				break;
		}

		foreach( $col as $val ) {
			if( trim( $val ) ) {
				$type   = new TPC_Helper_DataType( $val );

				if( $type->isArray( ) ) {
					foreach( $type->getData( ) as $chunk ) {
						if( !in_array( $chunk, $data[ 'arr' ] ) ) {
							$id                       = $val;
							$data[ 'arr' ][ ]         = $chunk;
							$data[ 'assoc' ][ $id ][] = $chunk;
						}
					}
				} else if( !in_array( $val, $data[ 'arr' ] ) ) {
					$data[ 'arr' ][ ]          = $val;
					$data[ 'assoc' ][ $val ][] = $val;
				}
			}
		}

		$data = apply_filters( 'tpc_autocomplete_columns', $data, $column );
		echo json_encode( $data );
		exit( 0 );
	}
}
