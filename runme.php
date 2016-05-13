#!/usr/bin/php
<?php

//upload_image("20151219-042442","images/20151219-042442.jpg");
//die();

$in = fopen("php://stdin","r");
stream_set_blocking($in, 0);

echo("Welcome to AURA project...\n");
$ret = 0;
//system("gphoto2 --set-config /main/capturesettings/flashmode=0",$ret);
//if ($ret!=0) echo("Could not set flashmode! Check photo avail!\n");

while (1) {
  $s = fread($in, 1024);
  if (!empty($s)) {
    echo("\n");
    system('bash -c "su -c \"ffplay -showmode 0 -t 0.35 sound_camera.wav -autoexit\" diod >>/tmp/aura.log 2>&1 &"');
    system('bash -c "gxmessage -geometry 400x200 -sticky -center \"Идет поиск...\" >>/tmp/aura.log 2>&1 &"');
    sleep(1);
    $ret = 0;
    //1800
    system('wmctrl -a gxmessage -e "0,440,400,400,200"',$ret);
    echo("wmctrl: ".$ret."\n");
    
    $id = date('Ymd-his');
    $f=capture_image($id);
    $res = upload_image($id,$f);
    echo("res: ".$res."\n");
    process_image($f);
    
    sleep(5);
    system('killall gxmessage');
  }
  $s = fread($in, 1024);

  $tm = date('Y-m-d H:i:s');
  echo("\r".$tm.": ");
  sleep(1);
  
}

function capture_image($id) {
  $fs = "images/${id}_src.jpg";
  $f  = "images/$id.jpg";
  $ret = 0;
  system("numlockx on");
  system("ffmpeg -f v4l2 -video_size 1280x960 -i /dev/video0 -ss 2.5 -frames 1 output%03d.png",$ret);
  system("numlockx off");
//  system("gphoto2 --capture-image-and-download --filename ".$fs, $ret);
  if ($ret!=0) {
    echo ("ERROR capturing!\n");
    return false;
  }
//  rename("output001.jpg", $fs);

  $cmd = "convert output001.png -crop 900x960+108+64 $fs";

/*
  $cmd = "convert $fs -geometry 1280x1024 $f";  
//  $cmd = "convert $fs -crop 2736x2188+456+120 -geometry 1280x1024 $f";
//  $cmd = "convert $fs -crop 2736x2000+456+220 -geometry 1280x1024 $f";
*/

  system($cmd,$ret);
  if ($ret!=0) {
    echo ("ERROR converting!\n");
    return false;
  }

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

