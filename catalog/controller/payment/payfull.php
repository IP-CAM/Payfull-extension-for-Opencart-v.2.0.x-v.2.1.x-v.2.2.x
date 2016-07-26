<?php
class ControllerPaymentPayfull extends Controller {

	public function index() {

		$this->language->load('payment/payfull');

		$data['entry_payfull_installmet'] 	= $this->language->get('entry_payfull_installmet');
		$data['entry_payfull_amount'] 		= $this->language->get('entry_payfull_amount');
		$data['entry_payfull_total'] 		= $this->language->get('entry_payfull_total');

		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['month_valid'] = [];
		$data['month_valid'][] = [
			'text' => $this->language->get('entry_cc_month'),
			'value' =>''
		];

		for ($i = 1; $i <= 12; $i++) {
			$data['month_valid'][] = array(
				'text'  => sprintf('%02d', $i).' - '.strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_valid'] = [];
		$data['year_valid'][] = [
			'text' => $this->language->get('entry_cc_year'),
			'value' =>''
		];
		for ($i = $today['year']; $i < $today['year'] + 17; $i++) {
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

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $base_url = $this->config->get('config_ssl');
        } else {
            $base_url = $this->config->get('config_url');
        }

		$data['visa_img_path']   = $base_url.'image/payfull/payfull_creditcard_visa.png';
		$data['master_img_path'] = $base_url.'image/payfull/payfull_creditcard_master.png';
		$data['not_supported_img_path'] = $base_url.'image/payfull/payfull_creditcard_not_supported.png';

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$total 		= $this->currency->format($order_info['total'], $order_info['currency_code'], true, true);
		$data['total']         = $total;

		//for opencart less than 2.x
		/*if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payfull.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/payfull.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/payfull.tpl', $data);
		}*/

		return $this->load->view('payment/payfull.tpl', $data);
	}

	public function get_card_info(){
		$this->load->model('checkout/order');
		$this->load->model('payment/payfull');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		//default data
		$defaultTotal 			=	$this->currency->format($order_info['total'], $order_info['currency_code'], true, true);
		$json 					= array();
		$json['has3d'] 			= 0;
		$json['installments'] 	= [['count' => 1, 'installment_total'=>$defaultTotal, 'total'=>$defaultTotal]];

		//no cc number
		if(empty($this->request->post['cc_number'])){
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}

		//get info from API about bank + card + instalments
		$card_info  		= json_decode($this->model_payment_payfull->get_card_info(), true);
		$installments_info 	= json_decode($this->model_payment_payfull->getInstallments(), true);
		$bank_info 			= array();

		//no bank is detected
		if(!isset($card_info['data']['bank_id']) Or $card_info['data']['bank_id'] == '') {
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}

		foreach($installments_info['data'] as $temp) {
			if($temp['bank'] == $card_info['data']['bank_id']) {
				$bank_info = $temp;
				break;
			}
		}

		//card bank is not in the list of installments
		if(!count($bank_info)) {
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}


		$payfull_3dsecure_status 	= $this->config->get('payfull_3dsecure_status');
		$payfull_installment_status = $this->config->get('payfull_installment_status');
		$oneShotTotal 				= $this->currency->format($order_info['total'], $order_info['currency_code'], true, true);
		$json['has3d'] 				= ($payfull_3dsecure_status)?1:0;

		//installments is not allowed for some reason
		if(!$payfull_installment_status){
			$json['installments'] = [['count' => 1, 'installment_total'=>$oneShotTotal, 'total'=>$oneShotTotal]];
			header('Content-type: text/json');
			echo json_encode($json);
			exit;
		}


		$this->session->data['bank_id'] = $bank_info['bank'];
		$this->session->data['gateway'] = $bank_info['gateway'];
		$json['bank_id'] 				= $bank_info['bank'];

		foreach($bank_info['installments'] as $justNormalKey=>$installment){
			$commission = $installment['commission'];
			$commission = str_replace('%', '', $commission);
			$total      = $order_info['total'] + ($order_info['total'] * $commission/100);
			$total      = $this->currency->format($total, $order_info['currency_code'], true, true);
			$bank_info['installments'][$justNormalKey]['total'] = $total;

			$installment_total = ($order_info['total'] + ($order_info['total'] * $commission/100))/$installment['count'];
			$installment_total = $this->currency->format($installment_total, $order_info['currency_code'], true, true);
			$bank_info['installments'][$justNormalKey]['installment_total'] = $installment_total;
		}


		$json['installments'] = array_merge(
			[
				['count' => 1, 'installment_total'=>$oneShotTotal, 'total'=>$oneShotTotal]
			],
			$bank_info['installments']
		);


		header('Content-type: text/json');
		echo json_encode($json);
		exit;
	}

	//send details to bank api 
	public function send(){
		$this->load->model('payment/payfull');

		$json = array();

		$error = $this->validation();
		if(count($error)){
			$json['error'] = $error;
			echo json_encode($json);
			exit;
		}

		$response = $this->model_payment_payfull->send();	

		$data = json_decode($response, true);

		if (isset($data['ErrorCode'])) { 

			//for successfull payment without error 
			if($data['ErrorCode'] == '00'){

				$this->model_payment_payfull->saveResponse($data);

				$this->model_checkout_order->addOrderHistory($data['passive_data'], $this->config->get('payfull_order_status_id'));

				$json['success'] = $this->url->link('checkout/success');
			}else{
				$json['error']['general_error'] = $data['ErrorMSG'];
			}
		}else{
			
			$this->db->query('insert into `'.DB_PREFIX.'payfull_3d_form` SET html="'.htmlspecialchars($response).'"');
			$this->session->data['payfull_3d_form_id'] = $this->db->getLastId();

			$json['success'] = $this->url->link('payment/payfull/secure');
		}
		
		echo json_encode($json);
	}

	//send details to bank api
	public function validation(){
		$this->language->load('payment/payfull');
		$error = [];

		if(!isset($this->request->post['cc_name']) OR $this->request->post['cc_name'] == ''){
			$error['cc_name'] = $this->language->get('entry_cc_name').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_number']) OR $this->request->post['cc_number'] == ''){
			$error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_month']) OR $this->request->post['cc_month'] == ''){
			$error['cc_month'] = $this->language->get('entry_cc_month').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_year']) OR $this->request->post['cc_year'] == ''){
			$error['cc_year'] = $this->language->get('entry_cc_year').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_cvc']) OR $this->request->post['cc_cvc'] == ''){
			$error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_required');
		}

		if(!isset($this->request->post['cc_cvc']) OR $this->request->post['cc_cvc'] == ''){
			$error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_required');
		}

        //------------------------------------
        if(isset($this->request->post['cc_number']) AND !is_numeric($this->request->post['cc_number']) ){
            $error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_cvc']) AND !is_numeric($this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_is_not_number');
        }

        //------------------------------------
        if(isset($this->request->post['cc_number']) AND !is_numeric($this->request->post['cc_number']) ){
            $error['cc_number'] = $this->language->get('entry_cc_number').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_number']) AND $this->checkCCNumber($this->request->post['cc_number']) == ''){
            $error['cc_number'] = $this->language->get('entry_cc_not_supported');
        }
        if(isset($this->request->post['cc_cvc']) AND !is_numeric($this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_field_is_not_number');
        }
        if(isset($this->request->post['cc_cvc']) AND  !$this->checkCCCVC($this->request->post['cc_number'], $this->request->post['cc_cvc']) ){
            $error['cc_cvc'] = $this->language->get('entry_cc_cvc').' '. $this->language->get('entry_cc_cvc_wrong');
        }
        if(isset($this->request->post['cc_month']) AND isset($this->request->post['cc_year']) AND !$this->checkCCEXPDate($this->request->post['cc_month'], $this->request->post['cc_year']) ){
            $error['cc_year'] = $this->language->get('entry_cc_date_wrong');
            $error['cc_month'] = $this->language->get('entry_cc_date_wrong');
        }


		return $error;
	}

    public function checkCCNumber($cardNumber){
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $len = strlen($cardNumber);
        if ($len < 15 || $len > 16) {
            return '';
        }else {
            switch($cardNumber) {
                case(preg_match ('/^4/', $cardNumber) >= 1):
                    return 'VISA';
                    break;
                case(preg_match ('/^5[1-5]/', $cardNumber) >= 1):
                    return 'MASTERCARD';
                    break;
                default:
                    return '';
                    break;
            }
        }
    }

    public function checkCCCVC($cardNumber, $cvc){
        // Get the first number of the credit card so we know how many digits to look for
        $firstnumber = (int) substr($cardNumber, 0, 1);
        if ($firstnumber === 3){
            if (!preg_match("/^\d{4}$/", $cvc)){
                // The credit card is an American Express card but does not have a four digit CVV code
                return false;
            }
        }
        else if (!preg_match("/^\d{3}$/", $cvc)){
            // The credit card is a Visa, MasterCard, or Discover Card card but does not have a three digit CVV code
            return false;
        }
        return true;
    }

    public function checkCCEXPDate($month, $year){
        if(strtotime('01/'.$month.'/'.$year) <= time()){
            return false;
        }
        return true;
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