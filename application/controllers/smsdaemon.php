<?php
class Smsdaemon extends CI_Controller {

    public function __construct(){
		parent::__construct();
		$this->load->model('sms/inbox_model');
		$this->load->model('sms/opini_model');
		$this->load->model('sms/autoreply_model');
		$this->load->model('sms/bc_model');
		$this->load->model('sms/pbk_model');
		$this->load->model('sms/setting_model');
	}
	
	function index($args = ""){
		if($this->input->is_cli_request()) {
			$this->load->database("3172100", FALSE, TRUE);
			ini_set('max_execution_time', 0);
			ini_set('max_input_time', -1);
			ini_set('html_errors', 'Off');
			ini_set('register_argc_argv', 'On');
			ini_set('output_buffering', 'Off');
			ini_set('implicit_flush', 'On');
			
			$loop=true;
			$x=1;
			while($loop){
				echo("\n".date("d-m-Y h:i:s") ." ".$x." ".$args." versi 1.0");
				
				$this->sms_reply($args);

				$this->sms_autoreply($args);

				$this->sms_opini($args);

				$this->sms_broadcast($args);

				$x++;
				sleep(5);
			}	
		}else{
			die("access via cli");
		}

	}
	
	function sms_send($nomor = "", $pesan=""){
		$data = array();
		$data['DestinationNumber'] = $nomor;
		$data['TextDecoded'] = $pesan;

		$this->db->insert('outbox',$data);
	}
	
	function sms_wrong($nomor = "", $pesan="" , $menu=""){
		$data = array();

		$pesan .= "\ngunakan kata kunci: ";
		if($menu==""){
			$info = $this->db->get("sms_info_menu")->result();
			$key = array();
			foreach ($info as $rows) {
				$key[]= $rows->code;
				$tmpt   = $rows->code;
			}
			$pesan .= implode(",", $key)."\ncontoh:".$menu."<spasi>".$tmpt;
			$pesan .= "\natau kirim masukan dengan format:\nopini<spasi>kalimat pesan";

		}else{
			$this->db->where("code_sms_menu", $menu);
			$this->db->where("tgl_mulai <= ", date("Y-m-d"));
			$this->db->where("tgl_akhir >= ", date("Y-m-d"));
			$info = $this->db->get("sms_info")->result();
			$tmpt = "";
			$key = array();
			foreach ($info as $rows) {
				$key[]= $rows->katakunci;
				$tmpt   = $rows->katakunci;
			}
			$pesan .= implode(",", $key)."\ncontoh:".$menu."<spasi>".$tmpt;
		}

		$data['DestinationNumber'] = $nomor;
		$data['TextDecoded'] = $pesan;

		$this->db->insert('outbox',$data);
	}

	function sms_reply($args = ""){
		echo "\nsms.sms_reply ...\n";

		//$operator = "'*123#','*111#','V-Tri','+3'";
		$operator = "'*123#'";

		//jika sms blm di proses, bukan operator, kata pertama menu 
		$this->db->where("Processed","false");
		$this->db->where("REPLACE(SenderNumber,'+62','') NOT IN (".$operator.")");
		$this->db->where("SUBSTRING_INDEX(TextDecoded,' ',1) NOT IN (SELECT `code` FROM `sms_info_menu`)");
		$this->db->where("SUBSTRING_INDEX(TextDecoded,' ',1) !='opini'");
		$inbox = $this->db->get("inbox")->result();
		foreach ($inbox as $rows) {

			$this->sms_wrong($rows->SenderNumber,"format sms salah");

			$update = array();
			$update['Processed'] = 'true';
			$this->db->where('ID',$rows->ID);
			$this->db->update('inbox',$update);
		}
	}
	

	function sms_autoreply($args = ""){
		echo "sms.autoteply ...\n";

		//$operator = "'*123#','*111#','V-Tri','+3'";
		$operator = "'*123#'";

		//jika sms blm di proses, bukan operator, kata pertama menu 
		$this->db->where("Processed","false");
		$this->db->where("REPLACE(SenderNumber,'+62','') NOT IN (".$operator.")");
		$this->db->where("SUBSTRING_INDEX(TextDecoded,' ',1) IN (SELECT `code` FROM `sms_info_menu`)");
		$inbox = $this->db->get("inbox")->result();
		foreach ($inbox as $rows) {
			$text = explode(" ",$rows->TextDecoded);

			if(isset($text[1])) {
				$this->db->where("katakunci",$text[1]);
				$errmsg = "katakunci tidak tersedia";
			}else {
				$this->db->where("katakunci","##");
				$errmsg = "silahkan ";
			}

			$this->db->where("code_sms_menu",$text[0]);
			$sms = $this->db->get("sms_info")->row();
			if(!empty($sms->pesan)){
				$this->sms_send($rows->SenderNumber,$sms->pesan);
			}else{
				$this->sms_wrong($rows->SenderNumber,$errmsg,$text[0]);
			}

			$update = array();
			$update['Processed'] = 'true';
			$this->db->where('ID',$rows->ID);
			$this->db->update('inbox',$update);
		}
	}
	
	function sms_opini($args = ""){
		echo "sms.opini ...\n";

		//$operator = "'*123#','*111#','V-Tri','+3'";
		$operator = "'*123#'";

		//jika sms blm di proses, bukan operator, kata pertama opini, 
		$this->db->where("Processed","false");
		$this->db->where("REPLACE(SenderNumber,'+62','') NOT IN (".$operator.")");
		$this->db->where("SUBSTRING_INDEX(TextDecoded,' ',1) ='opini'");
		$inbox = $this->db->get("inbox")->result();
		foreach ($inbox as $rows) {
			$opini = array();
			$opini['id_sms_tipe'] = '11';
			$opini['pesan'] = substr($rows->TextDecoded,6);
			$opini['nomor'] = $rows->SenderNumber;
			if($this->db->insert("sms_opini",$opini)){
				$this->db->where('ID',$rows->ID);
				$this->db->delete('inbox');
			}
		}
	}
	
	function sms_broadcast($args = ""){
		echo "sms.broadcast ...\n";

	}
}
