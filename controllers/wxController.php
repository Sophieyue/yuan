<?php

class wxController
{
    function __construct() {}

    public function index()
    {
        echo "Hello";
        print_r(core::app()->params);
    }

    /**
     *
     *
     */
    public function post()
    {
        $db = new DB();
        $sql = "delete from wechat_tmplist where load = 1";
        $db->delete($sql);
        $select_sql = "select content_url as result from wechat_tmplist order by id asc limit 0,1";
        $count = $db->select($select_sql);
        if ($count > 0) {
            $url = $content_url;
            $update_sql = "update wechat_tmplist set load = 1 where content_url='" .$url ."'";
            $db->update($update_sql);
        } else {
            $select_min_biz_sql="select biz as result from (select * from wechat order by collect asc ) as tmp_weixin limit 1";
            $biz = $db->select($select_min_biz_sql);
            #$url = "http://mp.weixin.qq.com/mp/getmasssendmsg?__biz=".$biz."#wechat_webview_type=1&wechat_redirect";
            $url = "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=".$biz."&scene=124#wechat_redirect";
        }
        //echo "<script>setTimeout(function(){window.location.href='".$url."';},2000);</script>";
    }

    /**
     *
     *
     */
    public function his()
    {
        $db = new DB();
        $sql = "delete from wechat_tmplist where load = 1";
        $db->delete($sql);
        $select_sql = "select content_url as result from wechat_tmplist";
        $content_url = $db->select($select_sql);
        if ($content_url == 0) {
            $select_mix_biz_sql="select biz as result from (select * from wechat order by collect asc ) as tmp_weixin limit 1";
            $biz = $db->select($select_mix_biz_sql);
            #$url = "http://mp.weixin.qq.com/mp/getmasssendmsg?__biz=".$biz."#wechat_webview_type=1&wechat_redirect";//first
            $url = "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=".$biz."&scene=124#wechat_redirect";//second
            //update collect time of biz
            $time = time();
            echo "time---" .$time;
            $update_sql = "update wechat set collect=" .$time ." where biz='" .$biz . "'";
            $db->update($update_sql);
        } else {
            $url = $content_url;
            $update_tmplist_sql = "update wechat_tmplist set load = 1 where content_url='" .$url ."'";
            $db->update($update_tmplist_sql);
        }
        echo "<script>setTimeout(function(){window.location.href='".$url."';},2000);</script>";
    }

    /**
     *
     *
     */
    public function msgExt()
    {
        include 'DB.php';

        $db = new DB();
        $str = $_POST['str'];
        $url = $_POST['url'];
        parse_str(parse_url(htmlspecialchars_decode(urldecode($url)),PHP_URL_QUERY ),$query);
        $biz = $query['__biz'];
        echo $biz + "\n";
        $sn = $query['sn'];
        echo $sn + "\n";
        $json = json_decode($str,true);

        echo $json + "\n";

        $read_num = $json['appmsgstat']['read_num'];
        echo "read_num=" .$read_num  . "\n";

        $like_num = $json['appmsgstat']['like_num'];
        echo "like_num=" .$like_num  . "\n";

        $delete_sql = "delete from wechat_tmplist where content_url like '%" .$sn ."%'";
        $db->delete($delete_sql);

        $update_sql = "update wechat_post set readNum=" .$read_num ." and likeNum=" .$like_num ." where biz="  .$biz ." and content_url like '%" .$sn ."%'";
        echo $update_sql;
        $db->update($update_sql);

        exit(json_encode($msg));
    }

