<?php
/**
 * TPC_Helper_WPUserColumn
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   : Jon Falcon <darkutubuki143@gmail.com>
 * @version  : 0.0.1
 */
class TPC_Helper_WPUserColumn implements TPC_Interface_TableColumn {
	/**
	 * Column ID
	 * @var string
	 */
	private $id;

	/**
	 * Column value
	 * @var mixed
	 */
	private $value;

	/**
	 * Column data type
	 * @var string
	 */
	private $type;

	/**
	 * Is the value an array?
	 * @var boolean
	 */
	private $isArray;

	/**
	 * Checks the data type
	 * @var object
	 */
	private $dataType;

	/**
	 * Initiates this object
	 * @param string $id    Column ID
	 * @param mixed $value  Column Value
	 */
	public function __construct( $id = '', $value = null ) {
		$this->setId( $id );
		$this->setValue( $value );
	}

	/**
	 * Sets the column ID
	 * @param string $id Column ID
	 */
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Gets the column ID
	 * @return string 
	 */
	public function getId( ) {
		return $this->id;
	}

	/**
	 * Set the column value
	 * @param mixed $value 
	 */
	public function setValue( $value ) {
		if( is_serialized( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		$this->value    = apply_filters( 'set_column_' . $this->id . '_value', $value );
		$this->dataType = new TPC_Helper_DataType( $this->value );

		return $this;
	}

	/**
	 * Gets the column value
	 * @return mixed 
	 */
	public function getValue( ) {
		return apply_filters( 'get_column_' . $this->id . '_value', $this->vallue );
	}

	/**
	 * Returns the data type
	 * @return array
	 */
	public function getType( ) {
		if( is_null( $this->type ) ) {
			$this->type = $this->dataType->toArray( );
		}

		return $this->type;
	}
}