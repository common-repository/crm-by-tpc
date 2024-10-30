<?php
/**
 * TPC_Helper_DataType
 *
 * Checks the data type of the given ID
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @version  1.0
 */
class TPC_Helper_DataTypeById {
	/**
	 * Data container
	 * @var mixed
	 */
	private $data;

	/**
	 * Initialize the object
	 * @param mixed $data 
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Checks if the data type is numeric;
	 * @return boolean
	 */
	public function isNumeric( ) {
		return ( bool ) preg_match( "/(age|number|count$|id$|min|max|posts)/", $this->data );
	}

	/**
	 * Checks if the data type is a string
	 * @return boolean
	 */
	public function isString( ) {
		return !$this->isNumeric( ) && !$this->isDate( );
	}

	/**
	 * Checks if the data type is an array
	 * @return boolean
	 */
	public function isArray( ) {
		return false;
	}

	/**
	 * Checks if the data type is a date
	 * @return boolean
	 */
	public function isDate( ) {
		return ( bool ) preg_match( "/(date|birthday)/", $this->data );
	}

	/**
	 * Checks if the data type is a json
	 * @return boolean
	 */
	public function isJson( ) {
		return false;
	}

	/**
	 * Checks if the data type is an object
	 * @return boolean
	 */
	public function isObject( ) {
		return false;
	}

	/**
	 * Returns all the validation into a string
	 * @return array 
	 */
	public function toArray( ) {
		$data = array (
				"isNumeric" => $this->isNumeric( ),
				"isString"  => $this->isString( ),
				"isObject"  => $this->isObject( ),
				"isArray"   => $this->isArray( ),
				"isDate"    => $this->isDate( ),
				"isJson"    => $this->isJson( ),
			);

		return $data;
	}
}