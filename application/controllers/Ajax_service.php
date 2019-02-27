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
            'id'    => $this->get_param('id_user'),
            'token' => $this->get_param('token')
        );

        $isValid = $this->token_validation($token);
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
                default:
                    $response['msg'] = 'Invalid request.';
                    break;
            }
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

}