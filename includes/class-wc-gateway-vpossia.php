<?php
/**
 * VPosSia Payment Gateway
 *
 * Provides a POS Sia Spa Payment Gateway.
 *
 * @package  WooCommerce
 * @category Payment Gateways
 */

class WC_Gateway_VPosSia extends WC_Payment_Gateway
{
    /**
     * Version
     *
     * @var string
     */
    public $version;

    /**
     * @access protected
     * @var array $data_to_send
     */
    protected $data_to_send = array ();

    // NOTICES TYPE
    const NOTICES_TYPE_INFO = "notice-info";
    const NOTICES_TYPE_ERROR = "notice-error";
    const NOTICES_TYPE_WARNING = "notice-warning";
    const NOTICES_TYPE_SUCCESS = "notice-success";

    // Specify position of notice
    const NOTICES_INLINE_TRUE = TRUE; // Before content of plugin
    const NOTICES_INLINE_FALSE = TRUE; // At the end of description plugin

    // Admin Settings Form Fields NAME - Specified ONLY NECESSARY IN ANOTHER FUNCTIONS
    const ADM_SETTINGS_FFN_TESTMODE_URL = 'testmode_url';
    const ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN = 'testmode_shop_id_dropdown';
    const ADM_SETTINGS_FFN_TESTMODE_SHOP_ID = 'testmode_shop_id';
    const ADM_SETTINGS_FFN_TESTMODE_STARTER_KEY = 'testmode_starter_key';
    const ADM_SETTINGS_FFN_TESTMODE_OUTCOME_KEY = 'testmode_outcome_key';
    const ADM_SETTINGS_FFN_SHOP_EMAIL = 'shop_email';
    const ADM_SETTINGS_FFN_URLMS_HEADER = 'urlms_header';
    const ADM_SETTINGS_FFN_URL = 'url';


    //Codici banche test
    const TEST_BANK_SIA_EUR_CODE = "SIA_EUR_CODE";
    const TEST_BANK_CREDEM_EUR_CODE = "CREDEM_EUR_CODE";
    const TEST_BANK_BANCODESIO_EUR_CODE = "BANCODESIO_EUR_CODE";
    const TEST_BANK_SPARKASSE_EUR_CODE = "SPARKASSE_EUR_CODE";
    const TEST_BANK_RAFFEISEN_EUR_CODE = "RAFFEISEN_EUR_CODE";
    const TEST_BANK_VOLKSBANK_EUR_CODE = "VOLKSBANK_EUR_CODE";
    const TEST_BANK_OTP_SERBIA_RSD = "OTP_SERBIA_RSD";
    const TEST_BANK_UNICREDITSERBIA_RSD = "UNICREDITSERBIA_RSD";
    const TEST_BANK_UNICREDITHUNGARY_HUF = "UNICREDITHUNGARY_HUF";
    const TEST_BANK_CREDINSALBANIA_ALL = "CREDINSALBANIA_ALL";

    /**
     * @access protected
     * @var array $data_outcome
     * Returned data after transaction
     */
    protected $data_outcome = array ();

    /**
     * @var array
     */
    private $groupedNotices = [];

    private $testBank = [];

    private $strLogmessage = "";


