<?php
/*
* ver. 3.0.0
* PayU Payment Modules
*
* @copyright  Copyright 2015 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ControllerPaymentPayU extends Controller
{
    private $error = array();

    //Config page
    public function index()
    {
        $this->load->language('payment/payu');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        //new config
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payu', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
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

        $data['help_merchantposid'] = $this->language->get('help_merchantposid');
        $data['help_signaturekey'] = $this->language->get('help_signaturekey');
        $data['help_total'] = $this->language->get('help_total');

        //error data
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        if (isset($this->error['signaturekey'])) {
            $data['error_signaturekey'] = $this->error['signaturekey'];
        } else {
            $data['error_signaturekey'] = '';
        }
        if (isset($this->error['merchantposid'])) {
            $data['error_merchantposid'] = $this->error['merchantposid'];
        } else {
            $data['error_merchantposid'] = '';
        }

        if (isset($this->request->post['payu_total'])) {
            $data['payu_total'] = $this->request->post['payu_total'];
        } else {
            $data['payu_total'] = $this->config->get('payu_total');
        }

        if (isset($this->request->post['payu_geo_zone_id'])) {
            $data['payu_geo_zone_id'] = $this->request->post['payu_geo_zone_id'];
        } else {
            $data['payu_geo_zone_id'] = $this->config->get('payu_geo_zone_id');
        }
        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        //preloaded config
        if (isset($this->request->post['payu_signaturekey'])) {
            $data['payu_signaturekey'] = $this->request->post['payu_signaturekey'];
        } else {
            $data['payu_signaturekey'] = $this->config->get('payu_signaturekey');
        }
        if (isset($this->request->post['payu_merchantposid'])) {
            $data['payu_merchantposid'] = $this->request->post['payu_merchantposid'];
        } else {
            $data['payu_merchantposid'] = $this->config->get('payu_merchantposid');
        }

        if (isset($this->request->post['payu_status'])) {
            $data['payu_status'] = $this->request->post['payu_status'];
        } else {
            $data['payu_status'] = $this->config->get('payu_status');
        }

        //Status
        if (isset($this->request->post['payu_new_status'])) {
            $data['payu_new_status'] = $this->request->post['payu_new_status'];
        } else {
            $data['payu_new_status'] = $this->config->get('payu_new_status');
        }

        if (isset($this->request->post['payu_cancelled_status'])) {
            $data['payu_cancelled_status'] = $this->request->post['payu_cancelled_status'];
        } else {
            $data['payu_cancelled_status'] = $this->config->get('payu_cancelled_status');
        }

        if (isset($this->request->post['payu_pending_status'])) {
            $data['payu_pending_status'] = $this->request->post['payu_pending_status'];
        } else {
            $data['payu_pending_status'] = $this->config->get('payu_pending_status');
        }

        if (isset($this->request->post['payu_complete_status'])) {
            $data['payu_complete_status'] = $this->request->post['payu_complete_status'];
        } else {
            $data['payu_complete_status'] = $this->config->get('payu_complete_status');
        }

        if (isset($this->request->post['payu_waiting_for_confirmation_status'])) {
            $data['payu_waiting_for_confirmation_status'] = $this->request->post['payu_waiting_for_confirmation_status'];
        } else {
            $data['payu_waiting_for_confirmation_status'] = $this->config->get('payu_waiting_for_confirmation_status');
        }


        if (isset($this->request->post['payu_sort_order'])) {
            $data['payu_sort_order'] = $this->request->post['payu_sort_order'];
        } else {
            $data['payu_sort_order'] = $this->config->get('payu_sort_order');
        }


        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/payu', 'token=' . $this->session->data['token'], 'SSL')
        );

//links
        $data['action'] = $this->url->link('payment/payu', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/payu.tpl', $data));

    } //index


    //validate
    private function validate()
    {
        //permisions
        if (!$this->user->hasPermission('modify', 'payment/payu')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        //check for errors
        if (!$this->request->post['payu_signaturekey']) {
            $this->error['signaturekey'] = $this->language->get('error_signaturekey');
        }
        if (!$this->request->post['payu_merchantposid']) {
            $this->error['merchantposid'] = $this->language->get('error_merchantposid');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('payment/payu');
        $this->model_payment_payu->createDatabaseTables();
    }

    public function uninstall()
    {
        $this->load->model('payment/payu');
        $this->model_payment_payu->dropDatabaseTables();
    }

}