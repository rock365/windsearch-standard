<?php

// +----------------------------------------------------------------------
// | rockAnalyzer
// +----------------------------------------------------------------------
// | Copyright (c) All rights reserved.
// +----------------------------------------------------------------------
// | Licensed: Apache License 2.0
// +----------------------------------------------------------------------
// | Author: rock365 https://github.com/rock365
// +----------------------------------------------------------------------
// | PHP Version: PHP_VERSION >= 5.6
// +----------------------------------------------------------------------

class Analyzer
{
    private $mask_value = 0xc3500;
    private $cache = [];
    private $resultArr = [];


    private $mainDicHand = false;
    private $mainDicInfos = [];
    private $mainDicFile = '';


    public function __construct()
    {

        $this->mainDicFile = dirname(__FILE__) . '/../windIndexCore/dic/dic_with_idf.dic';
    }


    function __destruct()
    {
        if ($this->mainDicHand !== false) {
            @fclose($this->mainDicHand);
        }
        unset($this->resultArr);
        unset($this->cache);
    }




    private function _get_index($key)
    {
        $l = strlen($key);
        $h = 0x238f13af;
        while ($l--) {
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }

        return ($h % $this->mask_value);
    }


    public function getWordInfo($key, $type = 'word')
    {
        

        if (!$this->mainDicHand) {
            
    
            $this->mainDicHand = fopen($this->mainDicFile, 'r');
           
        }
       
        $keynum = (int)$this->_get_index($key);
        if (isset($this->mainDicInfos[$keynum])) {
            $data = $this->mainDicInfos[$keynum];
        } else {

            $move_pos = $keynum * 12;
            fseek($this->mainDicHand, $move_pos, SEEK_SET);
            $dat = fread($this->mainDicHand, 12);
            $arr = unpack('I1s/N1l/N1c', $dat);
           
            if ($arr['l'] == 0) {
                return false;
            }
            fseek($this->mainDicHand, $arr['s'], SEEK_SET);
            $data = json_decode(fread($this->mainDicHand, $arr['l']), true);

            $this->mainDicInfos[$keynum] = $data;
        }

        

        if (!is_array($data) || !isset($data[$key])) {
            return false;
        }

        return (($type == 'word') ? $data[$key] : $data);
    }



    public function IsWord($word)
    {
        $winfos = $this->getWordInfo($word);
       
        return ($winfos !== false);
    }



