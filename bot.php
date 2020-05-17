<?php
use Telegram\Bot\Api;

require_once "vendor/autoload.php";
require_once "functions.php";

try {
  $telegram = new Api(getOptions()["TELEGRAM_BOT_TOKEN"]);
} catch (\Telegram\Bot\Exceptions\TelegramSDKException $e) {
  $currentDate = date("d.m.Y");
  file_put_contents(__DIR__ . "/log.txt", "[{$currentDate}] : {$e}");
}


?>