<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');

class Verification extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('verification_model', 'model');
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
		$request = $this->get_param('code');
    	$response = array();

    	if ($request != '') {
    		$result = $this->model->code($request);
    		if ($result) {
    			$this->load->view('registered');
    		} else{
    			redirect('http://bbt.kemenperin.go.id/', 'refresh');
    		}
    	} else{
    		redirect('http://bbt.kemenperin.go.id/', 'refresh');
    	}
    }

}