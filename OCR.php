<?php
//TODO: make current options default/overrideable
class OCR{
	
	private static $known_ocr_programs = array('abbyyocr11', 'tesseract', 'cuneiform', 'gocr', 'ocrad');
	private static $installed_ocr_programs;
	
	private static function init(){
		if(self::$installed_ocr_programs === null){
			self::$installed_ocr_programs = array();
			foreach(self::$known_ocr_programs as $program){
				if($program == 'ocrad'){
					exec('which convert', $output); //ocrad needs convert to run, part of imagemagick
					if(empty($output)){
						continue;
					}
					unset($output);
				}
				exec("which {$program}", $output);
				if(!empty($output)){
					self::$installed_ocr_programs[] = $program;
				}
				unset($output);
			}
		}
	}
	
	public static function run($base64_png_data, $program = null){
		self::init();
		$filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('OCR_') . '.png';
		file_put_contents($filename, base64_decode($base64_png_data));
		$programs = array();
		if($program !== null){
			if(in_array($program, self::$installed_ocr_programs)){
				$programs[] = $program;
			}
			else{
				throw new Exception("OCR program {$program} is not available.");	
			}
		}
		else{
			$programs = self::$installed_ocr_programs;
		}
		$class = get_called_class();
		$results = array();

		foreach($programs as $program){
			$method = "run_{$program}";
			if(method_exists($class, $method)){
				$results[$program] = '';
				foreach(forward_static_call(array($class, $method), $filename) as $line){
					$line = trim($line);
					if($line){
						$results[$program].="{$line}\n";
					}
				}
			}
			else{
				throw new Exception("Unknown OCR method $method");
			}
		}
		@unlink($filename);
		return $results;
	}
	
	public static function getInstalledPrograms(){
		self::init();
		return self::$installed_ocr_programs;
	}

	private static function run_abbyyocr11($filename){ //my god, the number of possible flags...and no man page...
		exec("abbyyocr11 -if {$filename} -f TextVersion10Defaults -lpp TextExtraction_Accuracy -ibw --enableTextExtractionMode -tet UTF8 -c", $output);
		return $output;
	}
	
	private static function run_ocrad($filename){
		$temp_file = "{$filename}.ppm"; //ocrad does not accept regular images, convert it to ppm or similar first
		exec("convert {$filename} {$temp_file}");
		exec("ocrad $temp_file", $output);
		@unlink($temp_file);
		return $output;
	}
	
	private static function run_gocr($filename){
		exec("gocr {$filename}", $output);
		return $output;
	}
	
	private static function run_cuneiform($filename){
		$temp_file = "{$filename}.txt"; //cuneiform can't seem to send to stdout
		exec("cuneiform --fax -l eng {$filename} -o {$temp_file}");
		$output = file($temp_file);
		@unlink($temp_file);
		return $output;
	}
	
	private static function run_tesseract($filename){
		exec("tesseract -psm 4 -l eng {$filename} stdout", $output); //see tesseract man page for -psm options
		return $output;
	}
}
