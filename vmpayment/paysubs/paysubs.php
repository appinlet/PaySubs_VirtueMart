<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */
defined( '_JEXEC' ) or die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' );

if ( !class_exists( 'vmPSPlugin' ) ) {
    require JPATH_VM_PLUGINS . DS . 'vmpsplugin.php';
}

class plgVmPaymentPaySubs extends vmPSPlugin
{
    public static $_this = false;

    public $payment_element = 'paysubs';

    public function __construct( &$subject, $config )
    {
        if ( !empty( $_GET['m_1'] ) ) {

            $_GET['on'] = $_GET['m_4'];

            $_GET['pm'] = $_GET['m_5'];

            echo "<script>location.href='index.php?option=" . $_GET['option'] . "&view=" . $_GET['view'] . "&task=" . $_GET['task'] . "&on=" . $_GET['on'] . "&pm=" . $_GET['pm'] . "&paysubs_status=" . $_GET['p3'] . "'</script>";
        }

        parent::__construct( $subject, $config );

        $jlang = JFactory::getLanguage();

        $jlang->load( 'plg_vmpayment_paysubs', JPATH_ADMINISTRATOR, null, true );

        $this->_loggable = true;

        $this->tableFields = array_keys( $this->getTableSQLFields() );

        $this->_tablepkey = 'id';

        $this->_tableId = 'id';

        $varsToPush = array( 'paysubs_terminal_id' => array( '', 'string' ),

            'paysubs_description_of_goods'            => array( '', 'string' ),

            'paysubs_currency'                        => array( '', 'char' ),

            'paysubs_populate_payer_email'            => array( '', 'char' ),

            'paysubs_md5_salt'                        => array( '', 'char' ),

            'paysubs_sms_send'                        => array( '', 'char' ),

            'paysubs_mobile_number'                   => array( '', 'char' ),

            'paysubs_sms_message'                     => array( '', 'char' ),

            'paysubs_approved_status'                 => array( '', 'char' ),

            'paysubs_failed_status'                   => array( '', 'char' ),

            'paysubs_cancelled_status'                => array( '', 'char' ),

            'paysubs_cancelled_url'                   => array( '', 'char' ),

            'paysubs_occur_create'                    => array( '', 'char' ),

            'paysubs_occur_frequency'                 => array( '', 'char' ),

            'paysubs_occur_count'                     => array( 0, 'int' ),

            'paysubs_occur_next_amount'               => array( 0, 'int' ),

            'paysubs_occur_next_date'                 => array( '', 'char' ),

            'paysubs_occur_send_email'                => array( '', 'char' ),

            'paysubs_urls_provided'                   => array( '', 'char' ),

            'paysubs_approved_url'                    => array( '', 'char' ),

            'paysubs_declined_url'                    => array( '', 'char' ),

            'cost_per_transaction'                => array( 0, 'int' ),

            'cost_percent_total'                  => array( 0, 'int' ),

            'tax_id'                              => array( 0, 'int' ),

            'payment_logos'                       => array( '', 'char' ),

            'min_amount'                          => array( 0, 'int' ),

            'max_amount'                          => array( 0, 'int' ),

            'paysubs_debug'                           => array( '', 'char' ) );

        if ( !defined( 'VM_VERSION' ) or VM_VERSION < 3 ) {
            $this->setConfigParameterable( 'payment_params', $varsToPush );
        } else {
            $this->setConfigParameterable( 'payment_params', $varsToPush );
        }
    }

    public function getVmPluginCreateTableSQL()
    {

        return $this->createTableSQL( 'Payment PaySubs Table' );

    }

