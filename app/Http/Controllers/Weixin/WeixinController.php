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
        $xml_obj = simplexml_load_string($content);
        // echo 'ToUserName: '. $data->ToUserName;echo '</br>';        // 公众号ID
        // echo 'FromUserName: '. $data->FromUserName;echo '</br>';    // 用户OpenID
        // echo 'CreateTime: '. $data->CreateTime;echo '</br>';        // 时间戳
        // echo 'MsgType: '. $data->MsgType;echo '</br>';              // 消息类型
        // echo 'Event: '. $data->Event;echo '</br>';                  // 事件类型
        // echo 'EventKey: '. $data->EventKey;echo '</br>';
        $wx_id = $xml_obj->ToUserName;             // 公众号ID
        $openid = $xml_obj->FromUserName;          //用户OpenID
        $event = $xml_obj->Event;                  //事件类型
        $msg_type = $xml_obj->MsgType;             // 消息类型
        if($event=='subscribe'){
            // 根据openid判断用户是否存在
            $local_user = WxUserModel::where(['openid'=>$openid])->first();
            if($local_user){
                echo '<xml>
                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                <CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA['. '欢迎回来 '. $local_user['nickname'] .']]></Content>
                </xml>';
            }else{
                // 获取用户信息
                $user =$this->getUserInfo($openid);
                // print_r($user) ;die;
                // 用户信息入库
                $user_Info=[
                    'openid'=>$user['openid'],
                    'nickname'=>$user['nickname'],
                    'sex'=>$user['sex'],
                    'headimgurl'=>$user['headimgurl']
                ];
                $id = WxUserModel::insert($user_Info);
                echo '<xml>
                <ToUserName><![CDATA['.$openid.']]></ToUserName>
                <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                <CreateTime>'.time().'</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA['. '欢迎关注 '. $user['nickname'] .']]></Content>
                </xml>';
            }
        }elseif($msg_type=='text'){
            if(strpos($xml_obj->Content,"+天气")){
                $city=explode('+',$xml_obj->Content)[0];
                // echo "City : ".$city;
                $url = "https://free-api.heweather.net/s6/weather/now?key=HE1904161042411866&location=".$city;
                $arr = json_decode(file_get_contents($url),true);
                // echo '<pre>';print_r($arr);echo "</pre>";               
                if($arr['HeWeather6'][0]['status']=='ok'){
                    $fl = $arr['HeWeather6'][0]['now']['fl'];               //摄氏度
                    $wind_dir = $arr['HeWeather6'][0]['now']['wind_dir'];   //风向
                    $wind_sc = $arr['HeWeather6'][0]['now']['wind_sc'];     //风力
                    $hum = $arr['HeWeather6'][0]['now']['hum'];             //湿度
                    $str="城市 : $city \n"."摄氏度 : $fl \n"."风向 : $wind_dir \n"."风力 : $wind_sc \n"."湿度 : $hum \n";
    
                    $response_xml='<xml>
                    <ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA['.$str.']]></Content>
                    </xml>';
                }else{
                    $response_xml='<xml>
                    <ToUserName><![CDATA['.$openid.']]></ToUserName>
                    <FromUserName><![CDATA['.$wx_id.']]></FromUserName>
                    <CreateTime>'.time().'</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA["城市名不正确"]]></Content>
                    </xml>';
                }
            }
            return $response_xml;
        }
    }
    // 获取微信AccessToken
    public function getAccessToken(){
        // 是否有缓存
        $key = 'wx_access_token';
        $token = Redis::get($key);
        if($token){

        }else{
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
    // 创建自定义菜单
    public function createMenu(){
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();

    }
    // 群发消息
    public function send(){
        $user_Info = WxUserModel::all()->toArray();
        // echo "<pre>";print_r($user_Info);echo "</pre>";
        $openid_arr = array_column($user_Info,'openid');
        // echo "<pre>";print_r($openid_arr);echo "</pre>";
        $content = "Nice 兄dei";
        $response = $this->sendMsg($openid_arr,$content);
        echo $response;
    }
    // 群发消息
    public function sendMsg($openid_arr,$content){
        $msg = [
            "touser" => $openid_arr,
            "msgtype" => "text",
            "text" => ["content" => $content]
        ];
        $data =json_encode($msg,JSON_UNESCAPED_UNICODE);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->getAccessToken();

        $client = new Client;
        $response = $client->request("post",$url,['body' => $data]);

        return  $response -> getBody();
    }
}