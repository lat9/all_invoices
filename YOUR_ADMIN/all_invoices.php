<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//
// ===============================================================================
// Add-On: All Invoices
// Designed for: Zen Cart
// Created by: Mathew O'Marah (www.mdodesign.co.uk)
// -------------------------------------------------------------------------------
// Version 2 modified by: lat9 (lat9@vinosdefrutastropicales.com) for v1.5.0
//
// v2.0.0, 2012-05-18:
//      - Include check to see if any orders with the requested status exist.  The
//        previous version resulted in a pseudo-whitepage in this case.
//      - Moved all language-related text to the language files.
//      - Use built-in Zen Cart function to create the order status dropdown.
//
// v2.5 2016-03-16 - Added ability to update order status for displayed/printed orders.
// -------------------------------------------------------------------------------
// Donations:  Please support Zen Cart!  paypal@zen-cart.com  - Thank you!
// ===============================================================================

require('includes/application_top.php');
$orderStatus   = '';
$order_message = '';

$statuses_array = $orders_status_array = array();
$statuses       = $db->Execute("select orders_status_id, orders_status_name
                          from " . TABLE_ORDERS_STATUS . "
                          where language_id = '" . (int)$_SESSION['languages_id'] . "'
                          order by orders_status_id");

while (!$statuses->EOF) {
    $statuses_array[] = array(
        'id'   => $statuses->fields['orders_status_id'],
        'text' => $statuses->fields['orders_status_name'] . ' [' . $statuses->fields['orders_status_id'] . ']',
    );

    $orders_status_array[$statuses->fields['orders_status_id']] = $statuses->fields['orders_status_name'];
    $statuses->MoveNext();
}

if (isset($_GET['status'])) {
    $orderStatus  = (int)$_GET['status'];
    $orders_check = $db->Execute("SELECT count(*) AS total FROM " . TABLE_ORDERS . " WHERE orders_status = $orderStatus");
    if ($orders_check->fields['total'] > 0) {

        // get actual orderids from database
        $sql    = "SELECT orders_id FROM " . TABLE_ORDERS . " WHERE orders_status = " . $orderStatus;
        $result = $db->Execute($sql);
        while (!$result->EOF) {
            $selected_orders[] = $result->fields['orders_id'];
            $result->MoveNext();
        }
    }
} elseif (isset($_POST['orderids'])) {
    $pending_orders = explode(',', preg_replace('/[^0-9,]/', ',', $_POST['orderids']));
    require('includes/templates/tpl_all_invoices.php');
    exit(0);
} elseif (isset($_POST['resetids'])) {
    // clean POST list back to an iterable array
    $pending_orders = preg_replace('/[^0-9,]/', ',', $_POST['resetids']);
    $pending_orders = preg_replace('/,+/', ',', $pending_orders);
    $pending_orders = explode(',', $pending_orders);

    $status = 2; // set to Processed
    foreach ($pending_orders as $oID) {
        $sql = "UPDATE " . TABLE_ORDERS . " SET orders_status = " . (int)$status . ", last_modified = now() where orders_id = " . (int)$oID;
        $db->Execute($sql);
        $db->Execute("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
            (orders_id, orders_status_id, date_added, customer_notified)
            values ('" . (int)$oID . "', " . (int)$status . ", now(), 0)");
    }
    $messageStack->add('Orders updated to "Processed"', 'success');
} else {
    $order_message = sprintf(WARNING_NO_ORDERS, zen_get_order_status_name($orderStatus));
}
?>
    <!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html <?php echo HTML_PARAMS; ?>>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
        <title><?php echo TITLE; ?></title>
        <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
        <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
        <script language="javascript" src="includes/menu.js"></script>
        <script language="javascript" src="includes/general.js"></script>
        <script type="text/javascript">
            <!--
            function init() {
                cssjsmenu('navbar');
                if (document.getElementById) {
                    var kill = document.getElementById('hoverJS');
                    kill.disabled = true;
                }
            }
            // -->
        </script>
    </head>
    <body onLoad="init()">
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->

    <!-- body //-->
    <table border="0" width="100%" cellspacing="2" cellpadding="2">
        <tr>
            <!-- body_text //-->

            <td width="100%" valign="top">
                <table border="0" width="100%" cellspacing="0" cellpadding="2">
                    <tr>
                        <td>
                            <table border="0" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
                                    <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p><?php echo TEXT_ALL_INVOICE_INFO; ?></p>
                            <?php
                            if ($order_message != '') {
                                ?>
                                <p><?php echo $order_message; ?></p>
                                <?php
                            }
                            ?>
                            <?php if (!isset($_GET['status'])) { ?>
                                <form style="margin-left: 20px;" action="all_invoices.php" method="get">
                                    <?php
                                    echo zen_draw_pull_down_menu('status', $statuses_array);  /*v2.0.0-c-lat9*/
                                    ?>
                                    <br>
                                    &nbsp;&nbsp;&nbsp;&nbsp; 1. <input type="submit" value="Get List">
                                </form>
                            <?php } else { ?>
                                &nbsp;&nbsp;&nbsp;&nbsp; 1. Selected STATUS=<?php echo (int)$orderStatus . '-' . $orders_status_array[$orderStatus]; ?>
                            <?php } ?>
                            <?php if (count($selected_orders)) { ?>
                                <br>
                                <form style="margin-left: 20px;" action="all_invoices.php" method="post" target="_new">
                                    <?php
                                    echo zen_draw_hidden_field('orderids', implode(',', $selected_orders));
                                    ?>
                                    2. <input type="submit" value="Generate"> (will open <?php echo count($selected_orders); ?> orders in a new window)
                                </form>

                                <br>
                                <form style="margin-left: 20px;" action="all_invoices.php" method="post">
                                    <?php
                                    echo zen_draw_hidden_field('resetids', implode(',', $selected_orders));
                                    ?>
                                    3. <input type="submit" value="Change Status to 2-Processed"> for <?php echo count($selected_orders); ?> orders.
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- body_text_eof //-->
        </tr>
    </table>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
    <br>
    </body>
    </html>
<?php

require(DIR_WS_INCLUDES . 'application_bottom.php');
