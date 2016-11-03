<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wx extends CI_Controller {

    public $wx;

    public function __construct() {
        parent::__construct();
        $this->qq = new stdClass();
        $this->load->helper('cli');
        $this->load->library('Wechat');
    }

    public function index() {
        \Helper\CLI::echo_system('欢迎来到七七终端版微信');
        //访问下首页 并且创建cookie文件
        $this->wechat->get_index();
        \Helper\CLI::echo_warning('开始登录,获取二维码,这可能需要五六秒的时间,请耐心等待');
        $code = $this->wechat->get_qrcode_code();
        if(strstr(PHP_OS, 'WIN'))
        {
            $this->wechat->get_qrcode($code);
        }
        else
        {
            $this->_terminal_qrcode($this->wechat->generate_qrcode($code));
        }

        \Helper\CLI::echo_system('等待扫码','');
        while (true) {
            $wx_address = $this->wechat->get_login_status($code);
            if ($wx_address !== false) {
                break;
            }
            echo '.';
            sleep(1);
        }
        \Helper\CLI::echo_system('扫码成功,开始登录微信');
        $success_code = $this->wechat->get_login_success_code($wx_address);
        if (!isset($success_code['ret'])) {
            exit('login error');
        }
        \Helper\CLI::echo_system('微信登录成功,开始初始化信息');
        $this->wechat->get_wxinit();
        \Helper\CLI::echo_system('初始化成功,开始获取好友列表');
        $this->wechat->get_friend();
        \Helper\CLI::echo_system('好友列表获取成功,开始读取用户信息');
        $user = $this->wechat->get_user();
        $username = isset($user['NickName'])?$user['NickName']:'未知';
        \Helper\CLI::echos('登录用户:'.$username);
//        \Helper\CLI::echo_system('获取下消息状态');
//        $this->wechat->get_wx_status_notify();
        \Helper\CLI::echo_system('获取群用户信息');
        //$this->wechat->get_group_user_list();
        \Helper\CLI::echo_system('群用户获取成功,开始读取消息');
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
                        if(isset($item['FromUserName']) and $item['FromUserName'] != $user['NickName'] )
                        {
                            if(false !== $this->wechat->get_group_name($item['FromUserName']))
                            {
                                $this->wechat->get_group_user_info($item['FromUserName']);
                                $group_name = $this->wechat->get_group_name($item['FromUserName']);
                                //$this->wechat->get_wx_status_notify($item['FromUserName']);这里报错了...不知道什么鬼
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
                    file_put_contents('msg'.time().'.log', $msg,FILE_APPEND);
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
