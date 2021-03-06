<?php 
class Patientlabels_Model extends Isp_Model 
{
 	var $table_name  = 'offer_lists';
	var $limit = 10;
	var $id_key = "list_id";
        
        function __construct()
        {
            // Call the Model constructor
            parent::__construct();
            $this->limit = 10;
            
        }
	
        //select the slected rows from product table.
	function __select($form_data)
	{
            $forms = $this->bind($form_data, $this->table_name);
            $this->db->select('*');
            $this->db->from($this->table_name);
            $this->db->where($forms[$this->table_name]);
            $qry = $this->db->get();
            return $qry;
	}
        
        //select all rows from the product table
        function __select_all_data($order_by = '',$form_data = array())
        {
            $forms = $this->bind($form_data, $this->table_name);
            $this->db->select('*');
            $this->db->from($this->table_name);
            if($order_by != '')
             $this->db->order_by($order_by, "asc");
            $qry = $this->db->get();
            return $qry;
	 }
         
         //select all rows from the product table
        function __select_all_datas_table($table_name, $order_by = '',$form_data = array(), $order='asc')
        {
            $forms = $this->bind($form_data, $table_name);
            $this->db->select('*');
            $this->db->from($table_name);
            if($order_by != '')
             $this->db->order_by($order_by, $order);
            $qry = $this->db->get();
            return $qry;
	 }
         
         //select the selected rows
        function __select_table($form_data, $table_name, $order_by='', $where= '')
	{
            $forms = $this->bind($form_data, $table_name);
            $this->db->select('*');
            $this->db->from($table_name);
            $this->db->where($forms[$table_name]); 
            if($where != '')
                $this->db->where('list_id !=',$where);
            if($order_by != '')
              $this->db->order_by($order_by, "asc");
            $qry = $this->db->get();
            return $qry;
	}
         //delete the selected rows
        function __delete_table($form_data, $table_name)
	{
            $forms = $this->bind($form_data, $table_name);
			$this->db->where($forms[$table_name]); 
            $this->db->delete($table_name);            
	}
        //update the value in the table
        function __update_table($where, $form_data, $table_name)
	{
            $this->db->where($where);
            $this->db->update($table_name, $form_data); 
	} 
        
        //insert the new row in the table
        public function __insert_table($form_data, $table_name)
	{
            //pre($form_data);
            $forms = $this->bind($form_data, $table_name);
            $this->msg = 'insert';
            $this->db->insert($table_name,$forms[$table_name]);
            return $this->db->insert_id();
	}
        
        //here update the order of products
         function __update_order($array = array(), $list_id = '')
         {             
            $count = 1;
            foreach ($array as $id=>$value) 
            {
                $query = "UPDATE `offers` SET `order` =".$count." WHERE `retailer_id` =".$value." AND `list_id` =".$list_id;
                $this->db->query($query);
                $count ++;	
            }
         }
         
         public function get_max_order_val($list_id)
         {
         	$qry = "SELECT max(`order`) FROM `offers` WHERE list_id = '".$list_id."'";
         	$res = $this->db->query($qry);
         	$get_val = $res->result_array();
         	if(!empty($get_val))
         		return $get_val[0]['max(`order`)'];
         	else
         		return '0';
	}
	
	public function updateSalesOrders($result)
	{
		if(count($result)>0)
		{
			$u=0;
			$i=0;
			foreach($result as $res)
			{
				$sales_order_id = $res['sales_order_id'];
				$sales_order_id_array[]=$sales_order_id;
				$isExist = $this->isSalesorderExist($sales_order_id);
				if($isExist==true)
				{
					$this->db->where('sales_order_id', $sales_order_id);
					
					//do not update field 'labels_required'
					//unset($res['labels_required']);
					$this->db->update('sales_order_wipinfo', $res); 
					$u++; 
				}
				else
				{					
					$this->db->insert('sales_order_wipinfo', $res);
					$this->insertLabelsOfSalesOrder($res['labels_required'],$this->db->insert_id());
					$i++;
				}				
				
			}
			
			//update shipped value if sales order exist in database but no longer in "Ready For Shipping" state
			$data=array('shipped'=>1);			
			$st="sales_order_id NOT IN (".implode(",",$sales_order_id_array).")";
			$this->db->where($st, NULL, FALSE);  
			$this->db->update('sales_order_wipinfo',$data);			
			
			$return['inserted']=$i;
			$return['updated']=$u;
			return $return;
		}
	}
	
	public function insertLabelsOfSalesOrder($row,$salesorder_id)
	{
		if($row) {
				for($i=1;$i<=$row;$i++) {
					$barcode =  mt_rand(100000, 999999);
					while($this->isLabelExist($barcode)) {
						$barcode = mt_rand(100000, 999999);
					}
					$res['barcode'] = $barcode;
					$res['salesordertable_id'] = $salesorder_id;
					$this->db->insert('sales_order_labels', $res);
				}
			return true;
		}		
		return false;			
	}
	
	function isLabelExist($barcode){
		$query = $this->db->get_where('sales_order_labels', array('barcode' => $barcode));
		if ($query->num_rows() > 0)
		{
			return true;
		} 
		return false;		
	}
	
	function isSalesorderExist($sales_order_id)
	{
		$query = $this->db->get_where('sales_order_wipinfo', array('sales_order_id' => $sales_order_id));
		if ($query->num_rows() > 0)
		{
			return true;
		} 
		return false;
	}
    
	function getSalesOrders($id='')
	{        
		$this->db->select('*');
		$this->db->from('sales_order_wipinfo');
		if($id!='')
		{
			$this->db->where('ID', $id);
		}
		$qry = $this->db->get()->result();
		return $qry;
	}
	
	function getLabels($salesorder_id='')
	{        
		$this->db->select('*');
		$this->db->from('sales_order_labels');
		if($salesorder_id!='')
		{
			$this->db->where('salesordertable_id', $salesorder_id);
		}
		$qry = $this->db->get()->result();
		return $qry;
	}
    
	function getAllCounties($id='')
	{        
		$this->db->distinct();
		$this->db->select('county');
		$this->db->from('county_taxzone_mapping');
		$qry = $this->db->get()->result();
		return $qry;
	}
         	
}