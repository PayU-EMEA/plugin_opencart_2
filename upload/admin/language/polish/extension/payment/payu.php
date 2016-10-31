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
$_['text_module'] = 'Moduły';
$_['text_payu'] = '<a onclick="window.open(\'http://www.payu.pl/\');"><img src="view/image/payment/payu.png" alt="PayU" title="PayU" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success'] = "Sukces: Udało się zmodyfikować moduł 'PayU'!";
$_['text_payment'] = 'Płatność';
$_['text_edit'] = 'Edytuj PayU';

// Entry
$_['entry_merchantposid'] = 'Id punktu płatności';
$_['entry_signaturekey'] = 'Drugi klucz (MD5)';
$_['entry_oauth_client_id'] = 'Protokół OAuth - client_id';
$_['entry_oauth_client_secret'] = 'Protokół OAuth - client_secret';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Kolejność';
$_['entry_complete_status'] = 'Status powiadomienia PayU: Completed';
$_['entry_cancelled_status'] = 'Status powiadomienia PayU: Cancelled';
$_['entry_pending_status'] = 'Status powiadomienia PayU: Pending';
$_['entry_waiting_for_confirmation_status'] = 'Status powiadomienia PayU: Waiting For Confirmation';
$_['entry_new_status'] = 'Status nowego zamówienia';
$_['entry_total'] = 'Suma zamówienia';
$_['entry_geo_zone'] = 'Strefa Geo';

// Help
$_['help_total'] = 'Ta metoda płatności będzie dostępna gdy wartość koszyka przekroczy tę wartość.';

// Error
$_['error_permission'] = "Uwaga: Brak uprawnień do modyfikacji modułu 'PayU'!";
$_['error_merchantposid'] = 'Podanie "Id punktu płatności" jest wymagane!';
$_['error_signaturekey'] = 'Podanie "Drugi klucz (MD5)" jest wymagane!';
$_['error_oauth_client_id'] = 'Podanie "OAuth - client_id" jest wymagane!';
$_['error_oauth_client_secret'] = 'Podanie "OAuth - client_secret" wymagane!';
