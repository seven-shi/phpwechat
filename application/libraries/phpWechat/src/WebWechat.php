<?php
namespace PhpWechat\WebWechat;
use \PhpWechat\WebWechat\WebWechatHelper;
use \PhpWechat\WebWechat\WebWechatCurl;
/*****************************************************************************************
 * 模拟微信网页扫码登录代码 
 *****************************************************************************************
 */

/**
 * 微信模拟登录
 * @email 397109515@qq.com
 * @author  seven
 */
class WebWechat {

    /**
     * cookie文件地址
     * @var type 
     */
    private $cookie_file;
    
    /**
     * 存储登录成功后的数据
     * @var arr 
     */
    private $login_success_data;
    
    /**
     * 保存synckey 信息  这里是url里用的  是一个字符串
     * @var string 
     */
    private $synckey;
    
    /**
     * 保存synckey信息 这里是post的时候用的  是一个数组
     * @var array
     */
    private $SyncKeyArr;
    
    /**
     * 二维码存放目录
     * @var type 
     */
    private $qrcode_path = 'qrcode/';
    
    /**
     * cookie存放目录
     * @var type 
     */
    private $cookie_path = 'cookie/';
    
    /**
     * 登录的用户信息
     * @var array 
     */
    private $user_data;
    
    /**
     * 好友列表
     * @var array 
     */
    private $friend;
    
    /**
     * 好友总数 好像群里的也算 
     * @var int
     */
    private $friend_num;
    
    /**
     * 群列表
     * @var arrya
     */
    private $group_list;
    
    /**
     * 订阅消息数量
     * @var int
     */
    protected $subscribe_msg_count;
    
    /**
     * 微信系统时间
     * @var int
     */
    protected $wx_system_time;
    
    /**
     * 客户端消息id
     * @var int 这里可能是个精确到3位毫秒的时间戳 也有可能是精确到六位毫秒的时间戳
     */
    protected $client_msg_id;
    
    /**
     * 群组id
     * @var type 
     */
    protected $group_id = [];
    
    /**
     * 消息id 这个应该是服务器的 
     * @var array[bigint] 这个是个很长的数字
     */
    protected $msg_id = [];
    
    /**
     * @var Curl对象 $curl
     */
    protected $curl;
    
    /**
     * 构造方法
     * @param string $cookie_path
     */
    public function __construct($cookie_path = false)
    {
        $this->client_msg_id = time().rand(100,999);
        $this->curl = new \PhpWechat\WebWechat\WebWechatCurl($cookie_path);
    }
    
    
    /**
     * 设置cookie存放目录
     * @param type $path
     */
    public function set_cookie_path($path)
    {
        $this->curl->set_cookie_path($path);
    }
    
    /**
     * 设置二维码存放目录
     * @param type $path
     */
    public function set_qrcode_path($path)
    {
        if(!is_dir($path))
        {
            mkdir($path, '0777', true);
        }
        $this->qrcode_path = $path;
    }
    
    /**
     * 根据群id 获取群的名称 主要用于判断消息是不是来自群
     * @param type $group_id
     * @return type
     */
    public function get_group_name($group_id)
    {
        if(strpos($group_id, '@@') === 0)
        {
            return isset($this->group_id[$group_id])?$this->group_id[$group_id]:'未知';
        }
        return false;
    }
    
    /**
     * 读取下首页 天知道首页是不是有啥必要的cookie
     */
    public function get_index() {
        $url = 'https://wx.qq.com/';
        $this->curl->set_cookie_file_path(time());
        $this->curl->curl_get($url);
    }
    
    
    /**
     * 获取二维码参数
     * @return string
     */
    public function get_qrcode_code() {
        $url = "https://login.wx.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_=1477640330239";
        $data = $this->curl->curl_get($url);
        return WebWechatHelper::get_uuid($data,false);
    }

