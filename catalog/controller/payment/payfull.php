<?php
class ControllerPaymentPayfull extends Controller {

	public function index() {

		$this->language->load('payment/payfull');
		
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['month_valid'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['month_valid'][] = array(
				'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_valid'] = array();

		for ($i = $today['year']; $i < $today['year'] + 20; $i++) {
			$data['year_valid'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}

		$data['entry_cc_name'] = $this->language->get('entry_cc_name');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_date'] = $this->language->get('entry_cc_date');
		$data['entry_cc_cvc'] = $this->language->get('entry_cc_cvc');

		$data['text_invalid_card'] = $this->language->get('text_invalid_card'); 
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['text_3d'] = $this->language->get('text_3d');
		$data['text_installments'] = $this->language->get('text_installments');
		$data['text_wait'] = $this->language->get('text_wait'); 
		$data['text_loading'] = $this->language->get('text_loading'); 

		//for opencart less than 2.x
		/*if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payfull.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/payfull.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/payfull.tpl', $data);
		}*/

		return $this->load->view('payment/payfull.tpl', $data);
	}

	public function get_card_info(){

		if(empty($this->request->post['cc_number'])){
			exit();
		}

		$this->load->model('payment/payfull');
		
		$json = array();
		$json['has3d'] = 0;
		$json['installments'] = array(array('count' => 1));
		
		//{"brand":"VISA","type":"CREDIT","level":"CLASSIC","network":"AXESS","issuer":"AKBANK T.A.S.","virtual":false,"country":"TUR","bank_id":"Akbank"}}
		$card_info = json_decode($this->model_payment_payfull->get_card_info(), true);

		$result = json_decode($this->model_payment_payfull->getInstallments(), true);

		$bank_info = array();

		if(isset($card_info['data']['bank_id'])) {
			foreach($result['data'] as $temp) {
				if($temp['bank'] == $card_info['data']['bank_id']) {
					$bank_info = $temp;
				}
			}	
		}

		if($bank_info) {
			$this->session->data['bank_id'] = $bank_info['bank'];
			$this->session->data['gateway'] = $bank_info['gateway'];

			$json['bank_id'] = $bank_info['bank'];
			
			//get installment info 
			$payfull_3dsecure_status = $this->config->get('payfull_3dsecure_status');
			$payfull_installment_status = $this->config->get('payfull_installment_status');

			//$json['installments'] = $bank_info['installments'];
			$json['installments'] = array_merge(array(array('count' => 1)), $bank_info['installments']);

			$json['has3d'] =  $bank_info['has3d'];

			if(!$payfull_3dsecure_status){
				$json['has3d'] = 0;
			}

			if(!$payfull_installment_status){
				$json['installments'] = array(array('count' => 1));
			}
		}
		
		header('Content-type: text/json');
		echo json_encode($json);
	}

	//send details to bank api 
	public function send(){
		$this->load->model('payment/payfull');

		$json = array();

		$response = $this->model_payment_payfull->send();	

		$data = json_decode($response, true);

		if (isset($data['ErrorCode'])) { 

			//for successfull payment without error 
			if($data['ErrorCode'] == '00'){

				$this->model_payment_payfull->saveResponse($data);

				$this->model_checkout_order->addOrderHistory($data['passive_data'], $this->config->get('payfull_order_status_id'));

				$json['success'] = $this->url->link('checkout/success');
			}else{
				$json['error'] = $data['ErrorMSG'];
			}
		}else{
			
			$this->db->query('insert into `'.DB_PREFIX.'payfull_3d_form` SET html="'.htmlspecialchars($response).'"');
			$this->session->data['payfull_3d_form_id'] = $this->db->getLastId();

			$json['success'] = $this->url->link('payment/payfull/secure');
		}
		
		echo json_encode($json);
	}

	//submit data to 3dsecure page 
	public function secure(){
		$html = $this->db->query('select html from `'.DB_PREFIX.'payfull_3d_form` WHERE payfull_3d_form_id = "'.$this->session->data['payfull_3d_form_id'].'"')->row['html'];
		
		//delete form 
		$this->db->query('delete from `'.DB_PREFIX.'payfull_3d_form` WHERE payfull_3d_form_id = "'.$this->session->data['payfull_3d_form_id'].'"');

		echo htmlspecialchars_decode($html);
	}

	
	/*
	------------------------------------
	callback from bank 
	------------------------------------
	Array
	(
	    [type] => Sale
	    [status] => 1
	    [ErrorMSG] => 
	    [ErrorCode] => 00
	    [transaction_id] => T4U_9b8b678785_42d6d9213
	    [passive_data] => 24
	    [original_currency] => USD
	    [total] => 312.59
	    [currency] => TRY
	    [conversion_rate] => 0.3391
	    [bank_id] => Akbank
	    [use3d] => 1
	    [installments] => 2
	    [time] => 02-06-2016 05:22:08
	    [confirm_action] => 0
	)
	*/
	public function callback() {

		$this->load->model('payment/payfull');

		//save response 
		$this->model_payment_payfull->saveResponse($this->request->post);

		if (isset($this->request->post['passive_data'])) {
			$order_id = $this->request->post['passive_data'];
		} else {
			$order_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info && $this->request->post['ErrorCode'] == '00') {			
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payfull_order_status_id'));
			$this->response->redirect($this->url->link('checkout/success'));
		}else{
			$this->response->redirect($this->url->link('checkout/failure'));
		}
	}
}