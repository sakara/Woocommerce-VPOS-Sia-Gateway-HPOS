<?php

use Automattic\WooCommerce\Admin\CategoryLookup;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

require_once(ABSPATH . 'wp-admin/includes/screen.php');
require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
require_once(ABSPATH . 'wp-admin/includes/template.php');

/**
 * Exceptions for error parameters setted in admin area
 *
 * Class ParameterError
 */
class ParameterError extends Exception
{
}

/**
 * Class VPOSSIA_My_List_Table
 *
 * Show and export orders executed with POS Sia Spa payment methods
 */
class VPOSSIA_My_List_Table extends WP_List_Table
{
    // Field to show on table
    CONST COL_FIELD_ID_CODE = "id";
    CONST COL_FIELD_ORDER_TOTAL_CODE = "total";
    CONST COL_FIELD_ORDER_NUMBER_CODE = "number";
    CONST COL_FIELD_ORDER_STATUS_CODE = "post_status";
    CONST COL_FIELD_DATE_CREATED_CODE = "date_created";
    CONST COL_FIELD_DATE_PAID_CODE = "date_paid";
    CONST COL_FIELD_FIRST_NAME_CODE = "first_name";
    CONST COL_FIELD_LAST_NAME_CODE = "last_name";
    CONST COL_FIELD_TRANSACTION_ID_CODE = "transaction_id";

    // Parameter for filter query
    const QUERY_PARAMETERS_ORDER_BY = "orderby";
    const QUERY_PARAMETERS_ORDER = "order";

    // Search parameter(s)
    const QUERY_PARAMETERS_SEARCH_STRING = "s";
    const QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM = "date_created_from";
    const QUERY_PARAMETERS_SEARCH_DATE_CREATED_TO = "date_created_to";

    // Action(s)
    const QUERY_PARAMETERS_ACTION_EXPORT_CSV = "exportcsv";

    //Cookie name for search value(s)
    const COOKIE_LIST_TABLE_FILTER_DATE_FROM = WC_GATEWAY_VPOSSIA_PLUGIN_NAME . "_" . self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM;
    const COOKIE_LIST_TABLE_FILTER_DATE_TO = WC_GATEWAY_VPOSSIA_PLUGIN_NAME . "_" . self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_TO;

    //Admin Notice type
    const NOTICES_TYPE_ERROR = "notice-error";
    const NOTICES_TYPE_WARNING = "notice-warning";
    const NOTICES_TYPE_SUCCESS = "notice-success";
    const NOTICES_TYPE_INFO = "notice-info";

    //
    const DATE_FORMAT = 'Y-m-d';

    // Variable(s) to store Query String value(s)
    protected $order = NULL;
    protected $orderBy = NULL;
    //
    protected $searchString = NULL;
    protected $searchDateCreatedFrom = NULL;
    protected $searchDateCreatedTo = NULL;
    //
    protected $requestExportCSV = FALSE;

    //
    private $logger = NULL;
    private $title = NULL;

    // Array of notices
    protected $notices = NULL;

    /**
     * VPOSSIA_My_List_Table constructor.
     *
     * @param array $args
     */
    public function __construct ($args = array ())
    {
        parent::__construct($args);

        // include jQuery UI
        add_action('admin_enqueue_scripts', array ($this, 'jqueryui'));

        // the function that filters posts
        add_action('pre_get_posts', array ($this, 'filterquery'));

        //
        $this->title = WC_GATEWAY_VPOSSIA_TITLE . "_" . __CLASS__;
        $this->init();
    }


