<?php
/**
 * WechatPay2Credentials
 * PHP version 5
 *
 * @category Class
 * @package  WechatPay
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */

namespace wechatpay_v3\GuzzleMiddleware\Auth;

use Psr\Http\Message\RequestInterface;
use wechatpay_v3\GuzzleMiddleware\Credentials;
use wechatpay_v3\GuzzleMiddleware\Auth\Signer;

/**
 * WechatPay2Credentials
 *
 * @category Class
 * @package  WechatPay\GuzzleMiddleware\Auth
 * @author   WeChat Pay Team
 * @link     https://pay.weixin.qq.com
 */
class WechatPay2Credentials implements Credentials
{

    /**
     * Merchant Id
     *
     * @var string
     */
    protected $merchantId;

    /**
     * signer
     *
     * @var Signer
     */
    protected $signer;

    /**
     * Constructor
     */
    public function __construct($merchantId, Signer $signer)
    {
        $this->merchantId = $merchantId;
        $this->signer = $signer;
    }

    /**
     * Get schema of credentials
     *
     * @return string
     */
    public function getSchema()
    {
        return 'WECHATPAY2-SHA256-RSA2048';
    }

    /**
     * Get token of credentials
     *
     * @param RequestInterface $request Api request
     *
     * @return string
     */
    public function getToken(RequestInterface $request)
    {
        $nonce = $this->getNonce();
        $timestamp = $this->getTimestamp();

        $message = $this->buildMessage($nonce, $timestamp, $request);
        
        $signResult = $this->signer->sign($message);
        $sign = $signResult->getSign();
        $serialNo = $signResult->getCertificateSerialNumber();

        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->merchantId, $nonce, $timestamp, $serialNo, $sign
        );
        return  $token;
    }
    /**
     * 
     */
    //签名
    public function getSign($appId, $timestamp, $nonce, $package)
    {
        $message = $appId."\n".
            $timestamp."\n".
            $nonce."\n".
            $package."\n";
        
        $signResult = $this->signer->sign($message);
        $sign = $signResult->getSign();
        return $sign;
    }


    /**
     * Get Merchant Id
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Get sign timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return \time();
    }

    /**
     * Get nonce
     *
     * @return string
     */
    public function getNonce()
    {
        static $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 32; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Build message to sign
     *
     * @param string            $nonce      Nonce string
     * @param integer           $timestamp  Unix timestamp
     * @param RequestInterface  $request    Api request
     *
     * @return string
     */
    protected function buildMessage($nonce, $timestamp, RequestInterface $request)
    {
        $body = '';
        $bodyStream = $request->getBody();
        // TODO: handle non-seekable stream
        if ($bodyStream->isSeekable()) {
            $body = (string)$bodyStream;
            $bodyStream->rewind();
        }
        
        return $request->getMethod()."\n".
            $request->getRequestTarget()."\n".
            $timestamp."\n".
            $nonce."\n".
            $body."\n";
    }
}
