<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {
	public function construct(){
		parent::__construct();
	}
	public function index(){
		$this->load->view('main');
		$this->load->view('components/sidebar');
		$this->load->view('components/toolbar');
		$this->load->view('components/content');
		$this->load->view('components/footer');
	}
}
