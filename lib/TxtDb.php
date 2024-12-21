<?php

define('KEY_SIZE', 128);
define('INDEX_SIZE', KEY_SIZE + 12);
define('SUCCESS', true);
define('FAILURE', false);
define('KEY_EXISTS', false);

class TxtDb
{

    private $idx_fp;
    private $dat_fp;
    public $db_size = 5000000;


    public function open($from, $pathName, $is_empty = true)
    {

        $idx_path = $pathName . '.idx';
        $dat_path = $pathName . '.dat';


        if ($is_empty) {
            file_put_contents($dat_path, '');
            copy($from . 'fb_' . strval($this->db_size) . '.idx', $idx_path);
            $mode = 'r+b';
        } else {
            $mode = 'r+b';
        }

        $this->idx_fp = fopen($idx_path, $mode);

        if (!$this->idx_fp) {
            return FAILURE;
        }

        $this->dat_fp = fopen($dat_path, $mode);
        if (!$this->dat_fp) {
            return FAILURE;
        }

        return SUCCESS;
    }


    public function getHash($key)
    {
        $len = 8;
        $key = substr(md5($key), 0, $len);
        $hash = 0;
        for ($i = 0; $i < $len; $i++) {
            $hash += 33 * $hash + ord($key[$i]);
        }
        return $hash & 0x7FFFFFFF;
    }


    public function add($key, $value)
    {


        $offset = ($this->getHash($key) % $this->db_size) * 4;

        $idxoffset = fstat($this->idx_fp);
        $idxoffset = intval($idxoffset['size']);

        $dataoffset = fstat($this->dat_fp);
        $dataoffset = intval($dataoffset['size']);


        $keylen = strlen($key);
        $vallen = strlen($value);

        if ($keylen > KEY_SIZE) {
            return FAILURE;
        }
        $block = pack('L', 0x00000000);
        $block .= $key;
        $space = KEY_SIZE - $keylen;
        $pack0 = pack('C', 0x00);
        $tempArr = array_pad([], $space, $pack0);
        $block .= implode('', $tempArr);

        $block .= pack('L', $dataoffset);
        $block .= pack('L', $vallen);


        fseek($this->idx_fp, $offset, SEEK_SET);
        $pos = unpack('L', fread($this->idx_fp, 4));
        $pos = $pos[1];

        if ($pos == 0) {

            fseek($this->idx_fp, $offset, SEEK_SET);
            fwrite($this->idx_fp, pack('L', $idxoffset), 4);

            fseek($this->idx_fp, 0, SEEK_END);
            fwrite($this->idx_fp, $block, INDEX_SIZE);

            fseek($this->dat_fp, 0, SEEK_END);
            fwrite($this->dat_fp, $value, $vallen);

            return SUCCESS;
        }


        $found = false;
        while ($pos) {
            fseek($this->idx_fp, $pos, SEEK_SET);
            $tmp_block = fread($this->idx_fp, INDEX_SIZE);
            $cpkey = substr($tmp_block, 4, KEY_SIZE);
            if (!strncmp($cpkey, $key, $keylen)) {
                $dataoff = unpack('L', substr($tmp_block, KEY_SIZE + 4, 4));
                $dataoff = $dataoff[1];
                $datalen = unpack('L', substr($tmp_block, KEY_SIZE + 8, 4));
                $datalen = $datalen[1];
                $found = true;
                break;
            }
            $prev = $pos;
            $pos = unpack('L', substr($tmp_block, 0, 4));
            $pos = $pos[1];
        }

        if ($found) {
            return KEY_EXISTS;
        }
        fseek($this->idx_fp, $prev, SEEK_SET);
        fwrite($this->idx_fp, pack('L', $idxoffset), 4);
        fseek($this->idx_fp, 0, SEEK_END);
        fwrite($this->idx_fp, $block, INDEX_SIZE);

        fseek($this->dat_fp, 0, SEEK_END);
        fwrite($this->dat_fp, $value, $vallen);
        return SUCCESS;
    }


    public function get($key)
    {
        $offset = ($this->getHash($key) % $this->db_size) * 4;
        fseek($this->idx_fp, $offset, SEEK_SET);
        $pos = unpack('L', fread($this->idx_fp, 4));
        $pos = $pos[1];
        $found = false;
        while ($pos) {
            fseek($this->idx_fp, $pos, SEEK_SET);
            $block = fread($this->idx_fp, INDEX_SIZE);
            $cpkey = substr($block, 4, KEY_SIZE);
            if (!strncmp($key, $cpkey, strlen($key))) {
                $dataoff = unpack('L', substr($block, KEY_SIZE + 4, 4));
                $dataoff = (int)$dataoff[1];
                $datalen = unpack('L', substr($block, KEY_SIZE + 8, 4));
                $datalen = (int)$datalen[1];
                $found = true;
                break;
            }
            $pos = unpack('L', substr($block, 0, 4));
            $pos = $pos[1];
        }
        if (!$found) {
            return null;
        }

        fseek($this->dat_fp, $dataoff, SEEK_SET);
        $data = fread($this->dat_fp, $datalen);

        return $data;
    }



    public function close()
    {
        fclose($this->idx_fp);
        fclose($this->dat_fp);
        clearstatcache();
    }
}
