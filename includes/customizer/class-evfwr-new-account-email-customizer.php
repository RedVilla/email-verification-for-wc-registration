<?php
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class evfwr_New_Account_Email_Customizer {
	// Get our default values	
	private static $order_ids  = null;
	public $defaults;
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->evfwr_generate_defaults();		
		
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'evfwr_my_account_customizer_options' ) );
		
		// Only proceed if this is own request.				
		if ( ! self::is_own_customizer_request() && ! self::is_own_preview_request()) {
			return;
		}		
		
		// Register our sections
		add_action( 'customize_register', array( wc_evfwr_customizer(), 'evfwr_add_customizer_sections' ) );	
		
		// Remove unrelated components.
		add_filter( 'customize_loaded_components', array( wc_evfwr_customizer(), 'remove_unrelated_components' ), 99, 2 );
		
		// Remove unrelated sections.
		add_filter( 'customize_section_active', array( wc_evfwr_customizer(), 'remove_unrelated_sections' ), 10, 2 );	
		
		// Unhook divi front end.
		add_action( 'woomail_footer', array( wc_evfwr_customizer(), 'unhook_divi' ), 10 );
		
		// Unhook Flatsome js
		add_action( 'customize_preview_init', array( wc_evfwr_customizer(), 'unhook_flatsome' ), 50  );	
		

		add_filter( 'customize_controls_enqueue_scripts', array( wc_evfwr_customizer(), 'enqueue_customizer_scripts' ) );	
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );	

		add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );		
	}			
		
	/**
	 * Add css and js for preview
	*/	
	public function enqueue_preview_scripts() {		 
		 wp_enqueue_style('evfwr-pro-preview-styles', woo_customer_email_verification()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), woo_customer_email_verification()->version  );		 
	}	
	
	/**
 	* Checks to see if we are opening our custom customizer preview
 	*  
 	* @return bool
 	*/
	public static function is_own_preview_request() {
  		if ( ! isset( $_REQUEST['action'] ) ) {
    			return false; // Early exit if no action is set
  		}

  		// Validate and sanitize the action before using it
  		$action = sanitize_key( $_REQUEST['action'] );

  		// Check for valid nonces for both actions (unchanged)
  		if ( ! ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'preview_evfwr_verification_lightbox' ) ||
         		wp_verify_nonce( $_REQUEST['_wpnonce'], 'guest_user_preview_evfwr_verification_lightbox' ) ) ) {
    				return false; // Invalid nonce
  			}

  			// If both nonce checks fail, the code continues here assuming the actions are valid
  			return ( $action === 'preview_evfwr_verification_lightbox' || $action === 'guest_user_preview_evfwr_verification_lightbox' );
		}
	
	/**
 	* Checks to see if we are opening our custom customizer controls
 	*  
 	* @return bool
 	*/
	public static function is_own_customizer_request() {
  		// Check for both GET and POST requests as some customizer actions might use either
  		if ( ! ( isset( $_GET['page'] ) && 'customize.php' === $_GET['page'] ) && ! ( isset( $_POST['page'] ) && 'customize.php' === $_POST['page'] ) ) {
    			return false;
  		}

  		// Check for the section name and add nonce verification
  		$nonce_action = 'evfwr_verification_widget_messages'; // Replace with unique action name for your section
  		$nonce_name = "evfwr_customizer_{$nonce_action}_nonce";
  		if ( ! isset( $_REQUEST['section'] ) || 'evfwr_verification_widget_messages' !== $_REQUEST['section'] || ! wp_verify_nonce( $_REQUEST[ $nonce_name ], $nonce_action ) ) {
    			return false;
  		}

  		return true;
	}

	
	/**
	 * Get Customizer URL
	 *
	 */
	public static function get_customizer_url( $section ) {
		
		$customizer_url = add_query_arg( array(
			'evfwr-customizer' => '1',
			'section' => $section,
			'url'     => urlencode( add_query_arg( array( 'evfwr-new-account-email-preview' => '1' ), home_url( '/' ) ) ),
		), admin_url( 'customize.php' ) );		
		return $customizer_url;
	}
	
	/**
	 * Code for initialize default value for customizer
	*/	
	public function evfwr_generate_defaults() {
		$customizer_defaults = array(
			'evfwr_new_acoount_email_heading' => __( 'Please verify your email address', 'customer-email-verification-for-woocommerce' ),
			'evfwr_new_verification_email_body' => __( 'Your Verification Code: {evfwr_user_verification_pin} 
Or, verify your account by clicking on the verification link: ', 'customer-email-verification-for-woocommerce' ),
		);
		return $customizer_defaults;
	}						
	
	/**
	 * Register our sample default controls
	 */
	public function evfwr_my_account_customizer_options( $wp_customize ) {	
	
		/**
		* Load all our Customizer Custom Controls
		*/
		require_once trailingslashit( dirname(__FILE__) ) . 'custom-controls.php';		
													
		
		// Email heading	
		$wp_customize->add_setting( 'evfwr_new_acoount_email_heading',
			array(
				'default' => $this->defaults['evfwr_new_acoount_email_heading'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'evfwr_new_acoount_email_heading',
			array(
				'label' => __( 'Verification Heading', 'woocommerce' ),
				'description' => esc_html__( 'Only for a New Account verification email', 'customer-email-verification-for-woocommerce' ),
				'section' => 'evfwr_new_account_email_section',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => $this->defaults['evfwr_new_acoount_email_heading'],
				),
			)
		);	
		
		// Email Body	
		$wp_customize->add_setting( 'evfwr_new_verification_email_body',
			array(
				'default' => $this->defaults['evfwr_new_verification_email_body'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'evfwr_new_verification_email_body',
			array(
				'label' => __( 'Verification Message', 'customer-email-verification-for-woocommerce' ),
				'description' => '',
				'section' => 'evfwr_new_account_email_section',
				'type' => 'textarea',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => $this->defaults['evfwr_new_verification_email_body'],
				),
			)
		);
		
		$wp_customize->add_setting( 'evfwr_new_email_code_block',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new WP_Customize_evfwr_codeinfoblock_Control( $wp_customize, 'evfwr_new_email_code_block',
			array(
				'label' => __( 'Available variables', 'customer-email-verification-for-woocommerce' ),
				'description' => '<code>{evfwr_user_verification_pin}<br>{evfwr_user_verification_link}</code>',
				'section' => 'evfwr_new_account_email_section',				
			)
		) );							
	}	
	
	/**
	 * Set up preview
	 *	 
	 * @return void
	 */
	public function set_up_preview() {
		
		// Make sure this is own preview request.
		if ( ! self::is_own_preview_request() ) {
			return;
		}
		include woo_customer_email_verification()->get_plugin_path() . '/includes/customizer/preview/preview_new.php';		
		exit;			
	}	

	/**
	 * Code for preview of tracking info in email
	*/	
	public function preview_new_account_email() {
		// Load WooCommerce emails.
		
		$wc_emails      = WC_Emails::instance();
		$emails         = $wc_emails->get_emails();
		WC_customer_email_verification_email_Common()->wuev_user_id  = 1;
		$email_heading     = get_option( 'evfwr_new_acoount_email_heading', $this->defaults['evfwr_new_acoount_email_heading'] );
		$email_heading 	   = WC_customer_email_verification_email_Common()->maybe_parse_merge_tags( $email_heading );
		$email_content     = get_option( 'evfwr_new_verification_email_body', $this->defaults['evfwr_new_verification_email_body'] );
		$email_type = 'WC_Email_Customer_New_Account';
		if ( false === $email_type ) {
			return false;
		}	
		
		$mailer = WC()->mailer();
		
		// Reference email.
		if ( isset( $emails[ $email_type ] ) && is_object( $emails[ $email_type ] ) ) {
			$email = $emails[ $email_type ];
		}
		$user_id = get_current_user_id();
		$user = get_user_by( 'id', $user_id );
		
		$email->object             = $user;
		$email->user_pass          = '{user_pass}';
		$email->user_login         = stripslashes( $email->object->user_login );
		$email->user_email         = stripslashes( $email->object->user_email );
		$email->recipient          = $email->user_email;
		$email->password_generated = true;
		
		// Get email content and apply styles.
		$content = $email->get_content();
		$content = $email->style_inline( $content );
		$content = apply_filters( 'woocommerce_mail_content', $content );
		
		if ( 'plain' === $email->email_type ) {
			$content = '<div style="padding: 35px 40px; background-color: white;">' . str_replace( "\n", '<br/>', $content ) . '</div>';
		}
		
		echo wp_kses_post( $content );
		
	}	
}
/**
 * Initialise our Customizer settings
 */

$evfwr_new_account_email_customizer = new evfwr_new_account_email_customizer();
