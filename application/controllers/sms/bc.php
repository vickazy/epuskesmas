<?php
class Bc extends CI_Controller {

    public function __construct(){
		parent::__construct();
		$this->load->helper('html');
	}
	
	function index(){
		$this->authentication->verify('sms','show');
		$data['title_group'] = "Jadwal SMS";
		$data['title_form'] = "Daftar Pesan";

		$data['content'] = $this->parser->parse("sms/bc/show",$data,true);
		$this->template->show($data,'home');
	}
}