    public function getTerm($zfc)
    {



        $max_len = 6;
        $fcContainerUtf8 = [];

        $strLen = strlen($zfc);

        if ($strLen <= $max_len * 3) {

            $max_len = (($strLen / 3));
        }

        if ($strLen == 3) {

            $fcContainerUtf8[] = $zfc;
        }

        $tempArr = ['着' => '', '上' => '', '下' => '', '中' => '', '里' => '', '外' => '', '出' => ''];

        $currCutPos = $strLen - 3 * $max_len;
        $tempCount = 0;
        for ($g = $currCutPos; $g < ($strLen - 3); $g += 3) {

            ++$tempCount;
            $isExitTerm = false;
            $str_cut = substr($zfc, $g);

            $is_wd = $this->IsWord($str_cut);

            if ($is_wd) {

                $isExitTerm = true;

                if (($tempCount == ($max_len - 1))) {
                    $lastWord = substr($str_cut, 3);


                    if (isset($tempArr[$lastWord])) {

                        $beg = ($g - 6);
                        if ($beg >= 0) {

                            $tempWord = substr($zfc, ($g - 6), 9);

                            $is_wd = $this->IsWord($tempWord);
                            if ($is_wd) {
                                $str_cut_last = substr($zfc, -3);

                                $fcContainerUtf8[] = $str_cut_last;
                                $fcContainerUtf8[] =  $tempWord;

                                $zfc = substr($zfc, 0, ($g - 6));

                                $strLen = strlen($zfc);
                                if ($strLen <= $max_len * 3) {
                                    $max_len = ($strLen / 3);
                                }
                                if ($strLen == 3) {
                                    $fcContainerUtf8[] = $zfc;
                                    break;
                                }
                                $g = $strLen - 3 * $max_len - 3;

                                $tempCount = 0;
                                $isExitTerm = false;
                                continue;
                            } else {
                                $tempWord = substr($zfc, ($g - 3), 6);

                                $is_wd = $this->IsWord($tempWord);
                                if ($is_wd) {
                                    $str_cut_last = substr($zfc, -3);

                                    $fcContainerUtf8[] = $str_cut_last;
                                    $fcContainerUtf8[] = $tempWord;

                                    $zfc = substr($zfc, 0, ($g - 3));

                                    $strLen = strlen($zfc);
                                    if ($strLen <= $max_len * 3) {
                                        $max_len = ($strLen / 3);
                                    }
                                    if ($strLen == 3) {

                                        $fcContainerUtf8[] = $zfc;
                                        break;
                                    }
                                    $g = $strLen - 3 * $max_len - 3;

                                    $tempCount = 0;
                                    $isExitTerm = false;
                                    continue;
                                }
                            }
                        } else {
                            $beg = ($g - 3);

                            if ($beg >= 0) {

                                $tempWord = substr($zfc, ($g - 3), 6);

                                $is_wd = $this->IsWord($tempWord);
                                if ($is_wd) {
                                    $str_cut_last = substr($zfc, -3);

                                    $fcContainerUtf8[] = $str_cut_last;
                                    $fcContainerUtf8[] = $tempWord;

                                    $zfc = substr($zfc, 0, ($g - 3));

                                    $strLen = strlen($zfc);
                                    if ($strLen <= $max_len * 3) {
                                        $max_len = ($strLen / 3);
                                    }
                                    if ($strLen == 3) {

                                        $fcContainerUtf8[] = $zfc;
                                        break;
                                    }
                                    $g = $strLen - 3 * $max_len - 3;

                                    $tempCount = 0;
                                    $isExitTerm = false;
                                    continue;
                                }
                            }
                        }
                    }
                }

                $fcContainerUtf8[] = $str_cut;



                $zfc = substr($zfc, 0, $g);
                $strLen = strlen($zfc);
                if ($strLen <= $max_len * 3) {
                    $max_len = ($strLen / 3);
                }
                $g = $strLen - 3 * $max_len - 3;

                $tempCount = 0;
                $isExitTerm = false;
            }

            if (($tempCount == ($max_len - 1)) && !$isExitTerm) {

                $strLen = strlen($zfc);
                if ($strLen == 3) {

                    $fcContainerUtf8[] = $zfc;

                    break;
                } else {

                    $str_cut_last = substr($zfc, -3);
                    $fcContainerUtf8[] = $str_cut_last;



                    $zfc = substr($zfc, 0, -3);
                    $strLen = strlen($zfc);
                    if ($strLen <= $max_len * 3) {
                        $max_len = ($strLen / 3);
                    }
                    $g = $strLen - 3 * $max_len - 3;
                }

                if ($strLen == 3) {

                    $fcContainerUtf8[] = $zfc;
                    break;
                }

                $tempCount = 0;
                $isExitTerm = false;
            }
        }

        $fcContainerUtf8 = array_reverse($fcContainerUtf8);

        return $fcContainerUtf8;
    }

