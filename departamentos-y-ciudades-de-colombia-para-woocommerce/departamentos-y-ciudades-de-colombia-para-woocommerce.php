<?php
/**
 * Plugin Name: Departamentos y Ciudades de Colombia para Woocommerce
 * Description: Plugin modificado con los departamentos y ciudades de Colombia
 * Version: 2.0.23
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: departamentos-y-ciudades-de-colombia-para-woocommerce
 * Domain Path: /languages
 * WC tested up to: 10.6
 * WC requires at least: 6.0
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('init', 'states_places_colombia_load_textdomain');
add_action('plugins_loaded','states_places_colombia_init');
add_action('admin_notices', 'dcco_promo_admin_notice');
add_action('wp_ajax_dcco_dismiss_promo_notice', 'dcco_dismiss_promo_notice_handler');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
        }
    }
);

function states_places_colombia_load_textdomain(){
    load_plugin_textdomain('departamentos-y-ciudades-de-colombia-para-woocommerce',
        FALSE, dirname(plugin_basename(__FILE__)) . '/languages');
}

function states_places_colombia_smp_notices($notice){
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function dcco_promo_admin_notice() {
    // Check if user has dismissed the notice
    $user_id = get_current_user_id();
    if ( get_user_meta( $user_id, 'dcco_dismiss_promo_notice', true ) ) {
        return;
    }
    
    $landing_url = 'https://saulmoralespa.com/woocommerce-colombia';
    $nonce = wp_create_nonce( 'dcco_dismiss_promo_nonce' );
    ?>
    <div class="notice notice-info is-dismissible dcco-promo-notice" style="padding: 15px; border-left-color: #00a0d2;">
        <p style="margin: 0 0 10px 0; font-size: 16px;">
            <strong>🚀 ¿Tu tienda ya está en producción?</strong>
        </p>
        <p style="margin: 0 0 10px 0;">
            Puedo ayudarte a mejorar tu checkout con:<br>
            ✔ Facturación electrónica (DIAN)<br>
            ✔ Envíos automatizados<br>
            ✔ Integraciones de pago
        </p>
        <p style="margin: 0;">
            <a href="<?php echo esc_url( $landing_url ); ?>" target="_blank" class="button button-primary">
                👉 Ver cómo puedo ayudarte
            </a>
        </p>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.dcco-promo-notice').on('click', '.notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'dcco_dismiss_promo_notice',
                nonce: '<?php echo $nonce; ?>'
            });
        });
    });
    </script>
    <?php
}

function dcco_dismiss_promo_notice_handler() {
    check_ajax_referer( 'dcco_dismiss_promo_nonce', 'nonce' );
    
    $user_id = get_current_user_id();
    update_user_meta( $user_id, 'dcco_dismiss_promo_notice', '1' );
    
    wp_send_json_success();
}

function states_places_colombia_init(){
    if (!class_exists('WC_States_Places_Colombia')) require_once ('includes/states-places.php');

    if (!function_exists('filters_by_cities_method')) require_once ('includes/filter-by-cities.php');

    /**
     * Instantiate class
     */
    new WC_States_Places_Colombia(__FILE__);

    add_filter( 'woocommerce_shipping_methods', function ($methods){
        $methods['filters_by_cities_shipping_method'] = 'Filters_By_Cities_Method';
        return $methods;
    });

    add_filter( 'woocommerce_default_address_fields', function( $fields ){
        if ($fields['city']['priority'] < $fields['state']['priority']){
            $state_priority = $fields['state']['priority'];
            $fields['state']['priority'] = $fields['city']['priority'];
            $fields['city']['priority'] = $state_priority;
        }
        return $fields;
    }, 1000, 1 );

    add_action( 'woocommerce_shipping_init', 'filters_by_cities_method' );
}
