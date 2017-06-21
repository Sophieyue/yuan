<?php

use OSS\OssClient;
use OSS\Core\OssException;


class Oss{

    private $accessKeyId = "LTAInu1kluN51B6P";
    private $accessKeySecret = "yLXkTzpVbtY6zXuw5fxsmYISwWpVIm";
    private $protocol = "http://";
    private $endpoint = "oss-cn-qingdao.aliyuncs.com";
    private $bucket = "gkresource";


    function upload($object,$content){ 
        $url = $this->protocol .$this->endpoint;
        $oss_url = '';
        try{
          $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $url);
          $ossClient->setTimeout(3600);
          $ossClient->setConnectTimeout(10);
          $ossClient->putObject($this->bucket, $object, $content);
          $oss_url = $this->protocol ."" .$this->bucket ."." .$this->endpoint ."/" .$object;
        } catch (OssException $e) {
          print $e->getMessage();
        }
        return $oss_url;
   }
}

