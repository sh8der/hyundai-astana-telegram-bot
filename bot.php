<?php

use Telegram\Bot\Api;
use RedBeanPHP\R;

require_once "vendor/autoload.php";
require_once "functions.php";
require_once "classes/BotWrapper.php";
require_once "classes/BaseState.php";
define("ABS", __DIR__);

$options = getOptions();
try {
  R::ext('xdispense', function ($type) {
    return R::getRedBean()->dispense($type);
  });
} catch (\RedBeanPHP\RedException $e) {
  writeToLogFile(['data' => $e]);
}

R::setup(
  "mysql:host={$options['DB_HOST']};dbname={$options['DB_NAME']}",
  $options['DB_USER'],
  $options['DB_PASS']
);
R::freeze($options['DB_FREEZE']);

try {
  $telegram = new BotWrapper($options["TELEGRAM_BOT_TOKEN"]);
  $telegram->startReply();
} catch (\Telegram\Bot\Exceptions\TelegramSDKException $e) {
  writeToLogFile(['data' => $e]);
}

R::close();