    /**
     * Get Label shown on table for ID Field
     *
     * @return string|void
     */
    static function COL_FIELD_ID_LABEL ()
    {
        return __("ID", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for ORDER TOTAL Field
     *
     * @return string|void
     */
    static function COL_FIELD_ORDER_TOTAL_LABEL ()
    {
        return __("Total amount", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for ORDER NUMBER Field
     *
     * @return string|void
     */
    static function COL_FIELD_ORDER_NUMBER_LABEL ()
    {
        return __("Order number", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for ORDER STATUS Field
     *
     * @return string|void
     */
    static function COL_FIELD_ORDER_STATUS_LABEL ()
    {
        return __("Status", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for DATA CREATED Field
     *
     * @return string|void
     */
    static function COL_FIELD_DATE_CREATED_LABEL ()
    {
        return __("Date created", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for DATE PIAD Field
     *
     * @return string|void
     */
    static function COL_FIELD_DATE_PAID_LABEL ()
    {
        return __("Date paid", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for FIRST NAME Field
     *
     * @return string|void
     */
    static function COL_FIELD_FIRST_NAME_LABEL ()
    {
        return __("First name", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for LAST NAME Field
     *
     * @return string|void
     */
    static function COL_FIELD_LAST_NAME_LABEL ()
    {
        return __("Last name", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * Get Label shown on table for TRANSACTION ID Field
     *
     * @return string|void
     */
    static function COL_FIELD_TRANSACTION_ID_LABEL ()
    {
        return __("Transaction ID", 'woocommerce-vpossia-payment-gateway');
    }

    /**
     * return HTML for all notices setted
     *
     * @return string
     */
    public function getNotices ()
    {
        return implode("<br>", $this->notices);
    }

    /**
     * Add notice to notices array
     *
     * @param      $type
     * @param      $message
     * @param bool $dismissible
     *
     * @return string|string[]
     */
    private function addNotices ($type, $message, $dismissible = FALSE)
    {
        $html = "<div class=\"notice #type# #dismissible#\"><p>#message#</p></div>";
        $html = str_replace("#type#", $type, $html);
        $html = str_replace("#dismissible#", ($dismissible ? "" : " is-dismissible"), $html);
        $html = str_replace("#message#", $message, $html);
        array_push($this->notices, $html);
        return $html;
    }

    /**
     * Init component, set all Variables from globals $_REQUEST, $_COOKIE, $_POST variable(s
     */
    private function init ()
    {
        $this->notices = [];
        $this->orderBy = $this->getQueryParameters(self::QUERY_PARAMETERS_ORDER_BY);
        $this->order = $this->getQueryParameters(self::QUERY_PARAMETERS_ORDER);
        $this->searchString = $this->getQueryParameters(self::QUERY_PARAMETERS_SEARCH_STRING);
        $this->searchDateCreatedFrom = $this->getQueryParameters(self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM);
        $this->searchDateCreatedTo = $this->getQueryParameters(self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_TO);
        $this->requestExportCSV = $this->getQueryParameters(self::QUERY_PARAMETERS_ACTION_EXPORT_CSV);
    }

    /**
     * Get Cookie Expire Time
     *
     * @return float|int
     */
    private function getCookieExpireTime ()
    {
        return time() + 60 * 60 * 24 * 30;
    }

    /**
     * Return Default date for specific $key
     *
     * if $key not matched return today;
     *
     * @param $key
     *
     * @return false|string
     */
    private function getFilter_DefaultDate ($key)
    {
        if (self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM == $key) {
            return date(self::DATE_FORMAT, strtotime("-30 days"));
        }
        return date(self::DATE_FORMAT);
    }

    /**
     * Get Cookie Value for specific $key
     *
     * if $key not exists return $defaultValue
     *
     * @param $key
     * @param $defaultValue
     *
     * @return mixed
     */
    private function getCookieValue ($key, $defaultValue)
    {
        $retVal = $defaultValue;
        if (isset($_COOKIE)) {
            if (array_key_exists($key, $_COOKIE)) {
                $retVal = $_COOKIE[$key];
            }
        }
        return $retVal;
    }

    /**
     * Get cookie Key from parameter key
     *
     * @param $key key for date parameter
     *
     * @return string|null
     * @throws ErrorException
     * @throws ParameterError
     */
    private function getCookieKey_fromDateKey ($key)
    {
        $cookieKey = NULL;
        switch ($key) {
            case self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM:
                $cookieKey = self::COOKIE_LIST_TABLE_FILTER_DATE_FROM;
                break;
            case self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_TO:
                $cookieKey = self::COOKIE_LIST_TABLE_FILTER_DATE_TO;
                break;
            default:
                throw new ParameterError(sprintf(__("Key \"%s\" for cookie not managed"), $key), 600);
                break;
        }
        return $cookieKey;
    }

    /**
     * Get filter date from $_POST, if is empty get from $_COOKIE;
     *
     * if $_POST and $_COOKIE not setted return default value from getFilter_DefaultFrom or getFilter_DefaultTo, depends on $key parameter setted
     *
     * @param $key
     *
     * @return false|int|mixed
     * @throws ErrorException
     */
    private function getFilterDate ($key)
    {
        try {
            $cookieKey = $this->getCookieKey_fromDateKey($key);
            $date = (isset($_POST[$key]) ? date(self::DATE_FORMAT, strtotime($_POST[$key])) : NULL);
            if (empty($date)) {
                $defaultDate = $this->getFilter_DefaultDate($key);
                $date = $this->getCookieValue($cookieKey, $defaultDate);
            }
            setcookie($cookieKey, $date, $this->getCookieExpireTime());

            return $date;
        } catch (ParameterError $e) {
            $this->addNotices(self::NOTICES_TYPE_ERROR, $e->getMessage());
            $this->log($e->getMessage(), WC_Log_Levels::ERROR);
            return NULL;
        }
    }

    /**
     * Log system processes.
     *
     * @since 1.0.0
     */
    public function log ($message, $level = WC_Log_Levels::NOTICE)
    {
        if (empty($this->logger)) {
            $this->logger = new WC_Logger();
        }
        $this->logger->add(str_replace(" ", "_", $this->title), $message, $level);
    }


    /**
     * Get Value from $_REQUEST, $_COOKIE, $_POST, depends from $key requested
     *
     * return NULL if not exits.
     *
     * @param $key Name of parameters
     *
     * @return false|int|mixed|string|null
     * @throws ErrorException
     */
    private function getQueryParameters ($key)
    {
        $retVal = NULL;
        switch ($key) {
            case self::QUERY_PARAMETERS_ORDER_BY:
            case self::QUERY_PARAMETERS_ORDER:
            case self::QUERY_PARAMETERS_SEARCH_STRING:
                $retVal = isset($_REQUEST[$key]) ? $_REQUEST[$key] : NULL;
                break;
            case self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_FROM:
            case self::QUERY_PARAMETERS_SEARCH_DATE_CREATED_TO:
                $retVal = $this->getFilterDate($key);
                break;
            case self::QUERY_PARAMETERS_ACTION_EXPORT_CSV:
                $retVal = isset($_POST[$key]) && $_POST[$key] == "1" ? TRUE : FALSE;
                break;
            default:
                break;
        }
        return $retVal;
    }

    /**
     * Returns a list of orders that are using one of POS Sia Spa's payment methods.
     */
    function get_orders ()
    {
        $order_query = array (
            'payment_method' => WC_GATEWAY_VPOSSIA_ID,
            'post_type' => 'shop_order',
            'numberposts' => $this->get_items_per_page('transactions_per_page', WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_DEFAULT_ITEM_PER_PAGE),
            'paged' => $this->get_pagenum(),
            'paginate' => TRUE
        );


        if (!empty($this->orderBy)) {
            $order_query[self::QUERY_PARAMETERS_ORDER_BY] = $this->orderBy;
        }
        if (!empty($this->order)) {
            $order_query[self::QUERY_PARAMETERS_ORDER] = $this->order;
        }

        if (!empty($this->searchDateCreatedFrom)) {
            $order_query["date_after"] = $this->searchDateCreatedFrom;
        }

        if (!empty($this->searchDateCreatedTo)) {
            $order_query["date_before"] = $this->searchDateCreatedTo;
        }

        $order_query['meta_query'] = [];

        if (!empty($this->searchString)) {

            //$order_query["transaction_id"] = $this->searchString;
            $order_query["s"] = $this->searchString;

            $order_query['meta_query'][] = array (
                'key' => 'transaction_id',
                'compare' => ' like ',
                'value' => "%" . $this->searchString . "%",
                'type' => 'numeric'
            );
        }
        $orders = wc_get_orders($order_query);
        return $orders;
    }

    /**
     * @inheritDoc
     */
    function get_columns ()
    {
        $columns = array (
            self::COL_FIELD_ID_CODE => self::COL_FIELD_ID_LABEL(),
            self::COL_FIELD_ORDER_NUMBER_CODE => self::COL_FIELD_ORDER_NUMBER_LABEL(),
            self::COL_FIELD_ORDER_STATUS_CODE => self::COL_FIELD_ORDER_STATUS_LABEL(),
            self::COL_FIELD_ORDER_TOTAL_CODE => self::COL_FIELD_ORDER_TOTAL_LABEL(),
            self::COL_FIELD_DATE_CREATED_CODE => self::COL_FIELD_DATE_CREATED_LABEL(),
            self::COL_FIELD_DATE_PAID_CODE => self::COL_FIELD_DATE_PAID_LABEL(),
            self::COL_FIELD_TRANSACTION_ID_CODE => self::COL_FIELD_TRANSACTION_ID_LABEL(),
            self::COL_FIELD_FIRST_NAME_CODE => self::COL_FIELD_FIRST_NAME_LABEL(),
            self::COL_FIELD_LAST_NAME_CODE => self::COL_FIELD_LAST_NAME_LABEL(),
        );
        return $columns;
    }

    /**
     * @inheritDoc
     */
    function no_items ()
    {
        echo __('No Transaction found.', 'woocommerce-vpossia-payment-gateway');
    }


    /**
     * @inheritDoc
     */
    function prepare_items ()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array ($columns, $hidden, $sortable);

        $orders = $this->get_orders();
        $per_page = $this->get_items_per_page('transactions_per_page', WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_DEFAULT_ITEM_PER_PAGE);

        $total_items = $orders->total;

        $this->set_pagination_args(array (
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page,                     //WE have to determine how many items to show on a page
            'date_created_from' => $this->searchDateCreatedFrom,
            'date_created_to' => $this->searchDateCreatedTo
        ));

        //Show data for specific page
        // $found_data = array_slice($orders, (($this->get_pagenum() - 1) * $per_page), $per_page);
        $found_data = $orders->orders;
        $this->items = $found_data;

        if ($this->requestExportCSV) {
            $this->exportCsv($found_data);
        }
    }

    /**
     * Export table into csv file
     *
     * @param $found_data
     */
    function exportCsv ($found_data)
    {
        // Throw away any output sent up until this point.
        ob_end_clean();

        /**
         * Generate CSV File Name
         */
        $csv_file_name = WC_GATEWAY_VPOSSIA_TRANSACTION_LIST_EXPORT_CSV_FILE_NAME_PREFIX . $this->searchDateCreatedFrom . '_' . $this->searchDateCreatedTo . '.csv';

        // Set Headers
        $csv_headers = array ();
        $csv_headers[] = self::COL_FIELD_ID_CODE;
        $csv_headers[] = self::COL_FIELD_ORDER_NUMBER_CODE;
        $csv_headers[] = self::COL_FIELD_ORDER_STATUS_CODE;
        $csv_headers[] = self::COL_FIELD_ORDER_TOTAL_CODE;
        $csv_headers[] = self::COL_FIELD_DATE_CREATED_CODE;
        $csv_headers[] = self::COL_FIELD_DATE_PAID_CODE;
        $csv_headers[] = self::COL_FIELD_TRANSACTION_ID_CODE;
        $csv_headers[] = self::COL_FIELD_FIRST_NAME_CODE;
        $csv_headers[] = self::COL_FIELD_LAST_NAME_CODE;

        $output_handle = fopen('php://output', 'w');

        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename=' . $csv_file_name);
        header('Expires: 0');
        header('Pragma: public');

        fputcsv($output_handle, $csv_headers);

        foreach ($found_data as $order) {
            $data = [];
            foreach ($this->get_columns() as $column_name => $label) {

                if ($column_name == VPOSSIA_My_List_Table::COL_FIELD_DATE_CREATED_CODE || $column_name == VPOSSIA_My_List_Table::COL_FIELD_DATE_PAID_CODE) {
                    $_data = $this->getDataForCsv($order, $column_name);
                    $data[] = (!empty($_data) && is_object($_data) ? $_data->date('d-m-Y H:i:s') : "");
                } else {
                    $data[] = $this->getDataForCsv($order, $column_name, FALSE);
                }


            }
            fputcsv($output_handle, $data);
        }

        fclose($output_handle);
        die();
    }

    /**
     * Get list of hidden columns
     *
     * @return array
     */
    function get_hidden_columns ()
    {
        $hidden_columns = array (
            self::COL_FIELD_ID_CODE
        );
        return $hidden_columns;
    }


    /**
     * @inheritDoc
     */
    function get_sortable_columns ()
    {
        $sortable_columns = array (
            self::COL_FIELD_ORDER_NUMBER_CODE => array (self::COL_FIELD_ORDER_NUMBER_CODE, TRUE),
            self::COL_FIELD_DATE_CREATED_CODE => array (self::COL_FIELD_DATE_CREATED_CODE, FALSE),
            self::COL_FIELD_DATE_PAID_CODE => array (self::COL_FIELD_DATE_PAID_CODE, FALSE),
            self::COL_FIELD_TRANSACTION_ID_CODE => array (self::COL_FIELD_TRANSACTION_ID_CODE, FALSE)
        );

        return $sortable_columns;
    }

    /**
     * @inheritDoc
     */
    function column_default ($item, $column_name)
    {
        if ($column_name == self::COL_FIELD_DATE_CREATED_CODE || $column_name == self::COL_FIELD_DATE_PAID_CODE) {
            $data = $this->getData($item, $column_name);
            return (!empty($data) ? $data->date('d-m-Y H:i:s') : "");
        } else {
            return $this->getData($item, $column_name);
        }
    }

    /**
     * Get Order satus Text for getData and getDataCsv
     *
     * @param $item
     *
     * @return string|void
     */
    private function getDataOrderStatus ($status, $item)
    {
        $text = "";

        switch ($status) {
            case 'pending':
                $text = _x('Pending payment', 'Order status', 'woocommerce');
                break;
            case 'processing':
                $text = _x('Processing', 'Order status', 'woocommerce');
                break;
            case 'on-hold':
                $text = _x('On hold', 'Order status', 'woocommerce');
                break;

            case 'completed':
                $text = _x('Completed', 'Order status', 'woocommerce');
                break;
            case 'cancelled':
                $text = _x('Cancelled', 'Order status', 'woocommerce');
                break;
            case 'refunded':
                $text = _x('Refunded', 'Order status', 'woocommerce');
                break;
            case 'failed':
                $text = _x('Failed', 'Order status', 'woocommerce');
                break;
            default:
                $text = $status;
                break;
        }
        return $text;
    }

    /**
     * Get value from single Row of dataset
     *
     * @param      $item
     * @param      $column_name
     * @param bool $amountOrdertotalFormatted
     *
     * @return string|true|void
     */
    public function getData ($item, $column_name, $amountOrdertotalFormatted = TRUE)
    {
        switch ($column_name) {
            case "cb";
                return $this->column_cb($item);
            case self::COL_FIELD_ID_CODE:
                return $item->get_id();
            case self::COL_FIELD_ORDER_NUMBER_CODE:
                return $item->get_order_number();
            case self::COL_FIELD_ORDER_STATUS_CODE:
                $status = $item->get_status();
                return '<mark class="order-status status-' . $status . ' tips"><span>' . $this->getDataOrderStatus($status, $item) . '</span></mark>';
            case self::COL_FIELD_FIRST_NAME_CODE:
                return $item->get_billing_first_name();
            case self::COL_FIELD_LAST_NAME_CODE:
                return $item->get_billing_last_name();
            case self::COL_FIELD_TRANSACTION_ID_CODE:
                return $item->get_transaction_id();
            case self::COL_FIELD_DATE_CREATED_CODE:
                return $item->get_date_created();
            case self::COL_FIELD_DATE_PAID_CODE:
                return $item->get_date_paid();
            case self::COL_FIELD_ORDER_TOTAL_CODE:
                if ($amountOrdertotalFormatted) {
                    return $item->get_formatted_order_total();
                } else {
                    return $item->get_total();
                }
            default:
                return print_r($item, TRUE); //Show the whole array for troubleshooting purposes
        }
    }

    public function getDataForCsv ($item, $column_name, $amountOrdertotalFormatted = TRUE)
    {
        switch ($column_name) {
            case "cb";
                return $this->column_cb($item);
            case self::COL_FIELD_ID_CODE:
                return $item->get_id();
            case self::COL_FIELD_ORDER_NUMBER_CODE:
                return $item->get_order_number();
            case self::COL_FIELD_ORDER_STATUS_CODE:
                $status = $item->get_status();
                return $this->getDataOrderStatus($status, $item);
            case self::COL_FIELD_FIRST_NAME_CODE:
                return $item->get_billing_first_name();
            case self::COL_FIELD_LAST_NAME_CODE:
                return $item->get_billing_last_name();
            case self::COL_FIELD_TRANSACTION_ID_CODE:
                return $item->get_transaction_id();
            case self::COL_FIELD_DATE_CREATED_CODE:
                return $item->get_date_created();
            case self::COL_FIELD_DATE_PAID_CODE:
                return $item->get_date_paid();
            case self::COL_FIELD_ORDER_TOTAL_CODE:
                if ($amountOrdertotalFormatted) {
                    return $item->get_formatted_order_total();
                } else {
                    return $item->get_total();
                }
            default:
                return print_r($item, TRUE); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * @param $status
     * @param $option
     * @param $value
     *
     * @return mixed
     */
    public static function set_screen ($status, $option, $value)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    function extra_tablenav ($which)
    { ?>
      <div class=" actions">
        <?php
        /**
         * Add extra button
         */
        if ($this->has_items()) { ?>
          <div class="alignleft" style="padding-right: 20px;">
          <form method="post">
            <input type="hidden" name="page" value="woocommerce-gateway-vpossia-transactions-list"/>
            <input type="hidden" name="exportcsv" value="1"/><?php
              submit_button(__('Export this page in CSV', 'woocommerce-vpossia-payment-gateway'), '', 'export_csv_action', FALSE, array ('id' => 'post-export-csv-submit')); ?>
          </form>&nbsp;&nbsp;&nbsp;&nbsp;
          </div><?php
        }

        // Create filter date only on top bar
        if ('top' === $which) {
            ob_start(); ?>
          <div class="alignleft">
          <form method="post">
          <input type="hidden" name="page" value="woocommerce-gateway-vpossia-transactions-list"/>
          <label><?php echo self::COL_FIELD_DATE_CREATED_LABEL() . " " . __("From", 'woocommerce-vpossia-payment-gateway') ?></label>
          <input type="date" id="datepicker_from" name="date_created_from" value="<?php echo $this->searchDateCreatedFrom ?>" class="example-datepicker"/>
          <label><?php echo __("To", 'woocommerce-vpossia-payment-gateway') ?></label>
          <input type="date" id="datepicker_to" name="date_created_to" value="<?php echo $this->searchDateCreatedTo ?>" class="example-datepicker"/>
            <?php
            $output = ob_get_clean();
            if (!empty($output)) {
                echo $output;
                submit_button(__('Filter', 'woocommerce-vpossia-payment-gateway'), '', 'filter_action', FALSE, array ('id' => 'post-query-submit'));
                echo "</form></div>";
            }
        } ?>
      </div><?php
    }
} ?>