    public function getAllTerm($zfc, $type = false)
    {



        $max_len = 6;
        $fcContainerUtf8 = [];

        $strLen = strlen($zfc);

        if ($strLen <= $max_len * 3) {
            if ($type) {
                $max_len = (($strLen / 3) - 1);
            } else {
                $max_len = (($strLen / 3));
            }
        }

        if ($strLen == 3) {

            $fcContainerUtf8[] = $zfc;
        }

        $tempArr = ['着' => '', '上' => '', '下' => '', '中' => '', '里' => '', '外' => '', '出' => ''];

        $currCutPos = $strLen - 3 * $max_len;
        $tempCount = 0;
        for ($g = $currCutPos; $g < ($strLen - 3); $g += 3) {

            ++$tempCount;
            $isExitTerm = false;
            $str_cut = substr($zfc, $g);

            $is_wd = $this->IsWord($str_cut);


            if ($is_wd) {

                $isExitTerm = true;

                if (($tempCount == ($max_len - 1)) && !$type) {
                    $lastWord = substr($str_cut, 3);


                    if (isset($tempArr[$lastWord])) {
                        $beg = ($g - 6);
                        if ($beg >= 0) {
                            $tempWord = substr($zfc, ($g - 6), 9);


                            $is_wd = $this->IsWord($tempWord);

                            if ($is_wd) {
                                $str_cut_last = substr($zfc, -3);

                                $fcContainerUtf8[] = $str_cut_last;

                                $tempRe = $this->deepFc($tempWord);
                                $tempRe[1][] = $tempWord;
                                foreach ($tempRe[1] as $w) {
                                    $fcContainerUtf8[] = $w;
                                }


                                $zfc = substr($zfc, 0, ($g - 6));

                                $strLen = strlen($zfc);
                                if ($strLen <= $max_len * 3) {
                                    $max_len = ($strLen / 3);
                                }
                                if ($strLen == 3) {

                                    $fcContainerUtf8[] = $zfc;
                                    break;
                                }
                                $g = $strLen - 3 * $max_len - 3;

                                $tempCount = 0;
                                $isExitTerm = false;
                                continue;
                            } else {
                                $tempWord = substr($zfc, ($g - 3), 6);


                                $is_wd = $this->IsWord($tempWord);


                                if ($is_wd) {
                                    $str_cut_last = substr($zfc, -3);

                                    $fcContainerUtf8[] = $str_cut_last;
                                    $fcContainerUtf8[] = $tempWord;

                                    $zfc = substr($zfc, 0, ($g - 3));

                                    $strLen = strlen($zfc);
                                    if ($strLen <= $max_len * 3) {
                                        $max_len = ($strLen / 3);
                                    }
                                    if ($strLen == 3) {

                                        $fcContainerUtf8[] = $zfc;
                                        break;
                                    }
                                    $g = $strLen - 3 * $max_len - 3;

                                    $tempCount = 0;
                                    $isExitTerm = false;
                                    continue;
                                }
                            }
                        } else {
                            $beg = ($g - 3);
                            if ($beg >= 0) {

                                $tempWord = substr($zfc, ($g - 3), 6);


                                $is_wd = $this->IsWord($tempWord);


                                if ($is_wd) {
                                    $str_cut_last = substr($zfc, -3);

                                    $fcContainerUtf8[] = $str_cut_last;
                                    $fcContainerUtf8[] = $tempWord;

                                    $zfc = substr($zfc, 0, ($g - 3));

                                    $strLen = strlen($zfc);
                                    if ($strLen <= $max_len * 3) {
                                        $max_len = ($strLen / 3);
                                    }
                                    if ($strLen == 3) {

                                        $fcContainerUtf8[] = $zfc;
                                        break;
                                    }
                                    $g = $strLen - 3 * $max_len - 3;

                                    $tempCount = 0;
                                    $isExitTerm = false;
                                    continue;
                                }
                            }
                        }
                    }
                }




                if (strlen($str_cut) > 6) {

                    $tempRe = $this->deepFc($str_cut);

                    $tempRe[1][] = $str_cut;
                    foreach ($tempRe[1] as $w) {
                        $fcContainerUtf8[] = $w;
                    }
                } else {

                    $fcContainerUtf8[] = $str_cut;
                }


                $zfc = substr($zfc, 0, $g);
                $strLen = strlen($zfc);
                if ($strLen <= $max_len * 3) {
                    $max_len = ($strLen / 3);
                }
                $g = $strLen - 3 * $max_len - 3;

                $tempCount = 0;
                $isExitTerm = false;
            }

            if (($tempCount == ($max_len - 1)) && !$isExitTerm) {

                $strLen = strlen($zfc);
                if ($strLen == 3) {
                    if (!$type) {

                        $fcContainerUtf8[] = $zfc;
                    }
                    break;
                } else {
                    if (!$type) {
                        $str_cut_last = substr($zfc, -3);

                        $fcContainerUtf8[] = $str_cut_last;
                    }


                    $zfc = substr($zfc, 0, -3);
                    $strLen = strlen($zfc);
                    if ($strLen <= $max_len * 3) {
                        $max_len = ($strLen / 3);
                    }
                    $g = $strLen - 3 * $max_len - 3;
                }

                if ($strLen == 3) {
                    $fcContainerUtf8[] = $zfc;
                    break;
                }

                $tempCount = 0;
                $isExitTerm = false;
            }
        }

        $fcContainerUtf8 = array_reverse($fcContainerUtf8);
        return $fcContainerUtf8;
    }