    /**
     * Constructor for admin page
     */
    public function __construct ()
    {
        $this->version = WC_GATEWAY_VPOSSIA_VERSION;
        $this->id = WC_GATEWAY_VPOSSIA_ID;
        $this->has_fields = FALSE;
        $this->method_title = WC_GATEWAY_VPOSSIA_TITLE;
        $this->title = WC_GATEWAY_VPOSSIA_TITLE_EXTENDED;
        /* translators: 1: a href link 2: closing href */
        $method_description = __('This module allows you to connect your e-commerce site to the payment platform offered by SIA for accepting online payments both with VISA and Mastercard cards issued by other banking institutions', 'woocommerce-vpossia-payment-gateway');

        $this->groupedNotices = [];

        $this->testBank = [
            self::TEST_BANK_SIA_EUR_CODE => ["shop_id" => "1292812928P0002", "startker_key" => "rhcXLfT-ZAx8-UW--N6KC3FmfK-6eHHqz-vPDct8k2GaJmd9f3s-wNH4W-bYvej-ZXFE8EthL-avr5W-vRtr-fycEhqGrse6JN-v", "outcome_key" => "bZX7mTt-2X7FxrERs-uVTFebje4-4hNULRdaZh-s--dB6myAWYpqDMhc5JJQ7qs--9sv-s-SJzNF97--wU-zH7VBK6WttBrWT4xs"],
            self::TEST_BANK_CREDEM_EUR_CODE => ["shop_id" => "1292812928P1002", "startker_key" => "MjAgEYfrpmUL7ff-gS--b--F-WVEv7GZx--4----mCrrAAEAwkmx--5X6-9-7fWNj2Bj-Vm4ZA-erEBJZzmM-35UfNBLFcSQgc92", "outcome_key" => "W-rRU-frkBgCB---cHcn73YGV-uGMjCLpJ6tTaaGYre-GTT-fvD---9z--ty7Rm48YqgwEb-v5QDNNQcnwJH-gD6--BdtgfnC6X-"],
            self::TEST_BANK_BANCODESIO_EUR_CODE => ["shop_id" => "1292812928P2002", "startker_key" => "wGrke-Gr-kvtbxUb9QmmaKA-YqHAuXTVsLNcvs-z2Rfvj-QFHYWZ7bTVxHbSnKdVG-b-SBC5-Kt8L5-utmWQWrW6kdC--4xzrXfh", "outcome_key" => "mWVDuP-cJmNz8FK9k--XS9pN-86--RHYhBHE-q76MBpBJc-RMsjxRp-md-Ynrjpwyg2QB5LuksYcRvdK-a-kpPZnEPZV9UtvFkCL"],
            self::TEST_BANK_SPARKASSE_EUR_CODE => ["shop_id" => "1292812928P3002", "startker_key" => "2B-mu2zVy-xSbcDm8q892FKQH7v-K5b-zBRnJz6V2tR55p--KkxbBaHsvy67sqhDzxapgx-8KQv---r-EAZ5-xaZXBzSh9-vskaE", "outcome_key" => "aZaP--Dvuhe-HkY-EfVku7trUdrh--qtP-tjQ5kaSFT7LH-Hq-Tnya-qCxnLU7-TpqHJ7Bw-xYDjANbBUXn42n4LSUjmZcAwT7ft"],
            self::TEST_BANK_RAFFEISEN_EUR_CODE => ["shop_id" => "1292812928P4002", "startker_key" => "X-WfRj3Px-JVFFjbpd-EE4Ta-zWQ--XJSBjbVKSyHbLyBj-zLHUJc-wPvCZz4TMBPvtnmw-DhGG2whay7euk7c4xQ-8-q3SKE-X-", "outcome_key" => "Kah-5jtTq6--Z4nqmbyHau-E8NnnVQB--PtekVc4rEprkgYk-yxRe-pur4yrmH-pZ-s-Y6Trg4CBB-b-5FajJYWtCu-D--SjzPq-"],
            self::TEST_BANK_VOLKSBANK_EUR_CODE => ["shop_id" => "1292812928P5002", "startker_key" => "UkCZe8EghuWk95LwPuC3qNQttKCtn9-ntFyXBu-QLLb2H-9-9WK-9-U8-C-zNg7Dsp-uuRVGFCtPeEf26Hp-RBp-fT9-f-XxpRWA", "outcome_key" => "DKzP-rxgh-kqfCSFBkG-Jth--sNyanV3ZJn-mCQdgeQ-2wcFYLzTAtYF-Bq-Jdug-jD94hp6cpnStjqPJNSss-Euzvb-XbDsT--n"],
            self::TEST_BANK_OTP_SERBIA_RSD => ["shop_id" => "80729SE99999999", "startker_key" => "YK76Fkqheec4dDKAmESLp---esPbwFj--2tkJHr73KDxtgpX-Lu6--vuGFXYRNLQXXvVFh96sDSvew-rpY-kAmQzZMDdCM-qnfs-", "outcome_key" => "aph3-LxfpVvPe3nU3atEg4G4-Sn-uXc-F6-g22keKgMP6TZPMY4AYcbCWjsBN2anJ--zDpRGYEBTaG-WkjtJAGL-n4ShNQUKsENX"],
            self::TEST_BANK_UNICREDITSERBIA_RSD => ["shop_id" => "80729IG99999999", "startker_key" => "Rq8-zSPTE3C-VTDFS-Jus--PUJP--7Uy2gA-bpZLp6eA-H-4tdcMwq62fn-P-z698fh4MJuG6HE9wErKzNY-xcfn-YR-9UbQKvS-", "outcome_key" => "hfh39hUQNvmMgSMqjSw-P-K4Kuk-TpypNWt--p-tdWN2gy2A8Q7U-pb-fFFg-49eBt-d2t-gWRAYqN-2-mHBzgDFq7DBZmDn--9v"],
            self::TEST_BANK_UNICREDITHUNGARY_HUF => ["shop_id" => "80729IF99999901", "startker_key" => "VtsNyG-4yj9kW6qEBZcyL-Jd-rPfe-NDaezsG-zJ3F-fup-Wxnw5WPY-uMc-YGT-x-jqZTm-SgdMCN-DXPD6DLKKFuP-KyH-Fa-r", "outcome_key" => "3-SRHtgzBTyP8wM9TaquZA-sJJn-wYxQdehGQXnBA-XXHP4dp73bW5q--UxFtMEREEq-G6vnvCWVPmN-bnp2tLFJEDMcDY-baB4G"],
            self::TEST_BANK_CREDINSALBANIA_ALL => ["shop_id" => "80729II99999901", "startker_key" => "Cqh-Y8y84N-zZw--j-DNQBcTRNu-mZ-xyHyNZjD-3H9VS96aSbNThXG--NztA-LThrEa--U4RpAN6H--J--rXR-QdWaM-y3-etG-", "outcome_key" => "-6v-dvKGH--6--aUF6ryPy8LET8UN-55n-k--gMFDKEsy-Z-c-e3-y-C8jB4XneDQtvCrpDF2WKC-ALTTE-QtdRCJZ4VB4R6xLfG"],
        ];


        // Description for admin area, woocommerce payments list
        $this->method_description = '<div class="updated inline notice is-dismissible"><table>';
        $this->method_description .= '<tr><td><img src="' . WC_GATEWAY_VPOSSIA_LOGO . '" style="max-height: 150px"></td></tr>';
        $this->method_description .= '<tr><td><span style="font-size: 1.2em;">' . $method_description . '</span></td></tr>';
        $this->method_description .= '<tr><td><img src="' . WC_GATEWAY_VPOSSIA_LOGO_CARDS . '" style="max-height: 150px"></td></tr>';
        $this->method_description .= '<tr><td>';

        if ('yes' === $this->get_option('testmode')) {
            $this->method_description .= '<button disabled="disabled" class="button-primary">' . __('VPOS portal', 'woocommerce-vpossia-payment-gateway') . '</button>&nbsp;&nbsp;';
        } else {
            $this->method_description .= '<button class="button-primary"><a  href="' . WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_PRODUCTION . '" target="_blank" style="color: antiquewhite; text-decoration: none;">' . __('VPOS portal', 'woocommerce-vpossia-payment-gateway') . '</a></button>&nbsp;&nbsp;';
        }

        if (get_locale() == 'it_IT') {
            $this->method_description .= '<button class="button-primary" ><a href="' . WC_GATEWAY_VPOSSIA_PATH_DOCS . 'readme_it-IT.pdf" target="_blank" style="color: antiquewhite; text-decoration: none;">' . __('Docs', 'woocommerce-vpossia-payment-gateway') . '</a></button>';
        } else {
            $this->method_description .= '<button class="button-primary"><a href="' . WC_GATEWAY_VPOSSIA_PATH_DOCS . 'readme_en-GB.pdf" target="_blank" style="color: antiquewhite; text-decoration: none;">' . __('Docs', 'woocommerce-vpossia-payment-gateway') . '</a></button>';
        }
        $this->method_description .= '</td></tr>';
        $this->method_description .= '</table></div>';

        //$this->icon = WP_PLUGIN_URL . '/' . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/logo_admin.png';
        $this->icon = WC_GATEWAY_VPOSSIA_LOGO_CARDS;
        $this->debug_email = get_option('admin_email');
        $this->available_countries = array ('AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR', 'IO', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CG', 'CD', 'CK', 'CR', 'CI', 'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MO', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI', 'NE', 'NG', 'NU', 'NF', 'MK', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'SS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU', 'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW');
        //$this->available_currencies = (array) apply_filters('woocommerce_gateway_vpossia_available_currencies', array ('EUR','CLP' ,'NOK','USD','RON'));
        //$this->available_currencies = (array) apply_filters('woocommerce_gateway_vpossia_available_currencies', array ('CZK', 'EUR', 'GBP', 'NOK', 'RON', 'RSD', 'ALL','HUF'));


        $this->available_currencies = (array) apply_filters('woocommerce_gateway_vpossia_available_currencies', array ('CZK', 'EUR', 'GBP', 'NOK', 'RON', 'RSD', 'ALL', 'HUF', 'USD'));


        // Supported functionality
        $this->supports = array (
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        if (!is_admin()) {
            $this->setup_constants();
        }

        // Setup default merchant data.
        $this->shop_id = $this->get_option('shop_id');
        $this->starter_key = $this->get_option('starter_key');
        $this->outcome_key = $this->get_option('outcome_key');
        $this->shop_email = $this->get_option('shop_email');
        $this->url = $this->get_option('url');
        $this->accountingMode = $this->get_option('accounting');
        $this->language = $this->get_option('language'); //VPOS language

        /**
         * The URL to which the user is to be sent in case the transaction is successfully completed (URLDONE)
         *
         * Gestire gli url in maniera corretta per poter richiamare le funzioni delle azioni sotto impostato
         * es. add_action('woocommerce_receipt_vpossia', array ($this, 'receipt_page'));
         */
        $this->url_done = add_query_arg('wc-api', 'WC_Gateway_VPosSia', home_url('/'));

        /**
         * The URL to be used by the @POS system in order to notify the store of the outcome of the completed transaction (URLMS)
         */
        $this->url_ums = add_query_arg('wc-api', 'gateway_vpossia_success', home_url('/'));

        $this->send_debug_email = 'yes' === $this->get_option('send_debug_email');
        $this->description = $this->makeDescriptionCheckoutPage();

        //
        $this->enabled = $this->get_option('enabled');
        /**
         * Se abilitato dall'utente verifico se posso usarlo in base alle regole di validazione
         */
        if ($this->enabled && !$this->is_valid_for_use()) {
            $this->enabled = 'no';
        }
        $this->enable_logging = 'yes' === $this->get_option('enable_logging');

        // Setup the test data, if in test mode.
        if ('yes' === $this->get_option('testmode')) {
            $this->shop_id = $this->get_option('testmode_shop_id');
            $this->url = $this->get_option('testmode_url');
            $this->starter_key = $this->get_option('testmode_starter_key');
            $this->outcome_key = $this->get_option('testmode_outcome_key');
        } else {
            $this->send_debug_email = FALSE;
        }

        /**
         * Action called in checkout page for create personalized components
         */
        add_action('woocommerce_receipt_vpossia', array ($this, 'receipt_page'));


        add_action('admin_enqueue_scripts', array ($this, 'my_enqueue'));

        /**
         * Per le azioni da richiamare tramite URL API dall'ending point usare
         * - prefisso "woocommerce_api_"
         * - nome metodho "test_metod"
         *
         * Si truaduce in public function woocommerce_api_test_metod();
         */
        add_action('woocommerce_api_gateway_vpossia_success', array ($this, 'gateway_vpossia_success'));
        // add_action('woocommerce_is_order_received_page', array ($this, 'gateway_vpossia_is_order_received_page'));

        //Only admin Save setting
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array ($this, 'process_admin_options'));
            add_action('admin_notices', array ($this, 'admin_notices'));
        } else {
            if (is_order_received_page()) {
                $this->gateway_vpossia_is_order_received_page(TRUE);
            }

        }
    }

    function my_enqueue ($hook)
    {
        // Only add to the edit.php admin page.
        // See WP docs.
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        //echo '"<script type="text/javascript">alert("CIAO");</script>"';
        wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . '/js/admin.js');
    }

    /**
     *  Show possible admin noticespayment_method
     *
     */
    public function admin_notices ()
    {
        echo $this->display_errors();
        if ('yes' !== $this->get_option('enabled')) {
            return;
        }
        echo $this->getAdminNotices();
    }

    public function process_admin_options ()
    {
        $this->init_settings();

        $ff = $this->get_form_fields();
        $post_data = $this->get_post_data();
        //Extract Test bank
        /*foreach ($ff as $key => $field) {
          if ('title' !== $this->get_field_type($field) && $key==self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN) {
            $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN] = $this->get_field_value(self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN, $field,$post_data);
            $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["shop_id"];
            $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_STARTER_KEY] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["startker_key"];
            $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_OUTCOME_KEY] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["outcome_key"];
            break;
          }
        }*/

        foreach ($ff as $key => $field) {
            if ('title' !== $this->get_field_type($field)) {
                try {

                    //Imposta i valori sempre fissi come da costanti così preveniamo eventuali problemi di modifica FORM da parte dell'utente e se dovranno essere modfiicati nel corso del tempo basterà avviare il salvataggio dei settings per avere i valori aggiornati.
                    if ($key == self::ADM_SETTINGS_FFN_TESTMODE_URL) {
                        $this->settings[$key] = WC_GATEWAY_VPOSSIA_TESTMODE_URL;
                        /*} elseif ($key == self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID) {
                           // $this->settings[$key] = WC_GATEWAY_VPOSSIA_TESTMODE_SHOPIP;
                        } elseif ($key == self::ADM_SETTINGS_FFN_TESTMODE_STARTER_KEY) {
                          //  $this->settings[$key] = WC_GATEWAY_VPOSSIA_TESTMODE_STARTER_KEY;
                        } elseif ($key == self::ADM_SETTINGS_FFN_TESTMODE_OUTCOME_KEY) {
                           // $this->settings[$key] = WC_GATEWAY_VPOSSIA_TESTMODE_OUTCOME_KEY;*/
                    } elseif ($key == self::ADM_SETTINGS_FFN_URL) {
                        $this->settings[$key] = WC_GATEWAY_VPOSSIA_PRODUCTIONMODE_URL;
                    } elseif ($key == self::ADM_SETTINGS_FFN_SHOP_EMAIL) {
                        $emlValue = $this->get_field_value($key, $field, $post_data);
                        if (trim($emlValue) !== "" && !filter_var($emlValue, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception(sprintf(__('Insert a valid Shop-Email address. The value "%s" is not valid', 'woocommerce-vpossia-payment-gateway'), $emlValue));
                        } else {
                            $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                        }
                    } elseif ($key == self::ADM_SETTINGS_FFN_URLMS_HEADER) {
                        $urlmsHeaderValue = $this->get_field_value($key, $field, $post_data);
                        //$regex = "/^[a-zA-Z0-9\.\-_]+$/";
                        $regex = "/^[a-zA-Z0-9\.\-_ =,;\/@%()&amp;]+$/";
                        if (!empty($urlmsHeaderValue) && !preg_match($regex, $urlmsHeaderValue)) {
                            throw new Exception(sprintf(__('Insert a valid URLMS Header. The value "%s" is not valid', 'woocommerce-vpossia-payment-gateway'), $urlmsHeaderValue));
                        } else {
                            $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                        }


                    } else {
                        $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                    }


                    $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["shop_id"];
                    $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_STARTER_KEY] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["startker_key"];
                    $this->settings[self::ADM_SETTINGS_FFN_TESTMODE_OUTCOME_KEY] = $this->testBank[$this->settings[self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN]]["outcome_key"];
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
        }
        return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    /**
     * Returns html code, after select payment tab on checkout page
     *
     * @return string;
     */
    protected function makeDescriptionCheckoutPage ()
    {
        //return '<span class="vpossia-logo-cards" style="display:block; background: transparent url(' . WC_GATEWAY_VPOSSIA_LOGO_CARDS . ') no-repeat; padding-top:40px">'. __('For fast and safe online payments with VISA and Mastercard cards issued by other banking institutions.', 'woocommerce-vpossia-payment-gateway').'</span>';
        return __('For fast and safe online payments with VISA and Mastercard cards issued by other banking institutions.', 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Add notice to group
     *
     * @param $groupType
     * @param $message
     */
    private function addGroupedNotice ($groupType, $message)
    {
        if (empty($this->groupedNotices[$groupType])) {
            $this->groupedNotices[$groupType] = [];
        }
        $this->groupedNotices[$groupType][] = $message;
    }

    /**
     * Return HTML code for grouped notices
     *
     * @param        $groupType
     * @param string $title
     *
     * @return string|string[]
     */
    private function getGroupedNotice ($groupType, $title = WC_GATEWAY_VPOSSIA_TITLE)
    {

        if (empty($this->groupedNotices[$groupType])) {
            return "";
        }
        $html = "<div class=\"notice  #type#\"><h3>$title</h3><p>#message#</p></div>";
        $html = str_replace("#type#", $groupType, $html);
        $html = str_replace("#message#", implode("<br>", $this->groupedNotices[$groupType]), $html);

        return $html;
    }

    /**
     *  Show admin notices
     */
    public function getAdminNotices ()
    {
        $html = "";
        if ('yes' !== $this->get_option('enabled')) {
            /**
             * If not active no message shown
             */
            return $html;
        }


        /*$emlValue = $this->get_option('shop_email');
        if (!filter_var($emlValue, FILTER_VALIDATE_EMAIL)) {
            $this->addGroupedNotice(self::NOTICES_TYPE_ERROR, sprintf(__('%s, requires a "Shop E-Mail" to work.', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE));
        }*/

        if ('yes' !== $this->get_option('testmode')) {
            if (empty($this->shop_id)) {
                $this->addGroupedNotice(self::NOTICES_TYPE_ERROR, sprintf(__('%s, requires a "Shop code" to work.', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE));
            }
            if (empty($this->starter_key)) {
                $this->addGroupedNotice(self::NOTICES_TYPE_ERROR, sprintf(__('%s, requires a "Starter Key" to work.', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE));
            }
            if (empty($this->outcome_key)) {
                $this->addGroupedNotice(self::NOTICES_TYPE_ERROR, sprintf(__('%s, requires a "Outcome Key" to work.', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE));
            }
        }
        $html .= $this->getGroupedNotice(self::NOTICES_TYPE_ERROR);

        return $html;
    }

    /**
     * Get readonly for html input
     *
     * @return array
     */
    private function getReadOnly ()
    {
        return ["readonly" => "readonly"];
    }


    /**
     * Initialise Gateway Settings Form Fields in admin page
     *
     * @since 1.0.0
     */
    public function init_form_fields ()
    {


        $this->form_fields = array (
            'pp_general_title' => array (
                'title' => sprintf('<span style="text-decoration: underline;color:brown;">' . __('General parameters', 'woocommerce-vpossia-payment-gateway') . '<span>'),
                'type' => 'title'
            ),
            'enabled' => array (
                'title' => sprintf(__('%s', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE),
                'label' => __('Enable/Disable', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'checkbox',
                'description' => __('This controls whether or not this gateway is enabled within WooCommerce.', 'woocommerce-vpossia-payment-gateway'),
                'default' => 'yes',
                'desc_tip' => FALSE,
            ),
            'accounting' => array (
                'title' => __('Accounting', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'select',
                'css' => 'width: 350px;',
                'description' => sprintf(__('If you choose deferred accounting, we remind you to confirm the authorization on the <a href="%1$s" target="_blank">VPOS portal</a>', 'woocommerce-vpossia-payment-gateway'), ('yes' === $this->get_option('testmode') ? "#" : WC_GATEWAY_VPOSSIA_LINK_MERCHANT_PORTAL_PRODUCTION)),
                'desc_tip' => __('Select accounting desidered', 'woocommerce-vpossia-payment-gateway'),
                'options' => array (
                    "D" => __('Deferred', 'woocommerce-vpossia-payment-gateway'),
                    "I" => __('Immediate', 'woocommerce-vpossia-payment-gateway')
                )
            ),
            'shop_email' => array (
                'title' => __('Shop E-Mail', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'description' => __('It contains the e-mail address to which the transaction results are to be sent. If it is not present, the one present in the store configuration data will be used.', 'woocommerce-vpossia-payment-gateway'),
                'default' => '',
            ),
            'language' => array (
                'title' => __('Language', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'select',
                'css' => 'width: 350px;',
                'description' => __('Language', 'woocommerce-vpossia-payment-gateway'),
                'desc_tip' => __('Select the language of the VPOS.', 'woocommerce-vpossia-payment-gateway'),
                'options' => array (
                    "AL" => __('Albanian', 'woocommerce-vpossia-payment-gateway'),
                    "DE" => __('German', 'woocommerce-vpossia-payment-gateway'),
                    "EL" => __('Hellenic', 'woocommerce-vpossia-payment-gateway'),
                    "EN" => __('English', 'woocommerce-vpossia-payment-gateway'),
                    "HU" => __('Hungarian', 'woocommerce-vpossia-payment-gateway'),
                    "IT" => __('Italian', 'woocommerce-vpossia-payment-gateway'),
                    "SR" => __('Serbian', 'woocommerce-vpossia-payment-gateway'),
                    "SC" => __('Serbian Cyrillic', 'woocommerce-vpossia-payment-gateway'),
                    "SK" => __('Slovakian', 'woocommerce-vpossia-payment-gateway'),
                ),
                'default' => 'EN'
            ),
            'urlms_header' => array (
                'title' => __('URLMS Header', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'description' => __('List of parameters to be added to the urlms header, if required by the merchant. Admitted characters include letters, numbers, “.”, “-“, “_”, “=”, “.”, “;”, “/”, “@”, “%”, “(”, “)”, “&amp;”. The regular expression [a-zA-Z0-9\.\-_ =,;\/@%()&amp;] is applied.', 'woocommerce-vpossia-payment-gateway'),
                'default' => '',
            ),
            'pp_sandox_title' => array (
                'title' => sprintf('<span style="text-decoration: underline;color:brown;">' . __('Test server settings', 'woocommerce-vpossia-payment-gateway') . '<span>'),
                'type' => 'title'
            ),
            'testmode' => array (
                'title' => __('Enable TEST Server', 'woocommerce-vpossia-payment-gateway'),
                'label' => __('Enable/Disable', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            self::ADM_SETTINGS_FFN_TESTMODE_URL => array (
                'title' => __('Gateway URL, TEST server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => WC_GATEWAY_VPOSSIA_TESTMODE_URL,
                'custom_attributes' => $this->getReadOnly()
            ),

            self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID_DROPDOWN => array (
                'title' => __('Select your bank', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'select',
                'css' => 'width: 350px;',
                //'description' => __('Select your bank', 'woocommerce-vpossia-payment-gateway'),
                //'desc_tip' => __('Select your bank', 'woocommerce-vpossia-payment-gateway'),
                'options' => array (
                    self::TEST_BANK_SIA_EUR_CODE => "SIA",
                    self::TEST_BANK_CREDEM_EUR_CODE => "Credem",
                    self::TEST_BANK_BANCODESIO_EUR_CODE => "Banco Desio",
                    self::TEST_BANK_SPARKASSE_EUR_CODE => "Sparkasse",
                    self::TEST_BANK_RAFFEISEN_EUR_CODE => "Raffeisen",
                    self::TEST_BANK_VOLKSBANK_EUR_CODE => "Volksbank",
                    self::TEST_BANK_OTP_SERBIA_RSD => "OTP Serbia",
                    self::TEST_BANK_UNICREDITSERBIA_RSD => "Unicredit Serbia",
                    self::TEST_BANK_UNICREDITHUNGARY_HUF => "Unicredit Hungary",
                    self::TEST_BANK_CREDINSALBANIA_ALL => "Credins Albania",
                ),
                'default' => self::TEST_BANK_SIA_EUR_CODE
            ),
            self::ADM_SETTINGS_FFN_TESTMODE_SHOP_ID => array (
                'title' => __('Shop code, TEST server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => $this->testBank [self::TEST_BANK_SIA_EUR_CODE]["shop_id"],
                'custom_attributes' => $this->getReadOnly()
            ),
            self::ADM_SETTINGS_FFN_TESTMODE_STARTER_KEY => array (
                'title' => __('Starter Key, TEST server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => $this->testBank [self::TEST_BANK_SIA_EUR_CODE]["startker_key"],
                'custom_attributes' => $this->getReadOnly()
            ),
            self::ADM_SETTINGS_FFN_TESTMODE_OUTCOME_KEY => array (
                'title' => __('Outcome API Key, TEST server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => $this->testBank [self::TEST_BANK_SIA_EUR_CODE]["outcome_key"],
                'custom_attributes' => $this->getReadOnly()
            ),

            /**
             * Production fields
             */
            'pp_production_title' => array (
                'title' => sprintf('<span style="text-decoration: underline;color:brown;">' . __('Production server settings', 'woocommerce-vpossia-payment-gateway') . '<span>'),
                'type' => 'title'
            ),
            self::ADM_SETTINGS_FFN_URL => array (
                'title' => __('Gateway URL, PRODUCTION server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => WC_GATEWAY_VPOSSIA_PRODUCTIONMODE_URL,
                'custom_attributes' => $this->getReadOnly()
            ),
            'shop_id' => array (
                'title' => __('Shop code, PRODUCTION server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'description' => __('Insert the shop code provided by SIA', 'woocommerce-vpossia-payment-gateway')
            ),
            'starter_key' => array (
                'title' => __('Starter Key, PRODUCTION server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'description' => __('Insert the starter key provided by SIA', 'woocommerce-vpossia-payment-gateway')
            ),
            'outcome_key' => array (
                'title' => __('Outcome API Key, PRODUCTION server', 'woocommerce-vpossia-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'description' => __('Insert the outcome API key provided by SIA', 'woocommerce-vpossia-payment-gateway')
            ),
            //
            'pp_developer_title' => array (
                'title' => sprintf('<span style="text-decoration: underline;color:brown;">' . __('Advanced settings', 'woocommerce-vpossia-payment-gateway') . '<span>'),
                'type' => 'title'
            )
        );


        /**
         * Set Logging Field
         */
        $label = __('Enable transaction logging for gateway.', 'woocommerce-vpossia-payment-gateway');

        if (defined('WC_LOG_DIR')) {
            $log_url = add_query_arg('tab', 'logs', add_query_arg('page', 'wc-status', admin_url('admin.php')));
            $log_key = 'woocommerce-vpossia-payment-gateway-' . sanitize_file_name(wp_hash('woocommerce-vpossia-payment-gateway-')) . '-log';
            $log_url = add_query_arg('log_file', $log_key, $log_url);
            $label .= ' | ' . sprintf(__('%1$sView Log%2$s', 'woocommerce-vpossia-payment-gateway'), '<a href="' . esc_url($log_url) . '">', '</a>');
        }

        $this->form_fields["enable_logging"] = array (
            'title' => __('Enable Logging', 'woocommerce-vpossia-payment-gateway'),
            'label' => $label,
            'type' => 'checkbox',
            'default' => 'no'
        );
    }

    /**
     * Override Fields if TEST Mode enabled
     *
     * @since 1.0.0
     */
    public function add_testmode_admin_settings_notice ()
    {
        // $this->form_fields['shop_id']['description'] .= ' <strong>' . __('Sandbox Merchant ID currently in use', 'woocommerce-vpossia-payment-gateway') . ' ( ' . esc_html($this->shop_id) . ' ).</strong>';
    }

    /**
     * is_valid_for_use()
     *
     * Check if this gateway is enabled and available in the base currency being traded with.
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_valid_for_use ()
    {
        $is_available = FALSE;
        $is_available_currency = in_array(get_woocommerce_currency(), $this->available_currencies);

        //Check if the store currency is present in the list of available currencies
        if ($is_available_currency) {
            if ('yes' === $this->get_option('testmode') || (
                    !empty($this->shop_id) &&
                    !empty($this->url) &&
                    !empty($this->starter_key) &&
                    !empty($this->outcome_key))
            ) {
                $is_available = TRUE;
            }
        }
        return $is_available;
    }

    /**
     * Admin Panel Options
     * Override basic function, block panel loading and show error message if specific conditions verified
     * - Currency settings wrong
     *
     * @since 1.0.0
     */
    public function admin_options ()
    {
        if (in_array(get_woocommerce_currency(), $this->available_currencies)) {
            parent::admin_options();
        } else {
            ?>
          <h3><?php sprintf(__('%s', 'woocommerce-vpossia-payment-gateway'), WC_GATEWAY_VPOSSIA_TITLE); ?></h3>

          <div class="inline error"><p><strong><?php __('Gateway Disabled', 'woocommerce-vpossia-payment-gateway'); ?></strong>
                  <?php /* translators: 1: a href link 2: closing href */
                  echo sprintf(__('Choose one of this currency:<ol><li>%1$s</li></ol> in %2$sGeneral Settings%3$s to enable the VPOS Sia Gateway.', 'woocommerce-vpossia-payment-gateway')
                      , implode("</li><li>", $this->available_currencies), '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=general')) . '">', '</a>'); ?></p></div>
            <?php
        }
    }

    /**
     * Return HTML "hidden" input
     *
     * @param $name
     * @param $value
     *
     * @return string
     */
    private function getInputFormHTML ($name, $value)
    {
        return '<input type="hidden" name="' . esc_attr($name) . '" value="' . utf8_encode(esc_attr($value)) . '" />';
    }

    /**
     * Create Array Args from data_to_send value
     *
     * @param $data_to_send
     *
     * @return array
     */
    private function createArrayArgs ($data_to_send)
    {
        $posVPosSia_args_array = [];
        foreach ($data_to_send as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $option) {
                    $posVPosSia_args_array[] = $this->getInputFormHTML($key, $option);
                }
            } else {
                $posVPosSia_args_array[] = $this->getInputFormHTML($key, $value);
            }
        }
        return $posVPosSia_args_array;
    }

    /**
     * Return string encrypted
     *
     * @param WC_Order|WC_Order_Refund $order
     *
     * @return string
     */
    private function generate3DSDATA ($order)
    {
        $ds = new \stdClass();

        // Check address (Billing abd Shipping) match
        if (
            ($order->get_billing_first_name() == $order->get_shipping_first_name()) &&
            ($order->get_billing_last_name() == $order->get_shipping_last_name()) &&
            ($order->get_billing_company() == $order->get_shipping_company()) &&
            ($order->get_billing_country() == $order->get_shipping_country()) &&
            ($order->get_billing_address_1() == $order->get_shipping_address_1()) &&
            ($order->get_billing_address_2() == $order->get_shipping_address_2()) &&
            ($order->get_billing_postcode() == $order->get_shipping_postcode()) &&
            ($order->get_billing_city() == $order->get_shipping_city())
        ) {
            $ds->addrMatch = "Y";
        } else {
            $ds->addrMatch = "N";
        }

        //
        $ds->acctID = $order->get_customer_id();

        //
        $ds->billAddrCity = $order->get_billing_city();
        $ds->billAddrCountry = $this->getIsoCodeCountry($order->get_billing_country());
        $ds->billAddrLine1 = $order->get_billing_address_1();
        $ds->billAddrPostCode = $order->get_billing_postcode();

        //
        if ($ds->addrMatch == "N") {
            $ds->shipAddrCity = $order->get_shipping_city();
            $ds->shipAddrCountry = $this->getIsoCodeCountry($order->get_shipping_country());
            $ds->shipAddrLine1 = $order->get_shipping_address_1();
            $ds->shipAddrPostCode = $order->get_shipping_postcode();
        }

        $JSON = json_encode($ds);

        return $this->encrypt($JSON, $this->outcome_key);
    }

    public function encrypt ($json, $password)
    {
        $method = "AES-128-CBC";
        $key = $this->substr($password, 0, 16);

        $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

        $ciphertext = openssl_encrypt($json, $method, $key, OPENSSL_RAW_DATA, $iv);

        $result = base64_encode($ciphertext);

        return $result;
    }

    private function substr ($str, $start, $length = FALSE, $encoding = 'utf-8')
    {
        if (is_array($str))
            return FALSE;
        if (function_exists('mb_substr'))
            return mb_substr($str, intval($start), ($length === FALSE ? $this->strlen($str) : intval($length)), $encoding);
        return substr($str, $start, ($length === FALSE ? strlen($str) : intval($length)));
    }

    private function strlen ($str)
    {
        if (is_array($str))
            return FALSE;
        if (function_exists('mb_strlen'))
            return mb_strlen($str, 'utf-8');
        return strlen($str);
    }

    private function getIsoCodeCountry ($isoCodeAlfa)
    {
        $isoCode = "";

        switch ($isoCodeAlfa) {
            case "AD":
                $isoCode = "020";
                break;
            case "AE":
                $isoCode = "784";
                break;
            case "AF":
                $isoCode = "004";
                break;
            case "AG":
                $isoCode = "028";
                break;
            case "AI":
                $isoCode = "660";
                break;
            case "AL":
                $isoCode = "008";
                break;
            case "AM":
                $isoCode = "051";
                break;
            case "AO":
                $isoCode = "024";
                break;
            case "AQ":
                $isoCode = "010";
                break;
            case "AR":
                $isoCode = "032";
                break;
            case "AS":
                $isoCode = "016";
                break;
            case "AT":
                $isoCode = "040";
                break;
            case "AU":
                $isoCode = "036";
                break;
            case "AW":
                $isoCode = "533";
                break;
            case "AX":
                $isoCode = "248";
                break;
            case "AZ":
                $isoCode = "031";
                break;
            case "BA":
                $isoCode = "070";
                break;
            case "BB":
                $isoCode = "052";
                break;
            case "BD":
                $isoCode = "050";
                break;
            case "BE":
                $isoCode = "056";
                break;
            case "BF":
                $isoCode = "854";
                break;
            case "BG":
                $isoCode = "100";
                break;
            case "BH":
                $isoCode = "048";
                break;
            case "BI":
                $isoCode = "108";
                break;
            case "BJ":
                $isoCode = "204";
                break;
            case "BL":
                $isoCode = "652";
                break;
            case "BM":
                $isoCode = "060";
                break;
            case "BN":
                $isoCode = "096";
                break;
            case "BO":
                $isoCode = "068";
                break;
            case "BQ":
                $isoCode = "535";
                break;
            case "BR":
                $isoCode = "076";
                break;
            case "BS":
                $isoCode = "044";
                break;
            case "BT":
                $isoCode = "064";
                break;
            case "BV":
                $isoCode = "074";
                break;
            case "BW":
                $isoCode = "072";
                break;
            case "BY":
                $isoCode = "112";
                break;
            case "BZ":
                $isoCode = "084";
                break;
            case "CA":
                $isoCode = "124";
                break;
            case "CC":
                $isoCode = "166";
                break;
            case "CD":
                $isoCode = "180";
                break;
            case "CF":
                $isoCode = "140";
                break;
            case "CG":
                $isoCode = "178";
                break;
            case "CH":
                $isoCode = "756";
                break;
            case "CI":
                $isoCode = "384";
                break;
            case "CK":
                $isoCode = "184";
                break;
            case "CL":
                $isoCode = "152";
                break;
            case "CM":
                $isoCode = "120";
                break;
            case "CN":
                $isoCode = "156";
                break;
            case "CO":
                $isoCode = "170";
                break;
            case "CR":
                $isoCode = "188";
                break;
            case "CU":
                $isoCode = "192";
                break;
            case "CV":
                $isoCode = "132";
                break;
            case "CW":
                $isoCode = "531";
                break;
            case "CX":
                $isoCode = "162";
                break;
            case "CY":
                $isoCode = "196";
                break;
            case "CZ":
                $isoCode = "203";
                break;
            case "DE":
                $isoCode = "276";
                break;
            case "DJ":
                $isoCode = "262";
                break;
            case "DK":
                $isoCode = "208";
                break;
            case "DM":
                $isoCode = "212";
                break;
            case "DO":
                $isoCode = "214";
                break;
            case "DZ":
                $isoCode = "012";
                break;
            case "EC":
                $isoCode = "218";
                break;
            case "EE":
                $isoCode = "233";
                break;
            case "EG":
                $isoCode = "818";
                break;
            case "EH":
                $isoCode = "732";
                break;
            case "ER":
                $isoCode = "232";
                break;
            case "ES":
                $isoCode = "724";
                break;
            case "ET":
                $isoCode = "231";
                break;
            case "FI":
                $isoCode = "246";
                break;
            case "FJ":
                $isoCode = "242";
                break;
            case "FK":
                $isoCode = "238";
                break;
            case "FM":
                $isoCode = "583";
                break;
            case "FO":
                $isoCode = "234";
                break;
            case "FR":
                $isoCode = "250";
                break;
            case "GA":
                $isoCode = "266";
                break;
            case "GB":
                $isoCode = "826";
                break;
            case "GD":
                $isoCode = "308";
                break;
            case "GE":
                $isoCode = "268";
                break;
            case "GF":
                $isoCode = "254";
                break;
            case "GG":
                $isoCode = "831";
                break;
            case "GH":
                $isoCode = "288";
                break;
            case "GI":
                $isoCode = "292";
                break;
            case "GL":
                $isoCode = "304";
                break;
            case "GM":
                $isoCode = "270";
                break;
            case "GN":
                $isoCode = "324";
                break;
            case "GP":
                $isoCode = "312";
                break;
            case "GQ":
                $isoCode = "226";
                break;
            case "GR":
                $isoCode = "300";
                break;
            case "GS":
                $isoCode = "239";
                break;
            case "GT":
                $isoCode = "320";
                break;
            case "GU":
                $isoCode = "316";
                break;
            case "GW":
                $isoCode = "624";
                break;
            case "GY":
                $isoCode = "328";
                break;
            case "HK":
                $isoCode = "344";
                break;
            case "HM":
                $isoCode = "334";
                break;
            case "HN":
                $isoCode = "340";
                break;
            case "HR":
                $isoCode = "191";
                break;
            case "HT":
                $isoCode = "332";
                break;
            case "HU":
                $isoCode = "348";
                break;
            case "ID":
                $isoCode = "360";
                break;
            case "IE":
                $isoCode = "372";
                break;
            case "IL":
                $isoCode = "376";
                break;
            case "IM":
                $isoCode = "833";
                break;
            case "IN":
                $isoCode = "356";
                break;
            case "IO":
                $isoCode = "086";
                break;
            case "IQ":
                $isoCode = "368";
                break;
            case "IR":
                $isoCode = "364";
                break;
            case "IS":
                $isoCode = "352";
                break;
            case "IT":
                $isoCode = "380";
                break;
            case "JE":
                $isoCode = "832";
                break;
            case "JM":
                $isoCode = "388";
                break;
            case "JO":
                $isoCode = "400";
                break;
            case "JP":
                $isoCode = "392";
                break;
            case "KE":
                $isoCode = "404";
                break;
            case "KG":
                $isoCode = "417";
                break;
            case "KH":
                $isoCode = "116";
                break;
            case "KI":
                $isoCode = "296";
                break;
            case "KM":
                $isoCode = "174";
                break;
            case "KN":
                $isoCode = "659";
                break;
            case "KP":
                $isoCode = "408";
                break;
            case "KR":
                $isoCode = "410";
                break;
            case "KW":
                $isoCode = "414";
                break;
            case "KY":
                $isoCode = "136";
                break;
            case "KZ":
                $isoCode = "398";
                break;
            case "LA":
                $isoCode = "418";
                break;
            case "LB":
                $isoCode = "422";
                break;
            case "LC":
                $isoCode = "662";
                break;
            case "LI":
                $isoCode = "438";
                break;
            case "LK":
                $isoCode = "144";
                break;
            case "LR":
                $isoCode = "430";
                break;
            case "LS":
                $isoCode = "426";
                break;
            case "LT":
                $isoCode = "440";
                break;
            case "LU":
                $isoCode = "442";
                break;
            case "LV":
                $isoCode = "428";
                break;
            case "LY":
                $isoCode = "434";
                break;
            case "MA":
                $isoCode = "504";
                break;
            case "MC":
                $isoCode = "492";
                break;
            case "MD":
                $isoCode = "498";
                break;
            case "ME":
                $isoCode = "499";
                break;
            case "MF":
                $isoCode = "663";
                break;
            case "MG":
                $isoCode = "450";
                break;
            case "MH":
                $isoCode = "584";
                break;
            case "MK":
                $isoCode = "807";
                break;
            case "ML":
                $isoCode = "466";
                break;
            case "MM":
                $isoCode = "104";
                break;
            case "MN":
                $isoCode = "496";
                break;
            case "MO":
                $isoCode = "446";
                break;
            case "MP":
                $isoCode = "580";
                break;
            case "MQ":
                $isoCode = "474";
                break;
            case "MR":
                $isoCode = "478";
                break;
            case "MS":
                $isoCode = "500";
                break;
            case "MT":
                $isoCode = "470";
                break;
            case "MU":
                $isoCode = "480";
                break;
            case "MV":
                $isoCode = "462";
                break;
            case "MW":
                $isoCode = "454";
                break;
            case "MX":
                $isoCode = "484";
                break;
            case "MY":
                $isoCode = "458";
                break;
            case "MZ":
                $isoCode = "508";
                break;
            case "NA":
                $isoCode = "516";
                break;
            case "NC":
                $isoCode = "540";
                break;
            case "NE":
                $isoCode = "562";
                break;
            case "NF":
                $isoCode = "574";
                break;
            case "NG":
                $isoCode = "566";
                break;
            case "NI":
                $isoCode = "558";
                break;
            case "NL":
                $isoCode = "528";
                break;
            case "NO":
                $isoCode = "578";
                break;
            case "NP":
                $isoCode = "524";
                break;
            case "NR":
                $isoCode = "520";
                break;
            case "NU":
                $isoCode = "570";
                break;
            case "NZ":
                $isoCode = "554";
                break;
            case "OM":
                $isoCode = "512";
                break;
            case "PA":
                $isoCode = "591";
                break;
            case "PE":
                $isoCode = "604";
                break;
            case "PF":
                $isoCode = "258";
                break;
            case "PG":
                $isoCode = "598";
                break;
            case "PH":
                $isoCode = "608";
                break;
            case "PK":
                $isoCode = "586";
                break;
            case "PL":
                $isoCode = "616";
                break;
            case "PM":
                $isoCode = "666";
                break;
            case "PN":
                $isoCode = "612";
                break;
            case "PR":
                $isoCode = "630";
                break;
            case "PS":
                $isoCode = "275";
                break;
            case "PT":
                $isoCode = "620";
                break;
            case "PW":
                $isoCode = "585";
                break;
            case "PY":
                $isoCode = "600";
                break;
            case "QA":
                $isoCode = "634";
                break;
            case "RE":
                $isoCode = "638";
                break;
            case "RO":
                $isoCode = "642";
                break;
            case "RS":
                $isoCode = "688";
                break;
            case "RU":
                $isoCode = "643";
                break;
            case "RW":
                $isoCode = "646";
                break;
            case "SA":
                $isoCode = "682";
                break;
            case "SB":
                $isoCode = "090";
                break;
            case "SC":
                $isoCode = "690";
                break;
            case "SD":
                $isoCode = "729";
                break;
            case "SE":
                $isoCode = "752";
                break;
            case "SG":
                $isoCode = "702";
                break;
            case "SH":
                $isoCode = "654";
                break;
            case "SI":
                $isoCode = "705";
                break;
            case "SJ":
                $isoCode = "744";
                break;
            case "SK":
                $isoCode = "703";
                break;
            case "SL":
                $isoCode = "694";
                break;
            case "SM":
                $isoCode = "674";
                break;
            case "SN":
                $isoCode = "686";
                break;
            case "SO":
                $isoCode = "706";
                break;
            case "SR":
                $isoCode = "740";
                break;
            case "SS":
                $isoCode = "728";
                break;
            case "ST":
                $isoCode = "678";
                break;
            case "SV":
                $isoCode = "222";
                break;
            case "SX":
                $isoCode = "534";
                break;
            case "SY":
                $isoCode = "760";
                break;
            case "SZ":
                $isoCode = "748";
                break;
            case "TC":
                $isoCode = "796";
                break;
            case "TD":
                $isoCode = "148";
                break;
            case "TF":
                $isoCode = "260";
                break;
            case "TG":
                $isoCode = "768";
                break;
            case "TH":
                $isoCode = "764";
                break;
            case "TJ":
                $isoCode = "762";
                break;
            case "TK":
                $isoCode = "772";
                break;
            case "TL":
                $isoCode = "626";
                break;
            case "TM":
                $isoCode = "795";
                break;
            case "TN":
                $isoCode = "788";
                break;
            case "TO":
                $isoCode = "776";
                break;
            case "TR":
                $isoCode = "792";
                break;
            case "TT":
                $isoCode = "780";
                break;
            case "TV":
                $isoCode = "798";
                break;
            case "TW":
                $isoCode = "158";
                break;
            case "TZ":
                $isoCode = "834";
                break;
            case "UA":
                $isoCode = "804";
                break;
            case "UG":
                $isoCode = "800";
                break;
            case "UM":
                $isoCode = "581";
                break;
            case "US":
                $isoCode = "840";
                break;
            case "UY":
                $isoCode = "858";
                break;
            case "UZ":
                $isoCode = "860";
                break;
            case "VA":
                $isoCode = "336";
                break;
            case "VC":
                $isoCode = "670";
                break;
            case "VE":
                $isoCode = "862";
                break;
            case "VG":
                $isoCode = "092";
                break;
            case "VI":
                $isoCode = "850";
                break;
            case "VN":
                $isoCode = "704";
                break;
            case "VU":
                $isoCode = "548";
                break;
            case "WF":
                $isoCode = "876";
                break;
            case "WS":
                $isoCode = "882";
                break;
            case "YE":
                $isoCode = "887";
                break;
            case "YT":
                $isoCode = "175";
                break;
            case "ZA":
                $isoCode = "710";
                break;
            case "ZM":
                $isoCode = "894";
                break;
            case "ZW":
                $isoCode = "716";
                break;
            default:
                $isoCode = "380";
                break;
        }

        return $isoCode;
    }

    /**
     * Generate the VPosSia button link on front-end
     *
     * Displayed after checkout, the user is asked to complete the order
     *
     * @since 1.0.0
     */
    public function generate_vpossia_form ($order_id)
    {
        $this->strLogmessage = PHP_EOL . "*********************************" . PHP_EOL . "BEGIN GENERATE_VPOSSIA_FORM" . PHP_EOL . "*********************************" . PHP_EOL;

        $order = wc_get_order($order_id);

        $orderkey = self::get_order_prop($order, 'order_key');
        $paymentID = ltrim($order->get_order_number(), _x('#', 'hash before order number', 'woocommerce-vpossia-payment-gateway'));
        $ordDescr = get_bloginfo('name') . ' - Order N. ' . $order->get_order_number();


        $this->strLogmessage .= "ORDER ID: $order_id " . PHP_EOL;
        $this->strLogmessage .= "ORDER KEY: $orderkey " . PHP_EOL;
        $this->strLogmessage .= "PAYMENT ID: $paymentID " . PHP_EOL;
        $this->strLogmessage .= $ordDescr . PHP_EOL;

        /**
         * Complete URL to which the customer’s browser is to be redirected once the transaction has been successfully
         * completed (it may include all the necessary parameters). The outcome parameters are appended to the selected URL. Maximum length 254 characters.
         */
        $urlDone = $this->get_return_url($order);
        $urlMs = add_query_arg('__order_id', $order_id, $this->url_ums);
        $urlmsHeader = $this->get_option('urlms_header');
        /**
         * Complete URL to which the user is to be redirected to go to the store (it may include all the necessary parameters) in case the payment process is cancelled. Maximum length 254 characters.
         */
        $urlBack = $order->get_cancel_order_url();

        $orderID = $order->get_order_number();
        $accountingMode = $this->accountingMode;
        $language = $this->language;

        /**
         * L'autorizzazione non è settabile dall'utente è deve sempre essere immediata
         */
        $authorMode = "I"; //$accountingMode;


        $amount = str_replace(array (".", ","), array ("", ""), $order->get_total());
        /**
         * Gli importi non possono iniziare con il carattere 0
         */
        while (strlen($amount)>0 && intval(substr($amount,0,1)) === 0) {
            $amount = substr($amount,1);
        }

        $currency = apply_filters('woocommerce_gateway_vpossia_currencies_number_code', $order->get_currency());
        $exponent = wc_get_price_decimals();

        $name = self::get_order_prop($order, 'billing_first_name');
        $surname = self::get_order_prop($order, 'billing_last_name');

        /**
         * The possible values for this parameter are the following: 01 – Visa
         * 02 – Mastercard
         * 04 – Maestro
         * 06 – American Express 07 – Diners
         * 08 – JCB
         * 49 – Paypass / Masterpass 80 – IBAN
         * 81 – AmazonPay
         * 82 – EnelX
         * 89 – ApplePay
         * 91 – BancomatPay (Jiffy) 92 – Paga con Postepay 94 – Postepay
         * 96 – MyBank
         * 97 – Paypal
         * A1 – GooglePay
         * CC – Credit cards
         * NC – Other payment instruments
         */
        $lockcard = NULL;

        $options = 'G'; //Insert all options without separator  Ex. GFI otherwise = NULL

        // Construct variables for post
        $this->data_to_send = [];
        $this->data_to_send['AMOUNT'] = $amount;
        $this->data_to_send['CURRENCY'] = $currency;
        //number of decimals for the currency (present only if option=x or currency not euro
        $this->data_to_send['EXPONENT'] = $exponent;
        $this->data_to_send['ORDERID'] = $orderID;
        // Merchant details
        $this->data_to_send['SHOPID'] = $this->shop_id;
        if (trim($this->shop_email) !== "" && filter_var($this->shop_email, FILTER_VALIDATE_EMAIL) !== FALSE) {
            $this->data_to_send['SHOPEMAIL'] = $this->shop_email;
        } else {
            $shopEmailFrom = apply_filters('woocommerce_email_from_address', get_option('woocommerce_email_from_address'), $this, '');
            if (trim($shopEmailFrom) !== "") {
                $this->data_to_send['SHOPEMAIL'] = $shopEmailFrom;
            }
        }

        //Customer E-Mail
        $this->data_to_send['EMAIL'] = $order->get_billing_email();

        $this->data_to_send['URLBACK'] = $urlBack;
        $this->data_to_send['URLDONE'] = $urlDone;
        $this->data_to_send['URLMS'] = $urlMs;


        $this->data_to_send['ACCOUNTINGMODE'] = $accountingMode;
        $this->data_to_send['AUTHORMODE'] = $authorMode;
        if (!empty($options)) {
            $this->data_to_send['OPTIONS'] = $options;
        }
        if (!empty($lockcard)) {
            $this->data_to_send['LOCKCARD'] = $lockcard;
        }

        $this->data_to_send['LANG'] = $language;

        $this->data_to_send['3DSDATA'] = $this->generate3DSDATA($order);
        if (!empty($urlmsHeader))
            $this->data_to_send['URLMSHEADER'] = $urlmsHeader;


        $this->data_to_send['MAC'] = $this->generateMAC_request($urlMs, $urlmsHeader, $urlDone, $orderID, $this->shop_id, $amount, $currency, $exponent, $accountingMode, $authorMode, $name, $surname, $ordDescr,
            $this->starter_key, $lockcard, $options, $this->data_to_send['3DSDATA']);

        //Extra Field
        $this->data_to_send['PLUGIN'] = WC_GATEWAY_VPOSSIA_EXTRA_TITLE;
        $this->data_to_send['PLUGINVERSION'] = WC_GATEWAY_VPOSSIA_VERSION;

        $posSia_args_array = $this->createArrayArgs($this->data_to_send);
        // Save log with all data to send
        $this->strLogmessage .= "data_to_send " . json_encode($this->data_to_send, JSON_PRETTY_PRINT) . PHP_EOL;
        $this->strLogmessage .= "*********************************" . PHP_EOL . "  END GENERATE_VPOSSIA_FORM" . PHP_EOL . "*********************************" . PHP_EOL;
        $this->log($this->strLogmessage);

        /**
         * Jump directly to external site
         *  * comment {jQuery("body").block }
         *  * uncommment {jQuery( "#submit_vpossia_payment_form" ).click();}
         *
         * Show step after checkout to confirm payment
         *  * uncomment {jQuery("body").block }
         *  * comment {jQuery( "#submit_vpossia_payment_form" ).click();}
         */
        return '<form action="' . esc_url($this->url) . '" method="post" id="pos_sia_payment_form" accept-charset="UTF-8" enctype="application/x-www-form-urlencoded">
				' . implode('', $posSia_args_array) . '
				<input type="submit" class="button-alt" id="submit_vpossia_payment_form" value="' . __('Pay via VPosSia', 'woocommerce-vpossia-payment-gateway') . '" />
				<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'woocommerce-vpossia-payment-gateway') . '</a>
				<script type="text/javascript">
					jQuery(function(){
					/*	jQuery("body").block(
							{
								message: "' . __('Thank you for your order. We are now redirecting you to VPOS SIA to make payment.', 'woocommerce-vpossia-payment-gateway') . '",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
									padding:        20,
									textAlign:      "center",
									color:          "#555",
									border:         "3px solid #aaa",
									backgroundColor:"#fff",
									cursor:         "wait"
								}
							});*/
						jQuery( "#submit_vpossia_payment_form" ).click();
					
					});
				</script>
			</form>';
    }

    /**
     * Process the payment and return the result.
     *
     * Called when click on Proceed Order
     * helpful for validate data before send reqeust to external site.
     *
     * @since 1.0.0
     */
    public function process_payment ($order_id)
    {
        $order = wc_get_order($order_id);

        /**
         * Reduce stock levels not implementd because deprecated
         */
        //$order->reduce_order_stock();

        return array (
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(TRUE),
        );
    }

    /**
     * Reciept page.
     *
     * Display text and a button to direct the user to External WebSite Gateway.
     *
     * Called after complete purchase from cart and choose This payment gateway
     *
     * @since 1.0.0
     */
    public function receipt_page ($order)
    {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with VPosSia.', 'woocommerce-vpossia-payment-gateway') . '</p>';
        echo $this->generate_vpossia_form($order);
    }

    /**
     * Obtain Parameters from QueryString
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    private function getQueryStringParameter ($key, $default = NULL)
    {
        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }
        return $default;
    }

    /**
     * Processa la risposta del gateway sia per URLM che per URLDONE
     * * Se l'ordine risulta già pagato non viene eseuita nessuna operazione
     */
    private function processGatewayResponse ($typeUrl, $orderid)
    {
        global $woocommerce;

        //$orderid = $this->getQueryStringParameter('ORDERID');

        $this->strLogmessage .= sprintf(PHP_EOL . '*********************************' . PHP_EOL);
        $this->strLogmessage .= sprintf(__('BEGIN NEW ' . $typeUrl . ' RESPONSE', 'woocommerce-vpossia-payment-gateway') . PHP_EOL . '*********************************' . PHP_EOL);
        $this->strLogmessage .= "ORDER ID: $orderid " . PHP_EOL;

        if (isset($orderid)) {
            /**
             * @var WC_Order
             */

            $order = wc_get_order($orderid);

            if ($dto = $order->get_date_paid()) {
                $this->strLogmessage .= sprintf(__('Order already paid in data: %s', 'woocommerce-vpossia-payment-gateway'), $dto->date('d/m/Y')) . PHP_EOL;
                $this->strLogmessage .= "status: " . $order->get_status() . PHP_EOL;
                header('HTTP/1.0 200 OK');
                flush();
            } else {
                $result = $this->getQueryStringParameter("RESULT"); //

                $shopid = $this->getQueryStringParameter("SHOPID");
                $authnumber = $this->getQueryStringParameter("AUTHNUMBER");  //(if the authorization is not present, the field will have the value of “NULL")
                $amount = $this->getQueryStringParameter("AMOUNT");
                $currency = $this->getQueryStringParameter("CURRENCY");
                $exponent = $this->getQueryStringParameter("EXPONENT");
                $network = $this->getQueryStringParameter("NETWORK");
                $transactionid = $this->getQueryStringParameter("TRANSACTIONID");
                $accountingmode = $this->getQueryStringParameter("ACCOUNTINGMODE");
                $authormode = $this->getQueryStringParameter("AUTHORMODE");

                $transactiontype = $this->getQueryStringParameter("TRANSACTIONTYPE");
                $issuercountry = $this->getQueryStringParameter("ISSUERCOUNTRY");
                $mac = $this->getQueryStringParameter("MAC");

                $payerid = $this->getQueryStringParameter("PAYERID");
                $payer = $this->getQueryStringParameter("PAYER");
                $payerstatus = $this->getQueryStringParameter("PAYERSTATUS");
                $hashpan = $this->getQueryStringParameter("HASHPAN");
                $iban = $this->getQueryStringParameter("IBAN");
                $accountholder = $this->getQueryStringParameter("ACCOUNTHOLDER");
                $aliasstr = $this->getQueryStringParameter("ALIASSTR");
                $pantail = $this->getQueryStringParameter("PANTAIL");
                $panexpirydate = $this->getQueryStringParameter("PANEXPIRYDATE");
                $panalias = $this->getQueryStringParameter("PANALIAS");
                $panaliasrev = $this->getQueryStringParameter("PANALIASREV");
                $panaliasexpdate = $this->getQueryStringParameter("PANALIASEXPDATE");
                $panaliastail = $this->getQueryStringParameter("PANALIASTAIL");
                $maskedpan = $this->getQueryStringParameter("MASKEDPAN");
                $acquirerbin = $this->getQueryStringParameter("ACQUIRERBIN");
                $merchantid = $this->getQueryStringParameter("MERCHANTID");
                $cardtype = $this->getQueryStringParameter("CARDTYPE");


                $chinfo = $this->getQueryStringParameter("CHINFO");
                $authcode = $this->getQueryStringParameter("AUTHCODE");
                $ahemail = $this->getQueryStringParameter("AHEMAIL");
                $ahtaxid = $this->getQueryStringParameter("AHTAXID");
                $amazonauthid = $this->getQueryStringParameter("AMAZONAUTHID");
                $amazoncaptureid = $this->getQueryStringParameter("AMAZONCAPTUREID");
                $pancode = $this->getQueryStringParameter("PANCODE");
                $trecurr = $this->getQueryStringParameter("TRECURR");
                $crecurr = $this->getQueryStringParameter("CRECURR");


                $ORDER_ID = $order->get_order_number();

                $macOutcome = $this->generateMAC_outcome(
                    $ORDER_ID, $shopid, $authnumber, $amount, $currency, $exponent, $transactionid, $accountingmode
                    , $authormode, $result, $transactiontype, $issuercountry
                    , $payerid
                    , $payer
                    , $payerstatus
                    , $hashpan
                    , $iban
                    , $accountholder
                    , $aliasstr
                    , $pantail
                    , $panexpirydate
                    , $panalias
                    , $panaliasrev
                    , $panaliasexpdate
                    , $panaliastail
                    , $maskedpan
                    , $acquirerbin
                    , $merchantid
                    , $cardtype
                    , $chinfo
                    , $authcode
                    , $ahemail
                    , $ahtaxid
                    , $amazonauthid
                    , $amazoncaptureid
                    , $pancode
                    , $trecurr
                    , $crecurr
                    , $network
                    , $this->outcome_key);

                $urlMs = add_query_arg('__order_id', $orderid, $this->url_ums);

                // Construct variables for transaction response
                $this->data_outcome = [];
                $this->data_outcome['AMOUNT'] = $amount;
                $this->data_outcome['CURRENCY'] = $currency;
                //number of decimals for the currency (present only if option=x or currency not euro
                $this->data_outcome['EXPONENT'] = $exponent;
                $this->data_outcome['NETWORK'] = $network;
                $this->data_outcome['ORDERID'] = $orderid;
                // Merchant details
                $this->data_outcome['SHOPID'] = $this->shop_id;
                //$this->data_outcome['URLBACK'] = $urlBack;
                //$this->data_outcome['URLDONE'] = $this->urldone;
                $this->data_outcome['URLMS'] = $urlMs;
                $this->data_outcome['ACCOUNTINGMODE'] = $accountingmode;
                $this->data_outcome['AUTHORMODE'] = $authormode;
                $this->data_outcome['AUTHNUMBER'] = $authnumber;
                $this->data_outcome['TRANSACTIONID'] = $transactionid;
                $this->data_outcome['TRANSACTIONTYPE'] = $transactiontype;
                $this->data_outcome['RESULT'] = $result;
                $this->data_outcome['ISSUSERCOUNTRY'] = $issuercountry;
                $this->data_outcome['MAC'] = $mac;

                $this->data_outcome['PAYERID'] = $payerid;
                $this->data_outcome['PAYER'] = $payer;
                $this->data_outcome['PAYERSTATUS'] = $payerstatus;
                $this->data_outcome['HASHPAN'] = $hashpan;
                $this->data_outcome['IBAN'] = $iban;
                $this->data_outcome['ACCOUNTHOLDER'] = $accountholder;
                $this->data_outcome['ALIASSTR'] = $aliasstr;
                $this->data_outcome['PANTAIL'] = $pantail;
                $this->data_outcome['PANEXPIRYDATE'] = $panexpirydate;
                $this->data_outcome['PANALIAS'] = $panalias;
                $this->data_outcome['PANALIASREV'] = $panaliasrev;
                $this->data_outcome['PANALIASEXPDATE'] = $panaliasexpdate;
                $this->data_outcome['PANALIASTAIL'] = $panaliastail;
                $this->data_outcome['MASKEDPAN'] = $maskedpan;
                $this->data_outcome['ACQUIRERBIN'] = $acquirerbin;
                $this->data_outcome['MERCHANTID'] = $merchantid;
                $this->data_outcome['CARDTYPE'] = $cardtype;

                $this->data_outcome["CHINFO"] = $chinfo;
                $this->data_outcome["AUTHCODE"] = $authcode;
                $this->data_outcome["AHEMAIL"] = $ahemail;
                $this->data_outcome["AHTAXID"] = $ahtaxid;
                $this->data_outcome["AMAZONAUTHID"] = $amazonauthid;
                $this->data_outcome["AMAZONCAPTUREID"] = $amazoncaptureid;
                $this->data_outcome["PANCODE"] = $pancode;
                $this->data_outcome["TRECURR"] = $trecurr;
                $this->data_outcome["CRECURR"] = $crecurr;


                $this->data_outcome['MACOUTCOME'] = $macOutcome;
                if (!empty($options)) {
                    $this->data_outcome['OPTIONS'] = $options;
                }
                if (!empty($lockcard)) {
                    $this->data_outcome['LOCKCARD'] = $lockcard;
                }

                $this->strLogmessage .= "data_outcome " . json_encode($this->data_outcome, JSON_PRETTY_PRINT) . PHP_EOL;

                if ($result == '00') {
                    if ($mac === $macOutcome) {
                        $this->handle_payment_complete($orderid, $authnumber, $transactionid);
                        header('HTTP/1.0 200 OK');
                        flush();
                    } else {
                        // QUESTO Caso che non dovrebbe mai verificarsi, se si verfica è c'è un errore o un attacco
                        //La transazione non ha il mac corretto

                        /**
                         * COME RICHIESTO DA SIA OTTOBRE 2021, Se il mac non è corretto  non annullare l'ordine ma loggare solo l'anomalia.
                         */
                        //$this->handle_payment_failed($orderid, $authnumber, $transactionid, $result, "OUTCOME MAC WRONG");
                        $this->strLogmessage .= "|**********************" . PHP_EOL . "|* ATTENZIONE!!!!" . PHP_EOL . "|** CALCOLO MAC OUTOME NON VALIDO - L'ORDINE NON E' SATO ANNUALLATO. RISPOSTA HTTP/1.0 500 Wrong MAC  " . PHP_EOL . "|**********************" . PHP_EOL;
                        header('HTTP/1.0 500 Wrong MAC');
                        flush();
                    }
                } else {
                    //Il risultato della transazione non è corretto
                    $this->handle_payment_failed($orderid, $authnumber, $transactionid, $result, $this->responseGetResultMessage($result));
                    header('HTTP/1.0 500 Wrong RESULT Code ' . $result);
                    flush();
                }
            }
        } else {
            header('HTTP/1.0 500 Wrong QUERY STRING PARAMETERS');
            flush();
        }
        $this->strLogmessage .= sprintf('*********************************' . PHP_EOL . __('END NEW ' . $typeUrl . ' RESPONSE', 'woocommerce-vpossia-payment-gateway') . PHP_EOL . '*********************************' . PHP_EOL);
        $this->log($this->strLogmessage);

        if ($typeUrl == "URLMS") {
            exit;
        }
    }

    /**
     * Return Messagge for result code from GATEWAY
     *
     * $result_code can have the following values:
     * - 00 | Success
     * - 01 | Denied by system
     * - 02 | Denied due to store configuration issues
     * - 03 | Denied due to communication issues with the authorization circuits
     * - 04 | Denied by card issuer
     * - 05 | Denied due to incorrect card number
     * - 06 | Unforeseen error during processing of request
     * - 07 | Duplicated order
     *
     * @param $result_code
     *
     * @return string
     */
    private function responseGetResultMessage ($result_code)
    {
        $strMessage = "NO message recognized for code: $result_code";
        switch ($result_code) {
            case "00":
                $strMessage = "Success";
                break;
            case "01":
                $strMessage = "Denied by system";
                break;
            case "02":
                $strMessage = "Denied due to store configuration issues";
                break;
            case "03":
                $strMessage = "Denied due to communication issues with the authorization circuits";
                break;
            case "04":
                $strMessage = "Denied by card issuer";
                break;
            case "05":
                $strMessage = "Denied due to incorrect card number";
                break;
            case "06":
                $strMessage = "Unforeseen error during processing of request";
                break;
            case "07":
                $strMessage = "Duplicated order";
                break;
            default:
                $strMessage = "NO message recognized for code: $result_code";
                break;
        }
        return $strMessage;
    }

    /**
     * Gestione pagina checkout
     * solo se $order_received ho ricevuto l'ordine, callback dal metodo di pagemento
     *
     * https://woocommerce.github.io/code-reference/hooks/hooks.html
     * https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-conditional-functions.html#source-view.223
     *
     * @param $order_received
     */
    public function gateway_vpossia_is_order_received_page ($order_received)
    {
        if ($order_received === TRUE) {
            global $wp;
            $order_id = $wp->query_vars['order-received'];
            // Get an instance of the WC_Order object
            $order = wc_get_order($order_id);

            // Set HERE your Payment Gateway ID
            if ($order->get_payment_method() == WC_GATEWAY_VPOSSIA_ID) {
                $this->processGatewayResponse("URLDONE", $order_id);
            }
        }
    }

    /**
     * API Ending Point URLMS, called when transaction it's ok or not ok from external Gateway
     */
    public function gateway_vpossia_success ()
    {
        sleep(0.3);
        $this->processGatewayResponse("URLMS", (isset($_GET["__order_id"]) ? $_GET["__order_id"] : -1));
    }

    /**
     * This function handles payment complete request and Outcome MAC it's OK
     *
     * @param $orderid
     */
    public function handle_payment_complete ($orderid, $authnumber, $transactionid)
    {
        global $woocommerce;

        /**
         * @var WC_Order
         */
        $order = wc_get_order($orderid);
        $order->payment_complete($transactionid);

        // Add order note
        $order->add_order_note(sprintf(__('%s payment completed! Auth number: %s | Transaction ID: %s', 'woocommerce-vpossia-payment-gateway'), $this->title, $authnumber, $transactionid));

        // Remove cart
        $woocommerce->cart->empty_cart();

        $debug_email = $this->get_option('debug_email', get_option('admin_email'));

        $vendor_name = get_bloginfo('name', 'display');
        $vendor_url = home_url('/');

        $subject = $this->title . " " . __('Transaction complete on your site', 'woocommerce-vpossia-payment-gateway');
        $body = sprintf(
            __("Hi,\n\n"
                . "A %s transaction has been completed on your website\n"
                . "------------------------------------------------------------\n"
                . "Site: %s \n"
                . "Order ID: %s\n"
                . "Transaction ID: %s\n"
                . "Order Status Code: %s", 'woocommerce-vpossia-payment-gateway'),
            $this->title,
            esc_html($vendor_name) . ' (' . esc_url($vendor_url) . ")",
            esc_html($orderid),
            esc_html($transactionid),
            self::get_order_prop($order, 'status')
        );


        $this->strLogmessage .= $body . PHP_EOL;

        $this->log_order_details($order);

        if ($this->send_debug_email) {
            wp_mail($debug_email, $subject, $body);
        }
    }

    /**
     * This function handles payment complete request and Outcome MAC it's WRONG
     *
     * @param $orderid
     */
    public function handle_payment_failed ($orderid, $authnumber, $transactionid, $result_code, $failed_message)
    {
        /**
         * @var WC_Order
         */
        $order = wc_get_order($orderid);

        /* translators: 1: payment status */
        $order->update_status('failed', sprintf(__('Payment via %s error.', 'woocommerce-vpossia-payment-gateway'), $this->title . " ( RESULT CODE: " . $result_code . ") ## "));

        $debug_email = $this->get_option('debug_email', get_option('admin_email'));
        $vendor_name = get_bloginfo('name', 'display');
        $vendor_url = home_url('/');

        $body = sprintf(__(
            "***** ATTENTION ****** \n" .
            "An invalid %s transaction on your website requires attention\n" .
            "------------------------------------------------------------\n" .
            "------------------------------------------------------------\n" .
            "%s \n" .
            "------------------------------------------------------------\n" .
            "------------------------------------------------------------\n" .
            "Site: %s \n" .
            "Remote IP Address: %s \n" .
            "Remote host name: %s \n" .
            "Purchase ID: %s \n" .
            "User ID: %s \n" .
            "Transaction ID: %s \n" .
            "RESULT CODE FROM GATEWAY: %s \n"
            , 'woocommerce-vpossia-payment-gateway'), $this->title, $failed_message, esc_html($vendor_name) . ' (' . esc_url($vendor_url) . ')', $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), self::get_order_prop($order, 'id'), self::get_order_prop($order, 'user_id'), $transactionid, $result_code);

        $this->strLogmessage .= ($body) . PHP_EOL;
        $this->log_order_details($order);

        $subject = $this->title . " " . __('Error Transaction on your site', 'woocommerce-vpossia-payment-gateway');

        if ($this->send_debug_email) {
            wp_mail($debug_email, $subject, $body);
        }
    }

    /**
     * Handle logging the order details.
     *
     * @since 1.4.5
     */
    public function log_order_details ($order)
    {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            $customer_id = get_post_meta($order->get_id(), '_customer_user', TRUE);
        } else {
            $customer_id = $order->get_user_id();
        }

        $details = "Order Details:"
            . PHP_EOL . 'customer id:' . $customer_id
            . PHP_EOL . 'order id:   ' . $order->get_id()
            . PHP_EOL . 'parent id:  ' . $order->get_parent_id()
            . PHP_EOL . 'status:     ' . $order->get_status()
            . PHP_EOL . 'total:      ' . $order->get_total()
            . PHP_EOL . 'currency:   ' . $order->get_currency()
            . PHP_EOL . 'key:        ' . $order->get_order_key()
            . "";

        $this->strLogmessage .= $details . PHP_EOL;
    }


    /**
     * Setup constants.
     *
     * Setup common values and messages used by the gateway.
     *
     * @since 1.0.0
     */
    public function setup_constants ()
    {
        // Create user agent string.
        define('VPSIA_SOFTWARE_NAME', 'WooCommerce');
        define('VPSIA_SOFTWARE_VER', WC_VERSION);

        define('VPSIA_MODULE_NAME', 'WoocomercePossiaPaymentGateway');
        define('VPSIA_MODULE_VER', $this->version);

        // Features
        // - PHP
        $pf_features = 'PHP ' . phpversion() . ';';

        // - cURL
        if (in_array('curl', get_loaded_extensions())) {
            define('VPSIA_CURL', '');
            $pf_version = curl_version();
            $pf_features .= ' curl ' . $pf_version['version'] . ';';
        } else {
            $pf_features .= ' nocurl;';
        }

        // Create user agent
        define('VPSIA_USER_AGENT', VPSIA_SOFTWARE_NAME . '/' . VPSIA_SOFTWARE_VER . ' (' . trim($pf_features) . ') ' . VPSIA_MODULE_NAME . '/' . VPSIA_MODULE_VER);

        // General Defines
        define('VPSIA_TIMEOUT', 15);
        define('VPSIA_EPSILON', 0.01);
    }

    /**
     * Log system processes.
     *
     * @param $message
     *
     * @since 1.0.0
     */
    public function log ($message)
    {

        if ('yes' === $this->get_option('testmode') || $this->enable_logging) {
            if (empty($this->logger)) {
                $this->logger = new WC_Logger();
            }
            $this->logger->add(str_replace(" ", "_", $this->title), $message);
        }
    }

    /**
     * amounts_equal()
     *
     * Checks to see whether the given amounts are equal using a proper floating
     * point comparison with an Epsilon which ensures that insignificant decimal
     * places are ignored in the comparison.
     *
     * eg. 100.00 is equal to 100.0001
     *
     * @param $amount1 Float 1st amount for comparison
     * @param $amount2 Float 2nd amount for comparison
     *
     * @return bool
     */
    public function amounts_equal ($amount1, $amount2)
    {
        return !(abs(floatval($amount1) - floatval($amount2)) > VPSIA_EPSILON);
    }

    /**
     * Get order property with compatibility check on order getter introduced
     * in WC 3.0.
     *
     * @param WC_Order $order Order object.
     * @param string   $prop  Property name.
     *
     * @return mixed Property value
     * @since 1.4.1
     *
     */
    public static function get_order_prop ($order, $prop)
    {
        switch ($prop) {
            case 'order_total':
                $getter = array ($order, 'get_total');
                break;
            default:
                $getter = array ($order, 'get_' . $prop);
                break;
        }

        return is_callable($getter) ? call_user_func($getter) : $order->{$prop};
    }

    /**
     * Generate MAC keyed hash value using the HMAC method to send to External website
     *
     * Message Authentication Code: it prevents the end user from changing the order data.
     *
     * @param string      $urlms
     * @param string      $urldone
     * @param string      $orderid
     * @param string      $shopip
     * @param             $amount
     * @param string      $currency
     * @param string      $exponent
     * @param string      $accountingmode
     * @param string      $authormode
     * @param string      $name
     * @param string      $surname
     * @param string      $ordDescr
     * @param string      $startSecretString
     * @param string      $lockcard
     * @param null|string $options
     * @param string      $dsDAta //3DSData encrypted value
     *
     * @return false|string
     */
    public function generateMAC_request ($urlms, $urlms_header, $urldone, $orderid, $shopip, $amount, $currency, $exponent, $accountingmode, $authormode, $name, $surname, $ordDescr, $startSecretString, $lockcard, $options, $dsDAta)
    {
        $strToHash = "URLMS=$urlms&URLDONE=$urldone&ORDERID=$orderid&SHOPID=$shopip&AMOUNT=$amount&CURRENCY=$currency&EXPONENT=$exponent&ACCOUNTINGMODE=$accountingmode&AUTHORMODE=$authormode";

        if (!empty($options)) {
            $strToHash .= "&OPTIONS=$options";
        }
        if (!empty($lockcard)) {
            $strToHash .= "&LOCKCARD=$lockcard";
        }

        $strToHash .= "&3DSDATA=$dsDAta";

        if (!empty($urlms_header)) {
            $strToHash .= "&URLMSHEADER=$urlms_header";
        }

        $hashMac = hash_hmac('sha256', $strToHash, $startSecretString);

        // Write log with mac value(s)
        $this->strLogmessage .= PHP_EOL . PHP_EOL . "generateMAC_request" . PHP_EOL;
        $this->strLogmessage .= "MAC STRING = " . $strToHash . PHP_EOL;
        $this->strLogmessage .= "MAC HASH = " . $hashMac . PHP_EOL;

        return $hashMac;
    }

    /**
     * Generate Outcome MAC with parameters sended from External web site, and return keyed hash value using the HMAC method
     *
     * @param $orderid
     * @param $shopid
     * @param $authnumber
     * @param $amount
     * @param $currency
     * @param $exponent
     * @param $transactionid
     * @param $accountingmode
     * @param $authormode
     * @param $result
     * @param $transactiontype
     * @param $issuercountry
     * @param $outcome_key
     *
     * @return false|string
     */
    public function generateMAC_outcome (
          $orderid, $shopid, $authnumber, $amount, $currency, $exponent, $transactionid, $accountingmode, $authormode, $result, $transactiontype, $issuercountry
        , $payerid
        , $payer
        , $payerstatus
        , $hashpan
        , $iban
        , $accountholder
        , $aliasstr
        , $pantail
        , $panexpirydate
        , $panalias
        , $panaliasrev
        , $panaliasexpdate
        , $panaliastail
        , $maskedpan
        , $acquirerbin
        , $merchantid
        , $cardtype
        , $chinfo
        , $authcode
        , $ahemail
        , $ahtaxid
        , $amazonauthid
        , $amazoncaptureid
        , $pancode
        , $trecurr
        , $crecurr
        , $network
        , $outcome_key)
    {
        $strMac = "ORDERID=$orderid&SHOPID=$shopid&AUTHNUMBER=$authnumber&AMOUNT=$amount&CURRENCY=$currency";
        empty($exponent) ? : $strMac .= "&EXPONENT=$exponent";
        $strMac .= "&TRANSACTIONID=$transactionid&ACCOUNTINGMODE=$accountingmode&AUTHORMODE=$authormode&RESULT=$result&TRANSACTIONTYPE=$transactiontype";

        empty($issuercountry) ? : $strMac .= "&ISSUERCOUNTRY=$issuercountry";

        if ($authcode !== NULL)
            $strMac .= "&AUTHCODE=" . $authcode;

        if ($payerid !== NULL)
            $strMac .= "&PAYERID=" . $payerid;

        if ($payer !== NULL)
            $strMac .= "&PAYER=" . $payer;

        if ($payerstatus !== NULL)
            $strMac .= "&PAYERSTATUS=" . $payerstatus;

        if ($hashpan !== NULL)
            $strMac .= "&HASHPAN=" . $hashpan;

        if ($panaliasrev !== NULL)
            $strMac .= "&PANALIASREV=" . $panaliasrev;

        if ($panalias !== NULL)
            $strMac .= "&PANALIAS=" . $panalias;

        if ($panaliasexpdate !== NULL)
            $strMac .= "&PANALIASEXPDATE=" . $panaliasexpdate;

        if ($panaliastail !== NULL)
            $strMac .= "&PANALIASTAIL=" . $panaliastail;

        if ($maskedpan !== NULL)
            $strMac .= "&MASKEDPAN=" . $maskedpan;

        if ($trecurr !== NULL)
            $strMac .= "&TRECURR=" . $trecurr;

        if ($crecurr !== NULL)
            $strMac .= "&CRECURR=" . $crecurr;

        if ($pantail !== NULL)
            $strMac .= "&PANTAIL=" . $pantail;

        if ($panexpirydate !== NULL)
            $strMac .= "&PANEXPIRYDATE=" . $panexpirydate;

        if ($accountholder !== NULL)
            $strMac .= "&ACCOUNTHOLDER=" . $accountholder;

        if ($iban !== NULL)
            $strMac .= "&IBAN=" . $iban;

        if ($aliasstr !== NULL)
            $strMac .= "&ALIASSTR=" . $aliasstr;

        if ($ahemail !== NULL)
            $strMac .= "&AHEMAIL=" . $ahemail;

        if ($ahtaxid !== NULL)
            $strMac .= "&AHTAXID=" . $ahtaxid;

        if ($acquirerbin !== NULL)
            $strMac .= "&ACQUIRERBIN=" . $acquirerbin;

        if ($merchantid !== NULL)
            $strMac .= "&MERCHANTID=" . $merchantid;

        if ($cardtype !== NULL)
            $strMac .= "&CARDTYPE=" . $cardtype;

        if ($amazonauthid !== NULL)
            $strMac .= "&AMAZONAUTHID=" . $amazonauthid;

        if ($amazoncaptureid !== NULL)
            $strMac .= "&AMAZONCAPTUREID=" . $amazoncaptureid;

        if ($chinfo !== NULL)
            $strMac .= "&CHINFO=" . $chinfo;

        if ($pancode !== NULL)
            $strMac .= "&PANCODE=" . $pancode;

        /*********/
        //$this->strLogmessage .= "*****---------------". PHP_EOL.PHP_EOL. "CALCOLO MAC -  " . $strMac . PHP_EOL. PHP_EOL;
        //$this->strLogmessage .= " | outcome_key=  " . $outcome_key . PHP_EOL."*****---------------". PHP_EOL;

        return hash_hmac('sha256', $strMac, $outcome_key);
    }
}
