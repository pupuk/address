<?php

class Address
{
    /*
    ** 智能解析
    */
    public static function smart($string, $user = true)
    {
        if ($user) {
            $decompose = self::decompose($string);
            $re = $decompose;
        } else {
            $re['addr'] = $string;
        }

        $fuzz = self::fuzz($re['addr']);
        $parse = self::parse($fuzz['a1'], $fuzz['a2'], $fuzz['a3']);

        $re['province'] = $parse['province'];
        $re['city'] = $parse['city'];
        $re['region'] = $parse['region'];

        $re['street'] = ($fuzz['street']) ? $fuzz['street'] : '';
        $re['street'] = str_replace([$re['region'], $re['city'], $re['province']], ['', '', ''], $re['street']);

        return $re;
    }

    /*
    ** 分离手机号(座机)，身份证号，姓名等用户信息
    */
    public static function decompose($string)
    {

        $compose = array();

        $search = array('收货地址', '详细地址', '地址', '收货人', '收件人', '收货', '所在地区', '邮编', '电话', '手机号码','身份证号码', '身份证号', '身份证', '：', ':', '；', ';', '，', ',', '。');
        $replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');
        $string = str_replace($search, $replace, $string);

        $string = preg_replace('/\s{1,}/', ' ', $string);

        $string = preg_replace('/0-|0?(\d{3})-(\d{4})-(\d{4})/', '$1$2$3', $string);

        preg_match('/\d{18}|\d{17}X/i', $string, $match);
        if ($match && $match[0]) {
            $compose['idn'] = strtoupper($match[0]);
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{7,11}|\d{3,4}-\d{6,8}/', $string, $match);
        if ($match && $match[0]) {
            $compose['mobile'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{6}/', $string, $match);
        if ($match && $match[0]) {
            $compose['postcode'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        $string = trim(preg_replace('/ {2,}/', ' ', $string));

        $split_arr = explode(' ', $string);
        if (count($split_arr) > 1) {
            $compose['name'] = $split_arr[0];
            foreach ($split_arr as $value) {
                if (strlen($value) < strlen($compose['name'])) {
                    $compose['name'] = $value;
                }
            }
            $string = trim(str_replace($compose['name'], '', $string));
        }

        $compose['addr'] = $string;

        return $compose;
    }

    /*
    ** 根据统计规律分析出二三级地址
    */
    public static function fuzz($addr)
    {
        $addr_origin = $addr;
        $addr = str_replace([' ', ','], ['', ''], $addr);
        $addr = str_replace('自治区', '省', $addr);
        $addr = str_replace('自治州', '州', $addr);

        $addr = str_replace('小区', '', $addr);
        $addr = str_replace('校区', '', $addr);

        $a1 = '';
        $a2 = '';
        $a3 = '';
        $street = '';

        if (mb_strpos($addr, '县') !== false && mb_strpos($addr, '县') < floor((mb_strlen($addr) / 3) * 2) || (mb_strpos($addr, '区') !== false && mb_strpos($addr, '区') < floor((mb_strlen($addr) / 3) * 2)) || mb_strpos($addr, '旗') !== false && mb_strpos($addr, '旗') < floor((mb_strlen($addr) / 3) * 2)) {

            if (mb_strstr($addr, '旗')) {
                $deep3_keyword_pos = mb_strpos($addr, '旗');
                $a3 = mb_substr($addr, $deep3_keyword_pos - 1, 2);
            }
            if (mb_strstr($addr, '区')) {
                $deep3_keyword_pos = mb_strpos($addr, '区');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '区');
                    $a3 = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                }
            }
            if (mb_strstr($addr, '县')) {
                $deep3_keyword_pos = mb_strpos($addr, '县');

                if (mb_strstr($addr, '市')) {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '县');
                    $a3 = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {

                    if (mb_strstr($addr, '自治县')) {
                        $a3 = mb_substr($addr, $deep3_keyword_pos - 6, 7);
                        if (in_array(mb_substr($a3, 0, 1), ['省', '市', '州'])) {
                            $a3 = mb_substr($a3, 1);
                        }
                    } else {
                        $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    }
                }
            }
            $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
        } else {
            if (mb_strripos($addr, '市')) {

                if (mb_substr_count($addr, '市') == 1) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                } else if (mb_substr_count($addr, '市') >= 2) {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                }
            } else {

                $a3 = '';
                $street = $addr;
            }
        }

        if (mb_strpos($addr, '市') || mb_strstr($addr, '盟') || mb_strstr($addr, '州')) {
            if ($tmp_pos = mb_strpos($addr, '市')) {
                $a2 = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '盟')) {
                $a2 = mb_substr($addr, $tmp_pos - 2, 3);
            } else if ($tmp_pos = mb_strpos($addr, '州')) {

                if ($tmp_pos = mb_strpos($addr, '自治州')) {
                    $a2 = mb_substr($addr, $tmp_pos - 4, 5);
                } else {
                    $a2 = mb_substr($addr, $tmp_pos - 2, 3);
                }
            }
        } else {
            $a2 = '';
        }
        $a2;

        $r = array(
            'a1' => $a1,
            'a2' => $a2,
            'a3' => $a3,
            'street' => $street,
        );

        return $r;
    }

    /*
    ** 智能解析出省市区+街道地址
    */
    public static function parse($a1, $a2, $a3)
    {
        require 'data/a3.php';
        require 'data/a2.php';
        require 'data/a1.php';

        $r = array();

        if ($a3 != '') {

            $area3_matches = array();
            foreach ($a3_data as $id => $v) {
                if (mb_strpos($v['name'], $a3) !== false) {
                    $area3_matches[$id] = $v;
                }
            }

            if ($area3_matches && count($area3_matches) > 1) {
                if ($a2) {
                    foreach ($a2_data as $id => $v) {
                        if (mb_strpos($v['name'], $a2) !== false) {
                            $area2_matches[$id] = $v;
                        }
                    }

                    if ($area2_matches) {
                        foreach ($area3_matches as $id => $v) {

                            if (isset($area2_matches[$v['pid']])) {
                                $r['city'] = $area2_matches[$v['pid']]['name'];
                                $r['region'] = $v['name'];
                                $sheng_id = $area2_matches[$v['pid']]['pid'];
                                $r['province'] = $a1_data[$sheng_id]['name'];
                            }
                        }
                    }
                } else {

                    $r['province'] = '';
                    $r['city'] = '';
                    $r['region'] = $a3;
                }
            } else if ($area3_matches && count($area3_matches) == 1) {
                foreach ($area3_matches as $id => $v) {
                    $city_id = $v['pid'];
                    $r['region'] = $v['name'];
                }
                $city = $a2_data[$city_id];
                $province = $a1_data[$city['pid']];

                $r['province'] = $province['name'];
                $r['city'] = $city['name'];
            } else if (empty($area3_matches) && $a2 == $a3) {

                foreach ($a2_data as $id => $v) {
                    if (mb_strpos($v['name'], $a2) !== false) {
                        $area2_matches[$id] = $v;
                        $sheng_id = $v['pid'];
                        $r['city'] = $v['name'];
                    }
                }

                $r['province'] = $a1_data[$sheng_id]['name'];
                $r['region'] = '';
            }
        }

        return $r;
    }
}
