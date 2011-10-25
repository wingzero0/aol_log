<?php
// This program will re-rank the aol original itemrank list by 
// sense traffic at a specific hour
// The program will read an itemrank file, a sense traffic file and 
// similarity file

// The following is the format of the itemrank file.
//
// query \t url \t score \n
// the higher score, the higher rank.
// 
// the format of sense traffic
// sense_name \t prob \n
// 
// the format of sense similarity
// urls \t sense \t similarity_score \n
 
require_once("aol_rerank.php");

class aol_rerank_dump extends aol_rerank {
	protected $dump_fp = null;
	public function __construct($para){
		if ( isset($para["dump"]) ){
			for ($a = 0.0;$a <= 1.0;$a+=0.1){
				$index = sprintf("%.1lf", $a); // if use double as the array index, it will get an error 
				$this->dump_fp[$index] = fopen($para["dump"].".".$index.".txt", "w");
				if ($this->dump_fp[$index] == NULL){
					fprintf(STDERR,$para["dump"].".".$index." can't be open\n");
					exit(-1);
				}
			}
		}
		parent::__construct($para);
	}
	public function dump_score($a, $q){
		if ($this->dump_fp == NULL){
			return false;
		}
		//for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($this->merge_score[$index] as $q => $ranking){
				//$counter=0;
				foreach ($ranking as $u => $score){
					fprintf($this->dump_fp[$index], "%s\t%s\t%lf\t", $q, $u, $score);
					$r_s = $this->sense_rank[$q][$u];
					$r = $this->itemrank[$q][$u];
					fprintf($this->dump_fp[$index], "= %lf / (itemrank)%d + %lf / (sense rank)%d\t",
						1-$a, $r, $a, $r_s);
					
					fprintf($this->dump_fp[$index], "sense_score = %lf = sum[", 
						$this->sense_score[$q][$u]);

					//arsort($this->sim[$u]);
					foreach ($this->traffic as $sense=>$v){
						if ( isset($this->sim[$u][$sense]) ){
							fprintf($this->dump_fp[$index], "(%s)%lf * %lf + ", 
								$sense,$this->sim[$u][$sense], $v);
						}
					}
					fprintf($this->dump_fp[$index], "\n");
					
				}
			}
		//}
		return true;
	}
	
	public function RerankAllQuery(){
		$querys = array_keys($this->itemrank);
		foreach ($querys as $q){
			$this->query_rerank($q);
		}

		for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($this->merge_score[$index] as $q => $ranking){
				foreach ($ranking as $u => $score){
					fprintf($this->output_a[$index], "%s\t%s\t%lf\n", $q, $u, $score);
				}
			}
			$this->dump_score($a, $q);
		}
	} 	
	public static function AolRerank($argc, $argv){
		// sample command
		// php aol_rerank_dump.php -itemrank Itemrank.txt 
		// -sense_traffic data_txt/sense_traffic.txt -similarity data_txt/similarity.txt 
		// -o rerank3 -dump rerankdump
		$para = ParameterParser($argc, $argv);
		$obj = new aol_rerank_dump($para);
		$obj->RerankAllQuery();
	}
}

class aol_rerank_dump_SortWithSense extends aol_rerank_dump {
	public function dump_score($a,$q){
		echo "in aol_rerank_dump_SortWithSense\n";
		if ($this->dump_fp == NULL){
			return false;
		}
		//for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($this->merge_score[$index] as $q => $ranking){
				//$counter=0;
				foreach ($ranking as $u => $score){
					fprintf($this->dump_fp[$index], "%s\t%s\t%lf\t", $q, $u, $score);
					$r_s = $this->sense_rank[$q][$u];
					$r = $this->itemrank[$q][$u];
					fprintf($this->dump_fp[$index], "= %lf / (itemrank)%d + %lf / (sense rank)%d\t",
						1-$a, $r, $a, $r_s);
					
					fprintf($this->dump_fp[$index], "sense_score = %lf = sum[", 
						$this->sense_score[$q][$u]);

					arsort($this->sim[$u]);
					foreach ($this->sim[$u] as $sense => $v){
						if ( isset($this->traffic[$sense]) ){  
							fprintf($this->dump_fp[$index], "(%s)%lf * %lf + ", 
								$sense,$v,$this->traffic[$sense]);
						}
					}
					fprintf($this->dump_fp[$index], "\n");
					
				}
			}
		//}
		return true;
	}
	public static function AolRerank($argc, $argv){
		// sample command
		// php aol_rerank_dump.php -itemrank Itemrank.txt 
		// -sense_traffic data_txt/sense_traffic.txt -similarity data_txt/similarity.txt 
		// -o rerank3 -dump rerankdump
		$para = ParameterParser($argc, $argv);
		$obj = new aol_rerank_dump_SortWithSense($para);
		echo "in aol_rerank_dump_SortWithSense\n";
		$obj->RerankAllQuery();
		/*
		$querys = array_keys($obj->itemrank);
		foreach ($querys as $q){
			$obj->query_rerank($q);
		}

		for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($obj->merge_score[$index] as $q => $ranking){
				foreach ($ranking as $u => $score){
					fprintf($obj->output_a[$index], "%s\t%s\t%lf\n", $q, $u, $score);
				}
			}
			$obj->dump_score($a, $q);
		}
		*/
	}
}

