<?php 

namespace Veritrans;

class Utility {

  public static function remoteCall($url, $server_key, $data_hash)
  {
    $ch = curl_init();
    
    if ($data_hash) {
      $body = json_encode($data_hash);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Basic ' . base64_encode($server_key . ':')
      ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);

    var_dump($result);

    curl_close($ch);

    // convert the result into an associative array
    return json_decode($result, true);
  }

}