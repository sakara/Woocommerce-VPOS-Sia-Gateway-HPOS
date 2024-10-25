<?php
/**
 * Plugin Name: WooCommerce VPOS Sia Gateway
 * Plugin URI: https://www.sia.eu
 * Description: This module allows you to connect your e-commerce site to the payment platform offered by SIA for accepting online payments both with VISA and Mastercard cards issued by other banking institutions
 * Author: Sia Spa
 * Author URI: https://www.sia.eu
 * Version: 1.0.0
 * Requires at least: 4.0.1
 * Tested up to: 4.0.1
 * WC tested up to: 4.0.1
 * WC requires at least: 4.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\OrderUtil::declare_compatibility( 'vposiaspa-payment-gateway', __FILE__ );
    }
});

/**
 * Initialize the gateway.
 *
 * @since 1.0.0
 */
function woocommerce_vpossia_init ()
{
    /**
     * Check component called from this component
     */
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    load_theme_textdomain('woocommerce-vpossia-payment-gateway', WP_PLUGIN_DIR . "/vpossia/lang");

    /**
     * PLUGIN BASIC INFO
     */
    define("WC_GATEWAY_VPOSSIA_PLUGIN_NAME", plugin_basename(dirname(__FILE__)));
    define('WC_GATEWAY_VPOSSIA_VERSION', '1.0.0');
    define('WC_GATEWAY_VPOSSIA_ID', 'vpossia');
    define("WC_GATEWAY_VPOSSIA_TITLE", "VPOS SIA");

    define("WC_GATEWAY_VPOSSIA_EXTRA_TITLE", "Woocommerce-SIA");

    define("WC_GATEWAY_VPOSSIA_TITLE_EXTENDED", "" . __('Pay with cards', 'woocommerce-vpossia-payment-gateway'));
    define("WC_GATEWAY_VPOSSIA_LOGO", WP_PLUGIN_URL . '/' . WC_GATEWAY_VPOSSIA_PLUGIN_NAME . '/assets/images/logo_admin.png');
    define("WC_GATEWAY_VPOSSIA_LOGO_CARDS", WP_PLUGIN_URL . '/' . WC_GATEWAY_VPOSSIA_PLUGIN_NAME . '/assets/images/logo_cards.png');
    define('WC_GATEWAY_VPOSSIA_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));


    /**
     * TEST MODE COSTANTS
     */
    define('WC_GATEWAY_VPOSSIA_TESTMODE_URL', 'https://virtualpostest.sia.eu/vpos/payments/main?PAGE=LAND');

    /**
     * PRODUCTION MODE COSTANTS
     */
    define('WC_GATEWAY_VPOSSIA_PRODUCTIONMODE_URL', 'https://virtualpos.sia.eu/vpos/payments/main?PAGE=LAND');

    /**
     * USEFUL LINKS
     */
    define('WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_TEST', 'https://virtualpos.sia.eu/cubo/fgu-apl/start-atpos-en.html');
    define('WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_PRODUCTION', 'https://virtualpostest.sia.eu/cubo/fgu-apl/start-atpos-en.html');

    define('WC_GATEWAY_VPOSSIA_PATH_DOCS', WP_PLUGIN_URL . '/' . WC_GATEWAY_VPOSSIA_PLUGIN_NAME . '/docs/');

    //
    define('WC_GATEWAY_VPOSSIA_CURRENCY', array (
        "AED" => "784"
    , "AFN" => "971", "ALL" => "008", "AMD" => "051", "ANG" => "532", "AOA" => "973", "ARS" => "032", "AUD" => "036", "AWG" => "533", "AZN" => "944", "BAM" => "977", "BBD" => "052", "BDT" => "050", "BGN" => "975", "BHD" => "048", "BIF" => "108", "BMD" => "060", "BND" => "096", "BOB" => "068", "BOV" => "984", "BRL" => "986", "BSD" => "044", "BTN" => "064", "BWP" => "072", "BYN" => "933", "BZD" => "084", "CAD" => "124", "CDF" => "976", "CHE" => "947", "CHF" => "756", "CHW" => "948", "CLF" => "990", "CLP" => "152", "CNY" => "156", "COP" => "170", "COU" => "970", "CRC" => "188", "CUC" => "931", "CUP" => "192", "CVE" => "132", "CZK" => "203", "DJF" => "262", "DKK" => "208", "DOP" => "214", "DZD" => "012", "EGP" => "818", "ERN" => "232", "ETB" => "230", "EUR" => "978", "FJD" => "242", "FKP" => "238", "GBP" => "826", "GEL" => "981", "GHS" => "936", "GIP" => "292", "GMD" => "270", "GNF" => "324", "GTQ" => "320", "GYD" => "328", "HKD" => "344", "HNL" => "340", "HRK" => "191", "HTG" => "332", "HUF" => "348", "IDR" => "360", "ILS" => "376", "INR" => "356", "IQD" => "368", "IRR" => "364", "ISK" => "352", "JMD" => "388", "JOD" => "400", "JPY" => "392", "KES" => "404", "KGS" => "417", "KHR" => "116", "KMF" => "174", "KPW" => "408", "KRW" => "410", "KWD" => "414", "KYD" => "136", "KZT" => "398", "LAK" => "418", "LBP" => "422", "LKR" => "144", "LRD" => "430", "LSL" => "426", "LYD" => "434", "MAD" => "504", "MDL" => "498", "MGA" => "969", "MKD" => "807", "MMK" => "104", "MNT" => "496", "MOP" => "446", "MRO" => "478", "MUR" => "480", "MVR" => "462", "MWK" => "454", "MXN" => "484", "MXV" => "979", "MYR" => "458", "MZN" => "943", "NAD" => "516", "NGN" => "566", "NIO" => "558", "NOK" => "578", "NPR" => "524", "NZD" => "554", "OMR" => "512", "PAB" => "590", "PEN" => "604", "PGK" => "598", "PHP" => "608", "PKR" => "586", "PLN" => "985", "PYG" => "600", "QAR" => "634", "RON" => "946", "RSD" => "941", "RUB" => "643", "RWF" => "646", "SAR" => "682", "SBD" => "090", "SCR" => "690", "SDG" => "938", "SEK" => "752", "SGD" => "702", "SHP" => "654", "SLL" => "694", "SOS" => "706", "SRD" => "968", "SSP" => "938", "STD" => "678", "SYP" => "760", "SZL" => "748", "THB" => "764", "TJS" => "972", "TMT" => "795", "TND" => "788", "TOP" => "776", "TRY" => "949", "TTD" => "780", "TWD" => "901", "TZS" => "834", "UAH" => "980", "UGX" => "800", "USD" => "840", "USN" => "997", "USS" => "998", "UYU" => "858", "UZS" => "860", "VEF" => "862", "VND" => "704", "VUV" => "548", "WST" => "882", "XAF" => "950", "XAG" => "961", "XAU" => "959", "XBA" => "955", "XBB" => "956", "XBC" => "957", "XBD" => "958", "XCD" => "951", "XDR" => "960", "XOF" => "952", "XPD" => "964", "XPF" => "953", "XPT" => "962", "XTS" => "963", "XXX" => "999", "YER" => "886", "ZAR" => "710", "ZMW" => "894", "ZWL" => "932"));

    /**
     * REPORT COSTANTS
     */
    define("WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_DEFAULT_ITEM_PER_PAGE", 100);
    define('WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_EXPORT_CSV_FILE_NAME_PREFIX', str_replace(" ", "_", WC_GATEWAY_VPOSSIA_TITLE) . "_");


    require_once(plugin_basename('includes/class-wc-gateway-vpossia.php'));
    require_once(plugin_basename('includes/class-wc-gateway-vpossia-privacy.php'));

    $prp = dirname(plugin_basename(__FILE__)) . '/lang';
    load_plugin_textdomain('woocommerce-vpossia-payment-gateway', FALSE, $prp);
    add_filter('woocommerce_payment_gateways', 'woocommerce_vpossia_add_gateway');

    if (is_admin()) {
        require_once(WC_GATEWAY_VPOSSIA_PLUGIN_BASE_PATH . 'includes/admin.php');
    }

}

add_action('plugins_loaded', 'woocommerce_vpossia_init', 0);

/**
 * Return list of useful links for plugin
 *
 * @param $links
 *
 * @return array
 */
function woocommerce_vpossia_plugin_links ($links)
{
    $settings_url = add_query_arg(
        array (
            'page' => 'wc-settings',
            'tab' => 'checkout',
            'section' => 'wc_gateway_vpossia',
        ),
        admin_url('admin.php')
    );

    /**
     * Link on section WP installed plugins
     */
    $plugin_links = array (
        '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-vpossia-payment-gateway') . '</a>'
        //,'<a  href="' . ('yes' === get_option('testmode') ? WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_TEST : WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_PRODUCTION) . '" target="_blank">' . __('VPOS Portal', 'woocommerce-vpossia-payment-gateway') . '</a>'
    );
    /*if ('yes' !== get_option('woocommerce_vpossia_testmode')){
        array_push($plugin_links, '<a  href="' .  WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_PRODUCTION . '" target="_blank">' . __('VPOS Portal', 'woocommerce-vpossia-payment-gateway') . '</a>');
    }*/


    if (get_locale() == 'it_IT') {
        array_push($plugin_links, '<a href="' . WC_GATEWAY_VPOSSIA_PATH_DOCS . 'readme_it-IT.pdf" target="_blank"> ' . __('Docs', 'woocommerce-vpossia-payment-gateway') . '</a>');
    } else {
        array_push($plugin_links, '<a href="' . WC_GATEWAY_VPOSSIA_PATH_DOCS . 'readme_en-GB.pdf" target="_blank"> ' . __('Docs', 'woocommerce-vpossia-payment-gateway') . '</a>');
    }

    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_vpossia_plugin_links');

/**
 * Add the gateway to WooCommerce
 *
 * @since 1.0.0
 */
function woocommerce_vpossia_add_gateway ($methods)
{
    // Add gateway(s), one gateway for one element of array
    $methods[] = 'WC_Gateway_VPosSia';
    return $methods;
}

/**
 * Extract ISO 4217 numeric value from String Code
 *
 * @param $currencyStringCode
 *
 * @return string
 * @throws Exception
 */
function woocommerce_gateway_vpossia_currencies_number_code ($currencyStringCode)
{
    if (!array_key_exists($currencyStringCode, WC_GATEWAY_VPOSSIA_CURRENCY)) {
        throw new Exception(WC_GATEWAY_VPOSSIA_PLUGIN_NAME . ' ' . __('does not support this currency code', 'woocommerce-vpossia-payment-gateway') . ' ' . $currencyStringCode);
    }

    return WC_GATEWAY_VPOSSIA_CURRENCY[$currencyStringCode];
}

add_filter('woocommerce_gateway_vpossia_currencies_number_code', 'woocommerce_gateway_vpossia_currencies_number_code', 10, 1);
