<?php
header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
chdir(__DIR__.'/../../');

_log("post");

//$ret = 0;
//system('bash -c "/usr/bin/ffplay -nodisp -showmode 0 -t 0.35 -autoexit /home/mediamead/aura-made/sound_camera.wav </dev/null >>/tmp/aura.log 2>&1 &"', $ret);

$id = date('Ymd-His');
$f=capture_image($id);

ob_start();
$res = upload_image($id,$f);
echo("res: ".$res."\n");
process_image($f);

_log(ob_get_contents());
ob_end_flush();

_log('',true); //close log

function capture_image($id) {
  ob_start();
  
  $fs = "images/${id}_src.jpg";
  $f  = "images/$id.jpg";
  $ret = 0;
  system('/home/mediamead/aura-made/numlockx.sh on');
  system("ffmpeg -f v4l2 -video_size 1280x960 -i /dev/video0 -ss 2.5 -frames 1 temp/output%03d.png",$ret);
  system('/home/mediamead/aura-made/numlockx.sh off');
//  system("gphoto2 --capture-image-and-download --filename ".$fs, $ret);
  if ($ret!=0) {
    _log(ob_get_contents());
    _log("ERROR capturing!");
    ob_end_flush();

    return false;
  }
//  rename("output001.jpg", $fs);

  $cmd = "convert temp/output001.png -crop 900x960+108+64 $fs";

//  $cmd = "convert $fs -geometry 1280x1024 $f";  
//  $cmd = "convert $fs -crop 2736x2188+456+120 -geometry 1280x1024 $f";
//  $cmd = "convert $fs -crop 2736x2000+456+220 -geometry 1280x1024 $f";

  system($cmd,$ret);
  if ($ret!=0) {
    _log(ob_get_contents());
    _log("ERROR converting!");
    ob_end_flush();
    return false;
  }
  ob_end_clean();

  return $fs;
}

function upload_image($id,$f) {
  $f = realpath($f);

  $url = 'http://things.mediamead.org/upload/';
  $request = curl_init($url);
  curl_setopt($request, CURLOPT_POST, true);
 
  $cFile = curl_file_create($f,'image/jpeg', basename($f));

  curl_setopt(
    $request,
    CURLOPT_POSTFIELDS,
    array(
      'userfile' => $cFile
    ));

  // output the response
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);

  // close the session
  curl_close($request);
  return $response;
}

function process_image($f) {
  $url = 'http://go.mediamead.org/aura/show/'.basename($f);
  $request = curl_init($url);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);
  return $response;  
}


function old_upload_image($id,$f) {

  $boundary = "---------------------------aura-robot-".$id;

  $file = file_get_contents($f);
  $data = $boundary."\r\n";
  $data.= "Content-Disposition: form-data; name=\"userfile\"; filename=\"".basename($f)."\"\r\n";
  $data.= "Content-Type: image/jpeg\r\n\r\n";
  $data.= $file;
  $data.= "\r\n".$boundary."--\r\n";

  $header = array('http' => array('method' => 'POST', 'header' => "Content-Type: multipart/form-data; boundary=".$boundary."\r\n",'content'=>$data));

    
  //Fire off the HTTP Request
  try {
    $fp = fopen($url, 'rb',false, stream_context_create($header));
    $response = @stream_get_contents($fp);
    print_r("reply: ".$response."\n");
    fclose($fp);
  } catch (Exception $e) {
    return array('status'=>'ERROR', 'statusInfo'=>'Network error');
  }
}

function _log($msg, $close = false) {
  static $fd = null;
  static $rq = '';
  
  if ($close && $fd) {
    fclose($fd);
    $fd = null;
    $rq = '';
    return;
  }
  
  if (!$fd) {
    $fd = fopen("/home/mediamead/avatar.log","a");
    
    $bytes = openssl_random_pseudo_bytes(4);
    $alpha = '1234567890abcdefghijeklmopqrstuvwxyzABCDEFGHIJEKLMOPQRSTUVWXYZ!@#$%^&*()_+-';
    
    $rq = '';
    for($i=0;$i<strlen($bytes);$i++) {
      $rq .= $alpha[$bytes[$i]%strlen($alpha)];
    }
  }
  
  $prefix = date('Y-m-d H:i:s').' '.$rq.' ';
  
  $m = explode("\n",$msg);
  
  foreach($m as $r) {
    fwrite($fd, $prefix.$r."\n");
  }
}