    /**
     *
     *
     */
    public function msgJson()
    {
        include 'oss.php';
        include 'DB.php';
        require 'lib/Readability.inc.php';
        require 'config.inc.php';

        $db = new DB();
        $str = $_POST['str'];
        $url = $_POST['url'];
        parse_str(parse_url(htmlspecialchars_decode(urldecode($url)),PHP_URL_QUERY ),$query);
        $biz = $query['__biz'];
        // find biz is exist in wechat
        $sql = "select * from wechat where biz='" .$biz . "'";
        $num = $db->isExist($sql);
        $time = time();
        if ($num < 1) {
           $sql_str = "insert into wechat(biz,collect) values ('" .$biz ."'," . $time .")";
           $db->insert($sql_str); 
        }
        $json = json_decode($str,true);
        if(!$json){
            $json = json_decode(htmlspecialchars_decode(urldecode($str)),true);
        }
        // interator deal
        foreach($json['list'] as $k=>$v){
             $type = $v['comm_msg_info']['type'];
             $content_url = "";
             if($type == 49){
                 //图文消息地址
                 $content_url = str_replace("\\", "", htmlspecialchars_decode($v['app_msg_ext_info']['content_url']));
                 $is_multi = $v['app_msg_ext_info']['is_multi'];
                 $datetime = $v['comm_msg_info']['datetime'];
                 $title = $v['app_msg_ext_info']['title'];
                 $new_title = str_replace("&nbsp;","",$title);
                 $title_encode = urlencode($new_title);
                 // insert into tmplist
                 $sel_sql = "select * from wechat_tmplist where content_url='" .$content_url ."'";
                 $number = $db->isExist($sel_sql);
                 if($number < 1){
                     $insert_sql = "insert into wechat_tmplist(content_url) values('" .$content_url ."')";
                     $db->insert($insert_sql);
                 }
                 $query_sql = "select * from wechat_post where content_url='" . $content_url . "' and title_encode='" .$title_encode ."'";
                 $count = $db->isExist($query_sql);
              if($count < 1) {
                      $fileid = $v['app_msg_ext_info']['fileid'];
                      //summary
                      $digest = $v['app_msg_ext_info']['digest'];
                      //阅读原文链接
                      $source_url = str_replace("\\", "", htmlspecialchars_decode($v['app_msg_ext_info']['source_url']));
                      //封面图
                      $cover = str_replace("\\", "", htmlspecialchars_decode($v['app_msg_ext_info']['cover']));
        
        	      $insert_post_sql = "insert into wechat_post(biz,field_id,title,title_encode,digest,content_url,source_url,cover,is_multi,datetime,readNum,likeNum,position) values('" .$biz ."'," .$fileid .",'" .$new_title ."','" .$title_encode ."','" .$digest ."','" .$content_url  ."','" .$source_url ."','" .$cover ."'," .$is_multi  .","  .$datetime .",0,0,1)";
                      $db->insert($insert_post_sql);
                      #内容处理
                      $html = file_get_contents($content_url);
                      preg_match_all("/id=\"js_content\">(.*)<script/iUs",$html,$content,PREG_PATTERN_ORDER);
                      $content = "<div id='js_content'>".$content[1][0]; 
                      $content = str_replace("data-src","src",$content);
                      $content = str_replace("preview.html","player.html",$content);
                      $select_sql = "select id as result from wechat_post where content_url='" .$content_url ."'";
                      $id = $db->select($select_sql);
                      $dir = $_SERVER['DOCUMENT_ROOT'] ."/".$biz ;
                      $filename = $dir ."/" .$id .".html";
                      if(!file_exists($dir)) {
                          if(!mkdir($dir, 0777, true)) {
                              echo "error...";
                          }
                          //mkdir($dir,0777,true);
                          chmod($dir,0777);
                      }
                      //get image list,upload image,get oss image url,write file
                      $imgRe = "/<img(.*?)src=\"(.+?)\".*?\/>/";
                      preg_match_all($imgRe,$content,$img);
                      $image_old_url_array = $img[2];
                      foreach($image_old_url_array as $key=>$urlValue){ 
                          if (substr_compare($urlValue,"wx_fmt=png",-strlen("wx_fmt=png")) === 0){
                              $suffix = ".png";
                          }else {
                              $suffix = ".jpeg";
                          }
                          if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                              $object = "infoq/infoqchina/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                              $object = "infoq/archtime/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                              $object = "infoq/devopsgeek/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                              $object = "infoq/bornmobile/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                              $object = "infoq/cloudnote/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                              $object = "infoq/frontshow/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                              $object = "infoq/bigdatatina2016/" .$id ."_" .$key  .$suffix;
                          } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                              $object = "ego/egonetworks/" .$id ."_" .$key  .$suffix;
                          } else{
                              $object = "infoq/infoqchina/" .$id ."_" .$key  .$suffix;
                          }
                          $image_content = file_get_contents($urlValue);
                          //echo $image_content;
                          $o = new Oss();
                          $image_new_url = $o->upload($object,$image_content);
                          if ($image_new_url === ''){
                             echo "image oss new url is null...";
                          }else{
                             $content = str_replace($urlValue,$image_new_url,$content); 
                          }
                      }
        	         
        	      //标题的背景图background image
        	      preg_match_all('/background-image: url\(&quot;(.*?)&quot;\);/',$content,$matches);
        	      $images = $matches[1];
        	      $unique_background_url = array_unique($images);
        	      foreach($unique_background_url as $key=>$value){
                        if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                            $object = "infoq/infoqchina/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                            $object = "infoq/archtime/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                            $object = "infoq/devopsgeek/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                            $object = "infoq/bornmobile/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                            $object = "infoq/cloudnote/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                            $object = "infoq/frontshow/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                            $object = "infoq/bigdatatina2016/" .$id ."_bg_" .$key  .$suffix;
                        } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                            $object = "ego/egonetworks/" .$id ."_" .$key  .$suffix;
                        } else{
                            $object = "infoq/infoqchina/" .$id ."_bg_" .$key  .$suffix;
                        }
                        $image_content = file_get_contents($value);
                        $o = new Oss();
                        $image_new_url = $o->upload($object,$image_content);
                        if ($image_new_url === ''){
                           echo "image oss new url is null...";
                        }else{
                           $content = str_replace($value,$image_new_url,$content);
                        }
         
        	       }
        
