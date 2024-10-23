<?php

/**
 * Add submenu for POS Sia Spa transactions list, into Woocommerce menu
 *
 * only if POS Sia Spa Gateway is enabled
 */
function woocommerce_gateway_vpossia_add_submenu_page ()
{
    global $submenu;

    $main_menu = 'woocommerce';
    $sub_menu = 'woocommerce-gateway-vpossia-transactions-list';


    /**
     * Check submenu exists
     */
    $sub_menu_exists = (isset($submenu[$main_menu]) && in_array('woocommerce-gateway-vpossia-transactions-list', wp_list_pluck($submenu[$main_menu], 2)));

    // Get all settings for plugin
    $settings = get_option('woocommerce_vpossia_settings', []);

    // Set value for enabled settings value for plugin
    if (array_key_exists('enabled', $settings)) {
        $pluginEnabled = ($settings['enabled'] == "yes");
    } else {
        $pluginEnabled = FALSE;
    }

    // Make sure WooCommerce gateway POS Sia Spa enabled
    if (!$pluginEnabled) {
        if ($sub_menu_exists) {
            //Plugin not enabled, remove submenu
            remove_submenu_page($main_menu, $sub_menu);
        }
    } elseif (!$sub_menu_exists) {
        // Submenu do not exists - add .
        $title = sprintf( __( '%s Transactions', 'woocommerce-vpossia-payment-gateway' ), WC_GATEWAY_VPOSSIA_TITLE );
        $hook  =  add_submenu_page($main_menu,
            $title, $title,
            'manage_woocommerce',
            $sub_menu, 'woocommerce_gateway_vpossia_transaction_display');

            add_action( "load-$hook", 'woocommerce_gateway_vpossia_add_options' );


            // Add global admin css style
            global $woocommerce;
            $woocommerce_version = function_exists('WC') ? WC()->version : $woocommerce->version;
            wp_enqueue_style('woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), $woocommerce_version);
    }
}

add_action('admin_menu', 'woocommerce_gateway_vpossia_add_submenu_page', 11);

add_filter('set-screen-option', 'woocommerce_gateway_vpossia_transactions_table_set_option', 10, 3);

/**
 * @param $status
 * @param $option
 * @param $value
 *
 * @return mixed
 */
function woocommerce_gateway_vpossia_transactions_table_set_option($status, $option, $value) {
    return $value;
}

/**
 *  add options
 */
function woocommerce_gateway_vpossia_add_options() {
    $option = 'per_page';
    $args = array(
        'label' => __('Show Nr° transactions for page', 'woocommerce-vpossia-payment-gateway'),
        'default' => WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_DEFAULT_ITEM_PER_PAGE,
        'option' => 'transactions_per_page'
    );
    add_screen_option( $option, $args );
}

// Global variable for list table used on woocommerce_gateway_vpossia_transaction_display
$myListTable = null;

/**
 * Show table of transacions list
 */
function woocommerce_gateway_vpossia_transaction_display ()
{
    global $myListTable;
    require_once(WC_GATEWAY_VPOSSIA_PLUGIN_BASE_PATH . 'includes/admin/class-my-list-table.php');
    $myListTable = new VPOSSIA_My_List_Table();

    echo '<div class="wrap">';
    echo '<h2>' . sprintf( __( '%s Transactions List', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE ) . '</h2>';
    echo '<img src="' . WC_GATEWAY_VPOSSIA_LOGO .'" style="max-height: 150px"><br><span style="font-size: 1.2em;">';
    $myListTable->prepare_items();

    //Shown  notice only for admin area
    $screen = get_current_screen();
    if ($screen->id === "woocommerce_page_woocommerce-gateway-vpossia-transactions-list") {
        echo $myListTable->getNotices();
    }

    $myListTable->display();
    echo '</div>';
}
?>