<?php
/*
Plugin Name: Responsive Facebook Gallery
Plugin URI: http://www.pixsols.com/test/wordpress/facebook-gallery/
Description: Responsive facebook gallery, just plug and play.
Version: 1.1
Author: Abbas
Author URI: http://www.pixsols.com/
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

if ( ! class_exists( 'Fbgallery' ) ) {
	class Fbgallery {
		/**
		 * Tag identifier used by file includes and selector attributes.
		 * @var string
		 */
		protected $tag = 'fbgallery';

		/**
		 * User friendly name used to identify the plugin.
		 * @var string
		 */
		protected $name = 'Responsive Facebook Gallery';

		/**
		 * Current version of the plugin.
		 * @var string
		 */
		protected $version = '1.1';

		/**
		 * List of options to determine plugin behaviour.
		 * @var array
		 */
		protected $options = array();

		/**
		 * List of settings displayed on the admin settings page.
		 * @var array
		 */
		protected $settings = array(
                    'Facebook App ID' => array(
                            'description' => 'Enter Facebook App ID',
                            'validator' => 'numeric',
                            'placeholder' => 'Facebook App ID'
                    ),
                    'Facebook App Secret' => array(
                            'description' => 'Enter Facebook App Secret',
                            'validator' => 'alphanumeric',
                            'placeholder' => 'Facebook Secret ID'
                    )
		);

		/**
		 * Initiate the plugin by setting the default values and assigning any
		 * required actions and filters.
		 *
		 * @access public
		 */
		public function __construct() {
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}
			add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
			if ( is_admin() ) {
				add_action( 'admin_init', array( &$this, 'settings' ) );
			}
		}

		/**
		 * Allow the fbgallery shortcode to be used.
		 *
		 * @access public
		 * @param array $atts
		 * @param string $content
		 * @return string
		 */
		public function shortcode( $atts, $content = null )
		{
			extract( shortcode_atts( array(
                            'url' => false,
                            'column' => '3'
			), $atts ) );
                        
                        $parts = parse_url($url);                        
                        parse_str($parts['query'], $parts);
                        $parameter = explode(".",$parts['set']);
                        
                        $class = 'col-sm-4 col-xs-6';
                        
                        if(!empty($column)) {
                            if($column == '1') {
                                $class = 'col-xs-6';
                            } else if($column == '2') {
                                $class = 'col-sm-6 col-xs-6';
                            } else if($column == '3') {
                                $class = 'col-sm-4 col-xs-6';
                            } else if($column == '4') {
                                $class = 'col-sm-3 col-xs-6';
                            } else if($column == '6') {
                                $class = 'col-sm-2 col-xs-6';
                            }
                        }
                        // Enqueue the required styles and scripts...
			$this->_enqueue();
                        // Output the terminal...
			ob_start();
                        
                        require_once dirname(__FILE__) .'/fbapi.php';
                        $fbgalleryOption = get_option( 'fbgallery' );
                        
                        $facebook = new Facebook(array('appId'  => $fbgalleryOption['Facebook App ID'],'secret' => $fbgalleryOption['Facebook App Secret'],'cookie' => false ));    
                        $album_idx = $parameter['3'].'_'.$parameter['2']; #facebook album id
                        
                        $fql    =   "SELECT object_id,pid, src, src_small, src_big,caption FROM photo WHERE aid = '" . $album_idx ."'  ORDER BY created ASC LIMIT 50";
                        $param  =   array(
                         'method'    => 'fql.query',
                         'query'     => $fql,
                         'callback'  => ''
                        );
                        $fqlResult   =   $facebook->api($param);

                        $x = 0;
                        $html = '';
                        
                        $html .= '<div class="row"><ul id="fbgallery">';
                        foreach( $fqlResult as $keys => $values ){
                            if( $values['caption'] == '' ){ $caption = ""; } else{ $caption = $values['caption']; }
                            $small_url = $values['src_small'];
                            $src_big = $values['src_big'];
                            $caption = $values['caption'];
                            $html .= '<li class="'.$class.'">';
                            $html .= '<a class="swipebox" href="'.$src_big.'" title="'.$caption.'"><img class="img-responsive" src="'.$src_big.'" alt="'.$caption.'" /></a>';
                            $html .= '</li>';
                            $x++;
                        }
                        $html .= '</ul></div>';
                        $html .='<script type="text/javascript">
                                jQuery( document ).ready(function() {
                                    jQuery(".swipebox" ).swipebox();
                                });
                                </script>';
                        echo $html; ?>
                        <?php
			return ob_get_clean();
		}

		/**
		 * Add the setting fields to the Reading settings page.
		 *
		 * @access public
		 */
		public function settings() {
			$section = 'reading';
			add_settings_section(
				$this->tag . '_settings_section',
				$this->name . ' Settings',
				function () {
					echo '<p>Configuration options for the ' . esc_html( $this->name ) . ' plugin.</p>';
				},
				$section
			);
			foreach ( $this->settings AS $id => $options ) {
				$options['id'] = $id;
				add_settings_field(
					$this->tag . '_' . $id . '_settings',
					$id,
					array( &$this, 'settings_field' ),
					$section,
					$this->tag . '_settings_section',
					$options
				);
			}
			register_setting (
				$section,
				$this->tag,
				array( &$this, 'settings_validate' )
			);
		}

		/**
		 * Append a settings field to the the fields section.
		 *
		 * @access public
		 * @param array $args
		 */
		public function settings_field( array $options = array() ) {
			$atts = array(
				'id' => $this->tag . '_' . $options['id'],
				'name' => $this->tag . '[' . $options['id'] . ']',
				'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),
				'class' => 'regular-text',
				'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null )
			);
			if ( isset( $this->options[$options['id']] ) ) {
				$atts['value'] = $this->options[$options['id']];
			}
			if ( isset( $options['placeholder'] ) ) {
				$atts['placeholder'] = $options['placeholder'];
			}
			if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
				if ( $atts['value'] ) {
					$atts['checked'] = 'checked';
				}
				$atts['value'] = true;
			}
			array_walk( $atts, function( &$item, $key ) {
				$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
			} );
			?>
			<label>
				<input <?php echo implode( ' ', $atts ); ?> />
				<?php if ( array_key_exists( 'description', $options ) ) : ?>
				<?php esc_html_e( $options['description'] ); ?>
				<?php endif; ?>
			</label>
			<?php
		}

		/**
		 * Validate the settings saved.
		 *
		 * @access public
		 * @param array $input
		 * @return array
		 */
		public function settings_validate( $input ) {
			$errors = array();
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				$validator = false;
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
					break;
					default:
						 $input[$key] = strip_tags( $value );
					break;
				}
			}
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			return $input;
		}

		/**
		 * Enqueue the required scripts and styles, only if they have not
		 * previously been queued.
		 *
		 * @access public
		 */
		protected function _enqueue() {
                    // Define the URL path to the plugin...
                    $plugin_path = plugin_dir_url( __FILE__ );
                    // Enqueue the styles in they are not already...
                    if ( !wp_style_is( $this->tag, 'enqueued' ) ) {
                            wp_enqueue_style(
                                    $this->tag,
                                    $plugin_path . 'fbgallery.css',
                                    array(),
                                    $this->version,
                                    'all'
                            );
                            wp_register_style(
                                    'bootstrap-'.$this->tag,
                                    $plugin_path . 'bootstrap.min.css',
                                    array(),
                                    $this->version,
                                    'all'
                            );
                            wp_enqueue_style('bootstrap-'.$this->tag);
                    }
                    // Enqueue the scripts if not already...
                    if ( !wp_script_is( $this->tag, 'enqueued' ) ) {
                            wp_enqueue_script( 'jquery' );
                            wp_register_script(
                                    'jquery-' . $this->tag,
                                    $plugin_path . 'ios-orientationchange-fix.js',
                                    array( 'jquery' ),
                                    '0.1.2'
                            );
                            wp_enqueue_script('jquery-' . $this->tag);
                            wp_register_script(
                                    'jquery-swipebox' . $this->tag,
                                    $plugin_path . 'jquery.swipebox.min.js',
                                    array( 'jquery' ),
                                    '0.1.2'
                            );
                            wp_enqueue_script('jquery-swipebox' . $this->tag);
                    // Make the options available to JavaScript...
                            $options = array_merge( array(
                                    'selector' => '.' . $this->tag
                            ), $this->options );
                            wp_localize_script( $this->tag, $this->tag, $options );
                            wp_enqueue_script( $this->tag );
                    }
		}

	}
	new Fbgallery;
}
