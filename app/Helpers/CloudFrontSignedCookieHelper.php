<?php

namespace App\Helpers;
use App\Models\MobileApp;
use Request;

class CloudFrontSignedCookieHelper
{

    public static function setCookies()
    {
        $cloudFrontHost = config('cloud-front.CDN_HOST');
        $cloudFrontCookieExpiry = CloudFrontSignedCookieHelper::getTimeInterval();
        $customPolicy = '{"Statement":[{"Resource":"https://' . $cloudFrontHost .
            '/*","Condition":{"DateLessThan":{"AWS:EpochTime":' . $cloudFrontCookieExpiry . '}}}]}';
        $encodedCustomPolicy = CloudFrontSignedCookieHelper::url_safe_base64_encode($customPolicy);
        $customPolicySignature = CloudFrontSignedCookieHelper::getSignedPolicy(
            config('cloud-front.CLOUDFRONT_KEY_PATH'),
            $customPolicy
        );
        CloudFrontSignedCookieHelper::setCookie("CloudFront-Policy", $encodedCustomPolicy);
        CloudFrontSignedCookieHelper::setCookie("CloudFront-Signature", $customPolicySignature);
        CloudFrontSignedCookieHelper::setCookie("CloudFront-Key-Pair-Id", config('cloud-front.CLOUDFRONT_KEY_PAIR_ID'));
    }

    public static function setCookie($name, $val)
    {
        // using our own implementation because
        // using php setcookie means the values are URL encoded and then AWS CF fails
        $domain = config('cloud-front.COOKIES_DOMAIN');
        # SameSite=None; parameter added on temporary basis as a fix of PDF file loading only first page in browser(CHROME)

        header("Set-Cookie: $name=$val; path=/; domain=$domain; SameSite=None; secure; httpOnly", false);

        if(Request::header('platform') == MobileApp::IOS) {
            header ( "JP-Cookie: $name=$val; path=/; domain=$domain; secure; httpOnly", false );
        }
    }

    public static function getTimeInterval()
    {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        // add one day time inteval
        $dt->add(new \DateInterval('P1D'));
        return $dt->format('U');
    }

    public static function url_safe_base64_encode($value)
    {
        $encoded = base64_encode($value);
        return str_replace(['+', '=', '/'], ['-', '_', '~'], $encoded);
    }

    public static function getSignedPolicy($private_key_filename, $policy)
    {
        $signature = CloudFrontSignedCookieHelper::rsa_sha1_sign($policy, $private_key_filename);
        $encoded_signature = CloudFrontSignedCookieHelper::url_safe_base64_encode($signature);
        return $encoded_signature;
    }

    public static function rsa_sha1_sign($policy, $private_key_filename)
    {
        $signature = "";
        openssl_sign($policy, $signature, file_get_contents($private_key_filename));
        return $signature;
    }
}
