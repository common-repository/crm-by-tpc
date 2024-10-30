<?php
/**
 * TPC_Interface_TableColumn
 *
 * A unified intercace for WP_List_Table Columns
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   Jon Falcon <darkutubuki143@gmail.com>
 * @version  1.0
 */
interface TPC_Interface_TableColumn {
	/**
	 * Set the column ID
	 * @param string $id
	 */
	public function setId( $id );

	/**
	 * Gets the column ID
	 */
	public function getId( );

	/**
	 * Sets the value
	 * @param mixed $value 
	 */
	public function setValue( $value );

	/**
	 * Return the column value
	 */
	public function getValue( );

	/**
	 * Return the column data type
	 */
	public function getType( );
}