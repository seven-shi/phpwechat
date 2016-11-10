<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wx extends CI_Controller {
    
    /**
     * 微信登录地址
     * @var string 
     */
    protected $wx_address;
    
    /**
     * 微信登录的用户数组
     * @var array 
     */
    protected $user;
    
    /**
     * 微信登录的用户名
     * @var type 
     */
    protected $username;

    public function __construct() {
        parent::__construct();
        $this->qq = new stdClass();
        $this->load->helper('cli');
        $this->load->library('Wechat');
    }
    
    public function index() {
        //欢迎信息
        $this->_hello();
        
        //登录二维码
        $this->_qrcode();
        
        //登录用户
        $this->_login();
        
        //读取消息
        $this->_get_msg();
        
    }
    
    /**
     * 欢迎信息
     */
    protected function _hello()
    {
        \Helper\CLI::echo_system('***********************************');
        \Helper\CLI::echo_system('*****欢迎来到七七终端版微信********');
        \Helper\CLI::echo_system('***********************************');
    }
    
    /**
     * 获取登录二维码
     */
    protected function _qrcode() {
        //访问下首页 并且创建cookie文件
        $this->wechat->get_index();
        $get_login_status_num = 0;
        $login = false;
        while(true)
        {
            $get_login_status_num?
            \Helper\CLI::echo_warning('二维码扫描等待超时 正在刷新二维码...'):
            \Helper\CLI::echo_warning('开始登录,获取二维码,这可能需要五六秒的时间,请耐心等待');
            $get_login_status_num = 0;//第二次运行的时候 初始化一下
            $code = $this->wechat->get_qrcode_code();
            if(strstr(PHP_OS, 'WIN'))
            {
                $file = $this->wechat->get_qrcode($code);
                \Helper\CLI::echo_system('文件保存地址:'.$file);
            }
            else
            {
                $this->_terminal_qrcode($this->wechat->generate_qrcode($code));
            }

            \Helper\CLI::echo_system('等待扫码');
            while (true) {
                if(10 === $get_login_status_num)//超过十次就跳出 继续获取一个新的二维码
                {
                    break;
                }
                $this->wx_address = $this->wechat->get_login_status($code);
                if ($this->wx_address !== false) {
                    break 2;
                }
                echo '.';
                $get_login_status_num++;
                sleep(1);
            }
        }
    }
    
    /**
     * 登录微信
     */
    protected function _login() {
        \Helper\CLI::echo_system('扫码成功,开始登录微信');
        $success_code = $this->wechat->get_login_success_code($this->wx_address);
        if (!isset($success_code['ret'])) {
            exit('login error');
        }
        \Helper\CLI::echo_system('微信登录成功,开始初始化信息');
        $this->wechat->get_wxinit();
        \Helper\CLI::echo_system('初始化成功,开始获取好友列表');
        $this->wechat->get_friend();
        \Helper\CLI::echo_system('好友列表获取成功,开始读取用户信息');
        $this->user = $this->wechat->get_user();
        $this->username = isset($this->user['NickName'])?$this->user['NickName']:'未知';
        \Helper\CLI::echos('登录用户:'.$this->username);
        //$this->wechat->get_wx_status_notify();
    }
    
    /**
     * 读取消息
     */
    protected function _get_msg() {
        
        while (true) {
            $this->wechat->get_msg();
            $msg_status = $this->wechat->get_msg_status();
            if(9 == $msg_status){
                \Helper\CLI::echo_system('微信已退出');
                exit;
            }
            else if (false === $msg_status) {
                sleep(1);
            } else if(in_array($msg_status, [2,7])){
                $msg = $this->wechat->get_msg();
                $msg = json_decode($msg,true);
                
                if(isset($msg['AddMsgList']))
                {
                    foreach($msg['AddMsgList'] as $item)
                    {
                        if(isset($item['FromUserName']) and $item['FromUserName'] != $this->user['NickName'] )
                        {
                            if(false !== $this->wechat->get_group_name($item['FromUserName']))
                            {
                                $this->wechat->get_group_user_info($item['FromUserName']);
                                $group_name = $this->wechat->get_group_name($item['FromUserName']);
                                //$this->wechat->get_wx_status_notify($item['FromUserName']);
                                $msg = explode(':<br/>', $item['Content']);
                                $msg_username = $this->wechat->get_group_user($item['FromUserName'],$msg[0]);
                                $msg_username = isset($msg_username['NickName'])?$msg_username['NickName']:'未知';
                                if(!isset($msg[1]))
                                {
                                    \Helper\CLI::echo_warning('无法识别的群消息:'.$item['Content']);
                                }
                                else
                                {
                                    \Helper\CLI::echos('群['.$group_name.']['.$msg_username.']发来信息:'.$msg[1]);
                                }
                                
                            }
                            else
                            {
                                $friend_info = $this->wechat->get_friend_info($item['FromUserName']);
                                $msg_friend_name = isset($friend_info['NickName'])?$friend_info['NickName']:'未知';
                                \Helper\CLI::echos('好友['.$msg_friend_name.']发来信息:'.$item['Content']);
                            }
                        }
                    }
                }
                else
                {
                    //file_put_contents('msg'.time().'.log', $msg,FILE_APPEND);
                }
                
            }
        }
    }
    
    /**
     * 生成linux 终端下的二维码
     */
    protected function _terminal_qrcode($qrcode)
    {
        if(!is_array($qrcode))
        {
            return false;
        }
        
        $line_count = count($qrcode);
        $strlen = strlen($qrcode[0])+2;
        $top = str_repeat(0,$strlen);
        array_unshift($qrcode,$top);
        $qrcode[] = $top;
        
        foreach($qrcode as $item)
        {
            $item = "0{$item}0";
            for($i=1;$i<=$strlen;$i++)
            {
                $len = $i-1;
                if(0 == $item{$len})
                {
                    \Helper\CLI::echo_colore('  ',"red", "yellow",false);
                }
                else
                {
                    \Helper\CLI::echo_colore('  ',"red", "black",false);
                }
            }
            echo PHP_EOL;
        }
    }

}
