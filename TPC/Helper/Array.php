<?php
/**
 * TPC_Helper_Array
 *
 * Array helpers
 *
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @package  : Customer Relationship Manager (Free)
 * @version  1.0
 */
class TPC_Helper_Array {
	/**
	 * Container for the default array
	 * @var array
	 */
	private $array;

	/**
	 * Initialize the object
	 * @param array $array
	 */
	public function __construct( $array ) {
		$this->array = $array;
	}

	/**
	 * Gets the value from a given array
	 * @param  string $key 	Array key
	 * @param  array  $array  	Array
	 * @return mixd        
	 */
	public function get( $key, $array = null, $default = "" ) {
		$numargs = func_num_args( );

		if( $numargs < 3 ) {
			$default = $array;
			$array   = $this->array;
		}

		if( is_array( $key ) ) {
			$arr = array();
			foreach( $key as $k ) {
				$arr[ $k ] = $this->get( $k, $array, $default );
			}
			return $arr;
		}

		if ( isset( $array[ $key ] ) ) return $array[ $key ];

		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return $default;
			}

			$array = $array[ $segment ];
		}

		return $array;
	}
}