<?php
/**
 * TPC_CRM_Admin_FeaturePointers
 *
 * @package  : Customer Relationship Manager (Free)
 */
class TPC_CRM_Admin_FeaturePointers implements TPC_Interface_Runnable {
	/**
	 * List of steps
	 * @var array
	 */
	private $steps = array ( );

	/**
	 * Initialize this object
	 */
	public function __construct( ) {
		$this->steps = array( 
			array( 
				"pointer"  => "#menu-users .menu-top",
				"position" => "left",
				"message"  => "<p>You will find the CRM Search & Filtering tools here.</p>",
				"url"      => admin_url( "users.php" )
			),
			array(
				"pointer"  => "#show-settings-link",
				"position" => "top",
				"message"  => sprintf( "<p>If you purchase the <a href=\"%s\">Premium version</a> you can hide or show additional columns including <a href=\"%s\">Custom Fields</a>.</p>",
							"https://www.wppluginco.com/product/customer-relationship-manager-plugin-for-wordpress",
							"http://www.advancedcustomfields.com/resources/getting-started/what-is-acf/" )
			),
			array(
				"pointer"  => ".wp-list-table thead tr th:nth-child(2)",
				"position" => "bottom",
				"message"  => sprintf( "<p>If you purchase the <a href=\"%s\">Premium version</a> you can reorder the columns by simply dragging and dropping them.</p>",
							"https://www.wppluginco.com/product/customer-relationship-manager-plugin-for-wordpress",
                             "http://www.advancedcustomfields.com/resources/getting-started/what-is-acf/"
                )
			)
		);
		
		if( class_exists( 'TPCP_CRM_Model_Preset' ) ) {
			$this->steps[] = array (
					"pointer"  => "#presetName",
					"position" => "top",
					"message"  => "<p>Save your Searches & Filters by giving it a name and selecting the checkmark beside this box.</p>"
				);
			$this->steps[] = array (
					"pointer"  => "#userFilters_chosen",
					"position" => "right",
					"message"  => "<p>Every time you create a Preset it will appear here for quick access.</p>"
				);
		}

		$this->steps[] = array(
				"pointer"  => "#search-tab .search-input",
				"position" => "right",
				"message"  => "<p>You can use date strings (e.g. today, last week, last month) to query date columns. NOTE: The global search will only search for the visible columns. For numeric fields, it'll look for the exact value while it'll look for the month for date fields (e.g. if you put in July 10, 2014, it'll look for items that are on July)."
			);
	}

	/**
	 * Run and install this module
	 */
	public function run( ) {
		add_action( 'admin_enqueue_scripts', array( $this, 'addScripts' ) );
		add_action( 'wp_ajax_tpc_feature_pointer', array( $this, 'steps' ) );
	}

	/**
	 * Add the required scripts
	 */
	public function addScripts( ) {
		$stepsTaken = intval( get_option( 'tpc_steps' ) );
		if( !isset( $this->steps[ $stepsTaken ] ) ) return;

		wp_enqueue_style( 'wp-pointer' );
	    wp_enqueue_script( 'wp-pointer' );

	    // Register our action
	    add_action( 'admin_print_footer_scripts', array( $this, 'scriptPointers' ) );
	}

	/**
	 * Add the feature pointers
	 */
	public function scriptPointers( ) {
		$stepsTaken = intval( get_option( 'tpc_steps' ) );
		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function( $ ) {
			<?php if( class_exists( 'TPCP_CRM_Model_Preset' ) ): ?>
				$( '#DataTables_Table_0_filter' ).addClass( 'active' );
			<?php endif; ?>
			var _addPointer = function( element, html, position ) {
			    $( element ).pointer({
			        content: '<h3>Customer Relationship Manager</h3>' + html,
			        position: {
		                edge: position,
		                align: 'center'
		            },
			        close: function() {
			        	$.post(
			        		ajaxurl,
			        		{
			        			action: 'tpc_feature_pointer'
			        		},
			        		function( response ) {
			        			console.log( response.nextURL );
			        			if( response.nextURL ) {
				        			window.location.href = response.nextURL;
				        		} else if( response.nextAction ) {
				        			_addPointer( response.nextPointer, response.nextAction, response.nextPosition );
				        		}
			        		}
			        	);
			        }
			    } ).pointer( 'open' );
			}
			_addPointer( '<?php echo $this->steps[ $stepsTaken ][ 'pointer']; ?>', '<?php echo $this->steps[ $stepsTaken ][ 'message' ]; ?>', '<?php echo $this->steps[ $stepsTaken ][ 'position' ]; ?>' );
		});
		//]]>
		</script>
		<?php
	}

	/**
	 * Process each steps
	 */
	public function steps( ) {
		header( "Content-type: application/json" );
		$request    = new TPC_Helper_Array( $_REQUEST );
		$stepsTaken = intval( get_option( 'tpc_steps' ) );
		$return     = array( 'success' => false, 'nextURL' => false, 'nextPointer' => false, 'nextAction' => false );

		if( isset( $this->steps[ $stepsTaken ] ) ) {
			$return[ 'success' ] = true;
			$next = $stepsTaken + 1;

			if( $next <= count( $this->steps ) ) {
				update_option( 'tpc_steps', $next );
			}

			if( isset( $this->steps[ $stepsTaken ][ "url" ] ) ) {
				$return[ 'nextURL' ] = $this->steps[ $stepsTaken ][ "url" ];
			} else {
				if( isset( $this->steps[ $next] ) ) {
					$return[ 'nextPointer' ]  = $this->steps[ $next ][ "pointer" ];
					$return[ 'nextAction' ]   = $this->steps[ $next ][ "message" ];
					$return[ 'nextPosition' ] = $this->steps[ $next ][ "position" ];
				}
			}
		}

		echo json_encode( $return );
		exit( 0 );
	}
}