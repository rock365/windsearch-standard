<?php

// +----------------------------------------------------------------------
// | WindSearch-standard （标准版-免费）
// +----------------------------------------------------------------------
// | Copyright (c) All rights reserved.
// +----------------------------------------------------------------------
// | Licensed: Apache License 2.0
// +----------------------------------------------------------------------
// | Author: rock365 https://github.com/rock365
// +----------------------------------------------------------------------
// | PHP Version: PHP_VERSION >= 5.6
// +----------------------------------------------------------------------

class Wind
{

	private $indexDir = '';
	private $IndexName = '';

	private $segword = [];
	private $countId = [];

	private $height_freq_word = [];
	private $arr_symbol_filter = [];
	private $arr_stop_word_filter = [];


	private $keepMaxNum = 500000;
	private $AllaySliceNumSmall = 300000;
	private $AllaySliceNumBig = 500000;
	private $mapping = [];

	private $subLen = 135;
	private $default_len = 500;
	private $terms = [];
	private $fcHandle;

	
	public function __construct($IndexName = '')
	{

		// 检测PHP版本
		if (version_compare(PHP_VERSION, '5.6.4', '<')) {
			die('PHP版本必须 ≥ 5.6.4 !');
		}

		$this->IndexName = $IndexName;
		$this->indexDir = dirname(__FILE__) . '/windIndex/';

		if (!is_dir($this->indexDir)) {
			mkdir($this->indexDir, 0777);
		}

		if (is_file($this->indexDir . $this->IndexName . '/Mapping')) {
			$this->mapping = json_decode(file_get_contents($this->indexDir . $this->IndexName . '/Mapping'), true);
		}
	}


	public function getMapping()
	{
		return $this->mapping;
	}


	public function getCurrDir()
	{
		return dirname(__FILE__) . '/';
	}

	public function getStorageDir()
	{
		return $this->indexDir . $this->IndexName . '/';
	}



	public function checkIndex()
	{
		if (!is_file($this->indexDir . $this->IndexName . '/Mapping')) {
			return false;
		} else {
			return true;
		}
	}



	public function createIndex()
	{
		if (!is_dir($this->indexDir . $this->IndexName)) {
			mkdir($this->indexDir . $this->IndexName, 0777);
		}
		if (!is_dir($this->indexDir . $this->IndexName . '/index/')) {
			mkdir($this->indexDir . $this->IndexName . '/index/', 0777);
		}
	}



	public function createDir()
	{
		$indexSegDir = $this->indexDir . $this->IndexName . '/makeIndexSeg/';
		if (!is_dir($indexSegDir)) {
			mkdir($indexSegDir, 0777);
		}



		for ($i = 1; $i <= $this->mapping['properties']['param']['indexSegNum']; ++$i) {

			if (!is_dir($indexSegDir . $i . '/')) {

				mkdir($indexSegDir . $i . '/');
			}
		}
	}


	public function createMapping($mapping)
	{
		$params = [
			'index' => $this->IndexName,
			'properties' => $mapping,
		];

		if (is_file($this->indexDir . $this->IndexName . '/Mapping')) {
			return false;
		}
		file_put_contents($this->indexDir . $this->IndexName . '/Mapping', json_encode($params));

		$indexSegDir = $this->indexDir . $this->IndexName . '/makeIndexSeg/';
		if (!is_dir($indexSegDir)) {
			mkdir($indexSegDir, 0777);
		}



		if (is_file($this->indexDir . $this->IndexName . '/Mapping')) {
			$this->mapping = json_decode(file_get_contents($this->indexDir . $this->IndexName . '/Mapping'), true);
		}

		for ($i = 1; $i <= $this->mapping['properties']['param']['indexSegNum']; ++$i) {
			if (!is_dir($indexSegDir . $i . '/')) {
				mkdir($indexSegDir . $i . '/');
			}
		}
	}



	public function buildIndexInit()
	{
		$this->loadHeightFreqWord();
		$this->loadSymbolStopword();

		$this->loadAnalyzer();
	}




