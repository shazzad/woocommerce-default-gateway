<?php
#sleep(20);
/**
 * WooCommerce Default Gateway Plugin.
 *
 * @class WC_Default_Gateway
 **/
final class WC_Default_Gateway {
	/**
	 * @var plugin name
	 */
	public $name = 'WooCommerce Default Gateway';

	/**
	 * @var plugin version
	 */
	public $version = '0.0.1';

	/**
	 * @var Singleton The reference the *Singleton* instance of this class
	 */
	protected static $_instance = null;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	private function __construct() {
		$this->define_constants();
		$this->register_hooks();
	}

	/**
	 * Define constants
	 */
	private function define_constants() {
		define( 'WCDG_DIR', plugin_dir_path( WCDG_PLUGIN_FILE ) );
		define( 'WCDG_URL', plugin_dir_url( WCDG_PLUGIN_FILE ) );
		define( 'WCDG_BASENAME', plugin_basename( WCDG_PLUGIN_FILE ) );
		define( 'WCDG_VERSION', $this->version );
		define( 'WCDG_NAME', $this->name );
	}

	/**
	 * Register hooks
	 */
	private function register_hooks() {
		add_filter( 'woocommerce_payment_gateways_setting_columns', array( $this, 'add_default_column' ) );
		add_action( 'woocommerce_payment_gateways_setting_column_default', array( $this, 'default_column_value' ) );
		add_action( 'wp_ajax_wcdg-toggle', array( $this, 'toogle_default_gateway' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . WCDG_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'template_redirect', array( $this, 'maybe_define_default_gateway' ) );
	}

	public function maybe_define_default_gateway() {
		if ( ! is_checkout() ) {
			return;
		}

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( WC()->session && ! WC()->session->get( 'chosen_payment_method' ) && $this->get_default_gateway() ) {
			$gateway_id = $this->get_default_gateway();
			if ( isset( $available_gateways[$gateway_id] ) ) {
				WC()->session->set( 'chosen_payment_method', $this->get_default_gateway() );
			}
		}
	}

	public function toogle_default_gateway() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'error' => __( 'Sorry, you can not perform this action.', 'woocommerce-default-gateway' )
			) );
		}

		if ( ! isset( $_POST['gateway_id'] ) ) {
			wp_send_json_error( array(
				'error' => __( 'Missing gateway id.', 'woocommerce-default-gateway' )
			) );
		}

		$gateway_id = $_POST['gateway_id'];

		if ( $this->is_default_gateway( $gateway_id ) ) {
			$this->delete_default_gateway();
			wp_send_json_success( array(
				'gateway_id' => ''
			));
		} else {
			$this->set_default_gateway( $gateway_id );
			wp_send_json_success( array(
				'gateway_id' => $gateway_id
			));
		}
	}

	public function is_default_gateway( $gateway_id ) {
		return $gateway_id === $this->get_default_gateway();
	}

	public function get_default_gateway() {
		return get_option( 'woocommerce_default_gateway_id' );
	}

	public function delete_default_gateway() {
		delete_option( 'woocommerce_default_gateway_id' );
	}

	public function set_default_gateway( $gateway_id ) {
		update_option( 'woocommerce_default_gateway_id', $gateway_id );
	}

	public function enqueue_scripts( $hook ) {
		if ( 'woocommerce_page_wc-settings' === $hook && isset( $_REQUEST['tab'] ) && 'checkout' === $_REQUEST['tab'] ) {
			wp_register_script( 'wc-default-gateway-admin', WCDG_URL . '/assets/js/admin.js', array( 'jquery' ) );
			wp_localize_script( 'wc-default-gateway-admin', 'wcdg', array(
				'enabledText' => __( 'Yes', 'woocommerce-default-gateway' ),
				'enabledClass' => 'woocommerce-input-toggle--enabled',
				'disabledText' => __( 'No', 'woocommerce-default-gateway' ),
				'disabledClass' => 'woocommerce-input-toggle--disabled',
				'loadingClass' => 'woocommerce-input-toggle--loading'
			) );
			wp_enqueue_script( 'wc-default-gateway-admin' );
			wp_enqueue_style( 'wc-default-gateway-admin', WCDG_URL . '/assets/css/admin.css' );
		}
	}

	public function default_column_value( $gateway ) {
		$key = 'default';

		$value = __( 'No', 'woocommerce-default-gateway' );
		$class = 'woocommerce-input-toggle--disabled';

		if ( $this->is_default_gateway( $gateway->id ) ) {
			$value = __( 'Yes', 'woocommerce-default-gateway' );
			$class = 'woocommerce-input-toggle--enabled';
		}

		echo '<td class="' . esc_attr( $key ) . '">';
			echo '<a class="wcdg-toggle" href="#">';
				echo '<span class="woocommerce-input-toggle ' . $class . '">' . $value . '</span>';
			echo '</a>';
		echo '</td>';
	}

	/**
	 * Add extra column 'defaul' to payment gateways table.
	 *
	 * @param array $columns Current columns.
	 * @return array $columns With new columns.
	 */
	public function add_default_column( $columns ) {
		$pos = array_search( 'status', array_keys( $columns ) );
		if ( ! $pos ) {
			$pos = 3;
		} else {
			$pos += 1;
		}

		$columns = array_slice( $columns, 0, $pos, true )
		+ array( 'default' => __( 'Default' ) )
		+ array_slice( $columns, $pos, count( $columns ) - $pos, true );
		return $columns;
	}

	/**
	 * Adds plugin action links.
	 */
	public function plugin_action_links( $links ) {
		$links['settings'] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woocommerce-default-gateway' ) . '</a>';
		return $links;
	}
}
