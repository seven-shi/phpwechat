<?php
namespace PhpWechat\WebWechat;

/*****************************************************************************************
 * curl请求 
 *****************************************************************************************
 */

/**
 * 微信模拟登录
 * @email 397109515@qq.com
 * @author  seven
 */
class WebWechatCurl {

    /**
     * cookie文件地址
     * @var type 
     */
    private $cookie_file;
    
    /**
     * cookie存放目录
     * @var type 
     */
    private $cookie_path = 'cookie/';

    
    public function __construct($cookie_path = false)
    {
        (false !== $cookie_path) and $this->set_cookie_path($cookie_path);
    }
    
    /**
     * 设置cookie存放目录
     * @param type $path
     */
    public function set_cookie_path($path)
    {
        if(!is_dir($path))
        {
            throw new Exception('cookie保存目录不存在:'.$path);
        }
        $this->cookie_path = $path;
    }
    
    /**
     * 设置cookie 文件
     * @param type $uid
     * @throws WechatException
     */
    public function set_cookie_file_path($file_name)
    {
        $this->cookie_file = $this->cookie_path.'wx' . $file_name . ".cookie";
        if (file_exists($this->cookie_file))
        {
            unlink($this->cookie_file);
        }
        if (0 != file_put_contents($this->cookie_file, '')) 
        {
            throw new Exception('生成cookie文件失败,请检查目录权限');
        }
        
        strstr(PHP_OS, 'WIN') or chmod($this->cookie_file, '777');
    }

    /**
     * 发起一个get请求
     * @param string $url
     * @param boolean error_return  是否输出错误 如果不是 那么就抛出错误
     * @return string
     */
    public function curl_get($url,$error_echo = true)
    {
        $ch = curl_init($url);
        // 必须要来路域名
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        $data = curl_exec($ch);
        curl_close($ch);
        if(false === $data)
        {
            if($error_echo)
            {
                throw new Exception('CURL ERROR :'.  curl_error($ch));
            }
            else
            {
                echo 'CURL ERROR :'.  curl_error($ch);
            }
        }
        return $data;
    }
    
    /**
     * 模拟一个 request payload 请求
     * @param string $url
     * @param array $post post参数
     * @param boolean error_return  是否输出错误 如果不是 那么就抛出错误
     * @return string 正常的话 返回网页返回的信息
     */
    public function get_request_payload($url,$post,$error_echo = true) 
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_REFERER, "https://wx.qq.com/");

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post)?json_encode($post):$post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
        curl_close($ch);
        if(false === $data)
        {
            if($error_echo)
            {
                throw new Exception('CURL ERROR :'.  curl_error($ch));
            }
            else
            {
                echo 'CURL ERROR :'.  curl_error($ch);
            }
        }
        return $data;
    }
}