    /**
     * 获取二维码图片 保存到本地
     * @param type $uuid
     * @return 文件路径
     */
    public function get_qrcode($uuid) {
        $url = "https://login.weixin.qq.com/qrcode/" . $uuid;
        $file = $this->qrcode_path.time().'.png';
        file_put_contents($file, $this->curl->curl_get($url));
        return $file;
    }
    
    /**
     * 生成扫码的二维码
     */
    public function generate_qrcode($uuid)
    {
        $url = "https://login.weixin.qq.com/l/{$uuid}";
        include_once('qrcode/qrlib.php'); 

        // generating 
        $text = \QRcode::text($url);

        return $text;
    }

    /**
     * 获取登录状态
     * @param type $uuid
     * @return boolean
     */
    public function get_login_status($uuid) {
        $time = ~time();
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid={$uuid}&tip=1&r={$time}&_=1477640330239";
        $data = $this->curl->curl_get($url,false);
        $reg = '/"(.*?)"/';
        preg_match_all($reg, $data, $result);

        if (isset($result[1][0])) {
            return $result[1][0];
        }
        return false;
    }
    
    /**
     * 返回登录的用户
     * @return array
     */
    public function get_user()
    {
        return $this->user_data;
    }
    
    

    /**
     * 获取登录后的参数
     */
    public function get_login_success_code($url) {
        $data = $this->curl->curl_get($url,false);
        $this->login_success_data = (array) simplexml_load_string($data);
        return $this->login_success_data;
    }
    
