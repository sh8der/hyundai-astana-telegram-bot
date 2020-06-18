<?php

use RedBeanPHP\R;

class Contacts extends BaseState
{
  public $state = "Contacts";
  public $replyMessage = null;
  public $bot = null;
  public $img = null;

  public function init($bot)
  {
    $this->bot = $bot;
    $this->replyMessage = R::getRow(
      'SELECT `image_list`, `message` FROM `reply_dictionary` WHERE `state` = ?',
      [$this->state]
    );
    $this->img = $this->replyMessage['image_list'];
    
    if ($this->production) {
      $botSubFolder = "";
      if (array_key_exists('BOT_FOLDER', getOptions()))
        $botSubFolder = "/" . getOptions()['BOT_FOLDER'];
      $this->img = "https://" . trim($_SERVER['HTTP_HOST']) . "{$botSubFolder}{$this->img}";
    } else {
      $this->img = getNgrokPublicUrl() . $this->img;
    }
    
    $this->sendTyping();
    $params = [
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'photo' => $this->img,
      'caption' => $this->replyMessage['message'],
      'parse_mode' => 'Markdown'
    ];
    $this->bot->sendPhoto($params);
    $this->sendTyping();
    $params = [
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'latitude' => 51.118802,
      'longitude' => 71.4107971,
    ];
    $this->bot->sendLocation($params);
  }
}