class aol_rerank_dump_SortWithSenseAndTraffic extends aol_rerank_dump {
	public function dump_score($a, $q){
		echo "in aol_rerank_dump_SortWithSenseAndTraffic\n";
		if ($this->dump_fp == NULL){
			return false;
		}
		//for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($this->merge_score[$index] as $q => $ranking){
				//$counter=0;
				foreach ($ranking as $u => $score){
					fprintf($this->dump_fp[$index], "%s\t%s\t%lf\t", $q, $u, $score);
					$r_s = $this->sense_rank[$q][$u];
					$r = $this->itemrank[$q][$u];
					fprintf($this->dump_fp[$index], "= %lf / (itemrank)%d + %lf / (sense rank)%d\t",
						1-$a, $r, $a, $r_s);
					
					fprintf($this->dump_fp[$index], "sense_score = %lf = sum[", 
						$this->sense_score[$q][$u]);
					
					$score = array();
					foreach ($this->sim[$u] as $sense => $v){
						if ( isset($this->traffic[$sense]) ){  
							$score[$sense] = $v * $this->traffic[$sense];
						}
					}
					arsort($score);
					foreach ($score as $sense => $s_v){
						if ( isset($this->traffic[$sense])  ){  
							fprintf($this->dump_fp[$index], "(%s)%lf * %lf + ", 
								$sense,$this->sim[$u][$sense],$this->traffic[$sense]);
						}
					}
					fprintf($this->dump_fp[$index], "\n");
					
				}
			}
		//}
		return true;
	}
	public static function AolRerank($argc, $argv){
		// sample command
		// php aol_rerank_dump.php -itemrank Itemrank.txt 
		// -sense_traffic data_txt/sense_traffic.txt -similarity data_txt/similarity.txt 
		// -o rerank3 -dump rerankdump
		$para = ParameterParser($argc, $argv);
		$obj = new aol_rerank_dump_SortWithSenseAndTraffic($para);
		echo "in aol_rerank_dump_SortWithSenseAndTraffic\n";
		$obj->RerankAllQuery();
		/*
		$querys = array_keys($obj->itemrank);
		foreach ($querys as $q){
			$obj->query_rerank($q);
		}

		for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($obj->merge_score[$index] as $q => $ranking){
				foreach ($ranking as $u => $score){
					fprintf($obj->output_a[$index], "%s\t%s\t%lf\n", $q, $u, $score);
				}
			}
			$obj->dump_score($a, $q);
		}
		*/
	}
}

class aol_rerank_dump_TopSense extends aol_rerank_with_top_sense{
	public function __construct($para){
		if ( isset($para["dump"]) ){
			for ($a = 0.0;$a <= 1.0;$a+=0.1){
				$index = sprintf("%.1lf", $a); // if use double as the array index, it will get an error 
				$this->dump_fp[$index] = fopen($para["dump"].".".$index.".txt", "w");
				if ($this->dump_fp[$index] == NULL){
					fprintf(STDERR,$para["dump"].".".$index." can't be open\n");
					exit(-1);
				}
			}
		}
		parent::__construct($para);
	}
	public function dump_score($a,$q){
		echo "in aol_rerank_dump_TopSense\n";
		if ($this->dump_fp == NULL){
			return false;
		}
		$index = sprintf("%.1lf", $a);
		foreach ($this->merge_score[$index] as $q => $ranking){
			foreach ($ranking as $u => $score){
				fprintf($this->dump_fp[$index], "%s\t%s\t%lf\t", $q, $u, $score);
				$r_s = $this->sense_rank[$q][$u];
				$r = $this->itemrank[$q][$u];
				fprintf($this->dump_fp[$index], "= %lf / (itemrank)%d + %lf / (sense rank)%d\t",
					1-$a, $r, $a, $r_s);
				
				fprintf($this->dump_fp[$index], "sense_score = %lf = sum[", 
					$this->sense_score[$q][$u]);

				arsort($this->sim[$u]);
				$counter = 0;
				foreach ($this->sim[$u] as $sense => $v){
					if ( isset($this->traffic[$sense]) ){  
						fprintf($this->dump_fp[$index], "(%s)%lf * %lf + ", 
							$sense,$v,$this->traffic[$sense]);
						$counter++;
						//echo "$counter\t".$this->NSense."\n";
					}
					if ($counter >= $this->NSense){
						//echo "break\n";
						break;
					}
				}
				fprintf($this->dump_fp[$index], "\n");
			}
		}
		return true;
	}
	public function RerankAllQuery(){
		$querys = array_keys($this->itemrank);
		foreach ($querys as $q){
			$this->query_rerank($q);
		}

		for ($a = 0.0; $a<= 1.0;$a+=0.1 ){
			$index = sprintf("%.1lf", $a);
			foreach ($this->merge_score[$index] as $q => $ranking){
				foreach ($ranking as $u => $score){
					fprintf($this->output_a[$index], "%s\t%s\t%lf\n", $q, $u, $score);
				}
			}
			$this->dump_score($a, $q);
		}
	} 
	public static function AolRerank($argc, $argv){
		// sample command
		// php aol_rerank_dump.php -itemrank Itemrank.txt 
		// -sense_traffic data_txt/sense_traffic.txt -similarity data_txt/similarity.txt 
		// -o rerank4 -dump rerankdump -NSense 3
		$para = ParameterParser($argc, $argv);
		$obj = new aol_rerank_dump_TopSense($para);
		$obj->RerankAllQuery();
	}
}
aol_rerank_dump_TopSense::AolRerank($argc,$argv);
//aol_rerank_dump_SortWithSenseAndTraffic::AolRerank($argc,$argv);
//aol_rerank_dump_SortWithSense::AolRerank($argc,$argv);
?>
