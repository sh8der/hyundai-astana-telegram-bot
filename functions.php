<?php
if (function_exists('date_default_timezone_set'))
  date_default_timezone_set('Asia/Almaty');

/**
 * Get options from .env file
 *
 * @param string $env_path
 * @return array
 */
function getOptions($env_path = ".env")
{
  $options = [];

  if ($file = fopen($env_path, "r")) {
    while (!feof($file)) {
      list($opt_name, $value) = explode("=", fgets($file));
      $options[$opt_name] = trim($value);
    }
    fclose($file);
  }

  return $options;
}

/**
 * Return public https url from ngrok, for set telegram webhook
 *
 * @param string $ngrok_web_interface_url
 * @return string
 */
function getNgrokPublicUrl($ngrok_web_interface_url = "http://localhost:4040/api/tunnels")
{

  $ngrok_api_resp = @file_get_contents($ngrok_web_interface_url);
//  if (empty($ngrok_api_resp)) {
//    $error_message = "Не получены данные от Ngrok api, возможно он не запущен.";
//    writeToLogFile(['data' => $error_message]);
//    die($error_message);
//  }
  $ngrok_api_resp = json_decode($ngrok_api_resp);
  $public_urls[0] = $ngrok_api_resp->tunnels[0]->public_url;
  $public_urls[1] = $ngrok_api_resp->tunnels[1]->public_url;
  return (strpos($public_urls[0], 'https:') !== false) ? $public_urls[0] : $public_urls[1];
}

/**
 * Записываем информацию в лог файл
 *
 * @param array $attr
 *
 * @var string $attr ['file']
 * @var array|string|int $attr ['data']
 */
function writeToLogFile(array $attr)
{
  extract($attr);
  if (empty($file)) {
    $file = __DIR__ . "/logs/log.txt";
  }
  if (is_array($data)) {
    $data = print_r($data, true);
  }
  $currentDateTime = date('m.d.Y H:i');
  file_put_contents($file, "[{$currentDateTime}] : {$data}\r", FILE_APPEND);
}