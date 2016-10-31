<?php
/*
* PayU Payment Modules
*
* @copyright  Copyright 2015 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/

// Heading
$_['heading_title'] = 'PayU';

// Text
$_['text_module'] = 'Modules';
$_['text_payu'] = '<a onclick="window.open(\'http://www.payu.pl/\');"><img src="view/image/payment/payu.png" alt="PayU" title="PayU" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success'] = 'Success: You have modified the PayU payment extension!';
$_['text_payment'] = 'Payment';
$_['text_edit'] = 'Edit PayU';

// Entry
$_['entry_merchantposid'] = 'POS ID';
$_['entry_signaturekey'] = 'Second key (MD5)';
$_['entry_oauth_client_id'] = 'OAuth protocol - client_id';
$_['entry_oauth_client_secret'] = 'OAuth protocol - client_secret';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_complete_status'] = 'PayU Notifications Status: Completed';
$_['entry_cancelled_status'] = 'PayU Notifications Status: Cancelled';
$_['entry_pending_status'] = 'PayU Notifications Status: Pending';
$_['entry_waiting_for_confirmation_status'] = 'PayU Notifications Status: Waiting For Confirmation';
$_['entry_new_status'] = 'Status of the new transaction';
$_['entry_total'] = 'Total';
$_['entry_geo_zone'] = 'Geo Zone';

// Help
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify module PayU !';
$_['error_merchantposid'] = '"POS ID" is required!';
$_['error_signaturekey'] = '"Second key (MD5)" is required!';
$_['error_oauth_client_id'] = '"OAuth protocol - client_id" is required!';
$_['error_oauth_client_secret'] = '"OAuth protocol - client_secret" is required!';
