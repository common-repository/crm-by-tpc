<?php
/**
 * TPC_Helper_DataType
 *
 * Checks the data type of the given variable
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @version  1.0
 */
class TPC_Helper_DataType {
	/**
	 * Raw data
	 * @var string
	 */
	private $raw;

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
		$this->raw  = $data;
		$this->data = maybe_unserialize( $data );
	}

	/**
	 * Get the processed data
	 * @return mixed 
	 */
	public function getData( ) {
		return $this->data;
	}

	/**
	 * Get the raw data
	 * @return string 
	 */
	public function getRawData( ) {
		return $this->raw;
	}

	/**
	 * Checks if the data type is numeric;
	 * @return boolean
	 */
	public function isNumeric( ) {
		return is_numeric( $this->data );
	}

	/**
	 * Checks if the data type is a string
	 * @return boolean
	 */
	public function isString( ) {
		return is_string( $this->data );
	}

	/**
	 * Checks if the data type is an array
	 * @return boolean
	 */
	public function isArray( ) {
		return is_array( $this->data );
	}

	/**
	 * Checks if the data type is a date
	 * @return boolean
	 */
	public function isDate( ) {
		return $this->isString( ) && ( bool ) strtotime( $this->data );
	}

	/**
	 * Checks if the data type is a json
	 * @return boolean
	 */
	public function isJson( ) {
		return is_object( json_encode( $this->data ) );
	}

	/**
	 * Checks if the data type is an object
	 * @return boolean
	 */
	public function isObject( ) {
		return is_object( $this->data );
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
				"data"      => $this->data
			);

		return $data;
	}
}