<?php

class WeiXinController extends \BaseController {

	const TOKEN = 'test_weixin';

	public function index(){
        $echostr=Input::get('echostr');
		if (!isset($echostr)) {
			$this->responseMsg();
		}else{
			$this->valid();
		}
	}

	// public function get_access_token(){
	// 	$access_token=file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::APPID.'&secret='.self::APPSCRET);
	// 	$access_token=json_decode($access_token,true)['access_token'];
	// 	return $access_token;
	// }

	//验证签名
	public function valid(){
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = self::TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr,SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }
/*
    //响应消息
    public function responseMsg(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            $result = $this->receiveText($postObj);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

    //接收文本消息
    private function receiveText($object){
        $keyword = trim($object->Content);
        //自动回复模式
        $content = "这是个文本消息,你之前输入的是： ".$keyword;
        //$content='<a href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx60a440035b3f92a4&redirect_uri=http://nibadeniba.sinaapp.com/oauth2.php&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect">点击这里体验</a>';
        //$content=JSAPI_TICKET;
        //$content='<a href="http://meng3test.sinaapp.com/sample.php?appscret='.APPSCRET.'&appid='.APPID.'">'.点击.'</a>';
        $result = $this->transmitText($object, $content);
        return $result;
    }
    //回复文本消息
    private function transmitText($object, $content){
        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
    */
}