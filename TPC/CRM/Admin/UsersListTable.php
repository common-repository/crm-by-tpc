<?php
/**
 * TPC_CRM_Admin_UsersListTable
 *
 * Adds hooks to the Users List Table
 *
 * @package  : Customer Relationship Manager (Free)
 */
class TPC_CRM_Admin_UsersListTable implements TPC_Interface_Runnable {
	/**
	 * Lazy load users so we won't create new objects again
	 * @var array
	 */
	private $users = array();

	/**
	 * Default columns
	 * @var array
	 */
	private $possibleColumns = array (
			"cb" 			  => null,
			"username"        => "Username",
			"name"            => "Name",
			"email"           => "Email",
			"role"            => "Role",
			"posts"           => "Posts",
			"first_name"      => "First Name",
			"last_name"       => "Last Name",
			"nickname"        => "Nickname",
			"website"         => "Website",
			"date_registered" => "Date Registered"
		);

	/**
	 * Custom Columns
	 * @var array
	 */
	private $customColumns = array(
			"cb" 			  => null,
			"username"        => "Username",
			"name"            => "Name",
			"email"           => "Email",
			"role"            => "Role",
			"posts"           => "Posts",
		);

	/**
	 * Custom Callbacks
	 * @var array
	 */
	private $customCallbacks = array();

	/**
	 * Adds a column
	 * @param string $columnID   	Column Unique ID
	 * @param string $columnName 	Optional. You can add your own custom column name.
	 * @return  $this    			Supports chaining
	 */
	public function addColumn( $columnID, $columnName = null, $callback = null ) {
		if( array_key_exists( $columnID, $this->possibleColumns ) ) {
			if( $columnName ) {
				$this->customColumns[ $columnID ] = $columnName;
			} else {
				$this->customColumns[ $columnID ] = $this->possibleColumns[ $columnID ];
			}
		} else {
			$this->customColumns[ $columnID ]   = $columnName;
			$this->customCallbacks[ $columnID ] = $callback;
		}
		return $this;
	}

	/**
	 * Removes a column
	 * @param  string $columnID 	Unique Identifier
	 * @return $this            	Supports chaining
	 */
	public function removeColumn( $columnID ) {
		unset( $this->customColumns[ $columnID ] );
		return $this;
	}

	/**
	 * Resets the columns
	 * @return $this 			Supports chaining
	 */	
	public function resetColumns( ) {
		$this->customColumns = array( );
		return $this;
	}

	/**
	 * Return the possible columns
	 * @return array 
	 */
	public function getPossibleColumns( ) {
		return $this->possibleColumns;
	}

	/**
	 * Return the custom columns
	 * @return array 
	 */
	public function getColumns( ) {
		return $this->customColumns;
	}

	public function getColumnsDataTypes( ) {
		$dataTypes = array( );
		foreach( $this->customColumns as $id => $label ) {
			if( $id == "cb" ) continue;
			$dataType                    = new TPC_Helper_DataTypeById( $id );
			$dataTypes[ $id ]            = $dataType->toArray( );
			$dataTypes[ $id ][ "label" ] = $label;
		}
		return $dataTypes;
	}

	/**
	 * Return the custom callbacks
	 * @return array 
	 */
	public function getCustomCallbacks( ) {
		return $this->customCallbacks;
	}

	/**
	 * Runs this module
	 */
	public function run( ) {
		add_filter( 'manage_users_columns'			, array( $this, 'customColumns' ) );
		add_filter( 'manage_users_sortable_columns'	, array( $this, 'customColumns' ) );
		add_filter( 'manage_users_custom_column'	, array( $this, 'customColumn'  ), 10, 3 );
	}

	/**
	 * Add custom columns
	 * @param  array $columns List of custom columns
	 * @return array          New list of columns
	 */
	public function customColumns( $columns ) {
		return $this->customColumns;
	}

	/**
	 * Get visible columns
	 * @return Array  All the visible columns
	 */
	public function getVisibleColumns( ) {
		$visible  = array( );
		$hidden   = ( array ) get_user_meta( get_current_user_id( ), 'manageuserscolumnshidden', true );
		$hidden[] = "cb";
		foreach( $this->customColumns as $id => $name ) {
			if( !in_array( $id, $hidden ) ) {
				$visible[ $id ] = $name;
			}
		}

		return $visible;
	}

	/**
	 * Get the column data types
	 * @param  Array $columns    List of columns. If empty, we'll fetch the visible columns.
	 * @return Array          
	 */
	public function getColumnsDataType( $columns = null ) {
		if( !$columns ) {
			$columns = $this->getVisibleColumns( );
		}

		$dataTypes = array( 
			"date"    => array( ),
			"numeric" => array( ),
			"string"  => array( ),
			"all"     => array( )
		);
		foreach( $columns as $id => $val ) {
			$type                      = new TPC_Helper_DataTypeById( $id );
			$dataTypes[ "all" ][ $id ] = $type;
			if( $type->isDate( ) ) {
				$dataTypes[ "date" ][ $id ] = $type;
			} elseif( $type->isNumeric( ) ) {
				$dataTypes[ "numeric" ][ $id ] = $type;
			} else {
				$dataTypes[ "string" ][ $id ] = $type;
			}
		}

		return $dataTypes;
	}

	/**
	 * Display the column
	 * @param  mixed $value       	Value of the column
	 * @param  string $columnID 	Title of the column
	 * @param  integer $userId     	User ID
	 * @return string             	Date Registered
	 */
	public function customColumn( $value, $columnID, $userId ) {
		if( !isset( $this->users[ $userId ] ) ) {
			$this->users[ $userId ] = new WP_User( $userId );
		}
		$user = $this->users[ $userId ];

		switch( $columnID ) {
			case "first_name":
				return $user->first_name;
				break;
			case "last_name":
				return $user->last_name;
				break;
			case "nickname":
				return $user->nickname;
				break;
			case "website":
				return $user->user_url;
				break;
			case "date_registered":
				return date( "F j, Y", strtotime( $user->user_registered ) );
				break;
			default:
				if( array_key_exists( $columnID, $this->customCallbacks ) ) {
					$callback = $this->customCallbacks[ $columnID ];
					if (empty($callback)) $callback = array($this, "fallbackFunc");
					
					return call_user_func_array( $callback, array( $user, $columnID ) );
				}
				return $value;
				break;
		}
	}
	
	public function fallbackFunc( $user, $columnID )
	{
		return null;
	}
}