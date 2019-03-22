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

    function employee_token_validation($data = array())
    {
        $result = false;

        $table = $this->db->dbprefix .'t_employee_token';
        $q =    "SELECT 
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
    				$result['testing'] = array();
    				$result['calibration'] = array();
    				$result['certification'] = array();
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

    // Data MT Tekstil
    function get_data_pekerjaan_pengujian_mt_tekstil()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` 
                        IN 
                    ('Lab. Pengujian Fisika', 'Lab. Pengujian Kimia') 
                GROUP BY 
                    `id_pemesanan` 
                ORDER BY 
                    `id_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_dokumentasi_mt_tekstil()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    GROUP_CONCAT(DISTINCT `pengguna` SEPARATOR ', ') AS `penguji` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    (
                        `nama_lab` = 'Lab. Pengujian Fisika' 
                            OR 
                        `nama_lab` = 'Lab. Pengujian Kimia'
                    ) 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('MT','Selesai') 
                GROUP BY 
                    `kd_pemesanan` 
                ORDER BY 
                    `tgl_masuk` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_info_kesanggupan_mt_tekstil()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT
                    *
                FROM
                    `laboratorium` 
                JOIN 
                    `kategori` 
                        USING(`id_lab`) 
                JOIN 
                    `contoh` 
                        USING(`id_kategori`) 
                JOIN 
                    `parameter` 
                        USING(`id_contoh`) 
                JOIN 
                    `satuan` 
                        USING(`id_satuan`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Fisika' 
                        OR 
                    `nama_lab` = 'Lab. Pengujian Kimia' 
                ORDER BY 
                    `kategori`
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_tekstil()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%EV%' 
                GROUP BY 
                    `id_pembayaran` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_produksi_mt_tekstil()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%EV%' 
                GROUP BY 
                    `id_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_tekstil_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to']
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function get_data_laporan_produksi_mt_tekstil_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to'],
            'filter_by' => $data['data']['by'],
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $wh = '';
            if ($param['filter_by'] == 1) {
                $wh = 'tgl_masuk';
            } elseif ($param['filter_by'] == 2) {
                $wh = 'tgl_perkiraan';
            } else{
                $wh = 'tgl_masuk';
            }

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%EV%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // End of Data MT Tekstil

    // Data MT Kalibrasi
    function get_data_pekerjaan_pengujian_mt_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi'
                GROUP BY 
                    `id_pemesanan` 
                ORDER BY 
                    `tgl_masuk` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_dokumentasi_mt_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    GROUP_CONCAT(DISTINCT `pengguna` SEPARATOR ', ') AS `penguji` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi'
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('MT','Selesai') 
                GROUP BY 
                    `kd_pemesanan` 
                ORDER BY 
                    `tgl_masuk` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_info_kesanggupan_mt_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT
                    *
                FROM
                    `laboratorium` 
                JOIN 
                    `kategori` 
                        USING(`id_lab`) 
                JOIN 
                    `contoh` 
                        USING(`id_kategori`) 
                JOIN 
                    `parameter` 
                        USING(`id_contoh`) 
                JOIN 
                    `satuan` 
                        USING(`id_satuan`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi' 
                ORDER BY 
                    `kategori`
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%KAL%' 
                GROUP BY 
                    `id_pembayaran` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_produksi_mt_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%KAL%' 
                GROUP BY 
                    `id_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_kalibrasi_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to']
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function get_data_laporan_produksi_mt_kalibrasi_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to'],
            'filter_by' => $data['data']['by'],
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $wh = '';
            if ($param['filter_by'] == 1) {
                $wh = 'tgl_masuk';
            } elseif ($param['filter_by'] == 2) {
                $wh = 'tgl_perkiraan';
            } else{
                $wh = 'tgl_masuk';
            }

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%KAL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // End of Data MT Kalibrasi

    // Data MT Lingkungan
    function get_data_pekerjaan_pengujian_mt_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan'
                GROUP BY 
                    `id_pemesanan` 
                ORDER BY 
                    `tgl_masuk` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_dokumentasi_mt_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_perkiraan`, 
                    `status_pengerjaan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    GROUP_CONCAT(DISTINCT `pengguna` SEPARATOR ', ') AS `penguji` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan'
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('MT','Selesai') 
                GROUP BY 
                    `kd_pemesanan` 
                ORDER BY 
                    `tgl_masuk` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_info_kesanggupan_mt_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT
                    *
                FROM
                    `laboratorium` 
                JOIN 
                    `kategori` 
                        USING(`id_lab`) 
                JOIN 
                    `contoh` 
                        USING(`id_kategori`) 
                JOIN 
                    `parameter` 
                        USING(`id_contoh`) 
                JOIN 
                    `satuan` 
                        USING(`id_satuan`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan' 
                ORDER BY 
                    `kategori`
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%PL%' 
                GROUP BY 
                    `id_pembayaran` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_produksi_mt_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                WHERE 
                    `kd_pemesanan` 
                        LIKE 
                    '%PL%' 
                GROUP BY 
                    `id_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_laporan_keuangan_mt_lingkungan_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to']
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `tgl_bayar` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pelanggan` 
                        JOIN 
                            `pemesanan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `pembayaran` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pembayaran` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function get_data_laporan_produksi_mt_lingkungan_filter($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'date_from' => $data['data']['from'],
            'date_to'   => $data['data']['to'],
            'filter_by' => $data['data']['by'],
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;

            $wh = '';
            if ($param['filter_by'] == 1) {
                $wh = 'tgl_masuk';
            } elseif ($param['filter_by'] == 2) {
                $wh = 'tgl_perkiraan';
            } else{
                $wh = 'tgl_masuk';
            }

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = '';
            if ($param['date_from'] != '' && $param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_from'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` >= '". $param['date_from'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } elseif ($param['date_to'] != '') {
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `". $wh ."` <= '". $param['date_to'] ."'
                                AND
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            } else{
                $Q =    "SELECT 
                            * 
                        FROM 
                            `pemesanan` 
                        JOIN 
                            `pelanggan` 
                                USING(`id_pelanggan`) 
                        JOIN 
                            `kelengkapan_pemesanan` 
                                USING(`id_pemesanan`) 
                        WHERE 
                            `kd_pemesanan` 
                                LIKE 
                            '%PL%' 
                        GROUP BY 
                            `id_pemesanan` DESC
                        ;";
            }

            $R = $DB->query($Q, false)->result_array();
            $result['data'] = $R;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // End of Data MT Lingkungan

    // Data Koorlab Fisika
    function get_data_pekerjaan_pengujian_koorlab_fisika()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna`, 
                    `pengujian_parameter`.`id_anggota`, 
                    `pengujian_parameter`.`keterangan` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Fisika' 
                        AND 
                    (
                        `status_pengerjaan` = 'Akan Dibagikan' 
                            OR 
                        `status_pengerjaan` = 'Analis / Teknisi' 
                            OR 
                        `status_pengerjaan` = 'Koordinator Lab'
                    ) 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_pengujian_koorlab_fisika()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Fisika' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Koordinator Lab','Dokumentasi') 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_history_analis_koorlab_fisika()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pengujian_parameter` a 
                JOIN 
                    `anggota` b 
                        ON 
                    a.`id_anggota` = b.`id_anggota` 
                JOIN
                    `pemesanan` c
                        ON
                    a.`id_pemesanan` = c.`id_pemesanan`
                JOIN
                    `parameter` d
                        ON
                    a.`id_parameter` = d.`id_parameter`
                WHERE 
                    a.`id_anggota` != '' 
                        AND
                    b.`id_jabatan` = 5
                ORDER BY 
                    a.`id_pengujian` DESC
                LIMIT 1000
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Koorlab Fisika

    // Data Koorlab Kimia
    function get_data_pekerjaan_pengujian_koorlab_kimia()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna`, 
                    `pengujian_parameter`.`id_anggota`, 
                    `pengujian_parameter`.`keterangan` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Kimia' 
                        AND 
                    (
                        `status_pengerjaan` = 'Akan Dibagikan' 
                            OR 
                        `status_pengerjaan` = 'Analis / Teknisi' 
                            OR 
                        `status_pengerjaan` = 'Koordinator Lab'
                    ) 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_pengujian_koorlab_kimia()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Kimia' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Koordinator Lab','Dokumentasi') 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_history_analis_koorlab_kimia()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pengujian_parameter` a 
                JOIN 
                    `anggota` b 
                        ON 
                    a.`id_anggota` = b.`id_anggota` 
                JOIN
                    `pemesanan` c
                        ON
                    a.`id_pemesanan` = c.`id_pemesanan`
                JOIN
                    `parameter` d
                        ON
                    a.`id_parameter` = d.`id_parameter`
                WHERE 
                    a.`id_anggota` != '' 
                        AND
                    b.`id_jabatan` = 6
                ORDER BY 
                    a.`id_pengujian` DESC
                LIMIT 100
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Koorlab Kimia

    // Data Koorlab Lingkungan
    function get_data_pekerjaan_pengujian_koorlab_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna`, 
                    `pengujian_parameter`.`id_anggota`, 
                    `pengujian_parameter`.`keterangan` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan' 
                        AND 
                    (
                        `status_pengerjaan` = 'Akan Dibagikan' 
                            OR 
                        `status_pengerjaan` = 'Analis / Teknisi' 
                            OR 
                        `status_pengerjaan` = 'Koordinator Lab'
                    ) 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_pengujian_koorlab_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Koordinator Lab','Dokumentasi') 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_history_analis_koorlab_lingkungan()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pengujian_parameter` a 
                JOIN 
                    `anggota` b 
                        ON 
                    a.`id_anggota` = b.`id_anggota` 
                JOIN
                    `pemesanan` c
                        ON
                    a.`id_pemesanan` = c.`id_pemesanan`
                JOIN
                    `parameter` d
                        ON
                    a.`id_parameter` = d.`id_parameter`
                WHERE 
                    a.`id_anggota` != '' 
                        AND
                    b.`id_jabatan` = 13
                ORDER BY 
                    a.`id_pengujian` DESC
                LIMIT 100
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Koorlab Lingkungan

    // Data Koorlab Kalibrasi
    function get_data_pekerjaan_pengujian_koorlab_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna`, 
                    `pengujian_parameter`.`id_anggota`, 
                    `pengujian_parameter`.`keterangan` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi' 
                        AND 
                    (
                        `status_pengerjaan` = 'Akan Dibagikan' 
                            OR 
                        `status_pengerjaan` = 'Analis / Teknisi' 
                            OR 
                        `status_pengerjaan` = 'Koordinator Lab'
                    ) 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_pemeriksaan_hasil_pengujian_koorlab_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengguna` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Koordinator Lab','Dokumentasi') 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_history_analis_koorlab_kalibrasi()
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    * 
                FROM 
                    `pengujian_parameter` a 
                JOIN 
                    `anggota` b 
                        ON 
                    a.`id_anggota` = b.`id_anggota` 
                JOIN
                    `pemesanan` c
                        ON
                    a.`id_pemesanan` = c.`id_pemesanan`
                JOIN
                    `parameter` d
                        ON
                    a.`id_parameter` = d.`id_parameter`
                WHERE 
                    a.`id_anggota` != '' 
                        AND
                    b.`id_jabatan` = 11
                ORDER BY 
                    a.`id_pengujian` DESC
                LIMIT 100
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Koorlab Kalibrasi

    // Data Analis Fisika
    function get_data_pekerjaan_analis_fisika($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengujian_parameter`.`keterangan`, 
                    `hasil_uji`,  
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Fisika' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Analis / Teknisi','Koordinator Lab') 
                        AND 
                    `pengujian_parameter`.`id_anggota` = '". $id ."' 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Analis Fisika

    // Data Analis Kimia
    function get_data_pekerjaan_analis_kimia($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengujian_parameter`.`keterangan`, 
                    `hasil_uji`,  
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Kimia' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Analis / Teknisi','Koordinator Lab') 
                        AND 
                    `pengujian_parameter`.`id_anggota` = '". $id ."' 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Analis Kimia

    // Data Analis Lingkungan
    function get_data_pekerjaan_analis_lingkungan($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengujian_parameter`.`keterangan`, 
                    `hasil_uji`,  
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`,
                    GROUP_CONCAT(DISTINCT `pengguna` SEPARATOR ', ') AS `penguji` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Pengujian Lingkungan' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Analis / Teknisi','Koordinator Lab') 
                        AND 
                    `pengujian_parameter`.`id_anggota` = '". $id ."' 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Analis Lingkungan

    // Data Analis Kalibrasi
    function get_data_pekerjaan_analis_kalibrasi($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);

        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `status_pengerjaan`, 
                    `pengujian_parameter`.`keterangan`, 
                    `hasil_uji`,  
                    GROUP_CONCAT(DISTINCT `nama_contoh` SEPARATOR ', ') AS `nama_contoh`, 
                    GROUP_CONCAT(DISTINCT `kategori` SEPARATOR ', ') AS `kategori`,
                    GROUP_CONCAT(DISTINCT `pengguna` SEPARATOR ', ') AS `penguji` 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pembayaran` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `nama_lab` = 'Lab. Kalibrasi' 
                        AND 
                    `status_pengerjaan` 
                        IN 
                    ('Analis / Teknisi','Koordinator Lab') 
                        AND 
                    `pengujian_parameter`.`id_anggota` = '". $id ."' 
                GROUP BY 
                    `kd_pemesanan` DESC
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }
    // End of Data Analis Kalibrasi

    function webapi001_employee($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $param = $data['data'];
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    a.`id_anggota`,
                    a.`id_jabatan`,
                    a.`pengguna`,
                    a.`nip`, 
                    a.`username`,
                    b.`id_bagian`,
                    b.`nama_jabatan`, 
                    c.`nama_bagian`
                FROM 
                    `anggota` a
                LEFT JOIN
                    `jabatan` b
                        ON
                    a.`id_jabatan` = b.`id_jabatan`
                LEFT JOIN
                    `bagian` c
                        ON
                    b.`id_bagian` = c.`id_bagian`
                WHERE 
                    a.`username` = '". $this->db->escape_str($param['username']) ."' 
                        AND 
                    a.`password` = '". md5($this->db->escape_str($param['password'])) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        if (count($R) > 0) {
            $result['result'] = true;
            $result['data'] = $R[0];
            $result['target'] = '../dashboard/dashboard.html';

            $id_anggota = $R[0]['id_anggota'];
            $nama_bagian = $R[0]['nama_bagian'];
            $nama_jabatan = $R[0]['nama_jabatan'];
            // Administrator
            if ($nama_bagian == 'Administrator' && $nama_jabatan == 'Administrator') {
                # code...
            // Pemasaran
            } elseif ($nama_bagian == 'Pemasaran' && $nama_jabatan == 'Pemasaran') {
                # code...
            // Operator Administrasi
            } elseif ($nama_bagian == 'Administrasi' && $nama_jabatan == 'Operator Administrasi') {
                # code...
            // MT Tekstil
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Manajer Teknik') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_tekstil();
                // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_tekstil();
                // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_tekstil();
                // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_tekstil();
                // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_tekstil();
                $result['target'] = '../mt/tekstil/dashboard.html';
            // MT Kalibrasi
            } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Manajer Teknik') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_kalibrasi();
                // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_kalibrasi();
                // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_kalibrasi();
                // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_kalibrasi();
                // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_kalibrasi();
                $result['target'] = '../mt/kalibrasi/dashboard.html';
            // MT Lingkungan
            } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Manajer Teknik') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_lingkungan();
                // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_lingkungan();
                // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_lingkungan();
                // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_lingkungan();
                // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_lingkungan();
                $result['target'] = '../mt/lingkungan/dashboard.html';
            // Koorlab Fisika
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Koordinator Lab Fisika') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_fisika();
                // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_fisika();
                // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_fisika();
                $result['target'] = '../koorlab/fisika/dashboard.html';
            // Koorlab Kimia
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Koordinator Lab Kimia') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_kimia();
                // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kimia();
                // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_kimia();
                $result['target'] = '../koorlab/kimia/dashboard.html';
            // Koorlab Lingkungan
            } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Koordinator Lab Lingkungan') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_lingkungan();
                // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_lingkungan();
                // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_lingkungan();
                $result['target'] = '../koorlab/lingkungan/dashboard.html';
            // Koorlab Kalibrasi
            } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Koordinator Lab Kalibrasi') {
                // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_kalibrasi();
                // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kalibrasi();
                // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_kalibrasi();
                $result['target'] = '../koorlab/kalibrasi/dashboard.html';
            // Analis Teknisi Fisika
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Analis / Teknisi Fisika') {
                // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_fisika($id_anggota);
                $result['target'] = '../analis/fisika/dashboard.html';
            // Analis Teknisi Kimia
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Analis / Teknisi Kimia') {
                // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_kimia($id_anggota);
                $result['target'] = '../analis/kimia/dashboard.html';
            // Analis Teknisi Lingkungan
            } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Analis / Teknisi Lingkungan') {
                // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_lingkungan($id_anggota);
                $result['target'] = '../analis/lingkungan/dashboard.html';
            // Analis Teknisi Kalibrasi
            } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Analis / Teknisi Kalibrasi') {
                // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_kalibrasi($id_anggota);
                $result['target'] = '../analis/kalibrasi/dashboard.html';
            // Dokumentasi Tekstil
            } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Dokumentasi') {
                $result['target'] = '../dokumentasi/tekstil/dashboard.html';
            // Dokumentasi Kalibrasi
            } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Dokumentasi') {
                $result['target'] = '../dokumentasi/kalibrasi/dashboard.html';
            // Dokumentasi Lingkungan
            } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Dokumentasi') {
                $result['target'] = '../dokumentasi/lingkungan/dashboard.html';
            } else{
                $result['target'] = '../dashboard/dashboard.html';
            }

            $table = $this->db->dbprefix .'t_employee_token';
            $token = md5($R[0]['id_anggota'] . time());
            $q =    "INSERT INTO 
                        `". $table ."` 
                        (
                            `id_t_user`,
                            `token`,
                            `generated_time`,
                            `generated_time_int`
                        ) 
                    VALUES 
                        (
                            '". $R[0]['id_anggota'] ."',
                            '". $token ."',
                            '". date('Y-m-d H:i:s') ."',
                            '". time() ."'
                        )
                        ;";
            if ($this->db->simple_query($q)) {
                $id = $this->db->insert_id();
                $q =    "SELECT 
                            `id_t_user` AS `id_user`,
                            `token`,
                            `generated_time`,
                            `generated_time_int`,
                            `status`
                        FROM 
                            `". $table ."` 
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
        } else{
            $result['msg'] = 'Invalid username or password.';
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

    function webapi004_employee($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $param = $data['data'];
        $table = $this->db->dbprefix .'t_employee_token';
        $q =    "SELECT 
                    *
                FROM 
                    `". $table ."`
                WHERE 
                    `id_t_user` = '". $param['id_user'] ."' 
                        AND 
                    `token` = '". $param['token'] ."' 
                        AND 
                    `status` = '1'
                ;";
        $r = $this->db->query($q, false)->result_array();
        if (count($r) > 0) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        a.`id_anggota`,
                        a.`id_jabatan`,
                        a.`pengguna`,
                        a.`nip`, 
                        a.`username`,
                        b.`id_bagian`,
                        b.`nama_jabatan`, 
                        c.`nama_bagian`
                    FROM 
                        `anggota` a
                    LEFT JOIN
                        `jabatan` b
                            ON
                        a.`id_jabatan` = b.`id_jabatan`
                    LEFT JOIN
                        `bagian` c
                            ON
                        b.`id_bagian` = c.`id_bagian`
                    WHERE 
                        a.`id_anggota` = '". $this->db->escape_str($param['id_user']) ."' 
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $result['result'] = true;
                $result['data'] = $R[0];
                $result['target'] = '../dashboard/dashboard.html';

                $id_anggota = $R[0]['id_anggota'];
                $nama_bagian = $R[0]['nama_bagian'];
                $nama_jabatan = $R[0]['nama_jabatan'];
                // Administrator
                if ($nama_bagian == 'Administrator' && $nama_jabatan == 'Administrator') {
                    # code...
                // Pemasaran
                } elseif ($nama_bagian == 'Pemasaran' && $nama_jabatan == 'Pemasaran') {
                    # code...
                // Operator Administrasi
                } elseif ($nama_bagian == 'Administrasi' && $nama_jabatan == 'Operator Administrasi') {
                    # code...
                // MT Tekstil
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Manajer Teknik') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_tekstil();
                    // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_tekstil();
                    // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_tekstil();
                    // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_tekstil();
                    // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_tekstil();
                    $result['target'] = '../mt/tekstil/dashboard.html';
                // MT Kalibrasi
                } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Manajer Teknik') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_kalibrasi();
                    // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_kalibrasi();
                    // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_kalibrasi();
                    // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_kalibrasi();
                    // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_kalibrasi();
                    $result['target'] = '../mt/kalibrasi/dashboard.html';
                // MT Lingkungan
                } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Manajer Teknik') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_mt_lingkungan();
                    // $result['data']['pemeriksaan_hasil_dokumentasi'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_lingkungan();
                    // $result['data']['info_kesanggupan'] = $this->get_data_info_kesanggupan_mt_lingkungan();
                    // $result['data']['laporan_keuangan'] = $this->get_data_laporan_keuangan_mt_lingkungan();
                    // $result['data']['laporan_produksi'] = $this->get_data_laporan_produksi_mt_lingkungan();
                    $result['target'] = '../mt/lingkungan/dashboard.html';
                // Koorlab Fisika
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Koordinator Lab Fisika') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_fisika();
                    // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_fisika();
                    // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_fisika();
                    $result['target'] = '../koorlab/fisika/dashboard.html';
                // Koorlab Kimia
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Koordinator Lab Kimia') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_kimia();
                    // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kimia();
                    // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_kimia();
                    $result['target'] = '../koorlab/kimia/dashboard.html';
                // Koorlab Lingkungan
                } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Koordinator Lab Lingkungan') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_lingkungan();
                    // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_lingkungan();
                    // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_lingkungan();
                    $result['target'] = '../koorlab/lingkungan/dashboard.html';
                // Koorlab Kalibrasi
                } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Koordinator Lab Kalibrasi') {
                    // $result['data']['pekerjaan_pengujian'] = $this->get_data_pekerjaan_pengujian_koorlab_kalibrasi();
                    // $result['data']['pemeriksaan_hasil_pengujian'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kalibrasi();
                    // $result['data']['history_analis'] = $this->get_data_history_analis_koorlab_kalibrasi();
                    $result['target'] = '../koorlab/kalibrasi/dashboard.html';
                // Analis Teknisi Fisika
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Analis / Teknisi Fisika') {
                    // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_fisika($id_anggota);
                    $result['target'] = '../analis/fisika/dashboard.html';
                // Analis Teknisi Kimia
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Analis / Teknisi Kimia') {
                    // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_kimia($id_anggota);
                    $result['target'] = '../analis/kimia/dashboard.html';
                // Analis Teknisi Lingkungan
                } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Analis / Teknisi Lingkungan') {
                    // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_lingkungan($id_anggota);
                    $result['target'] = '../analis/lingkungan/dashboard.html';
                // Analis Teknisi Kalibrasi
                } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan = 'Analis / Teknisi Kalibrasi') {
                    // $result['data']['pekerjaan_analis'] = $this->get_data_pekerjaan_analis_kalibrasi($id_anggota);
                    $result['target'] = '../analis/kalibrasi/dashboard.html';
                // Dokumentasi Tekstil
                } elseif ($nama_bagian == 'Layanan Pengujian Tekstil' && $nama_jabatan == 'Dokumentasi') {
                    $result['target'] = '../dokumentasi/tekstil/dashboard.html';
                // Dokumentasi Kalibrasi
                } elseif ($nama_bagian == 'Layanan Kalibrasi' && $nama_jabatan == 'Dokumentasi') {
                    $result['target'] = '../dokumentasi/kalibrasi/dashboard.html';
                // Dokumentasi Lingkungan
                } elseif ($nama_bagian == 'Layanan Pengujian Lingkungan' && $nama_jabatan == 'Dokumentasi') {
                    $result['target'] = '../dokumentasi/lingkungan/dashboard.html';
                } else{
                    $result['target'] = '../dashboard/dashboard.html';
                }
            } else{
                $result['msg'] = 'Your session was valid but your user data is missing.';
            }
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

    function webapi008($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Dalam Pengerjaan', 
                        `status_pengerjaan` = 'Akan Dibagikan' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Dalam Pengerjaan',
                                '". date('Y-m-d H:i:s') ."',
                                'Akan Dibagikan ke Analis'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi009($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Validasi Dokumen', 
                        `status_pengerjaan` = 'MT' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function get_data_dokumentasi_sertifikat($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                LEFT JOIN 
                    `hal_sertifikat` 
                        USING(`id_ko`) 
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_dokumentasi_valid_simpan($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_dokumentasi_customer($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_dokumentasi_barang_uji($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`)
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_dokumentasi_lampiran($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`)
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function get_data_dokumentasi_lampiran_uji($id = 0)
    {
        $R = array();

        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pelanggan` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `anggota` 
                        USING(`id_anggota`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`)
                WHERE 
                    `id_pemesanan` = '". $this->db->escape_str($id) ."'
                ;";
        $R = $DB->query($Q, false)->result_array();

        return $R;
    }

    function webapi010($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $dataResult = array(
                'sertifikat'    => $this->get_data_dokumentasi_sertifikat($param),
                'valid_simpan'  => $this->get_data_dokumentasi_valid_simpan($param),
                'customer'      => $this->get_data_dokumentasi_customer($param),
                'barang_uji'    => $this->get_data_dokumentasi_barang_uji($param),
                'lampiran'      => $this->get_data_dokumentasi_lampiran($param),
                'lampiran_uji'  => $this->get_data_dokumentasi_lampiran_uji($param)
            );

            $result['result'] = true;
            $result['msg'] = 'Berhasil.';
            $result['data'] = $dataResult;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi011($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pekerjaan Selesai', 
                        `status_pengerjaan` = 'Selesai',
                        `tgl_selesai` = '". date('Y-m-d') ."'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Selesai',
                                '". date('Y-m-d H:i:s') ."',
                                ''
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi012($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pembuatan Sertifikat', 
                        `status_pengerjaan` = 'Dokumentasi'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi013($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'OK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi014($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'TIDAK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi015($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Dalam Pengerjaan', 
                        `status_pengerjaan` = 'Akan Dibagikan' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Dalam Pengerjaan',
                                '". date('Y-m-d H:i:s') ."',
                                'Akan Dibagikan ke Analis'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi016($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Validasi Dokumen', 
                        `status_pengerjaan` = 'MT' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi017($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $dataResult = array(
                'sertifikat'    => $this->get_data_dokumentasi_sertifikat($param),
                'valid_simpan'  => $this->get_data_dokumentasi_valid_simpan($param),
                'customer'      => $this->get_data_dokumentasi_customer($param),
                'barang_uji'    => $this->get_data_dokumentasi_barang_uji($param),
                'lampiran'      => $this->get_data_dokumentasi_lampiran($param),
                'lampiran_uji'  => $this->get_data_dokumentasi_lampiran_uji($param)
            );

            $result['result'] = true;
            $result['msg'] = 'Berhasil.';
            $result['data'] = $dataResult;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi018($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pekerjaan Selesai', 
                        `status_pengerjaan` = 'Selesai',
                        `tgl_selesai` = '". date('Y-m-d') ."'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Selesai',
                                '". date('Y-m-d H:i:s') ."',
                                ''
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi019($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pembuatan Sertifikat', 
                        `status_pengerjaan` = 'Dokumentasi'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi020($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'OK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi021($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'TIDAK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi022($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Dalam Pengerjaan', 
                        `status_pengerjaan` = 'Akan Dibagikan' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Kalibrasi',
                                'Dalam Pengerjaan',
                                '". date('Y-m-d H:i:s') ."',
                                'Akan Dibagikan ke Analis'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi023($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Validasi Dokumen', 
                        `status_pengerjaan` = 'MT' 
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi024($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $dataResult = array(
                'sertifikat'    => $this->get_data_dokumentasi_sertifikat($param),
                'valid_simpan'  => $this->get_data_dokumentasi_valid_simpan($param),
                'customer'      => $this->get_data_dokumentasi_customer($param),
                'barang_uji'    => $this->get_data_dokumentasi_barang_uji($param),
                'lampiran'      => $this->get_data_dokumentasi_lampiran($param),
                'lampiran_uji'  => $this->get_data_dokumentasi_lampiran_uji($param)
            );

            $result['result'] = true;
            $result['msg'] = 'Berhasil.';
            $result['data'] = $dataResult;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi025($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pekerjaan Selesai', 
                        `status_pengerjaan` = 'Selesai',
                        `tgl_selesai` = '". date('Y-m-d') ."'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Kalibrasi',
                                'Selesai',
                                '". date('Y-m-d H:i:s') ."',
                                ''
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi026($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    SET 
                        `status_pemesanan` = 'Pembuatan Sertifikat', 
                        `status_pengerjaan` = 'Dokumentasi'
                    WHERE 
                        `id_pemesanan` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Kalibrasi',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi027($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'OK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi028($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `parameter` 
                    SET 
                        `status` = 'TIDAK' 
                    WHERE 
                        `id_parameter` = '". $param ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function get_order_pelanggan($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `kota` 
                        USING(`id_kota`) 
                JOIN 
                    `provinsi` 
                        USING(`id_provinsi`)
                WHERE
                    `id_pemesanan` = '". $id ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    function get_order_pelanggan_barang($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pengujian_parameter` 
                JOIN 
                    `pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`)
                WHERE
                    `id_pemesanan` = '". $id ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    function get_order_barang($id = 0, $lab = '')
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`)
                WHERE
                    `id_pemesanan` = '". $id ."'
                        AND
                    `nama_lab` = '". $lab ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    function get_analis_teknisi($jabatan = '')
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `jabatan` 
                JOIN 
                    `anggota` 
                        USING(`id_jabatan`) 
                WHERE 
                    `nama_jabatan` = '". $jabatan ."'
                ORDER BY
                    `pengguna` ASC
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    // for korlab
    function get_valid_simpan($id = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                WHERE 
                    `id_pemesanan` = '". $id ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    function get_lampiran($id = 0, $lab = '')
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT 
                    * 
                FROM 
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kelengkapan_pemesanan` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`) 
                where 
                    `id_pemesanan` = '". $id ."' 
                        and 
                    `nama_lab` = '". $lab ."' 
                GROUP BY 
                    `id_pemesanan`
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }
    // end for korlab

    // korlab fisika
    function webapi029($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Fisika'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Fisika')
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi030($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token  = $data['data']['token'];
        $id     = $data['data']['id'];
        $param  = $data['data']['analis'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['msg'] = 'Berhasil.';

            $DB = $this->load->database('si_tekstil', TRUE);
            for ($i=0; $i < count($param); $i++) { 
                $Q =    "UPDATE 
                            `pemesanan` 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `parameter` 
                                USING(`id_parameter`) 
                        JOIN 
                            `contoh` 
                                USING(`id_contoh`) 
                        JOIN 
                            `kategori` 
                                USING(`id_kategori`) 
                        JOIN 
                            `laboratorium` 
                                USING(`id_lab`)
                        SET
                            `status_pemesanan` = 'Analis / Teknisi',
                            `status_pengerjaan` = 'Analis / Teknisi',
                            `keterangan` = '". $this->db->escape_str($param[$i]['keterangan']) ."',
                            `id_anggota` = '". $this->db->escape_str($param[$i]['id_analis']) ."',
                            `status_uji` = 'Belum Selesai'
                        WHERE
                            `id_pemesanan` = '". $id ."'
                                AND
                            `id_pengujian` = '". $this->db->escape_str($param[$i]['id_pengujian']) ."'
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal.';
                    break;
                } else{
                    $Q =    "INSERT INTO 
                                `status_his` 
                                (
                                    `id_wo`,
                                    `id_anggota`,
                                    `kategori`,
                                    `status`,
                                    `tanggal`,
                                    `keterangan`
                                ) 
                            VALUES 
                                (
                                    '". $id ."',
                                    '". $token['id_user'] ."',
                                    'Pengujian',
                                    'Analis / Teknisi',
                                    '". date('Y-m-d H:i:s') ."',
                                    'Analis Fisika'
                                )
                            ;";
                    if (!$DB->simple_query($Q)) {
                        $result['result'] = false;
                        $result['msg'] = 'Gagal menambahkan history ke database.';
                    }
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi031($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'valid_simpan'  => $this->get_valid_simpan($param),
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Fisika'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Fisika'),
                'lampiran'      => $this->get_lampiran($param, 'Lab. Pengujian Fisika')
            );

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "DELETE FROM `retest_param` WHERE `id_pemesanan` = '". $this->db->escape_str($param) ."';";
            $DB->simple_query($Q);
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi032($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET 
                        `status_pemesanan` = 'Koordinator Lab', 
                        `status_pengerjaan` = 'Koordinator Lab' 
                    WHERE 
                        `id_pemesanan` = '". $param ."' 
                            AND
                        `nama_lab` = 'Lab. Pengujian Fisika'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi033($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `retest_param` 
                    WHERE 
                        `id_anggota` = '". $param['id_anggota'] ."'
                            AND
                        `id_contoh` = '". $param['id_contoh'] ."'
                            AND
                        `id_kategori` = '". $param['id_kategori'] ."'
                            AND
                        `id_lab` = '". $param['id_lab'] ."'
                            AND
                        `id_parameter` = '". $param['id_parameter'] ."'
                            AND
                        `id_pelanggan` = '". $param['id_pelanggan'] ."'
                            AND
                        `id_pemesanan` = '". $param['id_pemesanan'] ."'
                            AND
                        `id_pengujian` = '". $param['id_pengujian'] ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $Q = "DELETE FROM `retest_param` WHERE `id` = '". $R[0]['id'] ."';";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil hapus data retest parameter request.';
                }
            } else{
                $Q =    "INSERT INTO 
                            `retest_param` 
                            (
                                `id_anggota`,
                                `id_contoh`,
                                `id_jenis_usaha`,
                                `id_kategori`,
                                `id_kota`,
                                `id_lab`,
                                `id_layanan`,
                                `id_parameter`,
                                `id_pelanggan`,
                                `id_pemesanan`,
                                `id_pengujian`,
                                `id_satuan`,
                                `created_date`
                            ) 
                        VALUES 
                            (
                                '". $param['id_anggota'] ."',
                                '". $param['id_contoh'] ."',
                                '". $param['id_jenis_usaha'] ."',
                                '". $param['id_kategori'] ."',
                                '". $param['id_kota'] ."',
                                '". $param['id_lab'] ."',
                                '". $param['id_layanan'] ."',
                                '". $param['id_parameter'] ."',
                                '". $param['id_pelanggan'] ."',
                                '". $param['id_pemesanan'] ."',
                                '". $param['id_pengujian'] ."',
                                '". $param['id_satuan'] ."',
                                '". date('Y-m-d H:i:s') ."'
                            )
                        ;";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Data retest parameter request berhsil ditambahkan.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi034($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Pembuatan Sertifikat',
                        `status_pengerjaan` = 'Dokumentasi',
                        `keterangan` = 'Selesai',
                        `ket_dokumentasi` = 'Belum Diisi'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `nama_lab` = 'Lab. Pengujian Fisika'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Dokumentasi'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi035($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "SELECT * FROM `retest_param` WHERE `id_pemesanan` = '". $id ."';";
            $R = $DB->query($Q, false)->result_array();
            $hasil = true;
            for ($i=0; $i < count($R); $i++) { 
                $id_pengujian = $R[$i]['id_pengujian'];
                $Q =    "UPDATE 
                            `pengujian_parameter` 
                        SET 
                            `status_pengerjaan` = 'Analis / Teknisi', 
                            `status_uji` = 'Belum Selesai', 
                            `tgl_uji` = NULL 
                        WHERE 
                            `id_pengujian` = '". $id_pengujian ."'
                        ;";
                if ($DB->simple_query($Q)) {
                    $id_retest = $R[$i]['id'];
                    $Q = "DELETE FROM `retest_param` WHERE `id` = '". $id_retest ."';";
                    if (!$DB->simple_query($Q)) {
                        $hasil = false;
                    }
                } else{
                    $hasil = false;
                }
            }
            
            if ($hasil) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Analis / Teknisi',
                                '". date('Y-m-d H:i:s') ."',
                                'Pengujian Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of korlab fisika

    // korlab kimia
    function webapi036($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Kimia'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kimia')
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi037($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token  = $data['data']['token'];
        $id     = $data['data']['id'];
        $param  = $data['data']['analis'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['msg'] = 'Berhasil.';

            $DB = $this->load->database('si_tekstil', TRUE);
            for ($i=0; $i < count($param); $i++) { 
                $Q =    "UPDATE 
                            `pemesanan` 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `parameter` 
                                USING(`id_parameter`) 
                        JOIN 
                            `contoh` 
                                USING(`id_contoh`) 
                        JOIN 
                            `kategori` 
                                USING(`id_kategori`) 
                        JOIN 
                            `laboratorium` 
                                USING(`id_lab`)
                        SET
                            `status_pemesanan` = 'Analis / Teknisi',
                            `status_pengerjaan` = 'Analis / Teknisi',
                            `keterangan` = '". $this->db->escape_str($param[$i]['keterangan']) ."',
                            `id_anggota` = '". $this->db->escape_str($param[$i]['id_analis']) ."',
                            `status_uji` = 'Belum Selesai'
                        WHERE
                            `id_pemesanan` = '". $id ."'
                                AND
                            `id_pengujian` = '". $this->db->escape_str($param[$i]['id_pengujian']) ."'
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal.';
                    break;
                } else{
                    $Q =    "INSERT INTO 
                                `status_his` 
                                (
                                    `id_wo`,
                                    `id_anggota`,
                                    `kategori`,
                                    `status`,
                                    `tanggal`,
                                    `keterangan`
                                ) 
                            VALUES 
                                (
                                    '". $id ."',
                                    '". $token['id_user'] ."',
                                    'Pengujian',
                                    'Analis / Teknisi',
                                    '". date('Y-m-d H:i:s') ."',
                                    'Analis Kimia'
                                )
                            ;";
                    if (!$DB->simple_query($Q)) {
                        $result['result'] = false;
                        $result['msg'] = 'Gagal menambahkan history ke database.';
                    }
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi038($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'valid_simpan'  => $this->get_valid_simpan($param),
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Kimia'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kimia'),
                'lampiran'      => $this->get_lampiran($param, 'Lab. Pengujian Kimia')
            );

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "DELETE FROM `retest_param` WHERE `id_pemesanan` = '". $this->db->escape_str($param) ."';";
            $DB->simple_query($Q);
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi039($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET 
                        `status_pemesanan` = 'Koordinator Lab', 
                        `status_pengerjaan` = 'Koordinator Lab' 
                    WHERE 
                        `id_pemesanan` = '". $param ."' 
                            AND
                        `nama_lab` = 'Lab. Pengujian Kimia'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi040($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `retest_param` 
                    WHERE 
                        `id_anggota` = '". $param['id_anggota'] ."'
                            AND
                        `id_contoh` = '". $param['id_contoh'] ."'
                            AND
                        `id_kategori` = '". $param['id_kategori'] ."'
                            AND
                        `id_lab` = '". $param['id_lab'] ."'
                            AND
                        `id_parameter` = '". $param['id_parameter'] ."'
                            AND
                        `id_pelanggan` = '". $param['id_pelanggan'] ."'
                            AND
                        `id_pemesanan` = '". $param['id_pemesanan'] ."'
                            AND
                        `id_pengujian` = '". $param['id_pengujian'] ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $Q = "DELETE FROM `retest_param` WHERE `id` = '". $R[0]['id'] ."';";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil hapus data retest parameter request.';
                }
            } else{
                $Q =    "INSERT INTO 
                            `retest_param` 
                            (
                                `id_anggota`,
                                `id_contoh`,
                                `id_jenis_usaha`,
                                `id_kategori`,
                                `id_kota`,
                                `id_lab`,
                                `id_layanan`,
                                `id_parameter`,
                                `id_pelanggan`,
                                `id_pemesanan`,
                                `id_pengujian`,
                                `id_satuan`,
                                `created_date`
                            ) 
                        VALUES 
                            (
                                '". $param['id_anggota'] ."',
                                '". $param['id_contoh'] ."',
                                '". $param['id_jenis_usaha'] ."',
                                '". $param['id_kategori'] ."',
                                '". $param['id_kota'] ."',
                                '". $param['id_lab'] ."',
                                '". $param['id_layanan'] ."',
                                '". $param['id_parameter'] ."',
                                '". $param['id_pelanggan'] ."',
                                '". $param['id_pemesanan'] ."',
                                '". $param['id_pengujian'] ."',
                                '". $param['id_satuan'] ."',
                                '". date('Y-m-d H:i:s') ."'
                            )
                        ;";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Data retest parameter request berhsil ditambahkan.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi041($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Pembuatan Sertifikat',
                        `status_pengerjaan` = 'Dokumentasi',
                        `keterangan` = 'Selesai',
                        `ket_dokumentasi` = 'Belum Diisi'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `nama_lab` = 'Lab. Pengujian Kimia'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Dokumentasi'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi042($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "SELECT * FROM `retest_param` WHERE `id_pemesanan` = '". $id ."';";
            $R = $DB->query($Q, false)->result_array();
            $hasil = true;
            for ($i=0; $i < count($R); $i++) { 
                $id_pengujian = $R[$i]['id_pengujian'];
                $Q =    "UPDATE 
                            `pengujian_parameter` 
                        SET 
                            `status_pengerjaan` = 'Analis / Teknisi', 
                            `status_uji` = 'Belum Selesai', 
                            `tgl_uji` = NULL 
                        WHERE 
                            `id_pengujian` = '". $id_pengujian ."'
                        ;";
                if ($DB->simple_query($Q)) {
                    $id_retest = $R[$i]['id'];
                    $Q = "DELETE FROM `retest_param` WHERE `id` = '". $id_retest ."';";
                    if (!$DB->simple_query($Q)) {
                        $hasil = false;
                    }
                } else{
                    $hasil = false;
                }
            }
            
            if ($hasil) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Analis / Teknisi',
                                '". date('Y-m-d H:i:s') ."',
                                'Pengujian Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of korlab kimia

    // korlab kalibrasi
    function webapi043($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Kalibrasi'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kalibrasi')
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi044($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token  = $data['data']['token'];
        $id     = $data['data']['id'];
        $param  = $data['data']['analis'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['msg'] = 'Berhasil.';

            $DB = $this->load->database('si_tekstil', TRUE);
            for ($i=0; $i < count($param); $i++) { 
                $Q =    "UPDATE 
                            `pemesanan` 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `parameter` 
                                USING(`id_parameter`) 
                        JOIN 
                            `contoh` 
                                USING(`id_contoh`) 
                        JOIN 
                            `kategori` 
                                USING(`id_kategori`) 
                        JOIN 
                            `laboratorium` 
                                USING(`id_lab`)
                        SET
                            `status_pemesanan` = 'Analis / Teknisi',
                            `status_pengerjaan` = 'Analis / Teknisi',
                            `keterangan` = '". $this->db->escape_str($param[$i]['keterangan']) ."',
                            `id_anggota` = '". $this->db->escape_str($param[$i]['id_analis']) ."',
                            `status_uji` = 'Belum Selesai'
                        WHERE
                            `id_pemesanan` = '". $id ."'
                                AND
                            `id_pengujian` = '". $this->db->escape_str($param[$i]['id_pengujian']) ."'
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal.';
                    break;
                } else{
                    $Q =    "INSERT INTO 
                                `status_his` 
                                (
                                    `id_wo`,
                                    `id_anggota`,
                                    `kategori`,
                                    `status`,
                                    `tanggal`,
                                    `keterangan`
                                ) 
                            VALUES 
                                (
                                    '". $id ."',
                                    '". $token['id_user'] ."',
                                    'Pengujian',
                                    'Analis / Teknisi',
                                    '". date('Y-m-d H:i:s') ."',
                                    'Analis Kimia'
                                )
                            ;";
                    if (!$DB->simple_query($Q)) {
                        $result['result'] = false;
                        $result['msg'] = 'Gagal menambahkan history ke database.';
                    }
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi045($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'valid_simpan'  => $this->get_valid_simpan($param),
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Kalibrasi'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kalibrasi'),
                'lampiran'      => $this->get_lampiran($param, 'Lab. Kalibrasi')
            );

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "DELETE FROM `retest_param` WHERE `id_pemesanan` = '". $this->db->escape_str($param) ."';";
            $DB->simple_query($Q);
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi046($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET 
                        `status_pemesanan` = 'Koordinator Lab', 
                        `status_pengerjaan` = 'Koordinator Lab' 
                    WHERE 
                        `id_pemesanan` = '". $param ."' 
                            AND
                        `nama_lab` = 'Lab. Kalibrasi'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi047($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `retest_param` 
                    WHERE 
                        `id_anggota` = '". $param['id_anggota'] ."'
                            AND
                        `id_contoh` = '". $param['id_contoh'] ."'
                            AND
                        `id_kategori` = '". $param['id_kategori'] ."'
                            AND
                        `id_lab` = '". $param['id_lab'] ."'
                            AND
                        `id_parameter` = '". $param['id_parameter'] ."'
                            AND
                        `id_pelanggan` = '". $param['id_pelanggan'] ."'
                            AND
                        `id_pemesanan` = '". $param['id_pemesanan'] ."'
                            AND
                        `id_pengujian` = '". $param['id_pengujian'] ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $Q = "DELETE FROM `retest_param` WHERE `id` = '". $R[0]['id'] ."';";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil hapus data retest parameter request.';
                }
            } else{
                $Q =    "INSERT INTO 
                            `retest_param` 
                            (
                                `id_anggota`,
                                `id_contoh`,
                                `id_jenis_usaha`,
                                `id_kategori`,
                                `id_kota`,
                                `id_lab`,
                                `id_layanan`,
                                `id_parameter`,
                                `id_pelanggan`,
                                `id_pemesanan`,
                                `id_pengujian`,
                                `id_satuan`,
                                `created_date`
                            ) 
                        VALUES 
                            (
                                '". $param['id_anggota'] ."',
                                '". $param['id_contoh'] ."',
                                '". $param['id_jenis_usaha'] ."',
                                '". $param['id_kategori'] ."',
                                '". $param['id_kota'] ."',
                                '". $param['id_lab'] ."',
                                '". $param['id_layanan'] ."',
                                '". $param['id_parameter'] ."',
                                '". $param['id_pelanggan'] ."',
                                '". $param['id_pemesanan'] ."',
                                '". $param['id_pengujian'] ."',
                                '". $param['id_satuan'] ."',
                                '". date('Y-m-d H:i:s') ."'
                            )
                        ;";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Data retest parameter request berhsil ditambahkan.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi048($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Pembuatan Sertifikat',
                        `status_pengerjaan` = 'Dokumentasi',
                        `keterangan` = 'Selesai',
                        `ket_dokumentasi` = 'Belum Diisi'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `nama_lab` = 'Lab. Kalibrasi'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Dokumentasi'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi049($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "SELECT * FROM `retest_param` WHERE `id_pemesanan` = '". $id ."';";
            $R = $DB->query($Q, false)->result_array();
            $hasil = true;
            for ($i=0; $i < count($R); $i++) { 
                $id_pengujian = $R[$i]['id_pengujian'];
                $Q =    "UPDATE 
                            `pengujian_parameter` 
                        SET 
                            `status_pengerjaan` = 'Analis / Teknisi', 
                            `status_uji` = 'Belum Selesai', 
                            `tgl_uji` = NULL 
                        WHERE 
                            `id_pengujian` = '". $id_pengujian ."'
                        ;";
                if ($DB->simple_query($Q)) {
                    $id_retest = $R[$i]['id'];
                    $Q = "DELETE FROM `retest_param` WHERE `id` = '". $id_retest ."';";
                    if (!$DB->simple_query($Q)) {
                        $hasil = false;
                    }
                } else{
                    $hasil = false;
                }
            }
            
            if ($hasil) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Analis / Teknisi',
                                '". date('Y-m-d H:i:s') ."',
                                'Pengujian Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of korlab kalibrasi

    // korlab lingkungan
    function webapi050($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Lingkungan'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Lingkungan')
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi051($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token  = $data['data']['token'];
        $id     = $data['data']['id'];
        $param  = $data['data']['analis'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['msg'] = 'Berhasil.';

            $DB = $this->load->database('si_tekstil', TRUE);
            for ($i=0; $i < count($param); $i++) { 
                $Q =    "UPDATE 
                            `pemesanan` 
                        JOIN 
                            `pengujian_parameter` 
                                USING(`id_pemesanan`) 
                        JOIN 
                            `parameter` 
                                USING(`id_parameter`) 
                        JOIN 
                            `contoh` 
                                USING(`id_contoh`) 
                        JOIN 
                            `kategori` 
                                USING(`id_kategori`) 
                        JOIN 
                            `laboratorium` 
                                USING(`id_lab`)
                        SET
                            `status_pemesanan` = 'Analis / Teknisi',
                            `status_pengerjaan` = 'Analis / Teknisi',
                            `keterangan` = '". $this->db->escape_str($param[$i]['keterangan']) ."',
                            `id_anggota` = '". $this->db->escape_str($param[$i]['id_analis']) ."',
                            `status_uji` = 'Belum Selesai'
                        WHERE
                            `id_pemesanan` = '". $id ."'
                                AND
                            `id_pengujian` = '". $this->db->escape_str($param[$i]['id_pengujian']) ."'
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal.';
                    break;
                } else{
                    $Q =    "INSERT INTO 
                                `status_his` 
                                (
                                    `id_wo`,
                                    `id_anggota`,
                                    `kategori`,
                                    `status`,
                                    `tanggal`,
                                    `keterangan`
                                ) 
                            VALUES 
                                (
                                    '". $id ."',
                                    '". $token['id_user'] ."',
                                    'Pengujian',
                                    'Analis / Teknisi',
                                    '". date('Y-m-d H:i:s') ."',
                                    'Analis Lingkungan'
                                )
                            ;";
                    if (!$DB->simple_query($Q)) {
                        $result['result'] = false;
                        $result['msg'] = 'Gagal menambahkan history ke database.';
                    }
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi052($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'valid_simpan'  => $this->get_valid_simpan($param),
                'customer'      => $this->get_order_pelanggan($param),
                'barang_uji'    => $this->get_order_barang($param, 'Lab. Pengujian Lingkungan'),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Lingkungan'),
                'lampiran'      => $this->get_lampiran($param, 'Lab. Lingkungan')
            );

            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "DELETE FROM `retest_param` WHERE `id_pemesanan` = '". $this->db->escape_str($param) ."';";
            $DB->simple_query($Q);
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi053($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET 
                        `status_pemesanan` = 'Koordinator Lab', 
                        `status_pengerjaan` = 'Koordinator Lab' 
                    WHERE 
                        `id_pemesanan` = '". $param ."' 
                            AND
                        `nama_lab` = 'Lab. Pengujian Lingkungan'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi054($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `retest_param` 
                    WHERE 
                        `id_anggota` = '". $param['id_anggota'] ."'
                            AND
                        `id_contoh` = '". $param['id_contoh'] ."'
                            AND
                        `id_kategori` = '". $param['id_kategori'] ."'
                            AND
                        `id_lab` = '". $param['id_lab'] ."'
                            AND
                        `id_parameter` = '". $param['id_parameter'] ."'
                            AND
                        `id_pelanggan` = '". $param['id_pelanggan'] ."'
                            AND
                        `id_pemesanan` = '". $param['id_pemesanan'] ."'
                            AND
                        `id_pengujian` = '". $param['id_pengujian'] ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $Q = "DELETE FROM `retest_param` WHERE `id` = '". $R[0]['id'] ."';";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil hapus data retest parameter request.';
                }
            } else{
                $Q =    "INSERT INTO 
                            `retest_param` 
                            (
                                `id_anggota`,
                                `id_contoh`,
                                `id_jenis_usaha`,
                                `id_kategori`,
                                `id_kota`,
                                `id_lab`,
                                `id_layanan`,
                                `id_parameter`,
                                `id_pelanggan`,
                                `id_pemesanan`,
                                `id_pengujian`,
                                `id_satuan`,
                                `created_date`
                            ) 
                        VALUES 
                            (
                                '". $param['id_anggota'] ."',
                                '". $param['id_contoh'] ."',
                                '". $param['id_jenis_usaha'] ."',
                                '". $param['id_kategori'] ."',
                                '". $param['id_kota'] ."',
                                '". $param['id_lab'] ."',
                                '". $param['id_layanan'] ."',
                                '". $param['id_parameter'] ."',
                                '". $param['id_pelanggan'] ."',
                                '". $param['id_pemesanan'] ."',
                                '". $param['id_pengujian'] ."',
                                '". $param['id_satuan'] ."',
                                '". date('Y-m-d H:i:s') ."'
                            )
                        ;";
                if ($DB->simple_query($Q)) {
                    $result['result'] = true;
                    $result['msg'] = 'Data retest parameter request berhsil ditambahkan.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi055($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Pembuatan Sertifikat',
                        `status_pengerjaan` = 'Dokumentasi',
                        `keterangan` = 'Selesai',
                        `ket_dokumentasi` = 'Belum Diisi'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `nama_lab` = 'Lab. Pengujian Lingkungan'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Pembuatan Sertifikat',
                                '". date('Y-m-d H:i:s') ."',
                                'Dokumentasi'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi056($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q = "SELECT * FROM `retest_param` WHERE `id_pemesanan` = '". $id ."';";
            $R = $DB->query($Q, false)->result_array();
            $hasil = true;
            for ($i=0; $i < count($R); $i++) { 
                $id_pengujian = $R[$i]['id_pengujian'];
                $Q =    "UPDATE 
                            `pengujian_parameter` 
                        SET 
                            `status_pengerjaan` = 'Analis / Teknisi', 
                            `status_uji` = 'Belum Selesai', 
                            `tgl_uji` = NULL 
                        WHERE 
                            `id_pengujian` = '". $id_pengujian ."'
                        ;";
                if ($DB->simple_query($Q)) {
                    $id_retest = $R[$i]['id'];
                    $Q = "DELETE FROM `retest_param` WHERE `id` = '". $id_retest ."';";
                    if (!$DB->simple_query($Q)) {
                        $hasil = false;
                    }
                } else{
                    $hasil = false;
                }
            }
            
            if ($hasil) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $id ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Analis / Teknisi',
                                '". date('Y-m-d H:i:s') ."',
                                'Pengujian Ulang'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of korlab lingkungan

    function get_order_barang_analis($id_pemesanan = 0, $id_user = 0)
    {
        $DB = $this->load->database('si_tekstil', TRUE);
        $Q =    "SELECT
                    *
                FROM
                    `pemesanan` 
                JOIN 
                    `pelanggan` 
                        USING(`id_pelanggan`) 
                JOIN 
                    `pengujian_parameter` 
                        USING(`id_pemesanan`) 
                JOIN 
                    `parameter` 
                        USING(`id_parameter`) 
                JOIN 
                    `contoh` 
                        USING(`id_contoh`) 
                JOIN 
                    `kategori` 
                        USING(`id_kategori`) 
                JOIN 
                    `laboratorium` 
                        USING(`id_lab`)
                WHERE
                    `id_pemesanan` = '". $id_pemesanan ."'
                        AND
                    `id_anggota` = '". $id_user ."'
                ;";
        $R = $DB->query($Q, false)->result_array();
        return $R;
    }

    // analis fisika
    function webapi057($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan_barang($param),
                'barang_uji'    => $this->get_order_barang_analis($param, $token['id_user']),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Fisika'),
                'lampiran'      => $this->get_order_barang_analis($param, $token['id_user'])
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi058($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q1 =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pengerjaan` = 'Analis / Teknisi'
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            $Q2 =   "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."' 
                            AND 
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            if ($DB->simple_query($Q1) && $DB->simple_query($Q2)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                // $result['msg'] = 'Gagal.';
                // $result['msg'] = $DB->error();
                $result['msg'] = $Q2;
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi059($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Koordinator Lab',
                        `status_pengerjaan` = 'Koordinator Lab',
                        `status_uji` = 'Selesai',
                        `tgl_uji` = '". date('Y-m-d') ."'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Koordinator Lab',
                                '". date('Y-m-d H:i:s') ."',
                                'Cek Hasil Analis Fisika'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi060($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `lampiran_uji` = NULL, 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param['id_pemesanan']) ."' 
                    AND 
                        `id_pengujian` = '". $this->db->escape_str($param['id_pengujian']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $result['data'] = array(
                    'barang_uji'    => $this->get_order_barang_analis($param['id_pemesanan'], $token['id_user'])
                );

                $file = '../silateks/assets/dokumen_lampiran/lampiran_analis/'. $param['lampiran_uji'];
                if (file_exists($file)) {
                    unlink($file);
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of analis fisika

    // analis kimia
    function webapi061($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan_barang($param),
                'barang_uji'    => $this->get_order_barang_analis($param, $token['id_user']),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kimia'),
                'lampiran'      => $this->get_order_barang_analis($param, $token['id_user'])
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi062($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q1 =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pengerjaan` = 'Analis / Teknisi'
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            $Q2 =   "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."' 
                            AND 
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            if ($DB->simple_query($Q1) && $DB->simple_query($Q2)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                // $result['msg'] = 'Gagal.';
                // $result['msg'] = $DB->error();
                $result['msg'] = $Q2;
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi063($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Koordinator Lab',
                        `status_pengerjaan` = 'Koordinator Lab',
                        `status_uji` = 'Selesai',
                        `tgl_uji` = '". date('Y-m-d') ."'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Koordinator Lab',
                                '". date('Y-m-d H:i:s') ."',
                                'Cek Hasil Analis Kimia'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi064($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `lampiran_uji` = NULL, 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param['id_pemesanan']) ."' 
                    AND 
                        `id_pengujian` = '". $this->db->escape_str($param['id_pengujian']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $result['data'] = array(
                    'barang_uji'    => $this->get_order_barang_analis($param['id_pemesanan'], $token['id_user'])
                );

                $file = '../silateks/assets/dokumen_lampiran/lampiran_analis/'. $param['lampiran_uji'];
                if (file_exists($file)) {
                    unlink($file);
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of analis kimia

    // analis kalibrasi
    function webapi065($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan_barang($param),
                'barang_uji'    => $this->get_order_barang_analis($param, $token['id_user']),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Kalibrasi'),
                'lampiran'      => $this->get_order_barang_analis($param, $token['id_user'])
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi066($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q1 =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pengerjaan` = 'Analis / Teknisi'
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            $Q2 =   "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."' 
                            AND 
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            if ($DB->simple_query($Q1) && $DB->simple_query($Q2)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                // $result['msg'] = 'Gagal.';
                // $result['msg'] = $DB->error();
                $result['msg'] = $Q2;
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi067($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Koordinator Lab',
                        `status_pengerjaan` = 'Koordinator Lab',
                        `status_uji` = 'Selesai',
                        `tgl_uji` = '". date('Y-m-d') ."'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Koordinator Lab',
                                '". date('Y-m-d H:i:s') ."',
                                'Cek Hasil Analis Kalibrasi'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi068($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `lampiran_uji` = NULL, 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param['id_pemesanan']) ."' 
                    AND 
                        `id_pengujian` = '". $this->db->escape_str($param['id_pengujian']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $result['data'] = array(
                    'barang_uji'    => $this->get_order_barang_analis($param['id_pemesanan'], $token['id_user'])
                );

                $file = '../silateks/assets/dokumen_lampiran/lampiran_analis/'. $param['lampiran_uji'];
                if (file_exists($file)) {
                    unlink($file);
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of analis kalibrasi

    // analis lingkungan
    function webapi069($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $result['result'] = true;
            $result['data'] = array(
                'customer'      => $this->get_order_pelanggan_barang($param),
                'barang_uji'    => $this->get_order_barang_analis($param, $token['id_user']),
                'analis'        => $this->get_analis_teknisi('Analis / Teknisi Lingkungan'),
                'lampiran'      => $this->get_order_barang_analis($param, $token['id_user'])
            );
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi070($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $id = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q1 =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pengerjaan` = 'Analis / Teknisi'
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            $Q2 =   "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($id) ."' 
                            AND 
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";

            if ($DB->simple_query($Q1) && $DB->simple_query($Q2)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                // $result['msg'] = 'Gagal.';
                // $result['msg'] = $DB->error();
                $result['msg'] = $Q2;
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi071($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `status_pemesanan` = 'Koordinator Lab',
                        `status_pengerjaan` = 'Koordinator Lab',
                        `status_uji` = 'Selesai',
                        `tgl_uji` = '". date('Y-m-d') ."'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                            AND
                        `id_anggota` = '". $this->db->escape_str($token['id_user']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $Q =    "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $param ."',
                                '". $token['id_user'] ."',
                                'Pengujian',
                                'Koordinator Lab',
                                '". date('Y-m-d H:i:s') ."',
                                'Cek Hasil Analis Lingkungan'
                            )
                        ;";
                if (!$DB->simple_query($Q)) {
                    $result['result'] = false;
                    $result['msg'] = 'Gagal menambahkan history ke database.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi072($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['data'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `lampiran_uji` = NULL, 
                        `status_uji` = 'Belum Selesai', 
                        `tgl_uji` = NULL 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param['id_pemesanan']) ."' 
                    AND 
                        `id_pengujian` = '". $this->db->escape_str($param['id_pengujian']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $result['data'] = array(
                    'barang_uji'    => $this->get_order_barang_analis($param['id_pemesanan'], $token['id_user'])
                );

                $file = '../silateks/assets/dokumen_lampiran/lampiran_analis/'. $param['lampiran_uji'];
                if (file_exists($file)) {
                    unlink($file);
                }
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of analis lingkungan

    // dokumentasi tekstil
    function webapi073($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`)
                    SET
                        `status_pemesanan` = 'Pembuatan Sertifikat',
                        `status_pengerjaan` = 'Dokumentasi'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi074($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q1 =    "SELECT 
                        * 
                    FROM 
                        `pemesanan` 
                    JOIN 
                        `pelanggan` 
                            USING(`id_pelanggan`) 
                    JOIN 
                        `kota` 
                            USING(`id_kota`) 
                    JOIN 
                        `provinsi` 
                            USING(`id_provinsi`) 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            $R1 = $DB->query($Q1, false)->result_array();

            $Q2 =   "SELECT 
                        * 
                    FROM 
                        `pemesanan` 
                    JOIN 
                        `kelengkapan_pemesanan` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `pelanggan` 
                            USING(`id_pelanggan`) 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            $R2 = $DB->query($Q2, false)->result_array();

            $Q3 =   "SELECT 
                        * 
                    FROM
                        `pemesanan` 
                    JOIN 
                        `pelanggan` 
                            USING(`id_pelanggan`) 
                    JOIN 
                        `kelengkapan_pemesanan` 
                            USING(`id_pemesanan`) 
                    LEFT JOIN 
                        `hal_sertifikat` 
                            USING(`id_ko`) 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            $R3 = $DB->query($Q3, false)->result_array();
            if (count($R1) > 0 && count($R2) > 0 && count($R3) > 0) {
                $Q4 = "SELECT MAX(MID(id_sertifikat,4,4)) AS `idmax` FROM `hal_sertifikat`;";
                $R4 = $DB->query($Q4, false)->result_array();
                $kd = "";
                if(count($R4) > 0){
                    for ($i=0; $i < count($R4); $i++) { 
                        $tmp = ((int)$R4[$i]['idmax']) + 1;
                        $kd = sprintf("%04s", $tmp);
                    }
                }
                else{ 
                    $kd = "0001"; 
                }

                $sk = "SK";
                $bulan = date("m");
                $tahun = date("Y");

                $kode = $sk.'-'.$kd.'/'.$bulan.'/'.$tahun;

                $result['result'] = true;
                $result['data'] = array(
                    'customer'      => $R1[0],
                    'lampiran_gab'  => $R2[0],
                    'sertifikat'    => $R3[0],
                    'kode_id'       => $kode
                );
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi075($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `pemesanan` 
                    JOIN 
                        `pelanggan` 
                            USING(`id_pelanggan`) 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`) 
                    WHERE 
                        `id_pengujian` = '". $this->db->escape_str($param) ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $result['data'] = $R[0];
            }
            $result['result'] = true;
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi076($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'id'    => $data['data']['id'],
            'merek' => $data['data']['merek'],
            'tipe'  => $data['data']['tipe']
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pengujian_parameter` 
                    SET 
                        `merek` = '". $this->db->escape_str($param['merek']) ."', 
                        `tipe` = '". $this->db->escape_str($param['tipe']) ."', 
                        `ket_dokumentasi` = 'Sudah Diisi' 
                    WHERE 
                        `id_pengujian` = '". $this->db->escape_str($param['id']) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi077($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = array(
            'id'            => $data['data']['id'],
            'id_ko'         => $data['data']['id_ko'],
            'id_sertifikat' => $data['data']['id_sertifikat']
        );

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            if ($param['id_sertifikat'] == '0' || $param['id_sertifikat'] == 0) {
                $Q1 =    "UPDATE 
                            `hal_sertifikat` 
                        SET 
                            `tgl_terbit` = '". date('Y-m-d') ."' 
                        WHERE 
                            `id_ko` = '". $this->db->escape_str($param['id_ko']) ."'
                        ;";
                $Q2 =   "UPDATE 
                            pemesanan 
                        join 
                            pengujian_parameter 
                                using(id_pemesanan) 
                        SET 
                            `status_pemesanan` = 'Validasi Sertifikat', 
                            `status_pengerjaan` = 'MT' 
                        WHERE 
                            `id_pemesanan` = '". $this->db->escape_str($param['id']) ."'
                        ;";
                $Q3 =   "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $this->db->escape_str($param['id']) ."',
                                '". $this->db->escape_str($token['id_user']) ."',
                                'Pengujian',
                                'Manajer Teknik',
                                '". date('Y-m-d H:i:s') ."',
                                'Validasi Sertifikat'
                            )
                        ;";
                if ($DB->simple_query($Q1) && $DB->simple_query($Q2) && $DB->simple_query($Q3)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil.';
                } else{
                    $result['msg'] = 'Gagal.';
                }
            } else{
                $Q1 =    "INSERT INTO 
                            `hal_sertifikat` 
                            (
                                `id_sertifikat`,
                                `id_ko`,
                                `tgl_terbit`
                            ) 
                        VALUES 
                            (
                                '". $this->db->escape_str($param['id_sertifikat']) ."',
                                '". $this->db->escape_str(intval($param['id_ko'])) ."',
                                '". date('Y-m-d') ."'
                            )
                        ;";
                $Q2 =   "UPDATE 
                            pemesanan 
                        join 
                            pengujian_parameter 
                                using(id_pemesanan) 
                        SET 
                            `status_pemesanan` = 'Validasi Sertifikat', 
                            `status_pengerjaan` = 'MT' 
                        WHERE 
                            `id_pemesanan` = '". $this->db->escape_str($param['id']) ."'
                        ;";
                $Q3 =   "INSERT INTO 
                            `status_his` 
                            (
                                `id_wo`,
                                `id_anggota`,
                                `kategori`,
                                `status`,
                                `tanggal`,
                                `keterangan`
                            ) 
                        VALUES 
                            (
                                '". $this->db->escape_str($param['id']) ."',
                                '". $this->db->escape_str($token['id_user']) ."',
                                'Pengujian',
                                'Manajer Teknik',
                                '". date('Y-m-d H:i:s') ."',
                                'Validasi Sertifikat'
                            )
                        ;";
                if ($DB->simple_query($Q1) && $DB->simple_query($Q2) && $DB->simple_query($Q3)) {
                    $result['result'] = true;
                    $result['msg'] = 'Berhasil.';
                } else{
                    $result['msg'] = 'Gagal.';
                }
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi078($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['data']['token'];
        $param = $data['data']['id'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id_user'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "SELECT 
                        * 
                    FROM 
                        `pemesanan` 
                    JOIN 
                        `pelanggan` 
                            USING(`id_pelanggan`) 
                    JOIN 
                        `kota` 
                            USING(`id_kota`) 
                    JOIN 
                        `provinsi` 
                            USING(`id_provinsi`)
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            $R = $DB->query($Q, false)->result_array();
            if (count($R) > 0) {
                $result['result'] = true;
                $result['data'] = $R[0];
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }
    // end of dokumentasi tekstil

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

    function webapi002_upload($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['token'];
        $param = $data['data'];
        $upload = $data['upload'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `pemesanan` 
                    JOIN 
                        `pengujian_parameter` 
                            USING(`id_pemesanan`) 
                    JOIN 
                        `parameter` 
                            USING(`id_parameter`) 
                    JOIN 
                        `contoh` 
                            USING(`id_contoh`) 
                    JOIN 
                        `kategori` 
                            USING(`id_kategori`) 
                    JOIN 
                        `laboratorium` 
                            USING(`id_lab`)
                    SET
                        `lampiran_uji` = '". $this->db->escape_str($upload['file_name']) ."',
                        `status_uji` = 'Selesai',
                        `tgl_uji` = '". date('Y-m-d') ."'
                    WHERE
                        `id_pemesanan` = '". $this->db->escape_str($param['id_pemesanan']) ."'
                            AND
                        `id_pengujian` = '". $this->db->escape_str($param['id_pengujian']) ."'
                    ";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';

                $result['data'] = array(
                    'barang_uji'    => $this->get_order_barang_analis($param['id_pemesanan'], $token['id'])
                );
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi003_upload($data = array())
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());
        $token = $data['token'];
        $param = $data['data']['id'];
        $upload = $data['upload'];

        $valid = $this->secret_key_validation($data['auth']);
        if (!$valid) {
            $result['msg'] = 'Unauthorized access.';
            return $result;
        }

        $isValid = $this->employee_token_validation(array('id' => $token['id'], 'token' => $token['token']));
        if ($isValid) {
            $DB = $this->load->database('si_tekstil', TRUE);
            $Q =    "UPDATE 
                        `kelengkapan_pemesanan` 
                    SET 
                        `lampiran_gab` = '". $this->db->escape_str($upload['file_name']) ."' 
                    WHERE 
                        `id_pemesanan` = '". $this->db->escape_str($param) ."'
                    ;";
            if ($DB->simple_query($Q)) {
                $result['result'] = true;
                $result['msg'] = 'Berhasil.';
                $result['file_name'] = $upload['file_name'];
            } else{
                $result['msg'] = 'Gagal.';
            }
        } else{
            $result['msg'] = 'Your session was not valid.';
        }

        return $result;
    }

    function webapi001_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_mt_tekstil();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi002_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_tekstil();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil dokumentasi yang tersedia.';
        }

        return $result;
    }

    function webapi003_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_info_kesanggupan_mt_tekstil();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data info kesanggupan yang tersedia.';
        }

        return $result;
    }

    function webapi004_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_keuangan_mt_tekstil();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan keuangan yang tersedia.';
        }

        return $result;
    }

    function webapi005_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_produksi_mt_tekstil();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan produksi yang tersedia.';
        }

        return $result;
    }

    function webapi006_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_mt_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi007_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil dokumentasi yang tersedia.';
        }

        return $result;
    }

    function webapi008_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_info_kesanggupan_mt_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data info kesanggupan yang tersedia.';
        }

        return $result;
    }

    function webapi009_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_keuangan_mt_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan keuangan yang tersedia.';
        }

        return $result;
    }

    function webapi010_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_produksi_mt_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan produksi yang tersedia.';
        }

        return $result;
    }

    function webapi011_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_mt_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi012_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_dokumentasi_mt_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil dokumentasi yang tersedia.';
        }

        return $result;
    }

    function webapi013_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_info_kesanggupan_mt_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data info kesanggupan yang tersedia.';
        }

        return $result;
    }

    function webapi014_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_keuangan_mt_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan keuangan yang tersedia.';
        }

        return $result;
    }

    function webapi015_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_laporan_produksi_mt_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data laporan produksi yang tersedia.';
        }

        return $result;
    }

    // korlab fisika
    function webapi016_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_koorlab_fisika();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi017_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_fisika();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi018_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_history_analis_koorlab_fisika();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data riwayat analis yang tersedia.';
        }

        return $result;
    }
    // end of korlab fisika

    // korlab kimia
    function webapi019_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_koorlab_kimia();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi020_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kimia();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi021_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_history_analis_koorlab_kimia();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data riwayat analis yang tersedia.';
        }

        return $result;
    }
    // end of korlab kimia

    // korlab kalibrasi
    function webapi022_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_koorlab_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi023_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi024_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_history_analis_koorlab_kalibrasi();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data riwayat analis yang tersedia.';
        }

        return $result;
    }
    // end of korlab kalibrasi

    // korlab lingkungan
    function webapi025_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_pengujian_koorlab_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi026_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pemeriksaan_hasil_pengujian_koorlab_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pemeriksaan hasil pengujian yang tersedia.';
        }

        return $result;
    }

    function webapi027_load()
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_history_analis_koorlab_lingkungan();
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data riwayat analis yang tersedia.';
        }

        return $result;
    }
    // end of korlab lingkungan

    // analis
    function webapi028_load($id = 0)
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_analis_fisika($id);
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan yang tersedia.';
        }

        return $result;
    }

    function webapi029_load($id = 0)
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_analis_kimia($id);
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan yang tersedia.';
        }

        return $result;
    }

    function webapi030_load($id = 0)
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_analis_kalibrasi($id);
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan yang tersedia.';
        }

        return $result;
    }

    function webapi031_load($id = 0)
    {
        $result = array('result' => false, 'msg' => '', 'data' => array());

        $result['data'] = $this->get_data_pekerjaan_analis_lingkungan($id);
        if (count($result['data']) > 0) {
            $result['result'] = true;
            $result['msg'] = 'Loaded.';
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan yang tersedia.';
        }

        return $result;
    }
    // end of analis

}
