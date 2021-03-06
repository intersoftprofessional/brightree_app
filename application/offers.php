<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Offers extends Isp_Controller 
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -  
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in 
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    var $module_name = 'offers';
    var $list_view = 'offers/list_view';
    var $order_view = 'offers/order_view';
    var $model_name = 'Offers_Model';
    var $edit_view = 'offers/edit_view';
    
    public function __construct()
    {
            parent::__construct();
            $this->load->helper('url');  
            $this->load->model($this->model_name);
            $this->login_check();
            $this->load->library('form_validation');
            // Your own constructor code
    }
    
    public function list_view($id = '', $msg = '', $redirect = 'true')
    {
        if($this->session->userdata('user_level') == '1') 
        {
            if($msg != '')
            {
                if($msg == 'added')
                    $msg = 'New offer list successfully added to database';
                if($msg == 'edited')
                    $msg = 'Offer list successfully edited';
                if($msg == 'deleted')
                    $msg = 'offer list successfully deleted';
                if($msg == 'set_live')
                    $msg = 'Live list successfully changed';
                $this->data['msg'] = $msg;
            }
            if(isset($_POST['p']))
                $this->data['p'] = $_POST['p'];
            $form_ret['user_level'] = '0';
            $get_all_retailer_list = $this->{$this->model_name}->__select_table($form_ret, 'sos_retailers','business_name');
            $all_retailer = $get_all_retailer_list->result_array();
            if(!empty($all_retailer))
                $this->data['retailers'] = $all_retailer;
            
            $get_all_offer_lists = $this->{$this->model_name}->__select_all_data('list_name');
            $all_offer_lists = $get_all_offer_lists->result_array();
            if(!empty($all_offer_lists))
            {
                $this->data['lists'] = $all_offer_lists;
                foreach ($all_offer_lists as $list)
                {
                    if((isset($_POST['action']) && $_POST['action'] == 'edit') || $id != '' )
                    {
                        if((isset($_POST['list_id']) && $list['list_id'] == $_POST['list_id']) || ($id != '' && $list['list_id'] == $id))
                        {
                            $this->data['bean'] = $list;
                            if($id != '')
                                $form_off['list_id'] = $id;
                            else
                                $form_off['list_id'] = $list['list_id'];
                            $get_list_offers = $this->{$this->model_name}->__select_table($form_off, 'offers');
                            $offers = $get_list_offers->result_array();
                            if(!empty($offers))
                            {
                                $offers1 = array();
                                foreach ($offers as $offer)
                                {
                                    $offers1[$offer['list_id']][$offer['retailer_id']] = $offer['offer'];
                                }
                                $this->data['offers1'] = $offers1;
                            }
                        }
                    }
                    
                    if($list['status'] == '1')
                    {
                        $this->data['live_list'] = $list;
                        $form_data['status'] = '1';
                        $form_data['list_id'] = $list['list_id'];
                        if($redirect == 'true')
                            $get_live_list_offers = $this->{$this->model_name}->__select_table($form_data, 'offers');
                        else
                            $get_live_list_offers = $this->{$this->model_name}->__select_table($form_data, 'offers','order');
                        $all_offers = $get_live_list_offers->result_array();
                        if(!empty($all_offers))
                        {
                            $offers = array();
                            foreach ($all_offers as $offer)
                            {
                                $offers[$offer['list_id']][$offer['retailer_id']] = $offer;
                            }
                            $this->data['offers'] = $offers;
                        }
                    }
                }
            }
            if($redirect == 'true')
                $this->load->view($this->list_view, $this->data);
            else
                return $this->data;
        }
        else
            echo "You have no promission to access this page.";
    }
    
    function order_view()
    {
        $data = $this->list_view('','','false');
        $this->load->view($this->order_view, $data);
    }
    
    //here save values in the database.
    public function save()
    {
        //print_r($_POST); exit;
        $save_data=$this->input->post();
        foreach($save_data as $id=>$value)
        {
            if($id != 'offers1')
                $form_data[$id] = $value;
        }
        $files = $_FILES;
        
        if(isset($save_data['list_id']) && $save_data['list_id'] != '')
        {
            $where['list_id'] = $save_data['list_id'];
            $this->{$this->model_name}->__update_table($where, $form_data, 'offer_lists');
            $this->save_offer_list($where['list_id'], $_POST,'edit');
            $id = $save_data['list_id'];
            $msg = 'edited';
        }
        else
        {
            $id = $this->{$this->model_name}->__insert($form_data);
            $this->save_offer_list($id, $_POST,'edit');
            $msg = 'added';
        }
        redirect(site_url('offers/list_view/'.$id.'/'.$msg));
        
    }
    
    function save_offer_list($list_id = '', $data = array(),$page='')
    {
        //print_r($data); exit;
        if($page == 'edit')
        {
            $i = 1;
            $list_id = $list_id;
            $save_data = $data;
        }
        else
        {
            $i = '';
            $save_data=$this->input->post();
            $list_id = $save_data['list_id'];
        }
        if(isset($save_data['offers'.$i]))
        {
            $off = 1;
            foreach ($save_data['offers'.$i] as $id=>$value)
            {
                $form_data = array();
                $form_data['list_id'] = $list_id;
                $form_data['retailer_id'] = $id;
                $check = $this->{$this->model_name}->__select_table($form_data, 'offers');
                $rows = $check->result_array();
                if(!empty($rows))
                {
                    $form_data['offer'] = htmlentities(mysql_real_escape_string($value));
                    $where['list_id'] = $save_data['list_id'];
                    $where['retailer_id'] = $id;
                    $this->{$this->model_name}->__update_table($where, $form_data, 'offers');
                }
                else
                {
                    $form_data['offer'] = htmlentities(mysql_real_escape_string($value));
                    if($value == '')
                        $form_data['order'] = '0';
                    else
                    {
                        $form_data['order'] = $off;
                        $off++;
                    }
                    $id = $this->{$this->model_name}->__insert_table($form_data,'offers');
                }
            }
        }
        if($page == '')
            redirect(site_url('offers/list_view'));
    }
    function change_live_list()
    {
        $list_id = $_POST['live_list_id'];
        $get_all_offer_lists = $this->{$this->model_name}->__select_all_data('list_name');
        $all_offer_lists = $get_all_offer_lists->result_array();
        if(!empty($all_offer_lists))
        {
            foreach ($all_offer_lists as $list)
            {
                if($list['list_id'] == $list_id)
                    $status = '1';
                else
                    $status = '0';
                $where['list_id'] = $list['list_id'];
                $save_data['status'] = $status;
                $this->{$this->model_name}->__update_table($where, $save_data, 'offer_lists');
                $msg = 'set_live';
            }
        }
        redirect(site_url('offers/list_view/'.$msg));
    }
    
    
    function check_list_name()
    {
        $id = $_POST['list_id'];
        $list_name = $_POST['list_name'];
        $form_data['list_name'] = $list_name;
        if($id != '')
            $where = $id;
        else
            $where = '';
        $check = $this->{$this->model_name}->__select_table($form_data, 'offer_lists','',$where);
        $row = $check->result_array();
        if(empty($row))
            echo "true";
    }
    
    function update_order()
    {
        $array	= $_POST['arrayorder'];
        $list_id = $_POST['list_id'];
        //print_r($array); exit;
        if ($_POST['update'] == "update")
        {
           $this->{$this->model_name}->__update_order($array, $list_id);
        }
        echo "<p><strong>SUCCESS: </strong><span>The offer order have been updated.</span></p>";
    }
    
    function generate_email()
    {
        $form_ret['user_level'] = '0';
        $get_all_retailer_list = $this->{$this->model_name}->__select_table($form_ret, 'sos_retailers','business_name');
        $all_retailer = $get_all_retailer_list->result_array();
        
        $form_data['status'] = '1';
        $form_data['list_id'] = $_POST['list_id'];
        $get_live_list_offers = $this->{$this->model_name}->__select_table($form_data, 'offers','order');
        $all_offers = $get_live_list_offers->result_array();
        if(!empty($all_offers))
        {
            $offers = array();
            foreach ($all_offers as $offer)
            {
                $offers[$offer['list_id']][$offer['retailer_id']] = $offer;
            }
        }
        if(isset($all_retailer)) 
        {
            $offer = array();
            foreach ($all_retailer as $retailer)
            { 
                if($offers[$_POST['list_id']][$retailer['retailer_id']]['offer'] != '')
                {
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['id'] = $retailer['retailer_id'];
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['number'] = $retailer['retailer_member_number'];
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['slug'] = $retailer['slug_business_name'];
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['image'] = $retailer['main_profile_image'];
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['name'] = $retailer['business_name'];
                    $offer[$offers[$_POST['list_id']][$retailer['retailer_id']]['order']]['offer'] = stripslashes(html_entity_decode($offers[$_POST['list_id']][$retailer['retailer_id']]['offer']));
                }
            }
        }
        ksort($offer); 
        $save_pdth = 'http://'.$_SERVER['HTTP_HOST'].'/test/img/offer_thumb/87x65';
        $base_url = 'http://'.$_SERVER['HTTP_HOST'];
        //print_r($offer); exit;
        $html = '';
        $html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sold on Stourport - November offers</title>
</head>
<style type="text/css">
	@media screen and (-webkit-min-device-pixel-ratio:0) {
body { width:100% !important;  }
}
</style>
<body style="margin:0;padding:0;width:600px; -moz-text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%">


<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="blue-box" style="margin:0 auto !important; max-width:600px !important ; width:100%;">
    <tr>
        <td width="100%">
           
            <table cellpadding="0" cellspacing="0" border="0" width="100%" >
       
            	<tr>
                	<td align="left" valign="top" width="50%" bgcolor="#1e3ea4" height="206">
                    <table width="80%" bgcolor="#1e3ea4" border="0" cellspacing="0" cellpadding="0"height="206" >
                <tr>
                    <td align="left" valign="top" style="padding-top:20px; padding-left:10px;"><span style="color:#fff; font-size:20px; line-height:20px; font-family:Arial, Helvetica, sans-serif; ">25 Fantastic Offers for November</span></td>
                </tr>
                <tr>
                	<td align="left" valign="top"  style="color:#ffffff;font-family:Arial, sans-serif;font-size:14px;line-height:22px; padding-top:12px; padding-left:10px;">
                        As well as lots of great new offers we also have new retailers joining the scheme. If you\'re thinking of starting your Chrismas shopping early then why not grab your Sold on Stourport card and <span style="font-weight:bold">stay local and save</span> this November?</td>
                </tr>
                <tr>
                	
                    	 <td align="left" valign="bottom" width="346">
                    		<img  src="'.$base_url.'/images/offer/blue-left-bot.jpg" width="346" height="16" alt="" style="display:block; float:left; border:1px solid red vertical-align:top;" />
                    </td>
                    
                </tr>
            </table>
         
                    </td>
                    <td align="left" valign="top" width="254">
                    		<img  src="'.$base_url.'/images/offer/blue-right.jpg" width="254" height="206" alt="" style="display:block; float:left; border:1px solid red vertical-align:top;" />
                    </td>
                </tr>
                 
            </table>
        </td>
    </tr>
   
    <tr>
        <td width="100%" height="30">&nbsp;</td>
    </tr>
</table>




<!-- OFFER 1 START -->';
if(!empty($offer))
{
    $i = 1;
    foreach ($offer as $offer1)
    { 
        if($i <= 4)
        {
            $html .= '<table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto; max-width:600px; width:100%;">
    <tr>
            <td align="left" valign="middle" width="15%">
            	<img src="';
            if($offer1['image'] != '') 
                $html .= $save_pdth.'/'.$offer1['image']; 
            else 
                $html .= $save_pdth.'/default.jpg';
            $html .= '" alt="Stourport-Photo-Centre"  style="  -moz-box-sizing: border-box; border: 1px solid #CCCCCC;  border-radius: 5px; max-width:87px;" width="87" height="65" />
            </td>
           <td align="left" valign="top" width="5%">&nbsp;</td>
            <td align="left" valign="top" width="55%" >
            	<h2 style="font-family:Arial, Helvetica, sans-serif ; font-size:18px; line-height:20px; color:#E64578;">'.$offer1['name'].'</h2>
                <p style="font-size:16px; color:#1D3876; line-height:18px; font-family:Arial, Helvetica, sans-serif;">'.$offer1['offer'].'</p>
            </td>
               <td align="left" valign="top" width="5%">&nbsp;</td>
              <td align="left" valign="middle" width="20%"><a href="'.$base_url.'/retailer/'.$offer1['number'].'/'.$offer1['slug'].'.html">
              	<img src="http://soldonstourport.co.uk/img/email_thumbs/view-page.png" alt="Stourport-Photo-Centre"  style="border:none;max-width:56px;" width="53" height="35" />
              </a></td>
    </tr>
    <tr>
        <td width="100%" height="41" colspan="5"><img src="http://www.soldonstourport.co.uk/images/separator.png" width="600" height="41" alt="" style="display:block; width:100%; max-width:600px;" /></td>
    </tr>
</table>';
        }
        if($i == 4)
        {
            $html .= '<table width="600" border="0" cellspacing="0" cellpadding="0" align="center" class="pink-box" style="margin:0 auto; max-width:600px; width:100%;">
    <tr>
        <td width="29%" class="nodisplay" valign="top"><img  src="'.$base_url.'/images/offer/pink-left.jpg" width="233" height="198" alt="" style="display:block; vertical-align:top;" /></td>
        <td width="70%" valign="top" align="left">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            	<td width="100%" class="nodisplay" valign="top" align="left"><img  src="'.$base_url.'/images/offer/pink-rght-top.jpg" width="367" height="17" alt="" style="display:block; vertical-align:top;" /></td>
            </tr>
                <tr>
                    <td width="100%" align="left" bgcolor="#e881a6" style="color:#ffffff;font-family:Arial, sans-serif;font-size:22px;font-weight:bold"><img  src="http://www.soldonstourport.co.uk/images/mp.png" width="333" height="29" alt="£100&nbsp;Monthly&nbsp;Prize&nbsp;Draw!" style="display:block" /></td>
                </tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0"  bgcolor="#e881a6">
                <tr>
                	<td colspan="2" height="66" bgcolor="#e881a6">
                    	<table width="100%" border="0" cellspacing="0" cellpadding="0" >
	
        	<tr>
                    
                    <td width="100%" align="right" style="color:#ffffff;font-family:Arial, sans-serif;font-size:14px;line-height:18px">Win £100 in vouchers to spend at participating retailers.</td>
                </tr>
                <tr>
                    <td width="100%" colspan="2" align="right" style="color:#ffffff;font-family:Arial, sans-serif;font-size:14px;line-height:18px">If you\'re a winner please contact Severn Stitches, Lombard St.</td>
                </tr>
                <tr>
                    <td width="100%" height="10" colspan="2" style="font-size:1px;line-height:1px">&nbsp;</td>
                </tr>
     
</table>
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="24" colspan="2" align="right" style="color:#ffffff;font-family:Arial, sans-serif;font-size:16px;font-weight:bold"><img src="'.$base_url.'/images/offer/wcn-november-2013-.png" width="367" height="24" alt="November\'s winning card number: 1219" style="display:block" /></td>
                </tr>
                    <tr>
                    <td width="100%" class="nodisplay"  valign="top" align="left"><img  src="'.$base_url.'/images/offer/pink-rght-bot.jpg" width="367" height="62" alt="" style="display:block; vertical-align:top;" /></td>
                </tr>
            </table>
        </td>
    </tr>
    
</table>';
        }
        if($i > 4)
        {
            $html .= '<table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto; max-width:600px; width:100%;">
    <tr>
            <td align="left" valign="middle" width="15%">
            	<img src="';
            if($offer1['image'] != '') 
                $html .= $save_pdth.'/'.$offer1['image']; 
            else 
                $html .= $save_pdth.'/default.jpg';
            $html .= '" alt="Stourport-Photo-Centre"  style="  -moz-box-sizing: border-box; border: 1px solid #CCCCCC;  border-radius: 5px; max-width:87px;" width="87" height="65" />
            </td>
           <td align="left" valign="top" width="5%">&nbsp;</td>
            <td align="left" valign="top" width="55%" >
            	<h2 style="font-family:Arial, Helvetica, sans-serif ; font-size:18px; line-height:20px; color:#E64578;">'.$offer1['name'].'</h2>
                <p style="font-size:16px; color:#1D3876; line-height:18px; font-family:Arial, Helvetica, sans-serif;">'.$offer1['offer'].'</p>
            </td>
               <td align="left" valign="top" width="5%">&nbsp;</td>
              <td align="left" valign="middle" width="20%"><a href="'.$base_url.'/retailer/'.$offer1['number'].'/'.$offer1['slug'].'.html">
              	<img src="http://soldonstourport.co.uk/img/email_thumbs/view-page.png" alt="Stourport-Photo-Centre"  style="border:none;max-width:56px;" width="53" height="35" />
              </a></td>
    </tr>
    <tr>
        <td width="100%" height="41" colspan="5"><img src="http://www.soldonstourport.co.uk/images/separator.png" width="600" height="41" alt="" style="display:block; width:100%; max-width:600px;" /></td>
    </tr>
</table>';
        }
        $i++;
    }
}
$html .= '<table width="600" border="0" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto; max-width:600px; width:100%;">
    <tr>
        <td width="100%" height="45" align="center" valign="top" style="color:#d23c68;font-family:Arial, sans-serif;font-size:22px;font-weight:bold"><img src="http://www.soldonstourport.co.uk/images/rules.png" width="323" height="25" alt="Rules&nbsp;of&nbsp;the&nbsp;Scheme" style="display:block; width:323px;" /></td>
    </tr>
    <tr>
        <td width="100%">
            <table width="98%" border="0" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto">
                <tr>
                    <td width="10%" valign="top" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">1.</td>
                    <td width="90%" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">
                        Only one membership card may be held by any individual. In the event of more<br style="line-height:18px" />
                        than one card being held, eligibility to the prize draw is forfeited for all those cards.
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="5" colspan="2" style="font-size:1px;line-height:1px">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10%" valign="top" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">2.</td>
                    <td width="90%" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">
                        In the event of loss of the membership card, a replacement card will be issued<br style="line-height:18px" />
                        and the lost card will be cancelled.
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="5" colspan="2" style="font-size:1px;line-height:1px">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10%" valign="top" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">3.</td>
                    <td width="90%" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">
                        Winners of the prize draw may spend their £100 at up to five different Supporting<br style="line-height:18px" />
                        Retailers. Winners must make their claim within the month that their number is<br style="line-height:18px" />
                        drawn, and must spend their winnings withi n three months of their claim.
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="5" colspan="2" style="font-size:1px;line-height:1px">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10%" valign="top" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">4.</td>
                    <td width="90%" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">
                        In the event that a member wishes to come out of the scheme, they will write to<br style="line-height:18px" />
                        The Administrators, Stourport Town Centre Forum at PO Box 2725, Stourport-on-<br style="line-height:18px" />
                        Severn, DY13 8QN and their details will be removed.
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="5" colspan="2" style="font-size:1px;line-height:1px">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10%" valign="top" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">5.</td>
                    <td width="90%" style="color:#21488f;font-family:Arial, sans-serif;font-size:14px;line-height:18px">Should there be any dispute, the decision of The Administrators will be final.</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td width="100%"  align="center" valign="bottom"><img src="http://www.soldonstourport.co.uk/images/sos.jpg" width="466" height="360" alt="" style="display:block; max-width:466px; width:100%" /></td>
    </tr>
    <tr>
        <td width="100%"  align="center" valign="top" style="font-family:Arial, sans-serif;font-size:14px;font-weight:bold"><a href="http://www.crayonjuice.co.uk" target="_blank" style="color:#f79422;text-decoration:none"><img src="http://www.soldonstourport.co.uk/images/cjlogo.png" width="136" height="27" alt="crayonjuice" style="display:block;border:none; max-width:136px;" /></a></td>
    </tr>
    <tr>
        <td width="100%" align="center" style="color:#979797;font-family:Arial, sans-serif;font-size:11px;line-height:14px; text-align:center !important">
            Crayon Juice is providing all design, illustration, branding, printing<br style="line-height:14px" />
            and web development services for Sold on Stourport.<br style="line-height:14px" />
            24 Lombard Street, Stourport-on-Severn, DY13 8DT - <a href="http://www.crayonjuice.co.uk" target="_blank" style="color:#979797;text-decoration:none;line-height:14px">www.crayonjuice.co.uk</a>
        </td>
    </tr>
    <tr>
        <td width="100%" height="20">&nbsp;</td>
    </tr>
</table>

</body>
</html>
';
        echo htmlentities($html);
    }

}