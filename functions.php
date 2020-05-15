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

function getNgrokPublicUrl($ngrok_web_interface_url = "http://localhost:4040/api/tunnels") {
  return json_decode( file_get_contents($ngrok_web_interface_url) )->tunnels[0]->public_url;
}