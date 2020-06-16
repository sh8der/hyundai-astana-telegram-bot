<?php

use RedBeanPHP\R;

class Start extends BaseState
{
  public $state = "start";

  public function init($bot)
  {
    $this->bot = $bot;
    $lang = $this->bot->BotWrapperCurrentUserStore['lang'];
    $sendMessageData = [];

    $replyMessage = R::findOne(
      'reply_dictionary',
      '`state` = ? AND `lang` IN (?, "*")',
      [
        $this->state,
        $lang
      ]
    );

    if ($replyMessage['message'])
      $sendMessageData['text'] = $replyMessage['message'];

    $replyKeyboard = R::getCell(
      'SELECT `markup` FROM `menu` WHERE id = ?',
      [$replyMessage['reply_markup']]
    );


    if ($replyKeyboard) {
      $replyKeyboard = json_decode($replyKeyboard, true);
      $sendMessageData['keyboard'] = $replyKeyboard;
    }

    if ($replyMessage['image_list']) {
      $this->sendImage($replyMessage['image_list']);
    }

    if (!empty($sendMessageData)) {
      $this->sendMessage($sendMessageData);
    }
    
    $this->clearTempUserData();
    $this->bot->updateUserState($this->state);
  }
}