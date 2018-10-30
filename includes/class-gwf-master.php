<?php 
/**
 * Main Plugin Class.
 * 
 * @package gwf-master
 * @since 1.0
 */

class PNQ_GWF_Master {
	
	/**
	 * Google Font api url
	 * 
	 * @since 1.0
	 */
	private $api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=';
		
	/**
	 * Options which will be exist in database.
	 * 
	 * @since 1.0
	 */
	private $options = array(
		'pnq_gwf_google_api_key' => '',
		'pnq_gwf_dashboard_support' => '0',
		'pnq_gwf_font_rules' => ''
	);
	
	private $plugin_page = '';
	
	/**
	 * Allowed heading tags.
	 * 
	 * @since 1.0
	 */
	private $heading_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	
	/**
	 * Allowed content tags.
	 * 
	 * @since 1.0
	 */
	private $content_tags = array( 'p', 'span', 'li', 'a', 'th', 'td', 'input', 'select', 'textarea', 'pre', 'code', 'address', 'strong', 'em', 'cite', 'blockquote' );
	
	/**
	 * Admin notices
	 * 
	 * @since 1.0
	 */
	public $notices = array();
	
	/**
	 * Full list of google font
	 * 
	 * @since 1.0
	 */
	public $web_fonts = array();
	
	/**
	 * Retrieve the full list of google font.
	 * 
	 * @since 1.0
	 */
	public function retrieve_font_list() {
		
		// get font list from database
		$font_list = get_option( 'pnq_gwf_font_list' );

		// google api key
		$api_key = $this -> get_option( 'pnq_gwf_google_api_key' );

		// get font list from google
		if ( ! $font_list && $api_key != '' ) {
			$url = $this -> api_url . $api_key;
			$font_list = $this -> retrieve_font_list_from_google( $url, $api_key );
		}
		
		if ( ! $font_list ) {
			// use static font list
			$static_file = plugin_dir_path( __FILE__ ) . 'static-font-list.php';
			if ( file_exists( $static_file ) ) {
				require_once( $static_file );
				$font_list = $pnq_gwf_static_font_list;
			} else {
				$this -> notices['error'][] = sprintf( __( 'Cannot find static-font-list.php in the plugin\'s %s directory, please reinstall the plugin.', 'gwf' ), PNQ_GWF_INC );	
			}
		}
		
		$this -> web_fonts = $font_list;
	}
	
	/**
	 * Retrieve full font list from Google Font API.
	 * 
	 * @since 1.0
	 *
	 * @param string $url Google Api URL including args.
	 * @param string $api_key Google Api Key.
	 * @return mixed Google font list or false.
	 */
	public function retrieve_font_list_from_google( $url, $api_key ) {		
		$font_list = array();
		$response = wp_remote_get( $url );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_msg = wp_remote_retrieve_response_message( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );	

		if ( is_wp_error( $response ) ) {
			$this -> notices['error'][] = sprintf( __( 'Error occurred when retrieving font list from google: %s, using default font list.', 'gwf' ), wp_strip_all_tags( $response -> get_error_message() ) );
		} else if ( isset( $response_body['error'] ) && isset( $response_body['error']['errors'] ) ) {
			$errors = $response_body['error']['errors'];
			foreach ( $errors as $error ) {
				if ( $error['reason'] == 'keyInvalid' ) {
					$this -> notices['error'][] = sprintf( __( 'Invalid Google API Key: %s, default used.', 'gwf' ), $api_key );
					update_option( 'pnq_gwf_google_api_key', '' );
				}
			}
		} else if ( $response_code == 400 ) {
			$this -> notices['error'][] = sprintf( __( 'Error occurred when retrieving font list from google, %d : %s, please recheck your google api key. To avoid this error message, emptying that option or inputing your own valid <a href="http://pnqstudio.com/support/how-to-acquire-google-developer-api-key/" target="_blank">Google API Key</a>.', 'gwf' ), $response_code, $response_msg );
		}else if ( $response_code == 200 ) {
			$raw_fonts = json_decode( wp_remote_retrieve_body( $response ), true );	
			$fonts = $raw_fonts['items'];
			foreach ( $fonts as $font ) {
				$font_list[] = $font['family'];	
			}
			
			update_option( 'pnq_gwf_font_list', $font_list );
			return $font_list;
		}
		
		return false;
	}
	