    /**
     * 初始化微信
     */
    public function get_wxinit() {
        $time = -2147483647 + time();
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?r={$time}&lang=zh_CN&pass_ticket=" . ($this->login_success_data['pass_ticket']);

        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
        ]];
        
        $data = $this->curl->get_request_payload($url, $post,false);
        $this->synckey = '';
        $syn = json_decode($data, true);
        $this->user_data = $syn['User'];
        $this->contact_list = $syn['ContactList'];
        $group_list = $syn['ContactList'];
        $this->subscribe_msg_count = isset($syn['ContactList']['MPSubscribeMsgCount'])?$syn['ContactList']['MPSubscribeMsgCount']:0;
        $this->wx_system_time = $syn['SystemTime'];
        unset($group_list['Count']);
        unset($group_list['GrayScale']);
        unset($group_list['InviteStartCount']);
        unset($group_list['MPSubscribeMsgCount']);
        
        foreach($group_list as $item)
        {
            $this->group_list[$item['UserName']] = $item;
            $this->group_id[$item['UserName']] = $item['NickName'];
        }
        
        foreach ($syn['SyncKey']['List'] as $key => $item) {
            $synckey[] = $item['Key'] . '_' . $item['Val'];
        }
        
        if(!isset($synckey))
        {
            throw new Exception('初始化失败');;
        }
        
        $this->SyncKeyArr = $syn['SyncKey'];
        $this->synckey = implode('|', $synckey);
        return $data;
    }
    
    /**
     * 获取群成员列表
     * @param string $group_id 群组的id
     **********************************
     * 这里看参数应该可以传入一个数据 来获取一组的数据  不确定 等确认了 在改下
     **********************************
     */
    public function group_list($group_id)
    {
        $time = time();
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?type=ex&r={$time}658";
        $post = ['BaseRequest' => [
            'Uin' => $this->login_success_data['wxuin'],
            'Skey' => $this->login_success_data['skey'],
            'Sid' => $this->login_success_data['wxsid'],
            'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
        ]];
        $post['List'] = [['ChatRoomId'=>"",'UserName'=>$group_id]];
        $post['Count'] = 1;
        $data = json_decode($this->curl->get_request_payload($url, $post),true);
        $this->group_id[$group_id] = $data['ContactList'][0]['NickName'];
        $this->group_list[$group_id] = $data['ContactList'][0];
    }
    
    /**
     * 获取群用户信息
     */
    public function get_group_user_list()
    {
        foreach($this->group_list as $key=>$item)
        {
            $this->get_group_user_info($key);
        }
    }
    
    /**
     * 获取群用户信息具体实现方法 获取单群的用户   一下子获取所有容易卡死
     * @param string $group_id 群id
     * @return boolean
     */
    public function get_group_user_info($group_id = false)
    {
        if(isset($this->group_user_list[$group_id]))
        {
            return true;
        }
        
        if(WebWechatHelper::is_group_id($group_id) === false)
        {
            return  false;
        }
        
        if(!isset($this->group_list[$group_id]))
        {
            $this->group_list($group_id);
        }
        
        $group = $this->group_list[$group_id];
        $post = ['BaseRequest' => [
            'Uin' => $this->login_success_data['wxuin'],
            'Skey' => $this->login_success_data['skey'],
            'Sid' => $this->login_success_data['wxsid'],
            'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
        ]];

        if(isset($group['MemberList']) and count($group['MemberList']) >= 1)
        {
            $num = 1;
            $group_num = 1;
            //整理一下 50个一组
            $user_list = [];
            foreach($group['MemberList'] as $grou_list)
            {
                $user_list[$group_num][] = [
                    'EncryChatRoomId'=>$group['UserName'],
                    'UserName'=>$grou_list['UserName']
                ];
                if(count($user_list[$group_num]) >= 50)
                {
                    $group_num++;
                }
            }

            foreach($user_list as $group_num=>$group_user)
            {
                $post['List'] = $group_user;
                $post['Count'] = count($group_user);
                $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?type=ex&r=1478058837197&lang=zh_CN";
                $data = json_decode($this->curl->get_request_payload($url, $post),true);
                $this->_error($data,true,'获取群用户信息 ');
                foreach($data['ContactList'] as $group_user_info)
                {
                    $this->group_user_list[$group['UserName']]
                    [$group_user_info['UserName']] = $group_user_info;
                }
            }
        }
    }
    
    /**
     * 获取微信通知状态
     * @param type $to_name 如果没有 参数就是自己的id  否则是其他的id 
     */
    public function get_wx_status_notify($to_name = false)
    {
        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
            ],
            'ClientMsgId' => $this->client_msg_id,
            'code'=>$to_name?1:3,
            'FromUserName'=>$this->user_data['UserName'],
            'ToUserName' => $to_name?$to_name:$this->user_data['UserName']
        ];
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxstatusnotify";
        $data = $this->curl->get_request_payload($url, $post,false);
        $result = json_decode($data,true);
        var_dump($result);
        $this->_error($result, true,'获取微信通知状态');
        $this->msg_id = $result['MsgID'];
    }
    
    /**
     * 获取好友列表
     */
    public function get_friend()
    {
        $time = time();
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact?r={$time}543&seq=0&skey={$this->login_success_data['skey']}";
        $json_data = json_decode($this->curl->curl_get($url),true);
        
        if(!isset($json_data['BaseResponse']) or $json_data['BaseResponse']['Ret'] != 0)
        {
            throw new Exception('获取好友列表失败');;
        }
        
        if(isset($json_data['MemberCount']))
        {
            $this->friend_num = $json_data['MemberCount'];
        }
        
        if(isset($json_data['MemberList']))
        {
            foreach($json_data['MemberList'] as $item)
            {
                $this->friend[$item['UserName']] = $item;
            }
        }
    }
    
    /**
     * 获取好友信息  如果没参数 就是全部好友
     * @param type $username
     * @return boolean
     */
    public function get_friend_info($username = false)
    {
        if(false === $username)
        {
            return $this->friend;
        }
        
        if(isset($this->friend[$username]))
        {
            return $this->friend[$username];
        }
        
        return false;
    }
    
    /**
     * 获取消息状态 是否有新消息   selector:"0"  selector:"2"  selector:"7"
     * @return string
     */
    public function get_msg_status() {
        $deviceid = 'e' . rand(100000000, 999999999) . rand(10000, 99999);
        $post = "r=" . time() . "384&skey={$this->login_success_data['skey']}&sid={$this->login_success_data['wxsid']}&uin={$this->login_success_data['wxuin']}&deviceid={$deviceid}&synckey={$this->synckey}";
        $url = "https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin/synccheck?" . $post;
        $data = $this->curl->curl_get($url);
        if(strpos($data, 'retcode:"1101"'))
        {
            return 9;
        }
        $strpos_7 = strpos($data, 'selector:"7"');
        if (false !== $strpos_7) {
            return 7;
        }
        return (strpos($data, 'selector:"2"') !== false) ? 2 : false;
    }
    
    /**
     * 获取消息
     * @return string
     */
    public function get_msg() {
        //post提交的数据
        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
            ],
            'SyncKey' => $this->SyncKeyArr,
            'rr' => ~time()
        ];
        
        //url的get参数
        $get = "sid={$this->login_success_data['wxsid']}&skey={$this->login_success_data['skey']}&lang=zh_CN&pass_ticket={$this->login_success_data['pass_ticket']}";
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsync?" . $get;
        $data = $this->curl->get_request_payload($url, $post);
        $this->synckey = '';
        $syn = json_decode($data, true);
        if(true !== $this->_error($syn,true,'获取微信消息'))
        {
            echo '获取一条消息失败';
        }
        
        $synckey = [];
        foreach ($syn['SyncKey']['List'] as $key => $item) {
            $synckey[] = $item['Key'] . '_' . $item['Val'];
        }
        $this->synckey = implode('|', $synckey);
        $this->SyncKeyArr = $syn['SyncKey'];
        return $data;
    }
    
    /**
     * 发送微信消息
     * @param string $to_user
     * @param string $msg
     * @return type
     */
    public function send_wx_msg($to_user,$msg) {
        $this->client_msg_id = WebWechatHelper::get_client_id();
        //post提交的数据
        $post = ['BaseRequest' => [
                'Uin' => $this->login_success_data['wxuin'],
                'Skey' => $this->login_success_data['skey'],
                'Sid' => $this->login_success_data['wxsid'],
                'DeviceID' => 'e' . rand(100000000, 999999999) . rand(10000, 99999)
            ],
            'Msg' => [
                "ClientMsgId"=>$this->client_msg_id,
                "Content"=>$msg,
                "FromUserName"=>$this->user_data['UserName'],
                "LocalID"=>$this->client_msg_id,//如果发送成功 这个服务器会原样返回  应该是主要判断是否发送成功的
                "ToUserName"=>$to_user,
                "Type"=>14
            ],
            'Scene' => 0
        ];
        
        $url = "https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg?lang=zh_CN&pass_ticket=" . $this->login_success_data['pass_ticket'];
        $data = $this->curl->get_request_payload($url, $post);
        if(true === $this->_error($data,true,'发送微信消息'))
        {
            return true;
        }
    }
    
    /**
     * 获取群用户信息
     * @param type $group_id
     */
    public function get_group_user($group_id,$user_id)
    {
        return isset($this->group_user_list[$group_id][$user_id])?$this->group_user_list[$group_id][$user_id]:['Nickname'=>'未知'];
    }
    
    /**
     * 判断是否有错
     * @param array $data  数据
     * @param boolean $error_echo 是否输出数据
     * @param string $msg 通知的消息
     * @return boolean
     * @throws Exception
     */
    protected function _error($data,$error_echo = true,$msg = '')
    {
        if(!isset($data['BaseResponse']['Ret']))
        {
            var_dump($data);
            return false;
        }
        
        if($data['BaseResponse']['Ret'] != 0)
        {
            if($error_echo)
            {
                 WebWechatHelper::echos($msg.' 返回错误:'.$data['BaseResponse']['ErrMsg']);
            }
            else
            {
                throw new Exception($msg.' 返回错误:'.$data['BaseResponse']['ErrMsg']);
            }
        }
        return true;
    }
}