    public function getTableSQLFields()
    {
        $SQLfields = array(

            'id'                             => ' INT(11) unsigned NOT NULL AUTO_INCREMENT ',

            'virtuemart_order_id'            => ' int(1) UNSIGNED DEFAULT NULL',

            'order_number'                   => ' char(32) DEFAULT NULL',

            'virtuemart_paymentmethod_id'    => ' mediumint(1) UNSIGNED DEFAULT NULL',

            'payment_name'                   => 'varchar(5000)',

            'payment_order_total'            => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',

            'payment_currency'               => 'char(3) ',

            'cost_per_transaction'           => ' decimal(10,2) DEFAULT NULL ',

            'cost_percent_total'             => ' decimal(10,2) DEFAULT NULL ',

            'tax_id'                         => ' smallint(1) DEFAULT NULL',

            'paysubs_terminal_id'                => ' varchar(255)  ',

            'paysubs_transaction_reference'      => ' varchar(255)  ',

            'paysubs_duplicate'                  => ' varchar(255)  ',

            'paysubs_cardholder_name'            => ' varchar(255)  ',

            'paysubs_authorized_amount'          => ' varchar(255)  ',

            'paysubs_card_type'                  => ' varchar(255)  ',

            'paysubs_description_of_goods'       => ' varchar(255)  ',

            'paysubs_cardholder_email'           => ' varchar(255)  ',

            'paysubs_budget_period'              => ' varchar(255)  ',

            'paysubs_card_expiry_date'           => ' varchar(255)  ',

            'paysubs_authorisation_reponse_code' => ' varchar(255)  ',

            'paysubs_cardholder_ip'              => ' varchar(255)  ',

            'paysubs_masked_card_number'         => ' varchar(255)  ',

            'paysubs_transaction_type'           => ' varchar(255)  ',

            'paysubs_retrieval_reference_number' => ' varchar(255)  ',

            'paysubsresponse_raw'                => 'varchar(512)' );

        return $SQLfields;

    }

    public function plgVmConfirmedOrder( $cart, $order )
    {
        if ( !( $method = $this->getVmPluginMethod( $order['details']['BT']->virtuemart_paymentmethod_id ) ) ) {
            return null;
        }

        if ( !$this->selectedThisElement( $method->payment_element ) ) {
            return false;
        }

        $session = JFactory::getSession();

        $return_context = $session->getId();

        $this->_debug = $method->paysubs_debug;

        $this->logInfo( 'plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message' );

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php';
        }

        if ( !class_exists( 'VirtueMartModelCurrency' ) ) {
            require JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php';
        }

        $new_status = '';

        $usrBT = $order['details']['BT'];

        $address = (  ( isset( $order['details']['ST'] ) ) ? $order['details']['ST'] : $order['details']['BT'] );

        if ( !class_exists( 'TableVendors' ) ) {
            require JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php';
        }

        $vendorModel = VmModel::getModel( 'Vendor' );

        $vendorModel->setId( 1 );

        $vendor = $vendorModel->getVendor();

        $vendorModel->addImages( $vendor, 1 );

        $this->getPaymentCurrency( $method );

        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';

        $db = JFactory::getDBO();

        $db->setQuery( $q );

        $currency_code_3 = $db->loadResult();

        $paymentCurrency = CurrencyDisplay::getInstance( $method->payment_currency );

        $totalInPaymentCurrency = round( $paymentCurrency->convertCurrencyTo( $method->payment_currency, $order['details']['BT']->order_total, false ), 2 );

        $cd = CurrencyDisplay::getInstance( $cart->pricesCurrency );

        $merchant_id = $this->_getMerchantId( $method );

        $testReq = $method->paysubs_debug == 'Y' ? 'YES' : 'NO';

        $post_variables_md5 = array(

            'p1'   => $merchant_id,

            'p2'   => $order['details']['BT']->order_number,

            'p3'   => 'Online Purchase via PAYSUBS: ' . $order['details']['BT']->order_number,

            'p4'   => $totalInPaymentCurrency,

            'p5'   => 'zar',

            'p6'   => $method->paysubs_occur_create == 'Y' ? 'U' : '0',

            'p7'   => $method->paysubs_occur_create == 'Y' ? $method->paysubs_occur_frequency : '',

            'p8'   => $method->paysubs_sms_send == 'Y' ? $method->paysubs_mobile_number : '',

            'p9'   => $method->paysubs_sms_send == 'Y' ? $method->paysubs_sms_message : '',

            'p10'  => JROUTE::_( JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id ),

            'm_1'  => 'com_virtuemart',

            'm_2'  => 'pluginresponse',

            'm_3'  => 'pluginresponsereceived',

            'm_4'  => $order['details']['BT']->order_number,

            'm_5'  => $order['details']['BT']->virtuemart_paymentmethod_id,

            'm_6'  => '',

            'm_7'  => '',

            'm_8'  => '',

            'm_9'  => '',

            'm_10' => '' );

