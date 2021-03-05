<?php
/*
* PayU Payment Modules
*
* @copyright  Copyright 2015 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ControllerExtensionPaymentPayU extends Controller
{
    private $error = array();
    private $settings = array();

    //Config page
    public function index()
    {
        $this->load->language('extension/payment/payu');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        //new config
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payu', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
        }

        //language data
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['entry_currency'] = $this->language->get('entry_currency');
        $data['entry_merchantposid'] = $this->language->get('entry_merchantposid');
        $data['entry_signaturekey'] = $this->language->get('entry_signaturekey');
        $data['entry_oauth_client_id'] = $this->language->get('entry_oauth_client_id');
        $data['entry_oauth_client_secret'] = $this->language->get('entry_oauth_client_secret');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_complete_status'] = $this->language->get('entry_complete_status');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
        $data['entry_waiting_for_confirmation_status'] = $this->language->get('entry_waiting_for_confirmation_status');
        $data['entry_new_status'] = $this->language->get('entry_new_status');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');

        $data['help_total'] = $this->language->get('help_total');

        //Errors
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_signaturekey'] = isset($this->error['signaturekey']) ? $this->error['signaturekey'] : '';
        $data['error_merchantposid'] = isset($this->error['merchantposid']) ? $this->error['merchantposid'] : '';
        $data['error_oauth_client_id'] = isset($this->error['oauth_client_id']) ? $this->error['oauth_client_id'] : '';
        $data['error_oauth_client_secret'] = isset($this->error['oauth_client_secret']) ? $this->error['oauth_client_secret'] : '';

        //Zones, order statuses
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        //Settings
        $data['payu_total'] = isset($this->request->post['payu_total']) ?
            $this->request->post['payu_total'] : $this->config->get('payu_total');

        $data['payu_geo_zone_id'] = isset($this->request->post['payu_geo_zone_id']) ?
            $this->request->post['payu_geo_zone_id'] : $this->config->get('payu_geo_zone_id');

        $data['payu_signaturekey'] = isset($this->request->post['payu_signaturekey']) ?
            $this->request->post['payu_signaturekey'] : $this->config->get('payu_signaturekey');

        $data['payu_merchantposid'] = isset($this->request->post['payu_merchantposid']) ?
            $this->request->post['payu_merchantposid'] : $this->config->get('payu_merchantposid');

        $data['payu_oauth_client_id'] = isset($this->request->post['payu_oauth_client_id']) ?
            $this->request->post['payu_oauth_client_id'] : $this->config->get('payu_oauth_client_id');

        $data['payu_oauth_client_secret'] = isset($this->request->post['payu_oauth_client_secret']) ?
            $this->request->post['payu_oauth_client_secret'] : $this->config->get('payu_oauth_client_secret');

        $data['payu_status'] = isset($this->request->post['payu_status']) ?
            $this->request->post['payu_status'] : $this->config->get('payu_status');

        $data['payu_sort_order'] = isset($this->request->post['payu_sort_order']) ?
            $this->request->post['payu_sort_order'] :  $this->config->get('payu_sort_order');

        //Status
        $data['payu_new_status'] = isset($this->request->post['payu_new_status']) ?
            $this->request->post['payu_new_status'] : $this->config->get('payu_new_status');

        $data['payu_cancelled_status'] = isset($this->request->post['payu_cancelled_status']) ?
            $this->request->post['payu_cancelled_status'] : $this->config->get('payu_cancelled_status');

        $data['payu_pending_status'] = isset($this->request->post['payu_pending_status']) ?
            $this->request->post['payu_pending_status'] : $this->config->get('payu_pending_status');

        $data['payu_complete_status'] = isset($this->request->post['payu_complete_status']) ?
            $this->request->post['payu_complete_status'] : $this->config->get('payu_complete_status');

        $data['payu_waiting_for_confirmation_status'] = isset($this->request->post['payu_waiting_for_confirmation_status']) ?
             $this->request->post['payu_waiting_for_confirmation_status']:  $this->config->get('payu_waiting_for_confirmation_status');


        //Breadcroumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/payu', 'token=' . $this->session->data['token'], 'SSL')
        );

        //links
        $data['action'] = $this->url->link('extension/payment/payu', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL');


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/payu', $data));

    } //index


    //validate
    private function validate()
    {
        //permisions
        if (!$this->user->hasPermission('modify', 'extension/payment/payu')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        //check for errors
        if (!$this->request->post['payu_signaturekey']) {
            $this->error['signaturekey'] = $this->language->get('error_signaturekey');
        }
        if (!$this->request->post['payu_merchantposid']) {
            $this->error['merchantposid'] = $this->language->get('error_merchantposid');
        }
        if (!$this->request->post['payu_oauth_client_id']) {
            $this->error['oauth_client_id'] = $this->language->get('error_oauth_client_id');
        }
        if (!$this->request->post['payu_oauth_client_secret']) {
            $this->error['oauth_client_secret'] = $this->language->get('error_oauth_client_secret');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/payment/payu');
        $this->load->model('setting/setting');

        $this->settings = array(
            'payu_new_status' => 1,
            'payu_pending_status' => 1,
            'payu_complete_status' => 5,
            'payu_cancelled_status' => 7,
            'payu_waiting_for_confirmation_status' => 1,
            'payu_geo_zone_id' => 0,
            'payu_sort_order' => 1,
        );
        $this->model_setting_setting->editSetting('payu', $this->settings);
        $this->model_extension_payment_payu->createDatabaseTables();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/payu');
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('payu');
        $this->model_extension_payment_payu->dropDatabaseTables();
    }

}