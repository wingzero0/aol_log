<?php
// this class want to define some common function for convenient to access aol_log

include_once("kit_lib.php");

class aol_utility {
	//public function __construct($argc, $argv){
		//$para = ParameterParser($argc, $argv);
	//}
	public $output_fp; 
	public function __construct($para){
		if (isset($para["o"])){
			$this->output_fp = fopen($para["o"], "w");
			if ($this->output_fp == NULL){
				fprintf(STDERR, "%s can't be opened\n", $para["o"]);
				$this->output_fp = STDOUT;
			}
		}else{
			$this->output_fp = STDOUT;
		}
	}
	public function __destruct(){
		if ($this->output_fp != STDOUT){
			fclose($this->output_fp);
		}
	}
	public function convert_safe_str($str){
		$s_str = preg_replace("/'/", "\\\'", $str);
		return $s_str;
	}
	public function cut_last_newline($str){
		$r_str = preg_replace("/\n$/", "", $str);
		return $r_str;
	}
	
}

?>