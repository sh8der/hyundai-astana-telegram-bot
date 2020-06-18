<?php
require_once('vendor/autoload.php');
require_once('functions.php');
$guzzle = new GuzzleHttp\Client(['verify' => false]);
$token = getOptions()['TELEGRAM_BOT_TOKEN'];
$production = (bool)getOptions()['PRODUCTION'];
if ($production) {
  $botSubFolder = "";
  if (array_key_exists('BOT_FOLDER', getOptions()))
    $botSubFolder = "/" . getOptions()['BOT_FOLDER'];
  $currentPublicUrl = "https://" . trim($_SERVER['HTTP_HOST']) . "{$botSubFolder}/bot.php";
} else {
  $currentPublicUrl = getNgrokPublicUrl() . "/bot.php";
}
$removeWebHookURL = "https://api.telegram.org/bot{$token}/deleteWebhook";
$setWebHookURL = "https://api.telegram.org/bot{$token}/setWebhook?url={$currentPublicUrl}";

$response = json_decode($guzzle->request('GET', $removeWebHookURL)->getBody(), true);
if ($response['ok'] != true && $response['result'] != true) {
  writeToLogFile(["data" => $response]);
  echo print_r($response, true);
} else {
  writeToLogFile(["data" => "Old webhook url was deleted"]);
  echo "Old webhook url was deleted\n";
}

$response = json_decode($guzzle->request('GET', $setWebHookURL)->getBody(), true);
if ($response['ok'] != true && $response['result'] != true) {
  writeToLogFile(["data" => $response]);
  echo print_r($response, true);
} else {
  writeToLogFile(["data" => "New webhook url was set"]);
  echo "New webhook url was set\n";
}