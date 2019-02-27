<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company_model extends CI_Model {

    function index($key = '')
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());

    	$table = $this->db->dbprefix .'t_user_token';
    	$q = "SELECT * FROM `". $table ."` WHERE `token` = '". $key ."' AND `status` = 1;";
    	$r = $this->db->query($q, false)->result_array();
    	if (count($r) > 0) {
    		$DB = $this->load->database('si_tekstil', TRUE);
    		$Q = "SELECT * FROM `pelanggan`";
    		$R = $DB->query($Q, false)->result_array();

    		for ($i=0; $i < count($R); $i++) { 
    			$R[$i]['company_code'] = substr(strtoupper(md5('tex_'. $R[$i]['id_pelanggan'])), 0, 6);
    		}

    		$result['result'] = true;
    		$result['data'] = $R;
    	}

    	return $result;
    }

}