<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Patientidentificationlabels extends Isp_Controller {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -  
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in 
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    var $module_name = 'patientidentificationlabels';
    
    //var $model_name = 'Patientidentificationlabels_Model';    
    var $model_name = 'Patientlabels_Model'; 
	var $WIPUserTaskReasonSerializeArray = WIPUserTaskReasonSerializeArray; //"Ready For Shipping" WIP status of sales order

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model($this->model_name);
        $this->login_check();
        $this->load->library('form_validation');
        // Your own constructor code
    }
	
	function index($msg='',$salesorders_inserted=0,$salesorders_updated=0 )
	{	
		$data['salesorders'] = $this->Patientlabels_Model->getSalesOrders();
		
		if(count($data['salesorders']) > 0) {
			foreach($data['salesorders'] as &$salesorder) {
				$salesorder->labels = $this->Patientlabels_Model->getLabels($salesorder->ID);
			}		
		}
		
		if($msg=='salesorders_updated') {
			$data['msg']='New Sales Orders Inserted: '.$salesorders_inserted.'<br> Sales Orders updated: '.$salesorders_updated;
		}else if ($msg=='salesorder_deleted') {
			$data['msg']='Sales Order deleted successfully ';
		}
		$this->load->view('patientidentificationlabels/salesorders',$data);	
	}

	function labels($salesorder_id=0,$msg='',$labeladded=0)
	{	
		$data['labels'] = $this->Patientlabels_Model->getLabels($salesorder_id);		
		$get_sales_orders = $this->Patientlabels_Model->__select_table(array('ID' => $salesorder_id),'sales_order_wipinfo');
		$sales_orders = $get_sales_orders->result_array();
		if(count($sales_orders) > 0) {
			$data['sales_order_brightree_id'] = $sales_orders[0]['sales_order_id'];
			$data['sales_order_table_id'] = $salesorder_id;
		}else {
			$data['sales_order_brightree_id'] ='';
			$data['sales_order_table_id'] ='';
		}
		if($msg=='newlabel_added') {
			$data['msg']=$labeladded.' new label added to the sales order';
		}else if($msg=='label_deleted') {
			$data['msg']='Label deleted successfully';
		}		
		$this->load->view('patientidentificationlabels/labels',$data);	
	}

	function addnewlabel($salesorder_id=0,$labels=1)
	{
		if($salesorder_id) {
			$this->Patientlabels_Model->insertLabelsOfSalesOrder($labels,$salesorder_id);
			$msg = 'newlabel_added';
			$labeladded = $labels;
			redirect(site_url('patientidentificationlabels/labels/' . $salesorder_id.'/'.$msg.'/'.$labeladded));
		}
		die('No Sales Order Given');
	}
	
	function delete_label($label_id=0,$salesorder_id=0)  {
		if ($this->session->userdata('user_level') == '1') {		 
			
			//delete affected patients first
			$table_name = 'sales_order_labels';
			$form_data['ID'] = $label_id;
            $this->{$this->model_name}->__delete_table($form_data,$table_name);
			
			$msg = 'label_deleted';								
			redirect(site_url('patientidentificationlabels/labels/' . $salesorder_id.'/'.$msg));			
		}else
            echo "You have no permission to access this page.";	
	}
	
	public function delete_salesorder($salesorder_id=0)  {
		if ($this->session->userdata('user_level') == '1') {		 
			
			//delete all labels of the sales order
			$this->delete_all_labels($salesorder_id);
			
			//delete sales order
			$table_name = 'sales_order_wipinfo';
			$form_data['ID'] = $salesorder_id;
			$this->{$this->model_name}->__delete_table($form_data,$table_name);
			
			$msg = 'salesorder_deleted';								
			redirect(site_url('patientidentificationlabels/index/'.$msg));			
		}else
			echo "You have no permission to access this page.";	
	}

	public function delete_all_labels($salesorder_id=0)  {
		//delete affected patients first
		$table_name = 'sales_order_labels';
		$form_data['salesordertable_id'] = $salesorder_id;
		$this->{$this->model_name}->__delete_table($form_data,$table_name);		
		return;
	}
	
	public function generate_barcode_image($text="",$size="20",$orientation="horizontal"){
		$this->load->library('Barcode');
		$this->barcode->generate_barcode(array(
			'text' => $text,
			'size'=>$size,
			'orientation'=>$orientation
		));
	}
	
    public function fetch_sales_order_ready_for_shipping($id = '', $msg = '', $redirect = 'true') {
		//load library
		$this->load->library('Salesorders_With_Custom_Fields');		
		$AllResults = array();
		
		foreach(unserialize($this->WIPUserTaskReasonSerializeArray) as $WIPUserTaskReason) {		
			$result = $this->salesorders_with_custom_fields->fetch_sales_order_ready_for_shipping(array(
				'start_date' => '',
				'end_date' => '',
				'records_per_page' => 1000,
				'page' => 1,
				'WIPUserTaskReason'=>$WIPUserTaskReason
			));	
			$AllResults = array_merge($AllResults,$result);
		}
		
		$records= $this->Patientlabels_Model->updateSalesOrders($AllResults);
		$msg='salesorders_updated';
		redirect(site_url('patientidentificationlabels/index/' . $msg.'/'.$records['inserted'].'/'.$records['updated']));
    }
}