        $hash_string = '';
        foreach ( $post_variables_md5 as $key => $val ) {
            if ( $val != '' ) {
                $hash_string .= $val;
            }
        }

        $hash_post = md5( $hash_string . $method->paysubs_md5_salt );

        /* MD5 HASH Calculation Ends */

        $post_variables = array( 'p1' => $merchant_id,

            'p2'                         => $order['details']['BT']->order_number,

            'p3'                         => 'Online Purchase via PAYSUBS: ' . $order['details']['BT']->order_number,

            'p4'                         => $totalInPaymentCurrency,

            'p5'                         => 'zar',

            'p6'                         => $method->paysubs_occur_create == 'Y' ? 'U' : '0',

            'p7'                         => $method->paysubs_occur_create == 'Y' ? $method->paysubs_occur_frequency : '',

            'p8'                         => $method->paysubs_sms_send == 'Y' ? $method->paysubs_mobile_number : '',

            'p9'                         => $method->paysubs_sms_send == 'Y' ? $method->paysubs_sms_message : '',

            'p10'                        => JROUTE::_( JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id ),

            'm_1'                        => 'com_virtuemart',

            'm_2'                        => 'pluginresponse',

            'm_3'                        => 'pluginresponsereceived',

            'm_4'                        => $order['details']['BT']->order_number,

            'm_5'                        => $order['details']['BT']->virtuemart_paymentmethod_id,

            'm_6'                        => '',

            'm_7'                        => '',

            'm_8'                        => '',

            'm_9'                        => '',

            'm_10'                       => '',

            'URLSProvided'               => 'Y',

            'OtherPaymentMethods'        => 'Y',

            'ApprovedUrl'                => JROUTE::_( JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginResponseReceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id ),

            'DeclinedUrl'                => JROUTE::_( JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginResponseReceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id ),

            'Hash'                       => $hash_post );

        $dbValues['order_number'] = $order['details']['BT']->order_number;

        $dbValues['payment_name'] = $this->renderPluginName( $method, $order );

        $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;

        $dbValues['paysubs_custom'] = $return_context;

        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;

        $dbValues['cost_percent_total'] = $method->cost_percent_total;

        $dbValues['payment_currency'] = $method->payment_currency;

        $dbValues['payment_order_total'] = $totalInPaymentCurrency;

        $dbValues['tax_id'] = $method->tax_id;

        $dbValues['phone_1'] = $method->paysubs_mobile_number;

        $this->storePSPluginInternalData( $dbValues );

        $url = $this->_getPaySubsUrlHttps( $method );

        $html = '<html>

                    <head>

                        <title>Redirection</title>

                    </head>

                    <body><div style="margin: auto; text-align: center;">';

        $html .= '<form action="' . "https://" . $url . '" method="post" id="vm_paysubs_form" > ';

        $html .= '<input type="submit" class="button"  value="' . JText::_( 'VMPAYMENT_PAYSUBS_REDIRECT_MESSAGE' ) . '" />';

        foreach ( $post_variables as $name => $value ) {
            @$html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars( $value ) . '" />';
        }

        $html .= '</form></div>';

        $html .= ' <script type="text/javascript">';

        $html .= ' document.getElementById("vm_paysubs_form").submit();';

        $html .= ' </script></body></html>';

