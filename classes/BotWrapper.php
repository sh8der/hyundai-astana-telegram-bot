<?php

use RedBeanPHP\R;

class BotWrapper extends \Telegram\Bot\Api
{

  public $BotWrapperCurrentChatId;
  public $BotWrapperCurrentMessageText;
  public $BotWrapperCurrentUserID = null;
  public $BotWrapperCurrentUserStore = null;

  public function __construct($token)
  {
    parent::__construct($token);
    $this->BotWrapperCurrentUserID = $this->getWebhookUpdates()->getMessage()->getFrom()->getId();
    $this->storeUser($this->BotWrapperCurrentUserID);
    $this->BotWrapperCurrentUserStore = $this->getUser($this->BotWrapperCurrentUserID);
    $this->BotWrapperCurrentChatId = $this->getWebhookUpdates()->getMessage()->getChat()->getId();
    $this->BotWrapperCurrentMessageText = $this->getWebhookUpdates()->getMessage()->getText();
  }
  
  private function storeMessage()
  {
    $chatMessage = R::xdispense('chat_messages');
    $chatMessage['chat_id'] = $this->BotWrapperCurrentChatId;
    $chatMessage['message_id'] = $this->getWebhookUpdates()->getMessage()->getMessageId();
    $chatMessage['text'] = $this->BotWrapperCurrentMessageText;
    $chatMessage['date'] = date('Y-m-d H:i:s', $this->getWebhookUpdates()->getMessage()->getDate());
    return R::store($chatMessage);
  }

  private function storeUser($userID)
  {
    $user = $this->getUser($userID);
    if ($user === null) {
      $webHookUpdate = $this->getWebhookUpdates()->getMessage();
      $newUser = R::dispense('users');
      $newUser['telegram_id'] = $userID;
      $newUser['name'] = $webHookUpdate->getFrom()->getFirstName();
      if (!empty($webHookUpdate->getFrom()->getLastName())) {
        $newUser .= " " . $webHookUpdate->getFrom()->getLastName();
      }
      $newUser['current_screen'] = '/start';
      $newUser['lang'] = 'ru';
      return R::store($newUser);
    }
    return $user;
  }

  private function getUser($userID)
  {
    return R::findOne('users', 'telegram_id = ?', [$userID]);
  }

  private function getReplyMessage($text, $lang)
  {
    return R::getCell(
      'SELECT `message` FROM `reply_dictionary` WHERE `lang` = ? AND `screen` = ? LIMIT 1',
      [
        $lang,
        $text
      ]
    );
//    return R::findOne('reply_dictionary', 'lang = ? AND screen = ? AND action = ?',);
  }

  public function replyHello()
  {
    $this->sendMessage([
      'chat_id' => $this->BotWrapperCurrentChatId,
      'text' => 'Hello my friend'
    ]);
  }

  public function replyStart()
  {
    $message = $this->getReplyMessage($this->BotWrapperCurrentMessageText, $this->BotWrapperCurrentUserStore['lang']);
    var_dump($this->BotWrapperCurrentMessageText);
    var_dump($this->BotWrapperCurrentUserStore['lang']);
    $this->sendMessage([
      'chat_id' => $this->BotWrapperCurrentChatId,
      'text' => $message
    ]);
  }  

  public function hears($text, $functionName)
  {
    $this->storeMessage();
    if ($text === $this->BotWrapperCurrentMessageText)
      call_user_func($functionName);
  }

}