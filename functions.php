<?php

function getOptions($env_path = ".env")
{
  $options = [];

  if ($file = fopen($env_path, "r")) {
    while (!feof($file)) {
      list($opt_name, $value) = explode("=", fgets($file));
      $options[$opt_name] = $value;
    }
    fclose($file);
  }

  return $options;
}

function getNgrokPublicUrl($ngrok_web_interface_url = "http://localhost:4040/api/tunnels")
{
  $ngrok_api_resp = json_decode(file_get_contents($ngrok_web_interface_url));
  $public_urls[0] = $ngrok_api_resp->tunnels[0]->public_url;
  $public_urls[1] = $ngrok_api_resp->tunnels[1]->public_url;
  return strpos($public_urls[0], 'https:') ? $public_urls[0] : $public_urls[1];
}
