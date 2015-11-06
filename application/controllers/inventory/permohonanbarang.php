<?php
class Permohonanbarang extends CI_Controller {

    public function __construct(){
		parent::__construct();
		$this->load->model('inventory/permohonanbarang_model');
		$this->load->model('mst/puskesmas_model');
		$this->load->model('mst/inv_ruangan_model');
	}
	function json(){
		$this->authentication->verify('inventory','show');


		if($_POST) {
			$fil = $this->input->post('filterscount');
			$ord = $this->input->post('sortdatafield');

			for($i=0;$i<$fil;$i++) {
				$field = $this->input->post('filterdatafield'.$i);
				$value = $this->input->post('filtervalue'.$i);

				$this->db->like($field,$value);
			}

			if(!empty($ord)) {
				$this->db->order_by($ord, $this->input->post('sortorder'));
			}
		}

		$rows_all = $this->permohonanbarang_model->get_data();


		if($_POST) {
			$fil = $this->input->post('filterscount');
			$ord = $this->input->post('sortdatafield');

			for($i=0;$i<$fil;$i++) {
				$field = $this->input->post('filterdatafield'.$i);
				$value = $this->input->post('filtervalue'.$i);

				$this->db->like($field,$value);
			}

			if(!empty($ord)) {
				$this->db->order_by($ord, $this->input->post('sortorder'));
			}
		}

		$rows = $this->permohonanbarang_model->get_data($this->input->post('recordstartindex'), $this->input->post('pagesize'));
		$data = array();
		$no=1;
		foreach($rows as $act) {
			$data[] = array(
				'no'		=> $no++,
				'id_inv_permohonan_barang' => $act->id_inv_permohonan_barang,
				'tanggal'	=> $act->tanggal_permohonan,
				'jumlah'	=> $act->jumlah_unit,
				'ruangan'	=> $act->nama_ruangan,
				'keterangan'=> $act->keterangan,
				'status'	=> 1,
				'detail'	=> 1,
				'edit'		=> 1,
				'delete'	=> 1
			);
		}

		$size = sizeof($rows_all);
		$json = array(
			'TotalRows' => (int) $size,
			'Rows' => $data
		);

		echo json_encode(array($json));
	}

	function index(){
		$this->authentication->verify('inventory','edit');
		$data['title_group'] = "Parameter";
		$data['title_form'] = "Master Data - Daftar Permohonan Barang";
		$data['content'] = $this->parser->parse("inventory/permohonan_barang/show",$data,true);

		$this->template->show($data,"home");
	}


	function add(){
		$this->authentication->verify('inventory','add');

        $this->form_validation->set_rules('tgl', 'Tanggal Permohonan', 'trim|required');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'trim|required');
        $this->form_validation->set_rules('codepus', 'Puskesmas', 'trim|required');
        $this->form_validation->set_rules('ruangan', 'Ruangan', 'trim|required');
        

			if($this->form_validation->run()== FALSE){
				$data['title_group'] = "Parameter";
				$data['title_form']="Tambah Inventory Barang";
				$data['action']="add";
				$data['kode']="";
				$this->db->like('code','P'.$this->session->userdata(substr('puskesmas',6)));
				$data['kodepuskesmas'] = $this->puskesmas_model->get_data();
				$data['userdata'] = 'P'.$this->session->userdata('puskesmas');
			
				$data['content'] = $this->parser->parse("inventory/permohonan_barang/form",$data,true);
				$this->template->show($data,"home");
			}elseif($id = $this->permohonanbarang_model->insert_entry()){
				$this->session->set_flashdata('alert', 'Save data successful...');
				redirect(base_url().'inventory/permohonanbarang/edit/'. $id);
			}else{
				$this->session->set_flashdata('alert_form', 'Save data failed...');
				redirect(base_url()."inventory/permohonanbarang/add");
			}

	}

	function edit($kode=0)
	{
		$this->authentication->verify('inventory','add');

        $this->form_validation->set_rules('tgl', 'Tanggal Permohonan', 'trim|required');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'trim|required');
        $this->form_validation->set_rules('codepus', 'Puskesmas', 'trim|required');
        $this->form_validation->set_rules('ruangan', 'Ruangan', 'trim|required');

		if($this->form_validation->run()== FALSE){
			$data = $this->permohonanbarang_model->get_data_row($kode); 
			$cekpus = $this->puskesmas_model->get_data_row($kode);
			$cekruang = $this->inv_ruangan_model->get_data_row($kode);

			$data['title_group'] = "Parameter";
			$data['title_form']="Ubah inventory Barang";
			$data['action']="edit";
			$data['kode']=$kode;
			$this->db->like('code','P'.$this->session->userdata(substr('puskesmas',6)));
			$data['kodepuskesmas'] = $this->puskesmas_model->get_data();
			$data['userdata'] = 'P'.$this->session->userdata('puskesmas');
			$data['codepuskes']	= !empty($cekpus) ? $cekpus->code : $data['code_cl_phc'];
			$data['coderuangan']	= !empty($cekruang) ? $cekruang->id_mst_inv_ruangan : $data['code_cl_phc'];
			$data['document']	  = $this->load->view('inventory/permohonan_barang/document', $data, TRUE);
			$data['content'] = $this->parser->parse("inventory/permohonan_barang/edit",$data,true);
			$this->template->show($data,"home");
		}elseif($this->permohonanbarang_model->update_entry($kode)){
			$this->session->set_flashdata('alert_form', 'Save data successful...');
			redirect(base_url()."inventory/permohonanbarang/edit/".$this->input->post('kode'));
		}else{
			$this->session->set_flashdata('alert_form', 'Save data failed...');
			redirect(base_url()."inventory/permohonanbarang/edit/".$kode);
		}
	}

	function dodel($kode=0){
		$this->authentication->verify('inventory','del');

		if($this->invbarang_model->delete_entry($kode)){
			$this->session->set_flashdata('alert', 'Delete data ('.$kode.')');
			redirect(base_url()."inventory/permohonanbarang");
		}else{
			$this->session->set_flashdata('alert', 'Delete data error');
			redirect(base_url()."inventory/permohonanbarang");
		}
	}
	public function document($id = 0)
	{
		$data	  	= array();
		$filter 	= array();
		$filterLike = array();

		if($_POST) {
			$fil = $this->input->post('filterscount');
			$ord = $this->input->post('sortdatafield');

			for($i=0;$i<$fil;$i++) {
				$field = $this->input->post('filterdatafield'.$i);
				$value = $this->input->post('filtervalue'.$i);

				if($field == 'date_received' || $field == 'date_accepted') {
					$value = date("Y-m-d",strtotime($value));

					$this->db->where($field,$value);
				}elseif($field != 'year') {
					$this->db->like($field,$value);
				}
			}

			if(!empty($ord)) {
				$this->db->order_by($ord, $this->input->post('sortorder'));
			}
		}
		$activity = $this->permohonanbarang_model->getItem('inv_permohonan_barang_item', array('id_inv_permohonan_barang'=>$id))->result();

		foreach($activity as $act) {
			$data[] = array(
				'id_inv_permohonan_barang_item'   				=> $act->id_inv_permohonan_barang_item,
				'nama_barang'   		=> $act->nama_barang,
				'jumlah'		=> $act->jumlah,
				'keterangan'		=> $act->keterangan,
				'id_inv_permohonan_barang'		=> $act->id_inv_permohonan_barang,
				'code_mst_inv_barang'	=> $act->code_mst_inv_barang
			);
		}

		$json = array(
			'TotalRows' => sizeof($data),
			'Rows' => $data
		);

		echo json_encode(array($json));
	}
}
