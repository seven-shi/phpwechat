<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*****************************************************************************************
 * 模拟微信网页扫码登录代码 
 * 
 * 
 *****************************************************************************************
 */
include 'phpWechat/vendor/autoload.php';
class Wechat extends PhpWechat\WebWechat\WebWechat{
    public function __construct()
    {
        parent::__construct();
        $this->set_cookie_path(FCPATH.'cookie/');
        $this->set_qrcode_path(FCPATH.'qrcode/');
    }
}
