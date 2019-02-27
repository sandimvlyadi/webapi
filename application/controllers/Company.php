<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('company_model', 'model');
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
    	$key = $this->get_param('key');
    	$response = $this->model->index($key);

    	echo "<pre>";
    	echo json_encode($response, JSON_PRETTY_PRINT);
    }

}