	/**
	 * Add plugin admin menu as a submenu of 'Appearance'.
	 * 
	 * @since 1.0
	 */
	public function add_admin_page() {
		$this -> plugin_page = add_theme_page(
			__( 'Google Web Fonts Master', 'gwf' ),
			__( 'Google Fonts', 'gwf' ),
			'manage_options',
			__FILE__,
			array( &$this, 'display_admin_page' )
		);	
		
		// enqueue plugin page scripts
		add_action( 'load-' . $this -> plugin_page, array( &$this, 'enqueue_admin_page_styles_and_scripts' ) );
		
		// add plugin contextual help	
		add_action( 'load-' . $this -> plugin_page, array( &$this, 'add_plugin_help_tab' ), 10, 1 );
	}
	
	/**
	 * Add plugin help tab.
	 * 
	 * @since 1.0
	 */
	public function add_plugin_help_tab() {
		$screen = get_current_screen();
		if ( $screen -> id == $this -> plugin_page ) {
			$screen -> add_help_tab( array(
				'id' => 'pnq_gwf_help_tab_overview',
				'title' => 'Overview',
				'callback' => array( $this, 'help_tab_overview' )
			) );
			$screen -> add_help_tab( array(
				'id' => 'pnq_gwf_help_tab_steps',
				'title' => 'Use Steps',
				'callback' => array( $this, 'help_tab_steps' )
			) );	
			$screen -> add_help_tab( array(
				'id' => 'pnq_gwf_help_tab_attetions',
				'title' => 'Attentions',
				'callback' => array( $this, 'help_tab_attentions' )
			) );
		}
	}
	
	/**
	 * Output plugin help tab content.
	 * 
	 * @since 1.0
	 */
	public function help_tab_overview() {
		$help_content = '<h4>' . __( 'GWF Master Overview', 'gwf' ) . '</h4>'
						.'<p>' . __( 'Google Web Font Master is a premium, powerful and easy to use google web fonts management plugin.', 'gwf' ) . '</p>'
						.'<p>' . __( 'By using GWF Master, through several simple step you could customize almost all font appearance, either front end or back end Dashboard.', 'gwf' ) . '</p>';
		
		echo $help_content; 
	}
	
	/**
	 * Output plugin help tab content.
	 * 
	 * @since 1.0
	 */
	public function help_tab_steps() {
		$help_content = '<h4>' . __( 'GWF Master Use Steps', 'gwf' ) . '</h4>'
						.'<ol class="pnq-gwf-help-tab-list">'
							.'<li>1. ' . __( 'Choose a font you would like to use.', 'gwf' ) . '</li>'
							.'<li>2. ' . __( 'Choose affected tags or input your own css classes.', 'gwf' ) . '</li>'
							.'<li>3. ' . __( 'Create Font Rule.', 'gwf' ) . '</li>'
							.'<li>4. ' . __( 'Repeat step 1-3 till you satisfied with the whole site\'s font styles.', 'gwf' ) . '</li>'
							.'<li>5. ' . __( 'Save your settings.', 'gwf' ). '</li>'
						.'</ol>';	
						
		echo $help_content;
	}
	
	/**
	 * Output plugin help tab content.
	 * 
	 * @since 1.0
	 */
	public function help_tab_attentions() {
		$help_content = '<h4>' . __( 'Attentions', 'gwf' ) . '</h4>'
						.'<ol class="pnq-gwf-help-tab-list">'
							.'<li>1. ' . __( '<strong>Create Font Rules</strong>: When specifying your own classes, make sure the class name is valid. e.g. .example-class, .your-own-class, div.another-class.', 'gwf' ) . '</li>'
							.'<li>2. ' . __( '<strong>Dashboard Support</strong>: You need to refresh the browser to see the result.', 'gwf' ) . '</li>'
							.'<li>3. ' . __( '<strong>Google API Key</strong>: Any invalid key will be removed and default will be used instead. Using your own API Key will load font list from google font api, and you don\'t have to do so because that list does not change often. follow <a href="http://pnqstudio.com/support/how-to-acquire-google-developer-api-key/" target="_blank">this link</a> if you want to load your own API Key.', 'gwf' ) . '</li>'
							.'<li>4. ' . __( '<strong>Dashboard Support</strong>: You need refresh to see the results when changing “Dashboard Support” Option.', 'gwf' ) . '</li>'
						.'</ol>';
		
		echo $help_content;	
	}
	
