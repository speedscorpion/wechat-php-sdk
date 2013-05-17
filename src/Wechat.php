<?php
/**
 * 微信公众平台 PHP SDK
 *
 * @author NetPuter <netputer@gmail.com>
 */

  /**
   * 微信公众平台处理类
   */
  class Wechat {

    /**
     * 以数组的形式保存微信服务器每次发来的请求
     *
     * @var array
     */
    private $request;

    /**
     * 初始化，判断此次请求是否为验证请求，并以数组形式保存
     *
     * @param string $token 验证信息
     */
    public function __construct($token) {
      if ($this->isValid() && $this->validateSignature($token)) {
        exit($_GET['echostr']);
      }

      $xml = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);

      $this->request = array_change_key_case($xml, CASE_LOWER);
      // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
    }

    /**
     * 判断此次请求是否为验证请求
     *
     * @return boolean
     */
    private function isValid() {
      return isset($_GET['echostr']);
    }

    /**
     * 判断验证请求的签名信息是否正确
     *
     * @param  string $token 验证信息
     * @return boolean
     */
    private function validateSignature($token) {
      $signature = $_GET['signature'];
      $timestamp = $_GET['timestamp'];
      $nonce = $_GET['nonce'];

      $signatureArray = array($token, $timestamp, $nonce);
      sort($signatureArray);

      return sha1(implode($signatureArray)) == $signature;
    }

    /**
     * 获取本次请求中的参数，不区分大小
     *
     * @param  string $param 参数名
     * @return mixed
     */
    protected function getRequest($param = FALSE) {
      if ($param === FALSE) {
        return $this->request;
      }

      $param = strtolower($param);

      if (isset($this->request[$param])) {
        return $this->request[$param];
      }

      return NULL;
    }

    /**
     * 用户关注时时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onSubscribe() {}

    /**
     * 用户取消关注时时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onUnsubscribe() {}

    /**
     * 收到文本消息时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onText() {}

    /**
     * 收到图片消息时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onImage() {}

    /**
     * 收到地理位置消息时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onLocation() {}

    /**
     * 收到链接消息时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onLink() {}

    /**
     * 收到未知类型消息时触发，用于子类继承或重写
     *
     * @return void
     */
    protected function onUnknown() {}

    /**
     * 回复文本消息
     *
     * @param  string  $content  消息内容
     * @param  integer $funcFlag 设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseText($content, $funcFlag = 0) {
      exit(new TextResponse($this->getRequest('fromusername'), $this->getRequest('tousername'), $content, $funcFlag));
    }

    /**
     * 回复音乐消息
     *
     * @param  string  $title       音乐标题
     * @param  string  $description 音乐描述
     * @param  string  $musicUrl    音乐链接
     * @param  string  $hqMusicUrl  高质量音乐链接，Wi-Fi 环境下优先使用
     * @param  integer $funcFlag    设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseMusic($title, $description, $musicUrl, $hqMusicUrl, $funcFlag = 0) {
      exit(new MusicResponse($this->getRequest('fromusername'), $this->getRequest('tousername'), $title, $description, $musicUrl, $hqMusicUrl, $funcFlag));
    }

    /**
     * 回复图文消息
     * @param  array   $items    由单条图文消息 NewsResponseItem() 组成的数组
     * @param  integer $funcFlag 设为1时星标刚才收到的消息
     * @return void
     */
    protected function responseNews($items, $funcFlag = 0) {
      exit(new NewsResponse($this->getRequest('fromusername'), $this->getRequest('tousername'), $items, $funcFlag));
    }

    /**
     * 分析消息类型，并分发给对应的函数
     *
     * @return void
     */
    public function run() {
      switch ($this->getRequest('msgtype')) {

        case 'event':
          switch ($this->getRequest('event')) {

            case 'subscribe':
              $this->onSubscribe();
              break;

            case 'unsubscribe':
              $this->onUnsubscribe();
              break;

          }

          break;

        case 'text':
          $this->onText();
          break;

        case 'image':
          $this->onImage();
          break;

        case 'location':
          $this->onLocation();
          break;

        case 'link':
          $this->onLink();
          break;

        default:
          $this->onUnknown();
          break;

      }
    }

  }

  /**
   * 用于回复的基本消息类型
   */
  abstract class WechatResponse {

    protected $toUserName;
    protected $fromUserName;
    protected $funcFlag;

    public function __construct($toUserName, $fromUserName, $funcFlag) {
      $this->toUserName = $toUserName;
      $this->fromUserName = $fromUserName;
      $this->funcFlag = $funcFlag;
    }

    abstract public function __toString();

  }

  /**
   * 用于回复的文本消息类型
   */
  class TextResponse extends WechatResponse {

    protected $content;

    protected $template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA[%s]]></Content>
  <FuncFlag>%s<FuncFlag>
</xml>
XML;

    public function __construct($toUserName, $fromUserName, $content, $funcFlag = 0) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);
      $this->content = $content;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        $this->content,
        $this->funcFlag
      );
    }

  }

  /**
   * 用于回复的音乐消息类型
   */
  class MusicResponse extends WechatResponse {

    protected $title;
    protected $description;
    protected $musicUrl;
    protected $hqMusicUrl;

    protected $template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[music]]></MsgType>
  <Music>
    <Title><![CDATA[%s]]></Title>
    <Description><![CDATA[%s]]></Description>
    <MusicUrl><![CDATA[%s]]></MusicUrl>
    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
  </Music>
  <FuncFlag>%s<FuncFlag>
</xml>
XML;

    public function __construct($toUserName, $fromUserName, $title, $description, $musicUrl, $hqMusicUrl, $funcFlag) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);
      $this->title = $title;
      $this->description = $description;
      $this->musicUrl = $musicUrl;
      $this->hqMusicUrl = $hqMusicUrl;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        $this->title,
        $this->description,
        $this->musicUrl,
        $this->hqMusicUrl,
        $this->funcFlag
      );
    }

  }

  /**
   * 用于回复的图文消息类型
   */
  class NewsResponse extends WechatResponse {

    protected $items = array();

    protected $template = <<<XML
<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[news]]></MsgType>
  <ArticleCount>%s</ArticleCount>
  <Articles>
    %s
  </Articles>
  <FuncFlag>%s<FuncFlag>
</xml>'
XML;

    public function __construct($toUserName, $fromUserName, $items, $funcFlag) {
      parent::__construct($toUserName, $fromUserName, $funcFlag);
      $this->items = $items;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->toUserName,
        $this->fromUserName,
        time(),
        count($this->items),
        implode($this->items),
        $this->funcFlag
      );
    }

  }

  /**
   * 单条图文消息类型
   */
  class NewsResponseItem {

    protected $title;
    protected $description;
    protected $picUrl;
    protected $url;

    protected $template = <<<XML
<item>
  <Title><![CDATA[%s]]></Title>
  <Description><![CDATA[%s]]></Description>
  <PicUrl><![CDATA[%s]]></PicUrl>
  <Url><![CDATA[%s]]></Url>
</item>
XML;

    public function __construct($title, $description, $picUrl, $url) {
      $this->title = $title;
      $this->description = $description;
      $this->picUrl = $picUrl;
      $this->url = $url;
    }

    public function __toString() {
      return sprintf($this->template,
        $this->title,
        $this->description,
        $this->picUrl,
        $this->url
      );
    }

  }