        return $this->processConfirmedOrderPaymentResponse( 2, $cart, $order, $html, $dbValues['payment_name'], $new_status );
    }

    public function plgVmgetPaymentCurrency( $virtuemart_paymentmethod_id, &$paymentCurrencyId )
    {

        if ( !( $method = $this->getVmPluginMethod( $virtuemart_paymentmethod_id ) ) ) {
            return null;
        }

        if ( !$this->selectedThisElement( $method->payment_element ) ) {
            return false;
        }

        $this->getPaymentCurrency( $method );

        $paymentCurrencyId = $method->payment_currency;

    }

    public function plgVmOnPaymentResponseReceived( &$html, &$paymentResponse )
    {
        $virtuemart_paymentmethod_id = vRequest::getInt( 'pm', 0 );

        $order_number = vRequest::getVar( 'on', 0 );

        $vendorId = 0;

        /* check that the payment method exists */

        if ( !( $method = $this->getVmPluginMethod( $virtuemart_paymentmethod_id ) ) ) {
            return null;
        }

        /* check that this payment method is selected */

        if ( !$this->selectedThisElement( $method->payment_element ) ) {
            return false;
        }

        /* check that the cart module has been loaded */

        if ( !class_exists( 'VirtueMartCart' ) ) {
            require VMPATH_SITE . DS . 'helpers' . DS . 'cart.php';
        }

        /* check that the Shop functions module has been loaded */

        if ( !class_exists( 'shopFunctionsF' ) ) {
            require VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php';
        }

        /* check that the order modules has been loaded */

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require VMPATH_ADMIN . DS . 'models' . DS . 'orders.php';
        }

        $paysubs_data = vRequest::getGet();

        $payment_name = $this->renderPluginName( $method );

        if ( !empty( $paysubs_data ) ) {
            vmdebug( 'plgVmOnPaymentResponseReceived', $paysubs_data );

            $order_number = $paysubs_data['on'];

            $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber( $order_number );

            $payment_name = $this->renderPluginName( $method );

            if ( $virtuemart_order_id ) {
                $order['order_status'] = $this->_getPaymentStatus( $method, substr( $_POST['p3'], 6, 8 ) ); // OLD - Changed $_POST['APPROVED'] tp $_POST['p3']

                $order['comments'] = JText::sprintf( 'VMPAYMENT_PAYSUBS_PAYMENT_STATUS_CONFIRMED', $order_number );

                $modelOrder = VmModel::getModel( 'orders' );

                $orderitems = $modelOrder->getOrder( $virtuemart_order_id );

                $nb_history = count( $orderitems['history'] );

                if ( $orderitems['history'][$nb_history - 1]->order_status_code != $order['order_status'] ) {

                    $this->_storePaySubsInternalData( $method, $paysubs_data, $virtuemart_order_id );

                    $this->logInfo( 'plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message' );

                    $order['virtuemart_order_id'] = $virtuemart_order_id;

                    if ( strtolower( $order['order_status'] ) == "c" ) {

                        $order['comments'] = JText::sprintf( 'VMPAYMENT_PAYSUBS_EMAIL_SENT' );

                        $order['customer_notified'] = 1;

                    } else {

                        $order['customer_notified'] = 0;

                    }

                    $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );

                }

            } else {

                vmError( 'PaySubs data received, but no order number' );

                return;

            }

        } else {

            $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber( $order_number );

        }

        if ( !( $paymentTable = $this->_getPaySubsInternalData( $virtuemart_order_id, $order_number ) ) ) {
            return '';
        }

        $cart = VirtueMartCart::getCart();

        if ( $this->_getPaymentStatus( $method, substr( $_POST['p3'], 6, 8 ) ) == "C" ) {
            $html = $this->_getPaymentResponseHtml( $paymentTable, $payment_name );
            $cart->emptyCart();
        } else {
            $html = "";

            // get the order
            $modelOrder = VmModel::getModel( 'orders' );
            $order      = $modelOrder->getOrder( $virtuemart_order_id );

            // update the order
            $order['order_status'] = 'D';
            $order['comments']     = JText::sprintf( 'The payment was declined.' );
            $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );
            $paymentResponse = "Your payment was declined. Please try again or contact your card issuer for more information.";
        }

        return true;
    }

    public function plgVmOnUserPaymentCancel()
    {

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php';
        }

        $order_number = vRequest::getVar( 'on' );

        if ( !$order_number ) {
            return false;
        }

        $db = JFactory::getDBO();

        $query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";

        $db->setQuery( $query );

        $virtuemart_order_id = $db->loadResult();

        if ( !$virtuemart_order_id ) {
            return null;
        }

        $order['order_status'] = 'X';

        $modelOrder = VmModel::getModel( 'orders' );

        $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );

        return true;

    }

    public function plgVmOnPaymentNotification()
    {

        if ( !class_exists( 'VirtueMartModelOrders' ) ) {
            require JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php';
        }

        $paysubs_data = vRequest::getPost();

        $order_number = $paysubs_data['m_4'];

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber( $paysubs_data['m_4'] );

        if ( !$virtuemart_order_id ) {
            return;
        }

        $vendorId = 0;

        $payment = $this->getDataByOrderId( $virtuemart_order_id );

        $method = $this->getVmPluginMethod( $payment->virtuemart_paymentmethod_id );

        if ( !$this->selectedThisElement( $method->payment_element ) ) {
            return false;
        }

        $this->_debug = $method->paysubs_debug;

        if ( !$payment ) {
            $this->logInfo( 'getDataByOrderId payment not found: exit ', 'ERROR' );

            return null;
        }

        $this->logInfo( 'paysubs_data ' . implode( '   ', $paysubs_data ), 'message' );

        $this->_storePaySubsInternalData( $method, $paysubs_data, $virtuemart_order_id );

        if ( !( empty( $error_msg ) ) ) {

            $new_status = $method->status_canceled;

            $this->logInfo( 'process IPN ' . $error_msg . ' ' . $new_status, 'ERROR' );

        } else {
            $this->logInfo( 'process IPN OK', 'message' );
        }

        if ( empty( $_GET['paysubs_status'] ) || ( $_GET['paysubs_status'] != 'Completed' && $_GET['paysubs_status'] != 'Pending' ) ) {

            //return false;

        }

        $new_status = $this->_getPaymentStatus( $method, substr( $_POST['p3'], 6, 8 ) );

        $this->logInfo( 'plgVmOnPaymentNotification return new_status:' . $new_status, 'message' );

        $modelOrder = VmModel::getModel( 'orders' );

        $order = array();

        $order['order_status'] = $new_status;

        $order['customer_notified'] = 1;

        $order['comments'] = JText::sprintf( 'VMPAYMENT_PAYSUBS_PAYMENT_STATUS_CONFIRMED', $order_number );

        $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );

        $this->logInfo( 'Notification, sentOrderConfirmedEmail ' . $order_number . ' ' . $new_status, 'message' );

        $this->emptyCart( $return_context );

    }

    public function _storePaySubsInternalData( $method, $paysubs_data, $virtuemart_order_id )
    {

        $db = JFactory::getDBO();

        $query = 'SHOW COLUMNS FROM `' . $this->_tablename . '` ';

        $db->setQuery( $query );

        $columns = $db->loadResultArray( 0 );

        $post_msg = '';

        foreach ( $paysubs_data as $key => $value ) {

            $post_msg .= $key . "=" . $value . "<br />";

            $table_key = 'paysubs_response_' . $key;

            if ( in_array( $table_key, $columns ) ) {

                $response_fields[$table_key] = $value;

            }

        }

        $db->setQuery( "SELECT * FROM " . $this->_tablename . " WHERE virtuemart_order_id='" . $virtuemart_order_id . "'" );

        $databaseFields = $db->loadAssoc();

        $response_fields['virtuemart_order_id'] = $databaseFields['virtuemart_order_id'];

        $response_fields['order_number'] = $databaseFields['order_number'];

        $response_fields['virtuemart_paymentmethod_id'] = $databaseFields['virtuemart_paymentmethod_id'];

        $response_fields['payment_name'] = $databaseFields['payment_name'];

        $response_fields['payment_order_total'] = $databaseFields['payment_order_total'];

        $response_fields['payment_currency'] = $databaseFields['payment_currency'];

        $response_fields['cost_per_transaction'] = $databaseFields['cost_per_transaction'];

        $response_fields['cost_percent_total'] = $databaseFields['cost_percent_total'];

        $response_fields['tax_id'] = $databaseFields['tax_id'];

        $response_fields['paysubs_terminal_id'] = $databaseFields['paysubs_terminal_id'];

        $response_fields['paysubs_transaction_reference'] = $databaseFields['paysubs_transaction_reference'];

        $response_fields['paysubs_duplicate'] = $databaseFields['paysubs_duplicate'];

        $response_fields['paysubs_cardholder_name'] = $databaseFields['paysubs_cardholder_name'];

        $response_fields['paysubs_authorized_amount'] = $databaseFields['paysubs_authorized_amount'];

        $response_fields['paysubs_card_type'] = $databaseFields['paysubs_card_type'];

        $response_fields['paysubs_cardholder_email'] = $databaseFields['paysubs_cardholder_email'];

        $response_fields['paysubs_budget_period'] = $databaseFields['paysubs_budget_period'];

        $response_fields['paysubs_card_expiry_date'] = $databaseFields['paysubs_card_expiry_date'];

        $response_fields['paysubs_authorisation_reponse_code'] = $databaseFields['paysubs_authorisation_reponse_code'];

        $response_fields['paysubs_cardholder_ip'] = $databaseFields['paysubs_cardholder_ip'];

        $response_fields['paysubs_masked_card_number'] = $databaseFields['paysubs_masked_card_number'];

        $response_fields['paysubs_transaction_type'] = $databaseFields['paysubs_transaction_type'];

        $response_fields['paysubs_retrieval_reference_number'] = $databaseFields['paysubs_retrieval_reference_number'];

        $response_fields['paysubsresponse_raw'] = $databaseFields['paysubsresponse_raw'];

        $response_fields['paysubsresponse_raw'] = $post_msg;

        $return_context = '';

        $this->storePSPluginInternalData( $response_fields, 'virtuemart_order_id', true );

    }

    public function _getTablepkeyValue( $virtuemart_order_id )
    {

        $db = JFactory::getDBO();

        $q = 'SELECT ' . $this->_tablepkey . ' FROM `' . $this->_tablename . '` '

            . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;

        $db->setQuery( $q );

        if ( !( $pkey = $db->loadResult() ) ) {
            JError::raiseWarning( 500, $db->getErrorMsg() );
            return '';
        }

        return $pkey;

    }

    public function _getPaymentStatus( $method, $paysubs_status )
    {

        $new_status = 'P';

        if ( $paysubs_status == '' ) {
            $new_status = 'P';
        }

        if ( strstr( $paysubs_status, 'APPROVED' ) ) {
            $new_status = 'C';
        }

        return $new_status;
    }

    public function plgVmOnShowOrderBEPayment( $virtuemart_order_id, $payment_method_id )
    {

        if ( !$this->selectedThisByMethodId( $payment_method_id ) ) {
            return null;
        }

        if ( !( $paymentTable = $this->_getPaySubsInternalData( $virtuemart_order_id ) ) ) {
            return '';
        }

        $this->getPaymentCurrency( $paymentTable );

        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';

        $db = JFactory::getDBO();

        $db->setQuery( $q );

        $currency_code_3 = $db->loadResult();

        $html = '<table class="adminlist">' . "\n";

        $html .= $this->getHtmlHeaderBE();

        $html .= $this->getHtmlRowBE( 'PAYSUBS_PAYMENT_NAME', $paymentTable->payment_name );
        $code = "paysubs_response_";

        foreach ( $paymentTable as $key => $value ) {

            if ( substr( $key, 0, strlen( $code ) ) == $code ) {

                $html .= $this->getHtmlRowBE( $key, $value );}

        }

        $html .= '</table>' . "\n";

        return $html;

    }

    public function _getPaySubsInternalData( $virtuemart_order_id, $order_number = '' )
    {

        $db = JFactory::getDBO();

        $q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';

        if ( $order_number ) {
            $q .= " `order_number` = '" . $order_number . "'";
        } else {
            $q .= ' `virtuemart_order_id` = ' . $virtuemart_order_id;
        }

        $db->setQuery( $q );

        if ( !( $paymentTable = $db->loadObject() ) ) {
            return '';
        }

        return $paymentTable;
    }

    public function _getMerchantId( $method )
    {

        return $method->paysubs_terminal_id;

    }

    public function _getPaySubsUrl( $method )
    {

        return 'www.vcs.co.za/vvonline/vcspay.aspx';

    }

    public function _getPaySubsUrlHttps( $method )
    {

        return $this->_getPaySubsUrl( $method );

    }

    public function _getPaymentResponseHtml( $paysubsTable, $payment_name )
    {

        $html = '<table>' . "\n";
        $html .= $this->getHtmlRow( 'PAYSUBS_PAYMENT_NAME', $payment_name );

        if ( !empty( $paysubsTable ) ) {
            $html .= $this->getHtmlRow( 'PAYSUBS_ORDER_NUMBER', $paysubsTable->order_number );
        }

        $html .= '</table>' . "\n";

        return $html;
    }

    public function getCosts( VirtueMartCart $cart, $method, $cart_prices )
    {

        if ( preg_match( '/%$/', $method->cost_percent_total ) ) {

            $cost_percent_total = substr( $method->cost_percent_total, 0, -1 );
        } else {

            $cost_percent_total = $method->cost_percent_total;
        }

        return ( $method->cost_per_transaction + ( $cart_prices['salesPrice'] * $cost_percent_total * 0.01 ) );
    }

    public function checkConditions( $cart, $method, $cart_prices )
    {

        $address = (  ( $cart->ST == 0 ) ? $cart->BT : $cart->ST );

        $amount      = $cart_prices['salesPrice'];
        $amount_cond = ( $amount >= $method->min_amount and $amount <= $method->max_amount or ( $method->min_amount <= $amount and ( $method->max_amount == 0 ) ) );

        $countries = array();

        if ( !empty( $method->countries ) ) {

            if ( !is_array( $method->countries ) ) {

                $countries[0] = $method->countries;

            } else {

                $countries = $method->countries;

            }

        }

        if ( !is_array( $address ) ) {

            $address = array();

            $address['virtuemart_country_id'] = 0;

        }

        if ( !isset( $address['virtuemart_country_id'] ) ) {
            $address['virtuemart_country_id'] = 0;
        }

        if ( in_array( $address['virtuemart_country_id'], $countries ) || count( $countries ) == 0 ) {
            if ( $amount_cond ) {

                return true;

            }}

        return false;

    }

    /// This is also from the VM 3 docs
    public function plgVmDeclarePluginParamsCustomVM3( &$data )
    {
        return $this->declarePluginParams( 'custom', $data );
    }

    public function plgVmGetTablePluginParams( $psType, $name, $id, &$xParams, &$varsToPush )
    {
        return $this->getTablePluginParams( $psType, $name, $id, $xParams, $varsToPush );
    }

    /// End of VM 3 docs

    public function plgVmOnStoreInstallPaymentPluginTable( $jplugin_id )
    {
        return $this->onStoreInstallPluginTable( $jplugin_id );
    }

    public function plgVmOnSelectCheckPayment( VirtueMartCart $cart )
    {
        return $this->OnSelectCheck( $cart );
    }

    public function plgVmDisplayListFEPayment( VirtueMartCart $cart, $selected = 0, &$htmlIn )
    {
        return $this->displayListFE( $cart, $selected, $htmlIn );
    }

    public function plgVmonSelectedCalculatePricePayment( VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name )
    {
        return $this->onSelectedCalculatePrice( $cart, $cart_prices, $cart_prices_name );
    }

    public function plgVmOnCheckAutomaticSelectedPayment( VirtueMartCart $cart, array $cart_prices = array() )
    {
        return $this->onCheckAutomaticSelected( $cart, $cart_prices );
    }

    public function plgVmOnShowOrderFEPayment( $virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name )
    {
        $this->onShowOrderFE( $virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name );
    }

    public function plgVmonShowOrderPrintPayment( $order_number, $method_id )
    {
        return $this->onShowOrderPrint( $order_number, $method_id );
    }

    public function plgVmDeclarePluginParamsPaymentVM3( &$data )
    {
        return $this->declarePluginParams( 'payment', $data );
    }

    public function plgVmSetOnTablePluginParamsPayment( $name, $id, &$table )
    {
        return $this->setOnTablePluginParams( $name, $id, $table );
    }

}
