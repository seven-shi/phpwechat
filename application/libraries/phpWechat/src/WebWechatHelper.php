<?php
namespace PhpWechat\WebWechat;
/*****************************************************************************************
 * 微信的一些函数 
 *****************************************************************************************
 */

/**
 * 微信模拟登录
 * @email 397109515@qq.com
 * @author  seven
 */
class WebWechatHelper {

    /**
     * 判断id是否为群id
     * @param type $group_id
     * @return type
     */
    public static function is_group_id($group_id)
    {
        return (strpos($group_id, '@@') === 0);
    }

    /**
     * 匹配uuid参数
     * @param type $code
     * @return string or boolean
     *----------------------------------------------------------------------------------------------
     * 如果正常的话     window.QRLogin.code = Adf25646D==; window.QRLogin.uuid = "[0-9z-aA-Z]=="; 
     * ---------------------------------------------------------------------------------------------
     */
    public static function get_uuid($code) {
        $reg = '/"([0-9a-zA-Z\=]+)";/';
        preg_match_all($reg, $code, $data);
        if (isset($data[1][0])) {
            return $data[1][0];
        }
        return false;
    }
    
    /**
     * 生成一个客户端id 其实就是个毫秒时间戳
     * @return bigint
     */
    protected static function get_client_id() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*100000);
    }
    
    /**
     * 输出
     * @param type $message
     * @param type $br
     */
    static public function echos($message,$br = true)
    {
        switch (true) {
            case stristr(PHP_OS, 'WIN'): $t = eval('return '.mb_convert_encoding(var_export($message,true),'gb2312','utf-8').';');break;
            case stristr(PHP_OS, 'DAR'): $t = eval('return '.mb_convert_encoding(var_export($message,true),'utf-8','auto').';');break;
            case stristr(PHP_OS, 'LINUX'): $t = eval('return '.mb_convert_encoding(var_export($message,true),'utf-8','auto').';');break;
            default : $t = eval('return '.mb_convert_encoding(var_export($message,true),'utf-8','auto').';');
        }
        print_r($t);echo $br?PHP_EOL:'';
    }
}