	public function loadHeightFreqWord()
	{
		$this->height_freq_word = json_decode(file_get_contents(dirname(__FILE__) . '/windIndexCore/height_freq_word/height_freq_word_json.txt'), true);
	}

	public function loadSymbolStopword()
	{
		$arr_symbol_filter = explode('/', file_get_contents(dirname(__FILE__) . '/windIndexCore/symbol/symbol.txt'));
		$this->arr_symbol_filter = $arr_symbol_filter;

		$arr_stop_word_filter = unserialize(file_get_contents(dirname(__FILE__) . '/windIndexCore/stopword/stop_word.txt'));
		$this->arr_stop_word_filter = $arr_stop_word_filter;
	}

	public function filterSymbolStopWord($fc_arr)
	{

		$fc_arr = array_diff($fc_arr, $this->arr_symbol_filter);
		$fc_arr = array_diff($fc_arr, $this->arr_stop_word_filter);
		return $fc_arr;
	}


	public function buildSegWordContainer()
	{
		$zm = array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y',
			'Z'
		);
		for ($i = 1; $i <= $this->mapping['properties']['param']['indexSegNum']; ++$i) {
			foreach ($zm as $v) {
				if (!isset($this->segword[$v . $i])) {
					$this->segword[$v . $i] = [];
				}
			}
		}
	}



	public static function del_dir($dir)
	{
		if (!is_dir($dir)) {
			return;
		}
		$dh = opendir($dir);

		while ($file = readdir($dh)) {

			if ($file != "." && $file != "..") {
				$fullpath = $dir . '/' . $file;

				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					self::del_dir($fullpath);
				}
			}
		}

		closedir($dh);
		rmdir($dir);
	}



	public static function empty_dir($dir)
	{
		if (substr($dir, -1) != '/') {
			$dir = $dir . '/';
		}
		if (!is_dir($dir)) {
			mkdir($dir);
			return;
		}

		$dh = scandir($dir);

		foreach ($dh as $file) {

			if ($file != "." && $file != "..") {
				$fullpath = $dir . $file;

				if (!is_dir($fullpath)) {

					unlink($fullpath);
				} else {
					self::del_chilren_dir($fullpath);
				}
			}
		}
	}

	public static function del_chilren_dir($dir)
	{
		$dh = scandir($dir);
		foreach ($dh as $file) {

			if ($file != "." && $file != "..") {
				$fullpath = $dir . '/' . $file;

				if (!is_dir($fullpath)) {

					unlink($fullpath);
				} else {
					self::del_chilren_dir($fullpath);
				}
			}
		}
		rmdir($dir);
	}


	public function loadAnalyzer()
	{

		require 'lib/Analyzer.php';
		$this->fcHandle = new Analyzer();

	}






	public function filterSymbol_StopWord($text, $fc_arr)
	{

		$arr_symbol_filter = explode('/', file_get_contents(dirname(__FILE__) . '/windIndexCore/symbol/symbol.txt'));

		$fc_arr = array_diff($fc_arr, $arr_symbol_filter);

		$arr_stop_word_filter = unserialize(file_get_contents(dirname(__FILE__) . '/windIndexCore/stopword/stop_word.txt'));

		$fc_arr = array_diff($fc_arr, $arr_stop_word_filter);

		return $fc_arr;
	}






	public function segment($str, $is_all = false)
	{

		$str = strtolower($str);

		if ($is_all) {
			$arrresult = $this->fcHandle->segmentAll($str);
		} else {
			$arrresult = $this->fcHandle->standard($str);
		}

		$arrresult = array_unique($arrresult);
		$arrresult = array_filter($arrresult);
		$arrresult = array_values($arrresult);

		return $arrresult;
	}


	public function getTerms()
	{
		return $this->terms;
	}



	public function get_initial($str)
	{
		$str = strval($str);


		$arr_fh = array(
			33 => 'A', 34 => 'B', 35 => 'C', 36 => 'D', 37 => 'E', 38 => 'F', 39 => 'G', 40 => 'H', 41 => 'I', 42 => 'J', 43 => 'K', 44 => 'L', 45 => 'M', 46 => 'N', 47 => 'O', 			48 => 'H', 49 => 'I', 50 => 'J', 51 => 'K', 52 => 'L', 53 => 'M', 54 => 'N', 55 => 'O', 56 => 'P', 57 => 'Q', 			58 => 'A', 59 => 'B', 60 => 'C', 61 => 'D', 62 => 'E', 63 => 'F', 64 => 'G',
			91 => 'P', 92 => 'Q', 93 => 'R', 94 => 'S', 95 => 'T', 96 => 'V', 			123 => 'W', 124 => 'X', 125 => 'Y', 126 => 'Z',

		);
		$fchar = ord($str[0]);

		if (($fchar >= 65 && $fchar <= 90) || ($fchar >= 97 && $fchar <= 122)) {

			return strtoupper($str[0]);
		} else if (($fchar >= 33 && $fchar < 64) || ($fchar >= 91 && $fchar < 96) || ($fchar >= 123 && $fchar <= 126)) {
			$zm = $arr_fh[$fchar];

			return strtoupper($zm);
		} else {


			$s1 = mb_convert_encoding($str, "GBK", "UTF-8");
			$s2 = mb_convert_encoding($s1, "UTF-8", "GBK");

			$s = ($s2 == $str) ? $s1 : $str;





			$asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
			if ($asc >= -20319 && $asc <= -20284) {
				return 'A';
			}
			if ($asc >= -20283 && $asc <= -19776) {
				return 'B';
			}
			if ($asc >= -19775 && $asc <= -19219) {
				return 'C';
			}
			if ($asc >= -19218 && $asc <= -18711) {
				return 'D';
			}
			if ($asc >= -18710 && $asc <= -18527) {
				return 'E';
			}
			if ($asc >= -18526 && $asc <= -18240) {
				return 'F';
			}
			if ($asc >= -18269 && $asc <= -17923) {
				return 'G';
			}
			if ($asc >= -17922 && $asc <= -17418) {
				return 'H';
			}
			if ($asc >= -17417 && $asc <= -16475) {
				return 'J';
			}
			if ($asc >= -16474 && $asc <= -16213) {
				return 'K';
			}
			if ($asc >= -16212 && $asc <= -15641) {
				return 'L';
			}
			if ($asc >= -15640 && $asc <= -15166) {
				return 'M';
			}
			if ($asc >= -15165 && $asc <= -14923) {
				return 'N';
			}
			if ($asc >= -14922 && $asc <= -14915) {
				return 'O';
			}
			if ($asc >= -14914 && $asc <= -14631) {
				return 'P';
			}
			if ($asc >= -14630 && $asc <= -14150) {
				return 'Q';
			}
			if ($asc >= -14149 && $asc <= -14091) {
				return 'R';
			}
			if ($asc >= -14090 && $asc <= -13319) {
				return 'S';
			}
			if ($asc >= -13318 && $asc <= -12839) {
				return 'T';
			}
			if ($asc >= -12838 && $asc <= -12557) {
				return 'W';
			}
			if ($asc >= -12556 && $asc <= -11848) {
				return 'X';
			}
			if ($asc >= -11847 && $asc <= -11056) {
				return 'Y';
			}
			if ($asc >= -11055 && $asc <= -10247) {
				return 'Z';
			} else {
				return 'U';
			}
		}
	}




	public function index($row)
	{

		$params = [
			'index' => $this->IndexName,
			'id' => $row['id'],
			'body' => $row,

		];

		$id = $row['id'];

		$this->countId[] = $id;



		if (empty($this->mapping)) {
			return;
		}


		$fc_string = '';
		if (is_array($this->mapping['properties']['columns'])) {
			foreach ($this->mapping['properties']['columns'] as $v) {
				$fc_string .= (isset($params['body'][$v]) ? $params['body'][$v] : '');
			}
		} else {
			$fc_string = (isset($params['body'][$this->mapping['properties']['columns']]) ?  $params['body'][$this->mapping['properties']['columns']] : '');
		}



		$separator = isset($this->mapping['properties']['separator']) ? $this->mapping['properties']['separator'] : '';
		if ($separator == '') {
			$fc_arr = $this->segment($fc_string, true);
		} else {
			$fc_arr = explode($separator, str_replace(' ', '', strtolower($fc_string)));
		}



		$fc_arr = $this->filterSymbolStopWord($fc_arr);



		$strlen = strlen($fc_string);

		foreach ($fc_arr as $c => $v) {

			if (isset($this->height_freq_word[$v])) {
				$pos = stripos($fc_string, chr($v));
				if ($pos > ($strlen / 3)) {
					continue;
				}
			}
			$fir = $this->get_initial($v);


			$id_p = ceil($id / $this->mapping['properties']['param']['indexSegDataNum']);
			$this->segword[$fir . $id_p][] = $v . '—|' . $id . PHP_EOL;
		}


		return;
	}






	public function batchWrite()
	{


		if (empty($this->mapping)) {
			return;
		}


		$zm = array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y',
			'Z'
		);

		$indexSegDir = $this->indexDir . $this->IndexName . '/makeIndexSeg/';
		if (!is_dir($indexSegDir)) {
			mkdir($indexSegDir, 0777);
		}

		for ($i = 1; $i <= $this->mapping['properties']['param']['indexSegNum']; ++$i) {
			foreach ($zm as $v) {
				${$v . $i} = fopen($indexSegDir . $i . '/' . $v . '.index', "a");
				if (isset($this->segword[$v . $i])) {
					fwrite(${$v . $i}, implode('', $this->segword[$v . $i]));
				}
			}
		}




		$countIdFile = $this->indexDir . $this->IndexName . '/countIndex';
		$oldIdCount = (int)file_get_contents($countIdFile);
		$newIdCount = count($this->countId);
		$totalId = $oldIdCount + $newIdCount;
		file_put_contents($countIdFile, $totalId);
	}









	public function buildPostingListWholeIndex()
	{

		if (is_file($this->indexDir . $this->IndexName . '/Mapping')) {
			$mapping = json_decode(file_get_contents($this->indexDir . $this->IndexName . '/Mapping'), true);
		}


		file_put_contents($this->indexDir . $this->IndexName . '/dp.index', '');
		$arr_pice = range(1, $mapping['properties']['param']['indexSegNum']);

		$indexSegDir = $this->indexDir . $this->IndexName . '/makeIndexSeg/';


		$zm = array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y',
			'Z'
		);



		$Allterm = [];
		foreach ($zm as $z) {
			$PostingListArr = [];
			for ($i_ = 0; $i_ < count($arr_pice); ++$i_) {

				$i = $arr_pice[$i_];

				$index_pice_dir = $indexSegDir . $i . '/';

				if (is_dir($index_pice_dir)) {

					$file = $index_pice_dir . $z . '.index';
					if (is_file($file)) {

						$re = explode(PHP_EOL, file_get_contents($file));

						$tempArr = [];

						foreach ($re as $f => $d) {
							$arr_d = explode('—|', $d);
							$arr_0 = $arr_d[0];
							$arr_1 = $arr_d[1];

							if (!isset($tempArr[$arr_0]) && $arr_1 != '') {
								$tempArr[$arr_0] = $arr_1;
							} else {
								$tempArr[$arr_0] .= ',' . $arr_1;
							}
						}

						unset($re);


						foreach ($tempArr as $query => $v) {

							$v = trim($v, ',');


							if (!isset($PostingListArr[$query])) {
								$PostingListArr[$query] = $v;
							} else {
								$PostingListArr[$query] = $PostingListArr[$query] . ',' . $v;
							}

							unset($tempArr[$query]);
						}


						$tempArr = [];
					}
				}
			}

			foreach ($PostingListArr as $k => $v) {
				$v_arr = explode(',', $v);
				$arr_slice = array_slice($v_arr, 0, $this->keepMaxNum);
				$id_str = base64_encode(gzdeflate(implode(',', $arr_slice)));

				file_put_contents($this->indexDir . $this->IndexName . '/dp.index', $k . '|' . $id_str . PHP_EOL, FILE_APPEND);
				$Allterm[] = $k;
			}
		}



		$lines = (int)file_get_contents($this->indexDir . $this->IndexName . '/countIndex');
		$info = array(
			'docNum' => $lines,
			'create_time' => time(),
		);
		file_put_contents($this->indexDir . $this->IndexName . '/index_info', json_encode($info));

		$termList = implode(PHP_EOL, $Allterm);
		file_put_contents($this->indexDir . $this->IndexName . '/allTerm', $termList);
	}








	public function buildIndex()
	{


		$this->buildPostingListWholeIndex();

		$this->loadTxtDb();
		$this->emptyTxtDb();
		$storageDir = $this->getStorageDir();
		$specArr = ['\\', PHP_EOL];

		if (!is_file($storageDir . 'dp.index')) {
			return;
		}

		$file = fopen($storageDir .  'dp.index', "r");

		while (!feof($file)) {
			$line = fgets($file);
			if ($line != '') {
				$v_arr = explode('|', $line);
				$q = $v_arr[0];
				$d = trim($v_arr[1]);
				if ($q) {
					if (in_array($q, $specArr)) {
						continue;
					}

					$this->txtDbHandle->add($q, $d);
				}
			}
		}
		fclose($file);
	}



	public function delIndex()
	{

		$indexName = $this->indexDir . $this->IndexName;

		if (!is_dir($indexName)) {
			return;
		}

		$this->empty_dir($indexName);

		rmdir($indexName);
	}




	public function postingListWholeSearch($fc_arr, $page, $listRows)
	{


		$default_len = $this->default_len;
		$container = [];
		$res_total = 0;


		$allArr = [];

		$dp_index_dir = $this->indexDir . $this->IndexName . '/index/txt_index';
		$this->loadTxtDb();

		$db = (object)$this->txtDbHandle;
		$db->open('', $dp_index_dir, false);


		foreach ($fc_arr as $s => $k) {

			$ids = $db->get($k);
			$ids_gzinf = gzinflate(base64_decode($ids));
			$exp = explode(',', $ids_gzinf);
			$allArr[$k] = $exp;
		}
		$db->close();


		$id_arr = [];
		foreach ($allArr as $k => $exp) {
			if (count($fc_arr) == 1) {
				$splice = array_slice($exp, 0, $default_len);
			} else if (count($fc_arr) < 5) {
				$splice = array_slice($exp, 0, $this->AllaySliceNumBig);
			} else {
				$splice = array_slice($exp, 0, $this->AllaySliceNumSmall);
			}


			$container[$k] = $splice;
			$id_str = implode(',', $splice);

			if ($id_str != '') {
				$id_arr[] = $id_str;
			}
		}
		unset($exp);
		
		if (!empty($id_arr)) {

			$ids = explode(',', implode(',', $id_arr));
			$ids = array_filter($ids);
			$ids = implode('__,', $ids) . '_';
			$ids = explode('_,', $ids);

			$id_arr_count = array_count_values($ids);
			$res_total = count($id_arr_count);

			if (count($fc_arr) > 1) {
				arsort($id_arr_count);
			}

			$id_slice =	array_splice($id_arr_count, 0, 2000, true);

			unset($id_arr_count);
		}


		$id_arr_score = $id_slice;
		$id_arr =  str_replace('_', '', array_keys($id_slice));

		if (!isset($id_arr[0])) {
			$id_arr = array_values($id_arr);
		}



		$id_score = [];
		$curr_page_id_arr = [];
		$qs = ($page - 1) * $listRows;
		$js = ($page - 1) * $listRows + $listRows;

		for ($i = $qs; $i < $js; ++$i) {

			if (!isset($id_arr[$i])) {
				continue;
			}

			array_push($curr_page_id_arr, $id_arr[$i]);
			$id_score[$id_arr[$i]] = $id_arr_score[$id_arr[$i] . '_'];
		}


		if (count($curr_page_id_arr) > 1) {
			$id_str = implode(',', $curr_page_id_arr);
		} else {
			$id_str = isset($curr_page_id_arr[0]) ? $curr_page_id_arr[0] : '';
		}


		$intersection = array(
			'id_str' => $id_str, 
			'id_score' => $id_score,
			'curr_listRows_real' => count($curr_page_id_arr),
		);

		$info = array(
			'total' => isset($res_total) ? $res_total : 0,
		);

		$result_info = array(
			'intersection' => $intersection,
			'info' => $info,
		);


		return $result_info;
	}




	public function getCurrIndexStructure()
	{

		return 'postlist';
	}




	private $txtDbHandle;


	public function loadTxtDb()
	{
		require_once 'lib/TxtDb.php';
		$this->txtDbHandle = new \TxtDb();
	}




	public function emptyTxtDb()
	{
		$storageDir  = $this->getStorageDir();
		$currDir = $this->getCurrDir();

		$from = $currDir . 'windIndexCore/txt_db_basefile/';
		$to = $storageDir . '/index/txt_index';
		$this->txtDbHandle->open($from, $to, true);
	}





	public function getIndexList()
	{
		$scandir = scandir($this->indexDir);
		$arr = [];
		foreach ($scandir as $v) {
			if ($v == '.' || $v == '..') {
				continue;
			}
			$$mapping = $this->indexDir . $v . '/Mapping';
			if (is_file($$mapping)) {
				$mapping = json_decode(file_get_contents($$mapping), true);
				$arr[$mapping['index']]['name'] = $mapping['index'];
				$arr[$mapping['index']]['mapping'] = $mapping;

				$index_info = $this->indexDir . $v . '/index_info';
				if (is_file($index_info)) {
					$index_info = json_decode(file_get_contents($index_info), true);
					$arr[$mapping['index']]['info'] = $index_info;
				}
			}
		}
		return $arr;
	}



	public function maxSearchLen($len)
	{
		$this->subLen = ((int)$len < 1) ? 1 : (int)$len;
	}



	public function rewrite_query($text)
	{



		$str_len = mb_strlen($text, 'utf-8') + 1;
		$all_zm_len = 0;
		if (preg_match_all('/[a-zA-Z]+/i', $text, $mat)) {
			$all_zm_len = mb_strlen(implode('', $mat[0]), 'utf-8');
		}
		if (($all_zm_len / $str_len) > 0.8) {
			$text_cut = mb_substr($text, 0, $this->subLen + 50);

			if (stristr($text_cut, ' ') && (substr_count($text_cut, ' ') > 5)) {
				$text_cut = substr($text_cut, 0, strripos($text_cut, ' '));
			}
		} else {
			$text_cut = mb_substr($text, 0, $this->subLen);
		}




		$fc_arr = $this->segment($text_cut, false);
		$fc_arr_all = $fc_arr;


		if (count($fc_arr) > 4) {

			$heightFreq  = explode(PHP_EOL, file_get_contents(dirname(__FILE__) . '/windIndexCore/height_freq_word/height_freq_word_search_diff.txt'));
			$heightFreq  = array_filter($heightFreq);
			$heightFreq = array_slice($heightFreq, 0, 300);

			$stopWordEn  = explode(PHP_EOL, file_get_contents(dirname(__FILE__) . '/windIndexCore/stopword/baidu_stopword_en.txt'));
			$stopWordEn  = array_filter($stopWordEn);
			$diffArr = array_merge($heightFreq, $stopWordEn);
			$fc_arr_diff = array_diff($fc_arr, $diffArr);
			if (!empty($fc_arr_diff)) {
				$fc_arr = $fc_arr_diff;
			}
		}
		$fc_arr = $this->filterSymbol_StopWord($text, $fc_arr);

		$fc_arr_j = [];

		$this->terms = $fc_arr_all;
		$fc_arr_j['fc_arr'] = $fc_arr;


		return $fc_arr_j;
	}

	public function search($text, $page, $listRows)
	{

		$page = $page + 0;
		$page = ($page > 20) ? 20 : $page;

		$fc_arr_j = $this->rewrite_query($text);


		$fc_arr = $fc_arr_j['fc_arr'];

		$resArr =  $this->postingListWholeSearch($fc_arr, $page, $listRows);

		$res = [
			'result' => $resArr,
		];
		return $res;
	}
}
