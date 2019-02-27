<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax_service_model extends CI_Model {

	function secret_key_validation($key = '')
	{
		$result = true;

		$table = $this->db->dbprefix .'t_auth';
		$q = "SELECT * FROM `". $table ."` WHERE `secret_key` = '". $key ."' AND `status` = 1;";
		$r = $this->db->query($q, false)->result_array();

		if (count($r) < 1) {
			$result = false;
		}

		return $result;
	}

	function token_validation($data = array())
	{
		$result = false;

		$table = $this->db->dbprefix .'t_user_token';
		$q = 	"SELECT 
					* 
				FROM 
					`". $table ."` 
				WHERE 
					`id_t_user` = '". $this->db->escape_str($data['id']) ."' 
						AND 
					`token` = '". $this->db->escape_str($data['token']) ."' 
						AND 
					`status` = 1
				;";
		$r = $this->db->query($q, false)->result_array();
		if (count($r) > 0) {
			$result = true;
		}

		return $result;
	}

	function load_data_table($table = '', $id_company = 0)
	{
		$result = array();
		$DB = $this->load->database('si_tekstil', TRUE);

		switch ($table) {
			case 'testing': // pengujian
				$Q = 	"SELECT 
							* 
						FROM 
							`pemesanan` 
						WHERE 
							`id_pelanggan` = '". $id_company ."'
								AND
							`kd_invoice` LIKE '%EV%'
						ORDER BY
							`id_pemesanan` DESC
						;";
				$R = $DB->query($Q, false)->result_array();
				if (count($R) > 0) {
					$result = $R;
				}
				break;
			case 'calibration': // kalibrasi
				$Q = 	"SELECT 
							* 
						FROM 
							`pemesanan` 
						WHERE 
							`id_pelanggan` = '". $id_company ."'
								AND
							`kd_invoice` LIKE '%KAL%'
						ORDER BY
							`id_pemesanan` DESC
						;";
				$R = $DB->query($Q, false)->result_array();
				if (count($R) > 0) {
					$result = $R;
				}
				break;
			case 'certification': // sertifikasi
				$Q = 	"SELECT 
							* 
						FROM 
							`wo_sertifikasi` 
						WHERE 
							`id_pelanggan` = '". $id_company ."' 
						ORDER BY 
							`id_wo_sertifikasi` DESC
						;";
				$R = $DB->query($Q, false)->result_array();
				if (count($R) > 0) {
					$result = $R;
				}
				break;
			default:
				# code...
				break;
		}

		return $result;
	}

    function webapi001($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$param = $data['data'];
    	$table = $this->db->dbprefix .'t_user';
    	$table0 = $this->db->dbprefix .'t_user_detail';
    	$q = 	"SELECT 
    				a.`id` AS `id_user`,
    				a.`email`,
    				a.`status`,
    				b.`id_company`,
    				b.`company_code`,
    				b.`username`,
    				b.`fullname`,
    				b.`address`,
    				b.`contact`,
    				b.`display_picture` 
    			FROM 
    				`". $table ."` a
    			JOIN
    				`". $table0 ."` b
    					ON
    				a.`id` = b.`id_t_user`
    			WHERE 
    				a.`email` = '". $this->db->escape_str($param['email']) ."' 
    					AND 
    				a.`password` = '". md5($this->db->escape_str($param['password'])) ."' 
    				;";
    	$r = $this->db->query($q, false)->result_array();
    	if (count($r) > 0) {
    		if ($r[0]['status'] != 1) {
    			$result['msg'] = 'Your account still suspended. Please check your email address.';
    		} else{
    			$DB = $this->load->database('si_tekstil', TRUE);
    			$Q = "SELECT * FROM `pelanggan` WHERE `id_pelanggan` = '". $r[0]['id_company'] ."';";
    			$R = $DB->query($Q, false)->result_array();
    			if (count($R) > 0) {
    				$r[0]['company_name'] = $R[0]['nama_pelanggan'];
    				$result['testing'] = $this->load_data_table('testing', $r[0]['id_company']);
    				$result['calibration'] = $this->load_data_table('calibration', $r[0]['id_company']);
    				$result['certification'] = $this->load_data_table('certification', $r[0]['id_company']);
    			} else{
    				$r[0]['company_name'] = 'No Company';
    			}

    			$result['result'] = true;
    			$result['data'] = $r[0];
    			$result['target'] = '../dashboard/dashboard.html';

    			$table1 = $this->db->dbprefix .'t_user_token';
    			$token = md5($r[0]['id_user'] . time());
    			$q = 	"INSERT INTO 
    						`". $table1 ."` 
    						(
    							`id_t_user`,
    							`token`,
    							`generated_time`,
    							`generated_time_int`
    						) 
    					VALUES 
    						(
    							'". $r[0]['id_user'] ."',
    							'". $token ."',
    							'". date('Y-m-d H:i:s') ."',
    							'". time() ."'
    						)
    						;";
    			if ($this->db->simple_query($q)) {
    				$id = $this->db->insert_id();
    				$q = 	"SELECT 
    							`id_t_user` AS `id_user`,
    							`token`,
    							`generated_time`,
    							`generated_time_int`,
    							`status`
    						FROM 
    							`". $table1 ."` 
    						WHERE 
    							`id` = '". $id ."'
    						;";
    				$r = $this->db->query($q, false)->result_array();
    				if (count($r) > 0) {
    					$result['token'] = $r[0];
    				} else{
    					$result['result'] = false;
    					$result['msg'] = 'Failed retrieving your token.';
    				}
    			} else{
    				$result['result'] = false;
    				$result['msg'] = 'Failed generating your token.';
    			}
    		}
    	} else{
    		$result['msg'] = 'Invalid email or password.';
    	}

    	return $result;
    }

    function webapi002($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$param = $data['data'];
    	$table = $this->db->dbprefix .'t_user';
    	$table0 = $this->db->dbprefix .'t_user_detail';
    	$table1 = $this->db->dbprefix .'t_user_register';
    	$q = 	"INSERT INTO 
    				`". $table ."` 
    				(
    					`email`,
    					`password`
    				) 
    			VALUES 
    				(
    					'". $this->db->escape_str($param['email']) ."',
    					'". md5($this->db->escape_str($param['password'])) ."'
    				)
    				;";
    	if ($this->db->simple_query($q)) {
    		$id = $this->db->insert_id();

    		$key = md5($id . time());

    		$q = 	"INSERT INTO 
    					`". $table0 ."` 
    					(
    						`id_t_user`,
    						`fullname`
    					) 
    				VALUES 
    					(
    						'". $id ."',
    						'". $this->db->escape_str($param['fullname']) ."'
    					)
    					;";
    		if ($this->db->simple_query($q)) {
    			$q = 	"INSERT INTO 
	    					`". $table1 ."` 
	    					(
	    						`id_t_user`,
	    						`fullname`,
	    						`email`,
	    						`password`,
	    						`repeat_password`,
	    						`real_password`,
	    						`company_key`,
	    						`register_key`,
	    						`register_time`,
	    						`register_time_int`
	    					) 
	    				VALUES 
	    					(
	    						'". $id ."',
	    						'". $this->db->escape_str($param['fullname']) ."',
	    						'". $this->db->escape_str($param['email']) ."',
	    						'". $this->db->escape_str(md5($param['password'])) ."',
	    						'". $this->db->escape_str(md5($param['repeat_password'])) ."',
	    						'". $this->db->escape_str($param['password']) ."',
	    						'". $this->db->escape_str($param['secret_key']) ."',
	    						'". $key ."',
	    						'". date('Y-m-d H:i:s') ."',
	    						'". time() ."'
	    					)
	    					;";
	    		if ($this->db->simple_query($q)) {
	    			$result['result'] = true;
	    			$result['msg'] = 'Your account has been created. Please check your email for account activation.';
	    			$result['target'] = '../login/login.html';
	    			$result['code'] = $key;
	    		} else{
	    			$error = $this->db->error();
					$result['msg'] = 'Database error code: '. $error['code'];
	    		}
    		} else{
    			$error = $this->db->error();
				$result['msg'] = 'Database error code: '. $error['code'];
    		}
    	} else{
    		$error = $this->db->error();
    		if ($error['code'] == '1062') {
    			$result['msg'] = 'Email address already registered.';
    		} else{
    			$result['msg'] = 'Database error code: '. $error['code'];
    		}
    	}

    	return $result;
    }

    function webapi003($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$param = $data['data'];
    	$table = $this->db->dbprefix .'t_user_register';
    	$q = 	"SELECT 
    				`fullname`,
    				`real_password` AS `password` 
    			FROM 
    				`". $table ."` 
    			WHERE 
    				`email` = '". $this->db->escape_str($param['email']) ."' 
    					AND 
    				`status` = 0
    			;";
    	$r = $this->db->query($q, false)->result_array();
    	if (count($r) > 0) {
    		$result['result'] = true;
    		$result['msg'] = 'We have sent you an email. Please check immediately.';
    		$result['data'] = $r[0];
    	} else{
    		$result['msg'] = 'There is no active account with these email address.';
    	}

    	return $result;
    }

    function webapi004($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$param = $data['data'];
    	$table = $this->db->dbprefix .'t_user_token';
    	$table0 = $this->db->dbprefix .'t_user';
    	$table1 = $this->db->dbprefix .'t_user_detail';
    	$q = 	"SELECT 
    				c.`id` AS `id_user`,
    				c.`email`,
    				c.`status`,
    				b.`id_company`,
    				b.`company_code`,
    				b.`username`,
    				b.`fullname`,
    				b.`address`,
    				b.`contact`,
    				b.`display_picture` 
    			FROM 
    				`". $table ."` a
    			JOIN
    				`". $table0 ."` c
    					ON
    				a.`id_t_user` = c.`id`
    			JOIN 
    				`". $table1 ."` b 
    					ON
    				a.`id_t_user` = b.`id_t_user`
    			WHERE 
    				a.`id_t_user` = '". $param['id_user'] ."' 
    					AND 
    				a.`token` = '". $param['token'] ."' 
    					AND 
    				a.`status` = '1'
    			;";
    	$r = $this->db->query($q, false)->result_array();
    	if (count($r) > 0) {
    		$DB = $this->load->database('si_tekstil', TRUE);
			$Q = "SELECT * FROM `pelanggan` WHERE `id_pelanggan` = '". $r[0]['id_company'] ."';";
			$R = $DB->query($Q, false)->result_array();
			if (count($R) > 0) {
				$r[0]['company_name'] = $R[0]['nama_pelanggan'];
			} else{
				$r[0]['company_name'] = 'No Company';
			}

    		$result['result'] = true;
    		$result['msg'] = 'Your session is valid.';
    		$result['data'] = $r[0];
    	} else{
    		$result['msg'] = 'Your session was not valid.';
    	}

    	return $result;
    }

    function webapi005($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());
    	$fullname = $data['data']['fullname'];
    	$token = $data['data']['token'];

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$isValid = $this->token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
    	if ($isValid) {
    		$table = $this->db->dbprefix .'t_user_detail';
    		$q = 	"UPDATE 
    					`". $table ."` 
    				SET 
    					`fullname` = '". $this->db->escape_str($fullname) ."' 
    				WHERE 
    					`id_t_user` = '". $token['id_user'] ."'
    				;";
    		if ($this->db->simple_query($q)) {
    			$result['result'] = true;
    			$result['msg'] = 'Your fullname has been changed.';
    			$result['fullname'] = $fullname;
    		} else{
    			$error = $this->db->error();
	    		$result['msg'] = 'Database error code: '. $error['code'];
    		}
    	} else{
    		$result['msg'] = 'Your session was not valid.';
    	}

    	return $result;
    }

    function webapi006($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());
    	$company_code = $data['data']['company_code'];
    	$token = $data['data']['token'];

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$isValid = $this->token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
    	if ($isValid) {
    		$DB = $this->load->database('si_tekstil', TRUE);
    		$Q = "SELECT * FROM `pelanggan`;";
    		$R = $DB->query($Q, false)->result_array();
    		if (count($R) > 0) {
    			$found = false;
    			$id_company = 0;
    			$company_name = '';
    			for ($i=0; $i < count($R); $i++) { 
    				$code = substr(strtoupper(md5('tex_'. $R[$i]['id_pelanggan'])), 0, 6);
    				if ($code == $company_code) {
    					$found = true;
    					$id_company = $R[$i]['id_pelanggan'];
    					$company_name = $R[$i]['nama_pelanggan'];
    					break;
    				}
    			}

    			if ($found) {
    				$table = $this->db->dbprefix .'t_user_detail';
    				$q = 	"UPDATE 
    							`". $table ."` 
    						SET 
    							`id_company` = '". $id_company ."', 
    							`company_code` = '". $company_code ."' 
    						WHERE 
    							`id_t_user` = '". $token['id_user'] ."'
    						;";
    				if ($this->db->simple_query($q)) {
    					$result['result'] = true;
    					$result['msg'] = 'Your company code has been updated successfully.';
    					$result['company_code'] = $company_code;
    					$result['company_name'] = $company_name;
    					$result['testing'] = $this->load_data_table('testing', $id_company);
	    				$result['calibration'] = $this->load_data_table('calibration', $id_company);
	    				$result['certification'] = $this->load_data_table('certification', $id_company);
    				} else{
    					$error = $this->db->error();
	    				$result['msg'] = 'Database error code: '. $error['code'];
    				}
    			} else{
    				$result['msg'] = 'Invalid company code.';
    			}
    		} else{
    			$result['msg'] = 'Company list is empty.';
    		}
    	} else{
    		$result['msg'] = 'Your session was not valid.';
    	}

    	return $result;
    }

    function webapi007($data = array())
    {
    	$result = array('result' => false, 'msg' => '', 'data' => array());
    	$current_password = $data['data']['current_password'];
    	$md5Current = md5($current_password);
    	$new_password = $data['data']['new_password'];
    	$md5New = md5($new_password);
    	$token = $data['data']['token'];

    	$valid = $this->secret_key_validation($data['auth']);
    	if (!$valid) {
    		$result['msg'] = 'Unauthorized access.';
    		return $result;
    	}

    	$isValid = $this->token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
    	if ($isValid) {
    		$table = $this->db->dbprefix .'t_user_register';
    		$q = 	"SELECT 
    					* 
    				FROM 
    					`". $table ."` 
    				WHERE 
    					`id_t_user` = '". $token['id_user'] ."' 
    						AND 
    					`password` = '". $md5Current ."' 
    						AND
    					`real_password` = '". $this->db->escape_str($current_password) ."'
    						AND 
    					`status` = 0
    				;";
    		$r = $this->db->query($q, false)->result_array();
    		if (count($r) > 0) {
    			$table0 = $this->db->dbprefix .'t_user';
    			$q1 = 	"UPDATE 
    						`". $table ."` 
    					SET 
    						`password` = '". $md5New ."',
    						`repeat_password` = '". $md5New ."',
    						`real_password` = '". $this->db->escape_str($new_password) ."'
    					WHERE 
    						`id_t_user` = '". $token['id_user'] ."'
    					;";
    			$q2 = "UPDATE `". $table0 ."` SET `password` = '". $md5New ."' WHERE `id` = '". $token['id_user'] ."';";
    			if ($this->db->simple_query($q1) && $this->db->simple_query($q2)) {
    				$result['result'] = true;
    				$result['msg'] = 'Your password has been changed.';
    			} else{
    				$error = $this->db->error();
    				$result['msg'] = 'Database error code: '. $error['code'];
    			}
    		} else{
    			$result['msg'] = 'Invalid current password.';
    		}
    	} else{
    		$result['msg'] = 'Your session was not valid.';
    	}

    	return $result;
    }

    function webapi001_upload($data = array())
    {
    	$table = $this->db->dbprefix. 't_user_detail';
    	$q = 	"UPDATE 
    				`". $table ."` 
    			SET 
    				`display_picture` = '". $this->db->escape_str($data['filename']) ."' 
    			WHERE 
    				`id_t_user` = '". $this->db->escape_str($data['id']) ."' 
    					AND 
    				`status` = 1
    			;";
    	$this->db->query($q);
    }

}
