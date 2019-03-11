<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

class Ajax_service extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('ajax_service_model', 'model');
    }

	private function get_param($param = '')
    {
    	if (isset($_GET[$param])) {
    		return $_GET[$param];
    	} else{
    		return '';
    	}
    }

	public function index()
	{
		$request = $this->get_param('request');
    	$response = array();

    	switch ($request) {
    		case 'ping':
    			$response['result'] = true;
    			$response['msg'] = 'You\'re connected.';
    			break;
            case 'eedacb1cf19c9aa0a5194bede1d25a40': // md5('webapi001') // login
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST['Login']
                );
                $response = $this->model->webapi001($param);
                // $response['post'] = $param['data'];
                break;
            case '82185f6fb4fb3a93abd2459e38459dce': // md5('webapi001_employee') // login employee
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST['Login']
                );
                $response = $this->model->webapi001_employee($param);
                // $response['post'] = $param['data'];
                break;
            case 'a8e01d08c74ef782d4335b5815efcf7d': // md5('webapi002') // register
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST['Register']
                );
                $response = $this->model->webapi002($param);
                if ($response['result']) {
                    $this->load->library('email');

                    $config = array(
                        'protocol'      => "smtp",
                        'smtp_host'     => "ssl://smtp.gmail.com",
                        'smtp_port'     => 465,
                        'smtp_user'     => "sisfobbtkemenperin@gmail.com",
                        'smtp_pass'     => "sm27_gmail",
                        'smtp_timeout'  => 600,
                        'mailtype'      => "html", 
                        'charset'       => "iso-8859-1",
                        'newline'       => "\r\n",
                        'wrapchars'     => 256,
                        'wordwrap'      => TRUE,
                        'mailpath'      => '/usr/sbin/sendmail'
                    );
                    $this->email->initialize($config);

                    $str_ori = array('{FULLNAME}', '{URI}', '{SENDER}', '{ADDRESS}');
                    $str_rpc = array($_POST['Register']['fullname'], base_url('verification?code='.$response['code']), 'Balai Besar Tekstil Bandung', 'Jl. Jenderal Achmad Yani No. 390 Bandung, 40272');

                    $myfile = fopen("./storage/register.html", "r") or die("Unable to open file!");
                    $msg = fread($myfile, filesize("./storage/register.html"));
                    $msg = str_replace($str_ori, $str_rpc, $msg);
                    fclose($myfile);

                    $this->email->from('texirdti@bdg.centrin.net.id', 'Sistem Informasi Balai Besar Tekstil Bandung');
                    $this->email->to($_POST['Register']['email']);
                    $this->email->subject('Link Verifikasi');
                    $this->email->message($msg);
                    if (!$this->email->send()) {
                        file_put_contents('./uploads/email/verification_'. date('YmdHis') .'.html', $msg);
                        $response['result'] = false;
                        $response['msg'] = 'Failed to send verification email.';
                        // $response['msg'] = $this->email->print_debugger();
                    } 
                }
                //$response['post'] = $param['data']['Register'];
                break;
            case '6af28909c46705fc22e4e2259787bc94': // md5('webapi003') // forgot
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST['Forgot']
                );
                $response = $this->model->webapi003($param);
                if ($response['result']) {
                    $this->load->library('email');

                    $config = array(
                        'protocol'      => "smtp",
                        'smtp_host'     => "ssl://smtp.gmail.com",
                        'smtp_port'     => 465,
                        'smtp_user'     => "sisfobbtkemenperin@gmail.com",
                        'smtp_pass'     => "sm27_gmail",
                        'smtp_timeout'  => 600,
                        'mailtype'      => "html", 
                        'charset'       => "iso-8859-1",
                        'newline'       => "\r\n",
                        'wrapchars'     => 256,
                        'wordwrap'      => TRUE,
                        'mailpath'      => '/usr/sbin/sendmail'
                    );
                    $this->email->initialize($config);

                    $str_ori = array('{FULLNAME}', '{PASSWORD}', '{SENDER}', '{ADDRESS}');
                    $str_rpc = array($response['data']['fullname'], $response['data']['password'], 'Balai Besar Tekstil Bandung', 'Jl. Jenderal Achmad Yani No. 390 Bandung, 40272');

                    $myfile = fopen("./storage/forgot.html", "r") or die("Unable to open file!");
                    $msg = fread($myfile, filesize("./storage/forgot.html"));
                    $msg = str_replace($str_ori, $str_rpc, $msg);
                    fclose($myfile);

                    $this->email->from('texirdti@bdg.centrin.net.id', 'Sistem Informasi Balai Besar Tekstil Bandung');
                    $this->email->to($_POST['Forgot']['email']);
                    $this->email->subject('Forgot Password');
                    $this->email->message($msg);
                    if (!$this->email->send()) {
                        file_put_contents('./uploads/email/forgot_'. date('YmdHis') .'.html', $msg);
                        $response['result'] = false;
                        $response['msg'] = 'Sorry, our system failed to send you a recovery email.';
                        // $response['msg'] = $this->email->print_debugger();
                    } 
                }
                //$response['post'] = $param['data']['Forgot'];
                break;
            case '7557c877d37c51bb1b2b17cf7c07f7b5': // md5('webapi004') // session validation
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi004($param);
                // $response['post'] = $param['data'];
                break;
            case '08f6c4027f3cf8ebc9a2aca8e113eaf0': // md5('webapi004_employee') // session validation employee
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi004_employee($param);
                // $response['post'] = $param['data'];
                break;
            case '716f3902aa7d14149cee3ec2c6d1589d': // md5('webapi005') // save fullname
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi005($param);
                // $response['post'] = $param['data'];
                break;
            case '14417867d292840171a22404a85f3ff9': // md5('webapi006') // save company code
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi006($param);
                // $response['post'] = $param['data'];
                break;
            case 'a812b9339fcd1bfa5de8417205aa2749': // md5('webapi007') // save company code
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi007($param);
                // $response['post'] = $param['data'];
                break;
            case '0b001840bb938b47ba6c916fa2c24d1e': // md5('webapi008') // mt tekstil > pekerjaan pengujian > update status pemesanan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi008($param);
                // $response['post'] = $param['data'];
                break;
            case '761847c4cad83ba27c5225e8d15846b1': // md5('webapi009') // mt tekstil > pemeriksaan hasil dokumentasi > ulangi // fa-undo
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi009($param);
                // $response['post'] = $param['data'];
                break;
            case '71b26b86d00773baaa7d582d301bf246': // md5('webapi010') // mt tekstil > pemeriksaan hasil dokumentasi > data dokumentasi // fa-file-text-o
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi010($param);
                // $response['post'] = $param['data'];
                break;
            case '8ca7594f9dc5cf1c7c8a6e6d7c6349a1': // md5('webapi011') // mt tekstil > pemeriksaan hasil dokumentasi > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi011($param);
                // $response['post'] = $param['data'];
                break;
            case 'cb3c7d462b8fe82ea3a2d37ac9fdce5c': // md5('webapi012') // mt tekstil > pemeriksaan hasil dokumentasi > ulang
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi012($param);
                // $response['post'] = $param['data'];
                break;
            case 'fbae1afaffd17ea493a4036d37685c0b': // md5('webapi013') // mt tekstil > info kesanggupan > fa-check
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi013($param);
                // $response['post'] = $param['data'];
                break;
            case 'eaa9a1c28d6ca5e27e33ef9493c1ea9e': // md5('webapi014') // mt tekstil > info kesanggupan > fa-times
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi014($param);
                // $response['post'] = $param['data'];
                break;
            case '2b4a8978690586de38b02cfdbd66e92c': // md5('get_data_laporan_keuangan_mt_tekstil_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_keuangan_mt_tekstil_filter($param);
                break;
            case 'd4eadc6475a64babdeb6d0a252344b80': // md5('get_data_laporan_produksi_mt_tekstil_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_produksi_mt_tekstil_filter($param);
                break;

            // mt lingkungan
            case 'b2235b568aeaa5054bea8e21702efdb2': // md5('webapi015') // mt lingkungan > pekerjaan pengujian > update status pemesanan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi015($param);
                // $response['post'] = $param['data'];
                break;
            case '0f8b894c93038d9b7ed36fb58f1e3db8': // md5('webapi016') // mt lingkungan > pemeriksaan hasil dokumentasi > ulangi // fa-undo
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi016($param);
                // $response['post'] = $param['data'];
                break;
            case 'd5a68ad8cfb578ecaa8190a5b8a027e7': // md5('webapi017') // mt lingkungan > pemeriksaan hasil dokumentasi > data dokumentasi // fa-file-text-o
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi017($param);
                // $response['post'] = $param['data'];
                break;
            case '6a7e6c869f902eefa80443572997ddd4': // md5('webapi018') // mt lingkungan > pemeriksaan hasil dokumentasi > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi018($param);
                // $response['post'] = $param['data'];
                break;
            case '562bef396496ccc0ea14a71e83c4dfe5': // md5('webapi019') // mt lingkungan > pemeriksaan hasil dokumentasi > ulang
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi019($param);
                // $response['post'] = $param['data'];
                break;
            case '652b3fb6de9ca94ae8709c6aa04f502f': // md5('webapi020') // mt lingkungan > info kesanggupan > fa-check
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi020($param);
                // $response['post'] = $param['data'];
                break;
            case 'fe86aaf3b27fd09d71f78a57e44e48e5': // md5('webapi021') // mt lingkungan > info kesanggupan > fa-times
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi021($param);
                // $response['post'] = $param['data'];
                break;
            case 'b488da05c0a93a0cfde91679c1566ec8': // md5('get_data_laporan_keuangan_mt_lingkungan_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_keuangan_mt_lingkungan_filter($param);
                break;
            case '4504a72a037c1674f5a38f2cd280e992': // md5('get_data_laporan_produksi_mt_lingkungan_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_produksi_mt_lingkungan_filter($param);
                break;

            // mt kalibrasi
            case '731fcafdd77711bc391fd86510b32c5e': // md5('webapi022') // mt kalibrasi > pekerjaan pengujian > update status pemesanan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi022($param);
                // $response['post'] = $param['data'];
                break;
            case '73c9bfc816eddbc373b533076cacfe2e': // md5('webapi023') // mt kalibrasi > pemeriksaan hasil dokumentasi > ulangi // fa-undo
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi023($param);
                // $response['post'] = $param['data'];
                break;
            case '2f1b013317af514d58cc387ce0569f3e': // md5('webapi024') // mt kalibrasi > pemeriksaan hasil dokumentasi > data dokumentasi // fa-file-text-o
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi024($param);
                // $response['post'] = $param['data'];
                break;
            case '538931e29885a51f969d3ed4b3b4131c': // md5('webapi025') // mt kalibrasi > pemeriksaan hasil dokumentasi > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi025($param);
                // $response['post'] = $param['data'];
                break;
            case '8226e653e730d7b3f4b458fa0dea0084': // md5('webapi026') // mt kalibrasi > pemeriksaan hasil dokumentasi > ulang
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi026($param);
                // $response['post'] = $param['data'];
                break;
            case '4107a0f5cf94d9f8af90d36bc5ad2351': // md5('webapi027') // mt kalibrasi > info kesanggupan > fa-check
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi027($param);
                // $response['post'] = $param['data'];
                break;
            case '7e0566bc20b113e759bb22c29dccb116': // md5('webapi028') // mt kalibrasi > info kesanggupan > fa-times
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi028($param);
                // $response['post'] = $param['data'];
                break;
            case 'ab8f4c60f8f88284ed5d6c0ee1612634': // md5('get_data_laporan_keuangan_mt_kalibrasi_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_keuangan_mt_kalibrasi_filter($param);
                break;
            case '3a25677d5fcb5019d1735e67749fc9a7': // md5('get_data_laporan_produksi_mt_kalibrasi_filter') 
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->get_data_laporan_produksi_mt_kalibrasi_filter($param);
                break;

            // korlab fisika
            case 'f10b6ea58ff1495f827253838a97a89a': // md5('webapi029') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi029($param);
                // $response['post'] = $param['data'];
                break;
            case 'fe306d68510f47a85d2998f7d74f7a9a': // md5('webapi030') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi030($param);
                // $response['post'] = $param['data'];
                break;
            case 'a35e10084967a35ef80fffee6da10719': // md5('webapi031') // pemeriksaan hasil pengujian > data pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi031($param);
                // $response['post'] = $param['data'];
                break;
            case 'aba2e23345d6e4a01423bec4539254c2': // md5('webapi032') // pemeriksaan hasil pengujian > fa-danger
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi032($param);
                // $response['post'] = $param['data'];
                break;
            case 'b5af72a7ad80088570522117d2137680': // md5('webapi033') // pemeriksaan hasil pengujian > checkbox
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi033($param);
                // $response['post'] = $param['data'];
                break;
            case 'd52db1d87c4ff2092d9751c6f93b6df9': // md5('webapi034') //  pemeriksaan hasil pengujian > dokumentasikan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi034($param);
                // $response['post'] = $param['data'];
                break;
            case '4d64df456751dfd066da375e49174627': // md5('webapi035') // pemeriksaan hasil pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi035($param);
                // $response['post'] = $param['data'];
                break;
            // end of korlab fisika

            // korlab kimia
            case 'cac530299f963869422b6542a3dd0c69': // md5('webapi036') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi036($param);
                // $response['post'] = $param['data'];
                break;
            case '72e2d12fea3a0856471a5da4a5a1fe5a': // md5('webapi037') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi037($param);
                // $response['post'] = $param['data'];
                break;
            case '4a5de6e1cd3aaa80bb7c5383094991a2': // md5('webapi038') // pemeriksaan hasil pengujian > data pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi038($param);
                // $response['post'] = $param['data'];
                break;
            case 'e45cfcee9605b6734f9c2445f47fde81': // md5('webapi039') // pemeriksaan hasil pengujian > fa-danger
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi039($param);
                // $response['post'] = $param['data'];
                break;
            case '30c3aab1767001381832a31ec314c629': // md5('webapi040') // pemeriksaan hasil pengujian > checkbox
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi040($param);
                // $response['post'] = $param['data'];
                break;
            case '0507c5ef4b200e88eb0c95b37044c683': // md5('webapi041') // pemeriksaan hasil pengujian > dokumentasikan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi041($param);
                // $response['post'] = $param['data'];
                break;
            case 'a95addd88831790a7b6eb6b1d040fbd2': // md5('webapi042') // pemeriksaan hasil pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi042($param);
                // $response['post'] = $param['data'];
                break;
            // end of korlab kimia

            // korlab kalibrasi
            case 'f99c78ddc4ce71e054f3d3b16bb01967': // md5('webapi043') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi043($param);
                // $response['post'] = $param['data'];
                break;
            case '348633741e66e39a59cf0f12cd22ba75': // md5('webapi044') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi044($param);
                // $response['post'] = $param['data'];
                break;
            case 'f8cc3b2a0c4d4e6f3969bf6d8b05d311': // md5('webapi045') // pemeriksaan hasil pengujian > data pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi045($param);
                // $response['post'] = $param['data'];
                break;
            case '1a19b920107aa40b6b4551771e5f57fb': // md5('webapi046') // pemeriksaan hasil pengujian > fa-danger
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi046($param);
                // $response['post'] = $param['data'];
                break;
            case '31cb60b539fcf9123debd9c9de28656c': // md5('webapi047') // pemeriksaan hasil pengujian > checkbox
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi047($param);
                // $response['post'] = $param['data'];
                break;
            case 'c4533fad7f50502b7b3e22070aabaa26': // md5('webapi048') // pemeriksaan hasil pengujian > dokumentasikan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi048($param);
                // $response['post'] = $param['data'];
                break;
            case '29d45e135fbc2486708d94fdc9cddad7': // md5('webapi049') // pemeriksaan hasil pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi049($param);
                // $response['post'] = $param['data'];
                break;
            // end of korlab kalibrasi

            // korlab lingkungan
            case '76c26271322ec4a3a2402cd2f8f158a7': // md5('webapi050') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi050($param);
                // $response['post'] = $param['data'];
                break;
            case 'eecdc500d1d96e807820eb6be5c50d5f': // md5('webapi051') // pekerjaan pengujian > proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi051($param);
                // $response['post'] = $param['data'];
                break;
            case 'ae56dacd1dd8375a7ef69d4a8a20cf29': // md5('webapi052') // pemeriksaan hasil pengujian > data pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi052($param);
                // $response['post'] = $param['data'];
                break;
            case '3f858c8d5b08d48426549340234f7f61': // md5('webapi053') // pemeriksaan hasil pengujian > fa-danger
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi053($param);
                // $response['post'] = $param['data'];
                break;
            case '732da345ff0a712f7d16f9db09139128': // md5('webapi054') // pemeriksaan hasil pengujian > checkbox
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi054($param);
                // $response['post'] = $param['data'];
                break;
            case 'd7a27e25043f0df41d670fed5192c836': // md5('webapi055') // pemeriksaan hasil pengujian > dokumentasikan
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi055($param);
                // $response['post'] = $param['data'];
                break;
            case '93441bca170c08eb46194d167ca2dda3': // md5('webapi056') // pemeriksaan hasil pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi056($param);
                // $response['post'] = $param['data'];
                break;
            // end of korlab lingkungan

            // analis fisika
            case 'e54a6ef4b8eee622c2a2da8f13e0728b': // md5('webapi057') // proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi057($param);
                // $response['post'] = $param['data'];
                break;
            case '13257c20284994baa1c482ca4d9809e6': // md5('webapi058') // proses pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi058($param);
                // $response['post'] = $param['data'];
                break;
            case 'e61f11617a7d66c89562e8562067f7e0': // md5('webapi059') // proses pengujian > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi059($param);
                // $response['post'] = $param['data'];
                break;
            case '296bc5c121ed4a7fe8b92c9c92ce5e24': // md5('webapi060') // proses pengujian > hapus lampiran uji
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi060($param);
                // $response['post'] = $param['data'];
                break;
            // end of analis fisika

            // analis kimia
            case 'd6afc70ce770d2867389b147013d34a0': // md5('webapi061') // proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi061($param);
                // $response['post'] = $param['data'];
                break;
            case 'b9ca4513cc99a1fcdd2d3a9e9a5e719f': // md5('webapi062') // proses pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi062($param);
                // $response['post'] = $param['data'];
                break;
            case '5c2ff7f6331a70bbfd00bf43884d3e1b': // md5('webapi063') // proses pengujian > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi063($param);
                // $response['post'] = $param['data'];
                break;
            case '54d69a364b5b0a6ae6e84c1c383cc87b': // md5('webapi064') // proses pengujian > hapus lampiran uji
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi064($param);
                // $response['post'] = $param['data'];
                break;
            // end of analis kimia

            // analis kalibrasi
            case 'fb8ad96d98161c1f27a7a555b4c06f6f': // md5('webapi065') // proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi065($param);
                // $response['post'] = $param['data'];
                break;
            case '0b16ea3fbc98d913e074dfce2f05762c': // md5('webapi066') // proses pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi066($param);
                // $response['post'] = $param['data'];
                break;
            case '7069be195ccbf5931f00854b5913e803': // md5('webapi067') // proses pengujian > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi067($param);
                // $response['post'] = $param['data'];
                break;
            case 'ae3c663ae95df6c7dc8a629f0e7a1b4b': // md5('webapi068') // proses pengujian > hapus lampiran uji
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi068($param);
                // $response['post'] = $param['data'];
                break;
            // end of analis kalibrasi

            // analis lingkungan
            case '9c6351bc9d7c063a2dac8609e7c514c0': // md5('webapi069') // proses pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi069($param);
                // $response['post'] = $param['data'];
                break;
            case '1a62fb49026b5a2f38ba6739021fb671': // md5('webapi070') // proses pengujian > ulangi pengujian
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi070($param);
                // $response['post'] = $param['data'];
                break;
            case 'fe9d44255097454715a4bf22b02672ae': // md5('webapi071') // proses pengujian > selesai
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi071($param);
                // $response['post'] = $param['data'];
                break;
            case '47bfcb86aa93c75f5add32c9d8984331': // md5('webapi072') // proses pengujian > hapus lampiran uji
                $param = array(
                    'useragent' => $this->agent,
                    'ipaddress' => $this->input->ip_address(),
                    'auth' => $this->input->get_request_header('Authorization', TRUE),
                    'data' => $_POST
                );
                $response = $this->model->webapi072($param);
                // $response['post'] = $param['data'];
                break;
            // end of analis lingkungan

    		default:
    			$response['result'] = false;
                $response['msg'] = 'Invalid request.';
    			break;
    	}

    	echo json_encode($response, JSON_PRETTY_PRINT);
	}

    private function token_validation($param = array())
    {
        $result = $this->model->token_validation($param);
        return $result;
    }

    public function upload()
    {
        $response = array('result' => false, 'msg' => 'Your session is not valid to upload a file.');
        $request = $this->get_param('request');
        $token = array(
            'id'        => $this->get_param('id_user'),
            'token'     => $this->get_param('token'),
            'employee'  => $this->get_param('employee')
        );

        $isValid = false;
        if ($token['employee'] != '') {
            $isValid = $this->employee_token_validation($token);
        } else{
            $isValid = $this->token_validation($token);
        }
        if ($isValid) {
            switch ($request) {
                case '32ac9d61d08e9806f92489f89e206b84': // md5('webapi001_upload') // upload display picture
                    $config['upload_path']      = './uploads/img/';
                    $config['allowed_types']    = 'bmp|gif|jpeg|jpg|png'; // image/*
                    $config['max_size']         = 2048;
                    $config['encrypt_name']     = TRUE;

                    $this->upload->initialize($config);

                    if(!$this->upload->do_upload('file')){
                        $response['msg']    = $this->upload->display_errors();
                    } else{
                        $response['result'] = true;
                        $response['data']   = $this->upload->data();
                        $response['msg']    = 'Your display picture has been changed successfully.';

                        $resizer =  array(
                            'image_library'   => 'gd2',
                            'source_image'    =>  $response['data']['full_path'],
                            'maintain_ratio'  =>  TRUE,
                            'width'           =>  160,
                            'height'          =>  160,
                        );
                        $this->image_lib->clear();
                        $this->image_lib->initialize($resizer);
                        $this->image_lib->resize();

                        $param = array(
                            'id'        => $token['id'],
                            'filename'  => base_url('uploads/img/'. $response['data']['file_name'])
                        );

                        $this->model->webapi001_upload($param);
                        $response['file_uploaded'] = $param['filename'];
                    }
                    break;
                case 'f29ad6b45c52319eb8b0af074a18ecdb': // md5('webapi002_upload') // upload hasil uji analis
                    $config['upload_path']      = '../silateks/assets/dokumen_lampiran/lampiran_analis/';
                    $config['allowed_types']    = 'doc|docx|xls|xlsx|pdf|jpeg|jpg|png';
                    $config['max_size']         = 10240;
                    $config['encrypt_name']     = FALSE;
                    $config['overwrite']        = FALSE;

                    $this->upload->initialize($config);

                    if(!$this->upload->do_upload('file')){
                        $response['msg']    = $this->upload->display_errors();
                    } else{
                        $param = array(
                            'useragent' => $this->agent,
                            'ipaddress' => $this->input->ip_address(),
                            'auth' => $this->input->get_request_header('Authorization', TRUE),
                            'data' => $_POST,
                            'token' => $token,
                            'upload' => $this->upload->data()
                        );
                        $response = $this->model->webapi002_upload($param);
                        // $response['post'] = $param;
                    }
                    break;
                default:
                    $response['msg'] = 'Invalid request.';
                    break;
            }
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    private function employee_token_validation($param = array())
    {
        $result = $this->model->employee_token_validation($param);
        return $result;
    }

    public function load()
    {
        $response = array('result' => false, 'msg' => 'Your session is not valid to getting source.');
        $request = $this->get_param('request');
        $token = array(
            'id'    => $this->get_param('id_user'),
            'token' => $this->get_param('token')
        );

        $isValid = $this->employee_token_validation($token);
        if ($isValid) {
            switch ($request) {
                case '22d8b4400208fb8a4ea98ce718e3b25f': // md5('webapi001_load') // load pekerjaan pengujian mt tekstil
                    $response = $this->model->webapi001_load();
                    break;
                case '687d02e508c280ad344b31c48ebc5b42': // md5('webapi002_load') // load pemeriksaan hasil dokumentasi mt tekstil
                    $response = $this->model->webapi002_load();
                    break;
                case '10e8c537016e612d20f24c5e6e41cdfe': // md5('webapi003_load') // load info kesanggupan mt tekstil
                    $response = $this->model->webapi003_load();
                    break;
                case '81396fc8a8beb3d2370e94d6b43c3bcd': // md5('webapi004_load') // load laporan keuangan mt tekstil
                    $response = $this->model->webapi004_load();
                    break;
                case 'fb7bb7f807d4116ca6d99f2b2dfdb62a': // md5('webapi005_load') // load laporan produksi mt tekstil
                    $response = $this->model->webapi005_load();
                    break;
                case '2a8ecc98081b9b86ed2e6d12115b010a': // md5('webapi006_load') // load pekerjaan pengujian mt lingkungan
                    $response = $this->model->webapi006_load();
                    break;
                case '145ba3251860a532d6829b6d415aa714': // md5('webapi007_load') // load pemeriksaan hasil dokumentasi mt lingkungan
                    $response = $this->model->webapi007_load();
                    break;
                case '7ba2c01cc5fe13fe7a31c6d1859e31c4': // md5('webapi008_load') // load info kesanggupan mt lingkungan
                    $response = $this->model->webapi008_load();
                    break;
                case '21b525bd987baa625ddf29b979aeb196': // md5('webapi009_load') // load laporan keuangan mt lingkungan
                    $response = $this->model->webapi009_load();
                    break;
                case 'ddbdebdc3c1fe7a2c7e7612b2f890dec': // md5('webapi010_load') // load laporan produksi mt lingkungan
                    $response = $this->model->webapi010_load();
                    break;
                case 'c6292a262b9396a50ec6ff2dc43fd6c8': // md5('webapi011_load') // load pekerjaan pengujian mt kalibrasi
                    $response = $this->model->webapi011_load();
                    break;
                case '78b928d3ececaf1694d0bad0dbfbc0f3': // md5('webapi012_load') // load pemeriksaan hasil dokumentasi mt kalibrasi
                    $response = $this->model->webapi012_load();
                    break;
                case '738d2fc52897ece909a78f72926c1902': // md5('webapi013_load') // load info kesanggupan mt kalibrasi
                    $response = $this->model->webapi013_load();
                    break;
                case '40dafea2a822bedfa115e5dc5e1fb9c7': // md5('webapi014_load') // load laporan keuangan mt kalibrasi
                    $response = $this->model->webapi014_load();
                    break;
                case 'e5c986e7a094d0b3c7f84a2646f91eae': // md5('webapi015_load') // load laporan produksi mt kalibrasi
                    $response = $this->model->webapi015_load();
                    break;

                // korlab fisika
                case 'c8f3f110f4c8619619511c0cc32a9b71': // md5('webapi016_load') // load pekerjaan pengujian 
                    $response = $this->model->webapi016_load();
                    break;
                case '579be05eb7e0499f34f11835a9cefaca': // md5('webapi017_load') // load pemeriksaan hasil pengujian 
                    $response = $this->model->webapi017_load();
                    break;
                case 'e8655583131259a85f4f1e0d1a80b77d': // md5('webapi018_load') // load riwayat analis 
                    $response = $this->model->webapi018_load();
                    break;
                // end of korlab fisika

                // korlab kimia
                case '70c9db6b217048b91793f0ebe4aff367': // md5('webapi019_load') // load pekerjaan pengujian 
                    $response = $this->model->webapi019_load();
                    break;
                case '1df70b4dcbbf227492a8465c56337fe0': // md5('webapi020_load') // load pemeriksaan hasil pengujian 
                    $response = $this->model->webapi020_load();
                    break;
                case 'f6d355b2cdfbc5d1b16f90c581c6cdb4': // md5('webapi021_load') // load riwayat analis 
                    $response = $this->model->webapi021_load();
                    break;
                // end of korlab kimia

                // korlab kalibrasi
                case 'b9f9db4d4880e6419279fb72fa583678': // md5('webapi022_load') // load pekerjaan pengujian 
                    $response = $this->model->webapi022_load();
                    break;
                case 'c7e79f5e5043c47d49fffc8ff6f3c099': // md5('webapi023_load') // load pemeriksaan hasil pengujian 
                    $response = $this->model->webapi023_load();
                    break;
                case '2bb6dc53af6e4dd0d6c8ea95966dffec': // md5('webapi024_load') // load riwayat analis 
                    $response = $this->model->webapi024_load();
                    break;
                // end of korlab kalibrasi

                // korlab lingkungan
                case '46ca9cfd6863bd6bde1714f5b60a8fde': // md5('webapi025_load') // load pekerjaan pengujian 
                    $response = $this->model->webapi025_load();
                    break;
                case '077ed13d3924f2c765353df38f1d3736': // md5('webapi026_load') // load pemeriksaan hasil pengujian 
                    $response = $this->model->webapi026_load();
                    break;
                case '76df26f7182c13169ce82d1afc25f8ea': // md5('webapi027_load') // load riwayat analis 
                    $response = $this->model->webapi027_load();
                    break;
                // end of korlab lingkungan

                // analis
                case '63232e75fa4702f31c846908ca50e891': // md5('webapi028_load') // fisika > load pekerjaan
                    $response = $this->model->webapi028_load($token['id']);
                    break;
                case '699e5630fb59b63ce2ccab730e4b4fbd': // md5('webapi029_load') // kimia > load pekerjaan
                    $response = $this->model->webapi029_load($token['id']);
                    break;
                case 'ec6cf320eea2b18f0a22051879cb98c2': // md5('webapi030_load') // kalibrasi > load pekerjaan
                    $response = $this->model->webapi030_load($token['id']);
                    break;
                case '88f0536f00f35dea867adb261fec64bd': // md5('webapi031_load') // lingkungan > load pekerjaan
                    $response = $this->model->webapi031_load($token['id']);
                    break;
                // end of analis

                default:
                    $response['msg'] = 'Invalid request.';
                    break;
            }
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

}