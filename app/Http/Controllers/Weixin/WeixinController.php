<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Models\WX\WxUserModel;
use GuzzleHttp\Client;
class WeixinController extends Controller
{
    //处理首次接入get请求
    public function valid(){
        echo $_GET['echostr'];
    }
    public function atoken(){
        echo $this->getAccessToken();
    }
    // 接入微信事件推送POST
    public function wxEvent(){
        // 接收服务器推送
        $content=file_get_contents("php://input");
        $time=date("Y-m-d H:i:s");
        $str =$time.$content."\n";
        // 日志
        file_put_contents("logs/weixin_event.log",$str,FILE_APPEND);
        $data = simplexml_load_string($content);
        // echo 'ToUserName: '. $data->ToUserName;echo '</br>';        // 公众号ID
        // echo 'FromUserName: '. $data->FromUserName;echo '</br>';    // 用户OpenID
        // echo 'CreateTime: '. $data->CreateTime;echo '</br>';        // 时间戳
        // echo 'MsgType: '. $data->MsgType;echo '</br>';              // 消息类型
        // echo 'Event: '. $data->Event;echo '</br>';                  // 事件类型
        // echo 'EventKey: '. $data->EventKey;echo '</br>';
        $wx_id = $data->ToUserName;             // 公众号ID
        $openid = $data->FromUserName;          //用户OpenID
        $event = $data->Event;          //事件类型
        if($event='subscribe'){
            // 根据openid判断用户是否存在
            $local_user = WxUserModel::where(['openid'=>$openid])->first();
            if($local_user){
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wx_id.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['. '欢迎回来 '. $local_user['nickname'] .']]></Content></xml>';
            }else{
                // 获取用户信息
                $user =$this->getUserInfo($openid);
                // 用户信息入库
                $user_Info=[
                    'openid'=>$user['openid'],
                    'nickname'=>$user['nickname'],
                    'sex'=>$user['sex'],
                    'headimgurl'=>$user['headimgurl']
                ];
                $id = WxUserModel::insertGetId($user_Info);
                echo '<xml>
                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                <CreateTime>'.time().'</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA['. '欢迎关注 '. $user['nickname'] .']]>
                </Content>
                </xml>';
            }
        }
    }
    // 获取微信AccessToken
    public function getAccessToken(){
        // 是否有缓存
        $key = 'wx_access_token';
        $token = Redis::get($key);
        if($token){

        }else{
            // $url ="";
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response=file_get_contents($url);
            $arr=json_decode($response,true);
            // 缓存access_token
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
        }
        return $token;
    }
    // 获取微信用户信息
    public function getUserInfo($openid){
        // $openid='od-A-1FwnuCU3XUp3HU6wtIuDw48';
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
        $data = file_get_contents($url);
        $user = json_decode($data,true);
        return $user;
    }
}

// 3.1415926535897932384626