    public function deepFc($zfc)
    {


        $max_len = 6;
        $zfcOrg = $zfc;

        $fcContainerUcs = [];
        $fcContainerUtf8 = [];

        $strLen = strlen($zfc);

        if ($strLen < $max_len * 3) {
            $max_len = ($strLen / 3) - 1;
        }
        $count = 1;
        $currCutNum = $max_len;
        $currCutLen = 3 * $max_len;
        $currCutPos = $strLen - $currCutLen;

        for ($g = $currCutPos; $g > -1; $g -= 3) {

            if ($currCutNum == 1) {
                break;
            }

            $str_cut = substr($zfc, $g, $currCutLen);

            $is_wd = $this->IsWord($str_cut);

            if ($is_wd) {
                $fcContainerUtf8[] = $str_cut;
            }


            if ($g < 1) {
                ++$count;
                --$currCutNum;
                $currCutLen = 3 * $currCutNum;
                $g = $strLen - $currCutLen + 3;
            }
        }

        $fcContainerUtf8 = array_reverse($fcContainerUtf8);

        return [$fcContainerUcs, $fcContainerUtf8];
    }




    public function standard($str)
    {
        $this->resultArr = [];
        $regx = '/([\x{4E00}-\x{9FA5}]+)|([\x{3040}-\x{309F}]+)|([\x{30A0}-\x{30FF}]+)|([\x{AC00}-\x{D7AF}]+)|([a-zA-Z0-9]+)|([\-\_\+\!\@\#\$\%\^\&\*\(\)\|\}\{\“\\”：\"\:\?\>\<\,\.\/\'\;\[\]\~\～\！\@\#\￥\%\…\&\*\（\）\—\+\|\}\{\？\》\《\·\。\，\℃\、\.\~\～\；])/u';
        $regx_zh = '(^[\x{4e00}-\x{9fa5}]+$)';
        if (preg_match_all($regx, $str,  $mat)) {
            $all = $mat[0];
            $zh = $mat[1];
            $diff = array_diff($all, $zh);
            $notZh = implode('', $diff);
            foreach ($all as $blk) {
                if (mb_strlen($blk, 'UTF-8') == 0) {
                    continue;
                }

                if (preg_match('/' . $regx_zh . '/u', $blk)) {
                    $words = $this->getTerm($blk);
                    if (is_array($words)) {
                        $this->resultArr = array_merge($this->resultArr, $words);
                    }
                } else if (preg_match('/[a-zA-Z0-9]+/u', $blk)) {

                    $this->resultArr[] = $blk;
                } else {
                    for ($w = 0; $w < mb_strlen($blk, 'utf-8'); ++$w) {
                        $this->resultArr[] = mb_substr($blk, $w, 1, 'utf-8');
                    }
                }
            }
        }

        return $this->resultArr;
    }



    public function segmentAll($str)
    {
        $this->resultArr = [];

        $regx = '/([\x{4E00}-\x{9FA5}]+)|([\x{3040}-\x{309F}]+)|([\x{30A0}-\x{30FF}]+)|([\x{AC00}-\x{D7AF}]+)|([a-zA-Z0-9]+)|([\-\_\+\!\@\#\$\%\^\&\*\(\)\|\}\{\“\\”：\"\:\?\>\<\,\.\/\'\;\[\]\~\～\！\@\#\￥\%\…\&\*\（\）\—\+\|\}\{\？\》\《\·\。\，\℃\、\.\~\～\；])/u';
        $regx_zh = '(^[\x{4e00}-\x{9fa5}]+$)';
        if (preg_match_all($regx, $str,  $mat)) {
            $all = $mat[0];
            foreach ($all as $blk) {
                if (mb_strlen($blk, 'UTF-8') == 0) {
                    continue;
                }

                if (preg_match('/' . $regx_zh . '/u', $blk)) {

                    $words = $this->getAllTerm($blk);

                    if (is_array($words)) {
                        $this->resultArr = array_merge($this->resultArr, $words);
                    }
                } else if (preg_match('/[a-zA-Z0-9]+/u', $blk)) {

                    $this->resultArr[] = $blk;
                } else {
                    for ($w = 0; $w < mb_strlen($blk, 'utf-8'); ++$w) {
                        $this->resultArr[] = mb_substr($blk, $w, 1, 'utf-8');
                    }
                }
            }



            return $this->resultArr;
        }
    }
}
