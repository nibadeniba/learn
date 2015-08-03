<?php

class WeiXinController extends \BaseController {

	const TOKEN="test_weixin";
	const APPSCRET="dabd90109fb83b901b4a8619216f35c0";
	const APPID='wx60a440035b3f92a4';

	public function index(){
        $echostr=Input::get('echostr');
		if (!isset($echostr)) {
			$this->responseMsg();
		}else{
			$this->valid();
		}
	}

	public function get_access_token(){
		$access_token=file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.APPID.'&secret='.APPSCRET);
		$access_token=json_decode($access_token,true)['access_token'];
		return $access_token;
	}

	//验证签名
	public function valid(){
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    //响应消息
    public function responseMsg(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
             
            //消息类型分离
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                case "subscribe":
                 	$result=kf_txt_replay($postObj->FromUserName,'hello');
					return $result;
					break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

    public function http_post($url,$data){
        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result=curl_exec($curl);
        if(curl_errno($curl)){
            return 'error'.curl_error($curl);
        }
        curl_close($curl);
        return $result;
    }

    //接收事件消息
    private function receiveEvent($object){
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注我 ";
                $content .= (!empty($object->EventKey))?("\n来自二维码场景 ".str_replace("qrscene_","",$object->EventKey)):"";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "SCAN":
                $content = "扫描场景 ".$object->EventKey;
                break;
            case "CLICK":
                $content = $object->EventKey;
                break;
            case "SCANCODE_PUSH":
                $content = $object->EventKey;
                break;
            case "VIEW":
                $content = $object->EventKey;
                break;
            default:
                $content = "receive a new event: ".$object->Event;
                break;
        }
        $result = $this->transmitText($object, $content);

        return $result;
    }

    //接收文本消息
    private function receiveText($object){
        $keyword = trim($object->Content);
        //自动回复模式
        //$content = "这是个文本消息,你之前输入的是： ".$keyword;
        $content='<a href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx60a440035b3f92a4&redirect_uri=http://nibadeniba.sinaapp.com/oauth2.php&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect">点击这里体验</a>';
        //$content=JSAPI_TICKET;
        //$content='<a href="http://meng3test.sinaapp.com/sample.php?appscret='.APPSCRET.'&appid='.APPID.'">'.点击.'</a>';
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收图片消息
    private function receiveImage($object){
        $content = array("MediaId"=>$object->MediaId);
        $arr[0]=['title'=>'one','Description'=>'1','PicUrl'=>'http://img3.redocn.com/20091221/20091217_fa2a743db1f556f82b9asJ320coGmYFf.jpg','Url'=>'www.baidu.com'];
        //$arr[1]=['title'=>'two','Description'=>'2','PicUrl'=>'http://pica.nipic.com/2007-11-09/2007119124513598_2.jpg','Url'=>'www.baidu.com'];
        $result = $this->transmitNews($object, $arr);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object){
        $content = "你发送的是位置，纬度为：".$object->Location_X."；经度为：".$object->Location_Y."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object){
        $content = array("MediaId"=>$object->MediaId);
        $result = $this->transmitVoice($object, $content);
        return $result;
    }

    //接收视频消息
    private function receiveVideo($object){
        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object){
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
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

    //回复图片消息
    private function transmitImage($object, $imageArray){
        $itemTpl = "<Image>
            <MediaId><![CDATA[%s]]></MediaId>
        </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[image]]></MsgType>
            $item_str
            </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray){
        $itemTpl = "<Voice>
		    <MediaId><![CDATA[%s]]></MediaId>
		</Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);

        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[voice]]></MsgType>
			$item_str
			</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray){
        $itemTpl = "<Video>
		    <MediaId><![CDATA[%s]]></MediaId>
		    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
		    <Title><![CDATA[%s]]></Title>
		    <Description><![CDATA[%s]]></Description>
		</Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[video]]></MsgType>
			$item_str
			</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray){
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "    <item>
		        <Title><![CDATA[%s]]></Title>
		        <Description><![CDATA[%s]]></Description>
		        <PicUrl><![CDATA[%s]]></PicUrl>
		        <Url><![CDATA[%s]]></Url>
		    </item>
		";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>%s</ArticleCount>
			<Articles>
			$item_str</Articles>
			</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray){
        $itemTpl = "<Music>
		    <Title><![CDATA[%s]]></Title>
		    <Description><![CDATA[%s]]></Description>
		    <MusicUrl><![CDATA[%s]]></MusicUrl>
		    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
		</Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[music]]></MsgType>
			$item_str
			</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复多客服消息
    private function transmitService($object){
        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[transfer_customer_service]]></MsgType>
			</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }
}
?>