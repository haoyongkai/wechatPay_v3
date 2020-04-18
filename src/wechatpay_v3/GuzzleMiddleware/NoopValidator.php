<?php

/**
 * 下载平台证书
 */
namespace wechatpay_v3\GuzzleMiddleware;

use wechatpay_v3\GuzzleMiddleware\Validator;

class NoopValidator implements Validator
{
    public function validate(\Psr\Http\Message\ResponseInterface $response)
    {
        return true;
    }
}