<?php
/**
 * 电商收付通 相关接口封装
 * @author haoyongkai
 * @time 2020-03-17
 * 开发文档 参考 https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/pages/ecommerce.shtml
 */
namespace wechatpay_v3;

use wechatpay_v3\GuzzleMiddleware\WechatPayMiddleware;
use wechatpay_v3\GuzzleMiddleware\Util\PemUtil;
use wechatpay_v3\WxPayConfig;
use wechatpay_v3\GuzzleMiddleware\Auth\WechatPay2Credentials;
use wechatpay_v3\GuzzleMiddleware\Auth\PrivateKeySigner;
use Psr\Http\Message\RequestInterface;

class  Uploadimg{
    /**
    * Constructor
    */
     public function __construct(){}
    /**
     * v3图片上传接口
     * @author haoyongkai
     */
    public function uploadImg($filename)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/merchant/media/upload';
        $merchant_id = WxPayConfig::MCHID;
        $serial_no = WxPayConfig::MCHSERNUM;
        $mch_private_key = PemUtil::loadPrivateKey(WxPayConfig::SSLKEY_PATH);      //商户私钥

        $data['filename'] = 'ok.png';
        $meta['filename'] = 'ok.png';
        $meta['sha256'] = hash_file('sha256',$filename);
        $boundary = uniqid(); //分割符号
        $date = time();
        $nonce = $this->nonce_str();
        $sign = $this->sign($url,'POST',$date,$nonce,json_encode($meta),$mch_private_key,$merchant_id,$serial_no);//$http_method要大写
        $header[] = 'User-Agent:'.$_SERVER['HTTP_USER_AGENT'];
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 '.$sign;
		$header[] = 'Content-Type:multipart/form-data;boundary='.$boundary;

        $boundaryStr = "--{$boundary}\r\n";
        $out = $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="meta"'."\r\n";
        $out .= 'Content-Type: application/json'."\r\n";
        $out .= "\r\n";
        $out .= json_encode($meta)."\r\n";
        $out .=  $boundaryStr;
        $out .= 'Content-Disposition: form-data; name="file"; filename="'.$data['filename'].'"'."\r\n";
        $finfo = new \finfo(FILEINFO_MIME);
        $mime_type = $finfo->file($filename);
        $out .= 'Content-Type: '.$mime_type.';'."\r\n";
        $out .= "\r\n";
        $out .= file_get_contents($filename)."\r\n";
        $out .= "--{$boundary}--\r\n";
        $r = $this->doCurl($url,$out,$header);
        return $r;   
    }
    // HTTP Request
  


    private function nonce_str()
    {
        return date('YmdHis', time()) . rand(10000, 99999);
    }
    public function doCurl($url, $data , $header = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        //避免https 的ssl验证

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if (stripos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }


    //签名
    private function sign($url,$http_method,$timestamp,$nonce,$body,$mch_private_key,$merchant_id,$serial_no)
    {

        $url_parts = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message =
            $http_method."\n".
            $canonical_url."\n".
            $timestamp."\n".
            $nonce."\n".
            $body."\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $schema = 'WECHATPAY2-SHA256-RSA2048 ';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        return $token;
    }
}