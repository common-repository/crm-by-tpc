<?php
/**
 * TPC_CRM_Admin_Settings
 *
 * Display the info in Settings section
 *
 * @package  : Customer Relationship Manager (Free)
 * @author   : Iterate Marketing
 * @version  : 1.0.1
 */
class TPC_CRM_Admin_Settings implements TPC_Interface_Runnable {
	/**
	 * Runs the plugin
	 * @return $this 			Supports chaining
	 */

	private $page;

	public function admin_menu_page() {
		$im_plugin_extensions = array(
		);
		$im_plugins_list = array(
			'Bulk Photo And Product Importer Plugin For Wordpress' => 'https://wordpress.org/plugins/bulk-photo-to-product-importer-extension-for-woocommerce/',
			'Premium Bulk Photo To Product Importer Extension For WooCommerce' => 'https://www.wppluginco.com/product/premium-bulk-photo-to-product-importer-extension-for-woocommerce',
			'ACF Front End Form Plugin' => 'https://www.wppluginco.com/product/acf-front-end-form-plugin',
			'CRM Plugin For Wordpress' => 'https://www.wppluginco.com/product/crm-plugin-for-wordpress',
			'Custom Pointers Plugin For Wordpress' => 'https://www.wppluginco.com/product/custom-pointers-plugin-for-wordpress'
		);
		?>
		<div class="wp-box">
			<div class="title"><h3><?php _e( 'WPPC Customer Relationship Manager', 'tpc-crm' ); ?></h3></div>
			<table class="widefat">
				<tbody>
					<tr>
						<td>
							This Plugin was created by <a href="https://www.wppluginco.com/">WP Plugin Co.</a> and <a href="https://www.wppluginco.com/services/wordpress-plugins">you can find more of our Plugins, or request a custom one, here </a>.
							<?php
							if ( count($im_plugin_extensions) > 0 ) {
							?>
							<h4>Extensions</h4>
							<ul>
								<?php
								foreach($im_plugin_extensions as $title => $link) {
								?>
								<li><a href="<?php echo $link ?>"><?php echo $title ?></a></li>
								<?php
								}
								?>
							</ul>
							<?php
							}
							?>
							<?php
							if ( count($im_plugins_list) > 0 ) {
							?>
							<h4>Our other Plugins</h4>
							<ul style="list-style: circle inside;">
								<?php
								foreach($im_plugins_list as $title => $link) {
								?>
								<li><a href="<?php echo $link ?>"><?php echo $title ?></a></li>
								<?php
								}
								?>
							</ul>
							<?php
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
		return $this;
	}

	public function info_box() {
?>
<div class="wrap">
	<div class="metabox-holder">
This is sparta...
	</div>
</div>
<?php
	}

	public function run() {
		add_action('admin_menu', array($this, 'admin_menu_call'));

	}

	public function meta_boxes() {
		add_meta_box( 
	    'im-crm-info-box',
	    __( 'WPPC Customer Relationshop Manager' ),
	    array($this, 'info_box'),
	    'im-crm-settings',
	    'advanced'
	    );
	}

	public function admin_menu_call( ) {
		add_options_page(
			__('WPPC Customer Relationship Manager', TPC_CRM_SLUG ),
			__('CRM', TPC_CRM_SLUG ),
			'manage_options',
			'im-crm-settings',
			array( $this, 'admin_menu_page' )
		);

		return $this;
	}


}