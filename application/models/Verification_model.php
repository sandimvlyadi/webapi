<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verification_model extends CI_Model {

	function code($code = '')
	{
		$result = false;

		$table = $this->db->dbprefix .'t_user_register';
		$table0 = $this->db->dbprefix .'t_user';
		$q = "SELECT * FROM `". $table ."` WHERE `register_key` = '". $this->db->escape_str($code) ."' AND `status` = '1';";
		$r = $this->db->query($q, false)->result_array();
		if (count($r) > 0) {
			$result = true;

			$q = "UPDATE `". $table ."` SET `status` = 0 WHERE `register_key` = '". $this->db->escape_str($code) ."';";
			$this->db->query($q);

			$q = "UPDATE `". $table0 ."` SET `status` = 1 WHERE `id` = '". $r[0]['id_t_user'] ."';";
			$this->db->query($q, false);
		}

		return $result;
	}

}