        	       $bak = array_unique($background_img[1]); 
                       if (strpos($content,"video_iframe") != FALSE){
                         $type = 5; //interviews;
                       }else{
                         $type = 2;//articles           
                       }
                       $theme = "未设置";
                       $url = GR_URL ."/" .$biz ."/" .$id .".html"; 
                       $update_time = time();
                       if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                           $resource = "InfoQ";
                       } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                           $resource = "聊聊架构";
                       } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                           $resource = "高效开发运维";
                       } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                           $resource = "移动开发前线";
                       } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                           $resource = "细说云计算";
                       } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                           $resource = "前端之巅";
                       } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                           $resource = "大数据杂谈";
                       } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                           $resource = "EGONetworks";
                       } else{
                           $resource = "未知"; 
                       }
                       $release_date = date("Y年n月j日",$datetime);
        
                       $Readability = new Readability($content, "utf-8");
                       $data = $Readability->getContent();
                       $data_content = $data['content'];               
        	       $r_data_content = $db->mysql_real_escape_string_gk($data_content);
                       $insert_sql = "insert into wechat_info(theme,title,url,type,status,update_time,resource,summary,release_date,image_url,content_url,content,post_id,position) values('".$theme ."','" .$new_title ."','" .$url ."'," .$type .",1," .$update_time .",'" .$resource ."','" .$digest ."','" .$release_date ."','','" .$content_url ."','" .$r_data_content ."'," .$id .", 1)";
                       $db->insert($insert_sql); 
                       $file_w = fopen($filename, "w");
                       fwrite($file_w, $content);
                       fclose($file_w);
                     
                 }
                 if($is_multi == 1){//multi
                     foreach($v['app_msg_ext_info']['multi_app_msg_item_list'] as $kk=>$vv){
                         $content_url = str_replace("\\","",htmlspecialchars_decode($vv['content_url']));
                         $title = $vv['title'];
                         $new_title = str_replace("&nbsp;","",$title);
                         $title_encode = urlencode($new_title);
        		 
                         $query = "select * from wechat_post where content_url='" . $content_url . "' and title_encode='" .$title_encode ."'";
                         $count_1 = $db->isExist($query);
                         if($count_1 < 1){
                             $sel_sql = "select * from wechat_tmplist where content_url='" .$content_url ."'";
                             $number = $db->isExist($sel_sql);
                             if($number < 1){ 
                                 $insert_sql = "insert into wechat_tmplist(content_url) values('" .$content_url ."')";
                                 $db->insert($insert_sql);
                             }
                             $fileid = $vv['fileid'];
                             $digest = htmlspecialchars($vv['digest']);
                             $source_url = str_replace("\\","",htmlspecialchars_decode($vv['source_url']));
                             $cover = str_replace("\\","",htmlspecialchars_decode($vv['cover']));
                             $position = $kk + 2;
                             $insert_post_sql = "insert into wechat_post(biz,field_id,title,title_encode,digest,content_url,source_url,cover,is_multi,datetime,readNum,likeNum,position) values('" .$biz ."'," .$fileid .",'" .$new_title ."','" .$title_encode ."','" .$digest ."','" .$content_url  ."','" .$source_url ."','" .$cover ."'," .$is_multi  .","  .$datetime .",0,0," .$position .")";
                             $db->insert($insert_post_sql);
          
                             $html = file_get_contents($content_url);
                             preg_match_all("/id=\"js_content\">(.*)<script/iUs",$html,$content,PREG_PATTERN_ORDER);
                             $content = "<div id='js_content'>".$content[1][0];
                             $content = str_replace("data-src","src",$content);
                             $content = str_replace("preview.html","player.html",$content);
                             $select_sql = "select id as result from wechat_post where content_url='" .$content_url ."'";
                             $id = $db->select($select_sql);
                             $dir = $_SERVER['DOCUMENT_ROOT'] ."/".$biz ;
                             $filename = $dir ."/" .$id .".html";
                             if(!file_exists($dir)) {
                                 if(!mkdir($dir, 0777, true)) {
                                     echo "error...";
                                 }
                                 chmod($dir,0777);
                             }
                             //image replace
                             $imgRe = "/<img(.*?)src=\"(.+?)\".*?\/>/";
                             preg_match_all($imgRe,$content,$img);
                             $image_old_url_array = $img[2];
                             foreach($image_old_url_array as $key=>$urlValue){
                                 if (substr_compare($urlValue,"wx_fmt=png",-strlen("wx_fmt=png")) === 0){
                                     $suffix = ".png";
                                 }else {
                                     $suffix = ".jpeg";
                                 }
                                 if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                                     $object = "infoq/infoqchina/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                                     $object = "infoq/archtime/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                                     $object = "infoq/devopsgeek/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                                     $object = "infoq/bornmobile/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                                     $object = "infoq/cloudnote/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                                     $object = "infoq/frontshow/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                                     $object = "infoq/bigdatatina2016/" .$id ."_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                                     $object = "ego/egonetworks/" .$id ."_" .$key  .$suffix;
                                 } else{
                                     $object = "infoq/infoqchina/" .$id ."_" .$key  .$suffix;
                                 }
                                 $image_content = file_get_contents($urlValue);
                                 $o = new Oss();
                                 $image_new_url = $o->upload($object,$image_content);
                                 if ($image_new_url === ''){
                                    echo "image oss new url is null...";
                                 }else{
                                    $content = str_replace($urlValue,$image_new_url,$content);
                                 }
                             }
        		         //background image replace
        	             preg_match_all('/background-image: url\(&quot;(.*?)&quot;\);/',$content,$matches);
        	             $images = $matches[1];
        	             $unique_background_url = array_unique($images);
        	             foreach($unique_background_url as $key=>$value){
                                 if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                                     $object = "infoq/infoqchina/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                                     $object = "infoq/archtime/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                                     $object = "infoq/devopsgeek/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                                     $object = "infoq/bornmobile/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                                     $object = "infoq/cloudnote/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                                     $object = "infoq/frontshow/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                                     $object = "infoq/bigdatatina2016/" .$id ."_bg_" .$key  .$suffix;
                                 } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                                     $object = "ego/egonetworks/" .$id ."_" .$key  .$suffix;
                                 } else{
                                     $object = "infoq/infoqchina/" .$id ."_bg_" .$key  .$suffix;
                                 }
                               $image_content = file_get_contents($value);
                               $o = new Oss();
                               $image_new_url = $o->upload($object,$image_content);
                               if ($image_new_url === ''){
                                  echo "image oss new url is null...";
                               }else{
                                  $content = str_replace($value,$image_new_url,$content);
                               }
                         }


                             if (strpos($content,"video_iframe") != FALSE){
                               $type = 5; //interviews;
                             }else{
                               $type = 2;//articles           
                             }
                             $theme = "未设置";
                             $url = GR_URL ."/" .$biz ."/" .$id .".html"; 
                             //$url = "http://resource.geekbang.org/" .$biz ."/" .$id .".html"; 
                             $update_time = time();
                             if ( $biz == 'MjM5MDE0Mjc4MA==' ) {
                                 $resource = "InfoQ";
                             } elseif ( $biz == 'MzA5Nzc4OTA1Mw==') {
                                 $resource = "聊聊架构";
                             } elseif ( $biz == 'MzIzNjUxMzk2NQ==') {
                                 $resource = "高效开发运维";
                             } elseif ( $biz == 'MzA3ODg4MDk0Ng==') {
                                 $resource = "移动开发前线";
                             } elseif ( $biz == 'MzI4MjE3MTcwNA==') {
                                 $resource = "细说云计算";
                             } elseif ( $biz == 'MzIwNjQwMzUwMQ==') {
                                 $resource = "前端之巅";
                             } elseif ( $biz == 'MzA5NzkxMzg1Nw==') {
                                 $resource = "大数据杂谈";
                             } elseif ( $biz == 'MzA4NTU2MTg3MQ==') {
                                 $resource = "EGONetworks";
                             } else{
                                 $resource = "未知"; 
                             }


                             $release_date = date("Y年n月j日",$datetime);
                             $Readability = new Readability($content, "utf-8");
                             $data = $Readability->getContent();
                             $data_content = $data['content'];
                             $r_data_content = $db->mysql_real_escape_string_gk($data_content); 
                             $insert_sql = "insert into wechat_info(theme,title,url,type,status,update_time,resource,summary,release_date,image_url,content_url,content,post_id,position) values('".$theme ."','" .$new_title ."','" .$url ."'," .$type .",1," .$update_time .",'" .$resource ."','" .$digest ."','" .$release_date ."','','" .$content_url ."','" .$r_data_content ."'," .$id ."," .$position .")";
                             $db->insert($insert_sql); 
                             $file = fopen($filename, "w");
                             fwrite($file, $content);
                             fclose($file);
                         }
        
                     }
                 }
              }
        }
    }
}

