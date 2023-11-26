<?php

namespace App\Tools;

use App\Tools\PHPExcel\IOFactory;

/**
 * Excel 文档生成器
 * @package iPHP
 * @author 高祺
 *
 */
class ExcelWriter {
	/**
	 * 列
	 * @var unknown
	 */
	public static $columns = [];
	
	/**
	 * excel静态对象
	 * @var phpExcel
	 */
	public static $objPHPExcel = null;
	
	/**
	 * 初始化Excel文档属性
	 * @param unknown $title
	 * @param string $subject
	 * @param string $creator
	 * @param string $description
	 * @param string $keywords
	 * @param string $category
	 */
	public static function initialExcel($title,$subject='',$creator='',$description='',$keywords='',$category='') {
		self::$objPHPExcel = new PHPExcel();
		
		// Set document properties
		//echo date('H:i:s') , " Set document properties" , EOL;
		self::$objPHPExcel->getProperties()->setCreator($creator)
		->setLastModifiedBy($creator)
		->setTitle($title)
		->setSubject($subject)
		->setDescription($description)
		->setKeywords($keywords)
		->setCategory($category);
		
		self::$objPHPExcel->setActiveSheetIndex(0);
	}

	public static function loadTemplate($template='')
	{
		//$template = dirname(__FILE__).'/template.xls';          //使用模板  
    	self::$objPHPExcel = IOFactory::load($template);     //加载excel文件,设置模板  
	}
	/**
	 * 设定列
	 * @param Array $columns 列数组
	 */
	public static function setupColumns($columns) 
	{
		
		$sheet = self::$objPHPExcel->getActiveSheet();
		
		foreach($columns as $idx=>$col) {
			$c = chr(65+$idx);
			$sheet->setCellValue($c.'1', $col);
			
			$sheet->getColumnDimension($c)->setAutoSize(true);
			
		}
		
	}
	/**
	 * 打印数据至phpExcel对象
	 * @param Array $data 数组, key和columns数组对应
	 */
	public static function printData($data, $offset=2)
	{

		$sheet = self::$objPHPExcel->getActiveSheet();
		
		foreach($data as $row)
		{
			$idx = 0;
			foreach($row as $key=>$rs) {
				$c = chr(65+$idx);
				$sheet->setCellValue($c . $offset, $rs);
				$idx ++;
			}
			$offset++;
			
		}
		
	}
	/**
	 * 输出到流或文件
	 * @param string $filename
	 * @param string $mode file|stream
	 */
	public static function ourputFile($filename, $mode='stream') 
	{
		self::$objPHPExcel->getActiveSheet()->setAutoFilter(self::$objPHPExcel->getActiveSheet()->calculateWorksheetDimension());
		
		self::$objPHPExcel->setActiveSheetIndex(0);
		
		$objWriter = IOFactory::createWriter(self::$objPHPExcel, 'Excel2007');
		
		if($mode == 'stream') {
		// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.$filename.'"');
			header('Cache-Control: max-age=1920');
			
			$objWriter->save('php://output');
			exit;
		}
		if($mode == 'file') {
			$objWriter->save($filename);
		}
	}
	
}