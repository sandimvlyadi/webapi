<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

class Source extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
        $this->load->model('source_model', 'model');
    }

    private function get_param($param = '')
    {
    	if (isset($_GET[$param])) {
    		return $_GET[$param];
    	} else{
    		return '';
    	}
    }

    private function token_validation($param = array())
    {
        $result = $this->model->token_validation($param);
        return $result;
    }

    public function index()
    {
    	$param = $_GET;
    	$param['auth'] 	= $this->input->get_request_header('Authorization', TRUE);
    	$param['ip'] 	= $this->input->ip_address();
    	$param['agent'] = $this->agent;

    	$response = array(
    		'draw'				=> 1,
    		'recordsTotal'		=> 0,
    		'recordsFiltered'	=> 0,
    		'data'				=> array(),
    		'result'			=> false,
    		'msg'				=> ''
    	);

    	$isValid = $this->token_validation($param);
    	if ($isValid) {
    		switch ($param['request']) {
    			// mt pekerjaan pengujian
    			case '8ea6118a9632434fe21097b92752c882': // md5('webapi_source_001') // tekstil
    				$response = $this->model->webapi_source_001($param);
    				break;
    			case '674e1717496617e5a1c88c697ce912fd': // md5('webapi_source_002') // kalibrasi
    				$response = $this->model->webapi_source_002($param);
    				break;
    			case '2dba59fc0eb83e48a2da298153a99939': // md5('webapi_source_003') // lingkungan
    				$response = $this->model->webapi_source_003($param);
    				break;
    			// end of mt pekerjaan pengujian

                // dokumentasi
                // tekstil
                case '6e8731c411ecb435a0cfcb673ab18a0f': // md5('webapi_source_004') // data master
                    $response = $this->model->webapi_source_004($param);
                    break;
                case '256606b0abd2ef77a7f5968d715bbcdf': // md5('webapi_source_007') // data parameter uji
                    $response = $this->model->webapi_source_007($param);
                    break;
                case '0fe47b9149a5fab7a491e86d694bd516': // md5('webapi_source_008') // data lampiran uji
                    $response = $this->model->webapi_source_008($param);
                    break;
                case 'fcce92ea257ab45d47cc207fae90c123': // md5('webapi_source_009') // data arsip sertifikat
                    $response = $this->model->webapi_source_009($param);
                    break;
                case '84d07290680d5f011891776f8c8118d9': // md5('webapi_source_010') // data detail arsip sertifikat
                    $response = $this->model->webapi_source_010($param);
                    break;

                // kalibrasi
                case '589700c2c3066d41a65fc6134ce44fd3': // md5('webapi_source_005') // data master
                    $response = $this->model->webapi_source_005($param);
                    break;

                // lingkungan
                case 'fee2d7ebd36a882db952426f9912c535': // md5('webapi_source_006') // data master
                    $response = $this->model->webapi_source_006($param);
                    break;
                // end of dokumentasi

    			default:
    				$response['msg'] = 'Your request is not valid.';
    				break;
    		}
    	} else{
    		$response['msg'] = 'Your session is not valid.';
    	}

    	echo json_encode($response, JSON_PRETTY_PRINT);
    }

}