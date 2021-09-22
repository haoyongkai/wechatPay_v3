<?php
/**
 * 电商收付通 相关接口封装
 * @author haoyongkai
 * @weixin hyk585858
 * @time 2020-03-17
 * 开发文档 参考 https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/pages/ecommerce.shtml
 * 
 * 电商收付通简介：
 * 电商收付通是微信支付专为电商行业场景打造的支付、结算解决方案。
 * 电商平台的平台商户入驻微信支付成为二级商户。
 * 电商收付通支持将多个二级商户的订单进行合单支付（如电商购物车中的多笔订单合并支付），
 * 合单支付款项分别进入到二级商户各自的账户（资金为冻结状态，可用于实现二级商户账期）；
 * 电商平台在满足业务流程条件下（如确认收货等），可将二级商户的冻结状态的资金解冻，并收取平台佣金。
 * 
 * 功能介绍
 * 电商收付通API能力如下：
 * ● 二级商户入驻：通过该接口将电商平台的二级商户入驻成为微信支付二级商户；
 * ● 合单支付：通过该接口可以实现多商户（最多50个二级商户）商品同时支付的场景；通过在该接口传入需要分账的标识，交易资金分别进入到各二级商户账户中并处于冻结状态，系统默认冻结180天；
 * ● 分账：通过分账接口，根据实际业务场景将交易款项分账到其他业务参与方的账户(如：平台抽取佣金)，目前默认最高分账比例30%；同时通过该接口，实现合单交易冻结资金的解冻，从而实现对二级商户的账期；
 * ● 补差：针对电商平台补贴场景，通过补差接口在分账前将补贴款转入二级商户账户，统一进行分账；
 * ● 退款：通过退款接口将支付款退还给买家；
 * ● 余额查询与提现：帮助二级商户发起进行账户余额提现与查询申请，完成账户余额提现；不建议电商平台利用限制提现进行账期控制，特殊情况下商户可直接到微信支付进行提现，故而造成账期控制无效，账期控制详见分账介绍；
 * ● 账单：电商平台帮助二级商户获取微信支付账单。
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;

class  ECbill{

   /**
    * 客户端
    */
   protected $client;
   /**
   * Constructor
   */
    public function __construct()
    {
      // 商户配置
      $merchantId = WxPayConfig::MCHID;
      $merchantSerialNumber = WxPayConfig::MCHSERNUM;
      $merchantPrivateKey = PemUtil::loadPrivateKey(WxPayConfig::SSLKEY_PATH);
      $wechatpayCertificate = PemUtil::loadCertificate(WxPayConfig::SSLCERT_PLAT_PATH);

      // 构造一个WechatPayMiddleware
      $wechatpayMiddleware = WechatPayMiddleware::builder()
                              ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
                              ->withWechatPay([ $wechatpayCertificate ]) // 可传入多个微信支付平台证书，参数类型为array
                              ->build();

      // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
      $stack = \GuzzleHttp\HandlerStack::create();
      $stack->push($wechatpayMiddleware, 'wechatpay_v3');
      // 创建Guzzle HTTP Client时，将HandlerStack传入
      $this->client = new \GuzzleHttp\Client(['handler' => $stack]);
    }

    /**
     * 接口名称  申请交易账单API
     * 参数说明
     * @param $form_params
     * @return string
     * 接口说明
     *适用对象：电商平台 服务商 直连商户
     *请求URL：https://api.mch.weixin.qq.com/v3/bill/tradebill
     *请求方式：GET
     *接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function tradebill($bill_date, $sub_mchid = '', $bill_type = '', $tar_type = ''){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']];
        try {
            $url = 'https://api.mch.weixin.qq.com/v3/bill/tradebill?bill_date='.$bill_date;
            if ($sub_mchid) {
                $url = $url.'&sub_mchid='.$sub_mchid;
            }
            if ($bill_type) {
                $url = $url.'&bill_type='.$bill_type;
            }
            if ($tar_type) {
                $url = $url.'&tar_type='.$tar_type;
            }
            $resp = $this->client->request('GET', $url,
                ['headers' =>$headers]);
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        }
    }

    /**
     * 接口名称  申请资金账单API
     * 参数说明
     * @param $form_params
     * @return string
     * 接口说明
     *适用对象：电商平台 服务商 直连商户
     *请求URL：https://api.mch.weixin.qq.com/v3/bill/fundflowbill
     *请求方式：GET
     *接口规则：https://wechatpay-api.gitbook.io/wechatpay-api-v3
     */
    public function fundflowbill($bill_date, $account_type = '', $tar_type = ''){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']];
        try {
            $url = 'https://api.mch.weixin.qq.com/v3/bill/fundflowbill?bill_date='.$bill_date;
            if ($account_type) {
                $url = $url.'&account_type='.$account_type;
            }
            if ($tar_type) {
                $url = $url.'&tar_type='.$tar_type;
            }
            $resp = $this->client->request('GET', $url,
                ['headers' =>$headers]);
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        }
    }

    /**
     * 接口名称 下载账单API
     *
     */
    public function billdownload($download_url){
        // 接下来，正常使用Guzzle发起API请求，WechatPayMiddleware会自动地处理签名和验签
        $headers = ['Content-type' => 'application/json;','Accept' => 'application/json',
            'User-Agent' => $_SERVER['HTTP_USER_AGENT']];
        try {
            $resp = $this->client->request('GET', $download_url,
                ['headers' =>$headers]);
            return $resp->getBody()->getContents();
        }catch (\GuzzleHttp\Exception\RequestException $e){
            \tool\Commontool::error_log($e->getCode(), $e);
            return $e->getResponse()->getBody()->getContents();
        }
    }
}






