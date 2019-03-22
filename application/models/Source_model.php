<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Source_model extends CI_Model {

    var $DB;

    public function __construct()
    {
        parent::__construct();
        $this->DB = $this->load->database('si_tekstil', TRUE);
    }

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

		$isAuthorized = $this->secret_key_validation($data['auth']);
		if (!$isAuthorized) {
			return $result;
		}

		switch ($data['user']) {
			case 'employee':
				$table = $this->db->dbprefix .'t_employee_token';
		        $q =    "SELECT 
		                    * 
		                FROM 
		                    `". $table ."` 
		                WHERE 
		                    `id_t_user` = '". $this->db->escape_str($data['id_user']) ."' 
		                        AND 
		                    `token` = '". $this->db->escape_str($data['token']) ."' 
		                        AND 
		                    `status` = 1
		                ;";
		        $r = $this->db->query($q, false)->result_array();
		        if (count($r) > 0) {
		            $result = true;
		        }
				break;
			default:
				# code...
				break;
		}

        return $result;
	}

    // pekerjaan pengujian
    function get_data_pekerjaan_pengujian_mt($data = array())
    {
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
                    ". $data['lab'] ." ";

        if ($data['search']['value'] && !isset($data['all'])) {
            $Q .=   "AND
                        `kd_pemesanan`
                            LIKE
                        '%". $this->db->escape_str($data['search']['value']) ."%' ";
        }

        $Q .=   "GROUP BY 
                    `id_pemesanan` 
                ORDER BY 
                    `id_pemesanan` DESC
                ";

        return $Q;
    }

    function get_data_pekerjaan_pengujian_mt_list($data = array())
    {
        $Q = $this->get_data_pekerjaan_pengujian_mt($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_pekerjaan_pengujian_mt_filtered($data = array())
    {
        $Q = $this->get_data_pekerjaan_pengujian_mt($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_pekerjaan_pengujian_mt_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_pekerjaan_pengujian_mt($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }
    // end of pekerjaan pengujian

    // mt tekstil > pekerjaan pengujian
	function webapi_source_001($data = array())
	{
		$result = array(
    		'draw'				=> 1,
    		'recordsTotal'		=> 0,
    		'recordsFiltered'	=> 0,
    		'data'				=> array(),
    		'result'			=> false,
    		'msg'				=> ''
    	);

        $data['lab'] = "('Lab. Pengujian Fisika', 'Lab. Pengujian Kimia')";

    	$list = $this->get_data_pekerjaan_pengujian_mt_list($data);
        if (count($list) > 0) {
        	$result = array(
	    		'draw'				=> $data['draw'],
	    		'recordsTotal'		=> $this->get_data_pekerjaan_pengujian_mt_all($data),
	    		'recordsFiltered'	=> $this->get_data_pekerjaan_pengujian_mt_filtered($data),
	    		'data'				=> $list,
	    		'result'			=> true,
	    		'msg'				=> 'Loaded.',
                'start'             => (int) $data['start'] + 1
	    	);
        } else{
        	$result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

    	return $result;
	}

    // mt kalibrasi > pekerjaan pengujian
    function webapi_source_002($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $data['lab'] = "('Lab. Kalibrasi')";

        $list = $this->get_data_pekerjaan_pengujian_mt_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_pekerjaan_pengujian_mt_all($data),
                'recordsFiltered'   => $this->get_data_pekerjaan_pengujian_mt_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    // mt lingkungan > pekerjaan pengujian
    function webapi_source_003($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $data['lab'] = "('Lab. Pengujian Lingkungan')";

        $list = $this->get_data_pekerjaan_pengujian_mt_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_pekerjaan_pengujian_mt_all($data),
                'recordsFiltered'   => $this->get_data_pekerjaan_pengujian_mt_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan pengujian yang tersedia.';
        }

        return $result;
    }

    // pekerjaan dokumentasi
    function get_data_pekerjaan_dokumentasi($data = array())
    {
        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_selesai`, 
                    `status_pemesanan`, 
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
                    `nama_lab` 
                        IN 
                    ". $data['lab'] ."
                        AND
                    `status_pengerjaan` 
                        IN 
                    ('Dokumentasi', 'MT') ";

        if ($data['search']['value'] && !isset($data['all'])) {
            $Q .=   "AND
                        `kd_pemesanan`
                            LIKE
                        '%". $this->db->escape_str($data['search']['value']) ."%' ";
        }

        $Q .=   "GROUP BY 
                    `id_pemesanan`
                ORDER BY 
                    `id_pemesanan` DESC
                ";

        return $Q;
    }

    function get_data_pekerjaan_dokumentasi_list($data = array())
    {
        $Q = $this->get_data_pekerjaan_dokumentasi($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_pekerjaan_dokumentasi_filtered($data = array())
    {
        $Q = $this->get_data_pekerjaan_dokumentasi($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_pekerjaan_dokumentasi_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_pekerjaan_dokumentasi($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }

    function get_data_parameter_uji($data = array())
    {
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
                    `id_pemesanan` = '". $this->db->escape_str($data['id']) ."'
                ";

        return $Q;
    }

    function get_data_parameter_uji_list($data = array())
    {
        $Q = $this->get_data_parameter_uji($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_parameter_uji_filtered($data = array())
    {
        $Q = $this->get_data_parameter_uji($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_parameter_uji_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_parameter_uji($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }

    function get_data_lampiran_uji($data = array())
    {
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
                    `id_pemesanan` = '". $this->db->escape_str($data['id']) ."'
                ";

        return $Q;
    }

    function get_data_lampiran_uji_list($data = array())
    {
        $Q = $this->get_data_lampiran_uji($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_lampiran_uji_filtered($data = array())
    {
        $Q = $this->get_data_lampiran_uji($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_lampiran_uji_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_lampiran_uji($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }
    // end of pekerjaan dokumentasi

    // dokumentasi tekstil > pekerjaan dokumentasi
    function webapi_source_004($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $data['lab'] = "('Lab. Pengujian Fisika', 'Lab. Pengujian Kimia')";

        $list = $this->get_data_pekerjaan_dokumentasi_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_pekerjaan_dokumentasi_all($data),
                'recordsFiltered'   => $this->get_data_pekerjaan_dokumentasi_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data pekerjaan dokumentasi yang tersedia.';
        }

        return $result;
    }

    // dokumentasi tekstil > data parameter uji
    function webapi_source_007($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $list = $this->get_data_parameter_uji_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_parameter_uji_all($data),
                'recordsFiltered'   => $this->get_data_parameter_uji_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data parameter uji yang tersedia.';
        }

        return $result;
    }

    // dokumentasi tekstil > data lampiran uji
    function webapi_source_008($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $list = $this->get_data_lampiran_uji_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_lampiran_uji_all($data),
                'recordsFiltered'   => $this->get_data_lampiran_uji_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data lampiran uji yang tersedia.';
        }

        return $result;
    }

    // arsip sertifikat
    function get_data_arsip_sertifikat($data = array())
    {
        $Q =    "SELECT 
                    `id_pemesanan`, 
                    `kd_pemesanan`, 
                    `pengguna`, 
                    `nama_pelanggan`, 
                    `tgl_masuk`, 
                    `tgl_selesai`, 
                    `pengguna`, 
                    `bahasa`, 
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
                    `nama_lab` 
                        IN 
                    ". $data['lab'] ."
                        AND 
                    `status_pengerjaan` = 'Selesai' ";

        if ($data['search']['value'] && !isset($data['all'])) {
            $Q .=   "AND
                        `kd_pemesanan`
                            LIKE
                        '%". $this->db->escape_str($data['search']['value']) ."%' ";
        }

        $Q .=   "GROUP BY 
                    `id_pemesanan`
                ORDER BY 
                    `id_pemesanan` DESC
                ";

        return $Q;
    }

    function get_data_arsip_sertifikat_list($data = array())
    {
        $Q = $this->get_data_arsip_sertifikat($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_arsip_sertifikat_filtered($data = array())
    {
        $Q = $this->get_data_arsip_sertifikat($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_arsip_sertifikat_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_arsip_sertifikat($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }
    // end of arsip sertifikat

    // dokumentasi tekstil > data arsip sertifikat
    function webapi_source_009($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $data['lab'] = "('Lab. Pengujian Fisika', 'Lab. Pengujian Kimia')";

        $list = $this->get_data_arsip_sertifikat_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_arsip_sertifikat_all($data),
                'recordsFiltered'   => $this->get_data_arsip_sertifikat_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data arsip sertifikat yang tersedia.';
        }

        return $result;
    }

    // detail arsip sertifikat
    function get_data_detail_arsip_sertifikat($data = array())
    {
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
                    id_pemesanan = '". $this->db->escape_str($data['id']) ."' ";

        return $Q;
    }

    function get_data_detail_arsip_sertifikat_list($data = array())
    {
        $Q = $this->get_data_detail_arsip_sertifikat($data);
        $Q .= "LIMIT ". $this->db->escape_str($data['start']) .", ". $this->db->escape_str($data['length']);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();
        
        return $R;
    }

    function get_data_detail_arsip_sertifikat_filtered($data = array())
    {
        $Q = $this->get_data_detail_arsip_sertifikat($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q, false)->result_array();

        return count($R);
    }

    function get_data_detail_arsip_sertifikat_all($data = array())
    {
        $data['all'] = true;
        $Q = $this->get_data_detail_arsip_sertifikat($data);
        $Q = trim(preg_replace('/\s\s+/', ' ', $Q)); // trim to a line
        $R = $this->DB->query($Q)->result_array();

        return count($R);
    }
    // end of detail arsip sertifikat

    // dokumentasi tekstil > data detail arsip sertifikat
    function webapi_source_010($data = array())
    {
        $result = array(
            'draw'              => 1,
            'recordsTotal'      => 0,
            'recordsFiltered'   => 0,
            'data'              => array(),
            'result'            => false,
            'msg'               => ''
        );

        $list = $this->get_data_detail_arsip_sertifikat_list($data);
        if (count($list) > 0) {
            $result = array(
                'draw'              => $data['draw'],
                'recordsTotal'      => $this->get_data_detail_arsip_sertifikat_all($data),
                'recordsFiltered'   => $this->get_data_detail_arsip_sertifikat_filtered($data),
                'data'              => $list,
                'result'            => true,
                'msg'               => 'Loaded.',
                'start'             => (int) $data['start'] + 1
            );
        } else{
            $result['msg'] = 'Tidak ada data arsip sertifikat yang tersedia.';
        }

        return $result;
    }

}