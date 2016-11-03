<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wxs {

    //cookie文件地址
    private $cookie_path;
    private $login_success_data;
    private $deviceid;
    private $synckey;
    private $SyncKeyArr;
    private $post_data = [];

    /*
     * 设置cookie 文件
     */

    function set_cookie_file_path($uid, $tnf = false) {
        $this->cookie_path = 'wx' . $uid . ".txt";
        if ($tnf)
            return;
        if (file_exists($this->cookie_path)) {
            unlink($this->cookie_path);
        }
        if (0 != file_put_contents($this->cookie_path, '')) {
            CLI_FUN::echo_error('生成cookie文件失败,请检查目录权限');
            exit;
        }
        strstr(PHP_OS, 'WIN') or chmod($this->cookie_path, '777');
    }

    /**
     * 读取下首页 天知道首页是不是有啥必要的cookie
     */
    public function get_index() {
        $this->deviceid = 'e' . rand(100000000, 999999999) . rand(10000, 99999);
        $url = 'https://wx.qq.com/';
        self::set_cookie_file_path(time());
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        $data = curl_exec($ch);
        curl_close($ch);
    }
    
    public function get_qcode_code() {
        $url = "https://login.wx.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_=1477640330239";

        $ch = curl_init($url);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function _get_uuid($code) {
        $reg = '/"([0-9a-zA-Z\=]+)";/';
        preg_match_all($reg, $code, $data);
        if (isset($data[1][0])) {
            return $data[1][0];
        }
        return false;
    }

    /**
     * 获取二维码
     */
    public function get_qcode($uuid) {
        $url = "https://login.weixin.qq.com/qrcode/" . $uuid;

        $ch = curl_init($url);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 获取登录状态
     */
    public function get_login_status($uuid) {
        $time = ~time();
        echo $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid={$uuid}&tip=1&r={$time}&_=1477640330239";
        $ch = curl_init($url);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        $data = curl_exec($ch);
        curl_close($ch);
        $reg = '/"(.*?)"/';
        preg_match_all($reg, $data, $result);

        if (isset($result[1][0])) {
            return $result[1][0];
        }
        return false;
        //https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=Ac1vDHBDTNKFp9YNngxek1BF@qrticket_0&uuid=YdYhSv0wdg==&lang=zh_CN&scan=1477671691
    }

    /**
     * 获取登录后的参数
     */
    public function get_login_success_code($url) {
        $ch = curl_init($url);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        echo $data = curl_exec($ch);
        curl_close($ch);
        $this->login_success_data = (array) simplexml_load_string($data);
        return $this->login_success_data;
    }

    /**
     * 初始化微信
     */
    public function get_friend() {
        $time = -2147483647 + time();
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?r={$time}&lang=zh_CN&pass_ticket=" . ($this->login_success_data['pass_ticket']);
        $ch = curl_init($url);
        // 必须要来路域名
        //$post = "r={$time}&lang=zh_CN&pass_ticket={$this->login_success_data['pass_ticket']}";

        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
        ]];
        //print_r($post);
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
        curl_close($ch);
        $this->synckey = '';
        $syn = json_decode($data, true);
        foreach ($syn['SyncKey']['List'] as $key => $item) {
            $synckey[] = $item['Key'] . '_' . $item['Val'];
        }
        $this->SyncKeyArr = $syn['SyncKey'];
        $this->synckey = implode('|', $synckey);
        return $data;
    }

    public function get_msg_status() {
        $this->deviceid = 'e' . rand(100000000, 999999999) . rand(10000, 99999);
        $post = "r=" . time() . "384&skey={$this->login_success_data['skey']}&sid={$this->login_success_data['wxsid']}&uin={$this->login_success_data['wxuin']}&deviceid={$this->deviceid}&synckey={$this->synckey}";
        echo "https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin/synccheck?" . $post, "\r\n";
        $ch = curl_init("https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin/synccheck?" . $post);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);
        curl_close($ch);
        echo $data;
        $strpos_7 = strpos($data, 'selector:"7"');
        if (false !== $strpos_7) {
            return $strpos_7;
        }
        return strpos($data, 'selector:"2"');
    }

    public function get_msg() {
        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
            ],
            'SyncKey' => $this->SyncKeyArr,
            'rr' => ~time()
        ];

        $get = "sid={$this->login_success_data['wxsid']}&skey={$this->login_success_data['skey']}&lang=zh_CN&pass_ticket={$this->login_success_data['pass_ticket']}";
        $this->deviceid = 'e' . rand(100000000, 999999999) . rand(10000, 99999);

        $ch = curl_init("https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsync?" . $get);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $data = curl_exec($ch);
        curl_close($ch);

        $this->synckey = '';
        $syn = json_decode($data, true);
        foreach ($syn['SyncKey']['List'] as $key => $item) {
            $synckey[] = $item['Key'] . '_' . $item['Val'];
        }
        $this->synckey = implode('|', $synckey);
        $this->SyncKeyArr = $syn['SyncKey'];

        return $data;
    }

}