	/**
	 * Enqueue the styles and scripts when the plugin settings page has been loaded.
	 * 
	 * @since 1.0
	 */
	public function enqueue_admin_page_styles_and_scripts() {
		
		// styles 
		wp_register_style( 'gwf-admin-style', plugins_url( 'css/admin-styles.css', __FILE__ ) );	
		wp_register_style( 'jquery-chosen-style', plugins_url( 'css/chosen.css', __FILE__ ) );	
		wp_enqueue_style( 'gwf-admin-style' );
		wp_enqueue_style( 'jquery-chosen-style' );
		
		// scripts	
		wp_register_script( 'jquery-chosen', plugins_url( 'js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), '', true );
		wp_register_script( 'gwf-admin-scripts', plugins_url( 'js/admin-scripts.js', __FILE__ ), array( 'jquery', 'jquery-chosen' ), '', true );
		
		// make sure the webfont script doesn't load twice
		$dashboard_support = get_option( 'pnq_gwf_dashboard_support' );
		if ( ! $dashboard_support ) {
			wp_register_script( 'gwf-web-font-loader', plugins_url( 'js/webfont.js', __FILE__ ) );
			wp_enqueue_script( 'gwf-web-font-loader' );	
		}
		
		wp_enqueue_script( 'jquery' );		
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-effects-highlight' );
		wp_enqueue_script( 'jquery-chosen' );
		wp_enqueue_script( 'gwf-admin-scripts' );
	}
	
	/**
	 * Generate the plugin's settings page.
	 * 
	 * @since 1.0
	 */
	public function display_admin_page() {
		
		// handle update
		//var_dump($_POST);
		if ( isset( $_POST['Update'] ) ) {
			foreach ( $this -> options as $key => $val ) {
				if ( isset( $_POST[$key] ) ) {
					// decode json string
					if( $key == 'pnq_gwf_font_rules' ) {
						$value = json_decode( stripslashes( $_POST[$key] ), true );
						update_option( $key, $value );
						continue;
					}
					
					// use form data
					update_option( $key, trim($_POST[$key]) );	
				} else {
					// use default
					update_option( $key, $val );	
				}
			}
			
			$this -> notices['updated'][] = __( 'Settings saved', 'gwf' );
		}		
								
		// retrieve google font list
		$this -> retrieve_font_list();
		
		$google_api_key = $this -> get_option( 'pnq_gwf_google_api_key' );
		$dashboard_support = $this -> get_option( 'pnq_gwf_dashboard_support' );
		$font_rules = $this -> get_option( 'pnq_gwf_font_rules' );
		if( ! empty( $font_rules ) ) {
			$font_rules = $font_rules['rules'];
		}	
				
		// display settings page
		?>
        <div class="wrap">
        	<div id="icon-options-general" class="icon32"><br /></div>
        	<h2><?php _e( 'Google Web Fonts Master', 'gwf' ); ?></h2>
            
        	<?php foreach ( $this -> notices as $type => $notices ) : ?>
            	<div class="<?php echo esc_attr($type); ?>">
                    <ul class="pnq-gwf-notice-list">
					<?php foreach ( $notices as $notice ) : ?>   	
                        <li><?php echo $notice ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
			<?php endforeach ?>
             
             <form action="" id="pnq-gwf-data-form" method="post">
                <table class="form-table">
             		<tbody>
                    	<tr valign="top">
                        	<th scope="row"><label for=""><?php _e( 'Pick a Font', 'gwf' ); ?></label></th>
                            <td id="form_section_font">
                                <select data-placeholder="<?php _e( 'Choose a Font...', 'gwf' ); ?>" id="pnq_gwf_font_list" class="pnq-gwf-select pnq-gwf-font-list">
                                	<option value=""></option>
                                	<?php foreach ( $this -> web_fonts as $font ) : ?>
                                    	<option value="<?php echo $font ?>"><?php echo $font; ?></option>
                                    <?php endforeach ?>
                                </select>
                                <p class="description"><?php _e( 'Choose a font that you want to use.', 'gwf' ); ?></p>
                                <div class="pnq-gwf-form-section">
                                	<div class="pnq-gwf-font-preview">
                                        <h3><?php _e( 'Font Preview', 'gwf' ); ?></h3>
                                        <div class="pnq-gwf-preview-toolbar clearfix">
                                            <div class="pnq-gwf-preview-control">
                                                <span>Preview Text</span>
                                                <select class="pnq-gwf-select pnq-gwf-select-prev-text">
                                                    <option><?php _e( 'Font Name', 'gwf' ); ?></option>
                                                    <option selected="selected">Grumpy wizards make toxic brew for the evil Queen and Jack.</option>
                                                    <option>The quick brown fox jumps over the lazy dog.</option>
                                                    <option>AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz 0123456789</option>
                                                </select>
                                            </div>
                                            <div class="pnq-gwf-preview-control">
                                                <span><?php _e( 'Size', 'gwf' ); ?></span>  
                                                <select class="pnq-gwf-select pnq-gwf-select-font-size">
                                                    <option value="9px">9 px</option>
                                                    <option value="10px">10 px</option>
                                                    <option value="12px">12 px</option>
                                                    <option value="13px">13 px</option>
                                                    <option value="14px">14 px</option>
                                                    <option value="16px">16 px</option>
                                                    <option value="18px" selected="selected">18 px</option>
                                                    <option value="24px">24 px</option>
                                                    <option value="28px">28 px</option>
                                                    <option value="36px">36 px</option>
                                                    <option value="48px">48 px</option>
                                                    <option value="64px">64 px</option>
                                                    <option value="72px">72 px</option>
                                                </select> 
                                            </div>
                                         </div>
                                        <p class="pnq-gwf-preview-zone"></p>                                   
                                    </div>
                                </div>
                            </td>
                        </tr>   
                        <tr valign="top">
                        	<th scope="row"><label for=""><?php _e( 'Apply To', 'gwf' ); ?></label></th>
                            <td id="form_section_affected">
                            	<div class="pnq-gwf-form-section">                                   
                                    <ul class="pnq-gwf-apply-list pnq-gwf-body-tag clearfix">
                                        <li id="pnq_gwf_tag_body">body</li> - The Entire Page
                                    </ul>
                                    <ul class="pnq-gwf-apply-list pnq-gwf-heading-tag clearfix">
                                        <?php foreach ( $this -> heading_tags as $heading_tag) : ?>
                                        	<li id="pnq_gwf_heading_<?php echo $heading_tag ?>"><?php echo $heading_tag; ?></li>
                                        <?php endforeach ?>
                                        <li class="pnq-gwf-all-headings">All Headings</li>
                                    </ul>
                                    <ul class="pnq-gwf-apply-list pnq-gwf-content-tag clearfix">
                                   		<?php foreach ( $this -> content_tags as $content_tag ) : ?>   
                                        	<li id="pnq_gwf_content_tag_<?php echo $content_tag; ?>"><?php echo $content_tag; ?></li>                      
                                        <?php endforeach ?>
                                    </ul>
                                    <p class="description"><?php _e( 'Choose which tags will be affected for.', 'gwf' ); ?></p>
                                </div>
                                <div class="pnq-gwf-form-section">
                                    <input type="text" id="pnq_gwf_apply_classes" class="regular-text code" />
                                    <p class="description"><?php _e( 'You can specify one or more css classes(seprated by comma) which will be affected too.', 'gwf' ); ?></p>
                                </div>
                            </td>
                        </tr>   
                        <tr valign="top">
                        	<th scope="row"><label for=""><?php _e('Font Rules'); ?></label></th>
                            <td>
                            	<input type="button" class="button button-secondary" id="pnq_gwf_create_font_rule" value="<?php _e( 'Create New Font Rule', 'gwf' ) ?>" />
                                <p class="description"><?php _e( 'Continuing adding font rules until you are satisfied with them.', 'gwf' ); ?></p>
                                <div class="pnq-gwf-font-rules">
                                	<?php if ( ! empty( $font_rules ) && sizeof($font_rules) > 0 ) : ?>
                                    	<?php foreach ( $font_rules as $rule ) : ?>
                                        	<div class="pnq-gwf-font-rule clearfix">
                                            	<div><h4>Font:</h4><p class="pnq-gwf-rule-family"><?php echo $rule['family'] ?></p></div>
                                                <div><h4>Apply To:</h4><p class="pnq-gwf-rule-affected"><?php echo implode( ',', $rule['affected'] ); ?></p></div>
                                                <a href="#" class="delete" title="delete"></a>
                                            </div>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                        <tr valign="top">
                        	<th scope="row"><label for""><?php _e( 'Dashboard Support', 'gwf' ); ?></label></th>
                            <td>
                            	<fieldset>
                                	<legend class="screen-reader-text"><span>Dashboard Support</span></legend>
                            		<label for="pnq_gwf_dashboard_support"><input type="checkbox" id="pnq_gwf_dashboard_support" name="pnq_gwf_dashboard_support" value="<?php echo esc_attr( $dashboard_support ); ?>" <?php checked( 1, $dashboard_support, true ) ?> /> Enable</label>
                                </fieldset>
                            	<p class="description"><?php _e( 'Whether to apply font rules to Dashboard(need refresh after save).', 'gwf' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                        	<th scope="row"><label for""><?php _e( 'Google Api Key', 'gwf' ); ?></label></th>
                            <td>
                            	<input type="text" id="pnq_gwf_google_api_key" name="pnq_gwf_google_api_key" class="regular-text code" value="<?php echo esc_attr( $google_api_key ); ?>" />
                            	<p class="description"><?php _e( 'Get your own Google Fonts API Key(optional).<a href="http://pnqstudio.com/support/how-to-acquire-google-developer-api-key/" target="_blank">Learn More</a>', 'gwf' ); ?></p>
                            </td>
                        </tr>                 
                    </tbody>
             	</table>
                <p class="submit"><?php submit_button( __( 'Save Changes' ), 'primary', 'Update' ); ?></p>            	
             </form>
        </div>
		<?php	
	}
	
	/**
	 * Add a link to plugin config page.
	 * 
	 * @since 1.0
	 *
	 * @param array $actions The action links which will be displayed in the plugin list.
	 */
	public function add_plugin_actions( $actions ) {
		array_unshift( $actions, '<a href="themes.php?page=' . plugin_basename( __FILE__ ) . '">' . __('Config', 'gwf') . '</a>' );
		return $actions;
	}
	
	/**
	 * Check to see whether to add dashboard support depends on user settings.
	 * 
	 * @since 1.0
	 */
	public function check_dashboard_support() {
		$dashboard_support = get_option( 'pnq_gwf_dashboard_support' );
		
		if ( $dashboard_support ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'add_webfont_support' ) );
		}
	}
	
	/**
	 * Let google font settings also applied to dashboard.
	 * 
	 * @since 1.0
	 */
	public function add_webfont_support() {
		wp_register_script( 'gwf-web-font-loader', plugins_url( 'js/webfont.js', __FILE__ ) );
		wp_enqueue_script( 'gwf-web-font-loader' );
	}
	
	/**
	 * Init options if not exist in database.
	 * 
	 * @since 1.0
	 */
	public function init_options() {
		foreach ( $this -> options as $key => $val ) {
			if ( ! get_option( $key ) ) {
				update_option( $key, $val );
			}
		}
	}
	
	/**
	 * Retrive the api key if exists.
	 * 
	 * @since 1.0
	 *
	 * @return mixed String if exists, false if not exists or invalid.
	 */
	public function retrieve_api_key() {	
		// check whether does the API key exist in database
		$api_key = get_option( 'pnq_gwf_google_api_key' );
		if ( $api_key == '' ) {
			return false;	
		}

		// make a request to google font api to make sure the key is valid
		$url = $this -> api_url . $api_key;
		$response = wp_remote_get( $url );	
		
		if( is_wp_error( $response ) ) {
			var_dump($response);
			$this -> notices['error'][] = sprintf( __( 'Error occured, please recheck your Google Api Key and try again. Error message: %s.', 'gwf' ), $response -> get_error_message() );
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
				
		// valid key
		if ( $response_code == 200 ) {
			return $api_key;	
		}
		
		// invalid key
		if ( isset( $response_body['error'] ) && isset( $response_body['error']['errors'] ) ) {
			$errors = $response_body['error']['errors'];
			foreach ( $errors as $error ) {
				if ( $error['reason'] == 'keyInvalid' ) {
					$this -> notices['error'][] = sprintf( __( 'Invalid Google API Key: $s, default used', 'gwf' ), $api_key );
					update_option( 'pnq_gwf_google_api_key', '' );
				}
			}
		}
			
		return false;
	}
	
	/**
	 * Prepare font style scripts.
	 * 
	 * @since 1.0
	 *
	 * @return array $result Prepared font scripts.
	 */
	public function prepare_google_font_style() {		
		$font_rules = $this -> get_option( 'pnq_gwf_font_rules' );		
		if ( empty( $font_rules ) ) {
			return;
		}	
		$font_rules = $font_rules['rules'];
		$dashboard_support = $this -> get_option( 'pnq_gwf_dashboard_support' );

		$css = '';
		$js = array();
		foreach ( $font_rules as $rule ) {
			$css = $css . (implode( ',', $rule['affected'] ) . ' { font-family: "' . $rule['family'] . '"; } ');
			array_push( $js, '"' . $rule['family'] . '"' );
		}
		
		$js = implode( ',', $js );
		$js = 'WebFont.load({ google: { families: [' . $js . '] } });';
		
		$result['css'] = $css;
		$result['js'] = $js;
		return $result;
	}
	
	/**
	 * Hook into proper actions depending on user settings, in order to print font styles.
	 * 
	 * @since 1.0
	 */
	public function apply_google_font_style() {
		$font_rules = $this -> get_option( 'pnq_gwf_font_rules' );	
		$font_rules = isset( $font_rules['rules'] ) ? $font_rules['rules'] : '';	
		if ( empty( $font_rules ) ) {
			return;
		}	
		$dashboard_support = $this -> get_option( 'pnq_gwf_dashboard_support' );
		
		add_action( 'wp_head', array( &$this, 'print_google_font_style' ) );
		
		if ( $dashboard_support == 1 ) {
			add_action( 'admin_head', array( &$this, 'print_google_font_style' ) );
		}
	}
	
	/**
	 * Print the prepared font scripts in proper position.
	 * 
	 * @since 1.0
	 */
	public function print_google_font_style() {
		$scripts = $this -> prepare_google_font_style();
		
		?>
			<style><?php echo $scripts['css']; ?></style>
            <script><?php echo $scripts['js']; ?></script>
		<?php
	}
	
	/**
	 * Wrapper function for get_options.
	 * If option doesn't exist in database, return the default value.
	 * 
	 * @since 1.0
	 *
	 * @param string $key The option name.
	 * @return mixed The option value.
	 */
	public function get_option($key) {
		return get_option( $key, $this -> options[$key] );
	}
	
	/**
	 * Clean database when uninstalling plugin.
	 * 
	 * @since 1.0
	 */
	public function clean_database() {
		$option_names = array_keys( $this -> options );
		foreach ( $option_names as $option_name ) {
			delete_option( $option_name );	
		}
		delete_option( 'pnq_gwf_font_list' );
	}
		
	protected static $instance;
	
	protected function __construct() {
		
		// whether to add dashborad support
		$this -> check_dashboard_support();
		
		// add plugin actions when it is actived
		add_action( 'plugin_action_links_' . plugin_basename(GWF_PLUGIN_MAIN_FILE), array( &$this, 'add_plugin_actions' ) );
		
		// add plugin admin menu
		add_action( 'admin_menu', array( &$this, 'add_admin_page' ) );		
		
		// add front end support
		add_action( 'wp_enqueue_scripts', array( &$this, 'add_webfont_support' ) );
		
		// apply font style
		add_action( 'init', array( &$this, 'apply_google_font_style' ) );

	}
	
	final public static function instance() {
		if ( !isset( static::$instance )	) {
			$c = get_called_class();
			static::$instance = new $c;
		}
		
		return static::$instance;
	}
	
	final public function __clone() {
		trigger_error( 'Cannot duplicate a singleton.', E_USER_ERROR );	
	}
}