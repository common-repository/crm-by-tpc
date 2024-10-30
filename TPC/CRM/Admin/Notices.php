<?php
/**
 * TPC_CRM_Admin_Notices
 *
 * Display admin notices
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   : Jon Falcon <darkutubuki143@gmail.com>
 * @version  : 1.0.0
 */
class TPC_CRM_Admin_Notices implements TPC_Interface_Runnable {
	/**
	 * List of notices
	 * @var  array
	 */
	private $notices = array(
			'errors'  => array(),
			'updates' => array()
		);

	/**
	 * Runs the plugin
	 * @return $this 			Supports chaining
	 */
	public function run( ) {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );

		return $this;
	}

	/**
	 * Add error message
	 * @param string $message 	Error Message
	 * @return $this 			Supports chaining
	 */
	public function addError( $message ) {
		$this->notices[ 'errors' ][] = $message;

		return $this;
	}

	/**
	 * Add a message
	 * @param string $message
	 * @return $this 			Supports chaining
	 */
	public function addMessage( $message ) {
		$this->notices[ 'updated' ][] = $message;

		return $this;
	}

	/**
	 * Display the messages
	 * @return $this 			Supports chaining
	 */
	public function display_notices( ) {
		foreach( $this->notices as $id => $messages ) {
			$class = $id == 'errors' ? 'error' : 'updated';

			foreach( $messages as $message ) {
				printf( '<div class="%s"><p>%s</p></div>', $class, $message );
			}
		}
		return $this;
	}
}