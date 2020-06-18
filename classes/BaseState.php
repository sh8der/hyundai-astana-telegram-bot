<?php

use RedBeanPHP\R;

class BaseState
{
  public $state = 'baseState';
  public $oldStateName = null;
  public $bot = null;
  public $replyMessage = null;
  public $production = null;
  
  public function init($bot)
  {
    $this->production = (bool)getOptions()['PRODUCTION'];
    $this->bot = $bot;
    $lang = $this->bot->BotWrapperCurrentUserStore['lang'];
    $sendMessageData = [];

    $this->replyMessage = R::findOne(
      'reply_dictionary',
      '`action` = ? AND `state` = ? AND `lang` IN (?, "*")',
      [
        $this->bot->BotWrapperCurrentMessageText,
        $this->state,
        $lang
      ]
    );

    if ($this->replyMessage['message'])
      $sendMessageData['text'] = $this->replyMessage['message'];

    $replyKeyboard = R::getCell(
      'SELECT `markup` FROM `menu` WHERE id = ?',
      [$this->replyMessage['reply_markup']]
    );


    if ($replyKeyboard) {
      $replyKeyboard = json_decode($replyKeyboard, true);
      $sendMessageData['keyboard'] = $replyKeyboard;
    }


    if ($this->replyMessage['image_list']) {
      $this->sendImage($this->replyMessage['image_list']);
    }

    if (!empty($sendMessageData)) {
      $this->sendMessage($sendMessageData);
    }

    if ($this->replyMessage['file_list'])
      $this->sendFile($this->replyMessage['file_list']);

    $this->bot->updateUserState($this->state);
  }

  public function sendImage($img)
  {
    list($imgPath, $telegramImgId) = explode('::', $img);

    if ($telegramImgId) {
      $replyImage = $telegramImgId;
    } else {
      if ($this->production) {
        $botSubFolder = "";
        if (array_key_exists('BOT_FOLDER', getOptions()))
          $botSubFolder = "/" . getOptions()['BOT_FOLDER'];
        $replyImage = "https://" . trim($_SERVER['HTTP_HOST']) . "{$botSubFolder}{$imgPath}";
      } else {
        $replyImage = getNgrokPublicUrl() . $imgPath;
      }
    }

    $this->bot->sendChatAction([
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'action' => 'upload_photo'
    ]);

    $send_file_resp = $this->bot->sendPhoto([
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'photo' => $replyImage
    ]);

    if (!$telegramImgId) {
      $file_id = json_decode($send_file_resp, true);
      $file_id = end($file_id['photo'])['file_id'];
      $img = $img . "::" . $file_id;
      $this->replyMessage['image_list'] = $img;
      R::store($this->replyMessage);
    }
  }

  public function sendFile($files = "")
  {
    $files = explode(',', $files);
    $newFileList = [];

    foreach ($files as $file) {
      list($filePath, $telegramFileId) = explode('::', $file);

      if ($telegramFileId) {
        $replyFile = $telegramFileId;
      } else {
        $replyFile = getNgrokPublicUrl() . $filePath;
      }

      $this->bot->sendChatAction([
        'chat_id' => $this->bot->BotWrapperCurrentChatId,
        'action' => 'upload_document'
      ]);

      $send_file_resp = $this->bot->sendDocument([
        'chat_id' => $this->bot->BotWrapperCurrentChatId,
        'document' => $replyFile,
      ]);

      if (!$telegramFileId) {
        $file_id = json_decode($send_file_resp, true);
        $file_id = $file_id['document']['file_id'];
        $file = $file . "::" . $file_id;
        array_push($newFileList, $file);
      }
    }

    if (!empty($newFileList)) {
      $this->replyMessage['file_list'] = implode(',', $newFileList);
      R::store($this->replyMessage);
    }

  }

  public function sendMessage(array $message)
  {
    $sendData = [
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'text' => $message['text'],
      'parse_mode' => 'Markdown'
    ];
    if ($message['keyboard']) {
      $keyboard = $this->bot->replyKeyboardMarkup([
        'keyboard' => $message['keyboard'],
        'resize_keyboard' => true,
        'one_time_keyboard' => false
      ]);
      $sendData['reply_markup'] = $keyboard;
    }
    $this->bot->sendMessage($sendData);
  }
  
  public function sendTyping()
  {
    $this->bot->sendChatAction([
      'chat_id' => $this->bot->BotWrapperCurrentChatId,
      'action' => 'typing'
    ]);
  }
  
  public function clearTempUserData()
  {
    $this->bot->BotWrapperCurrentUserStore['state_temp_data'] = '';
    R::store($this->bot->BotWrapperCurrentUserStore);
  }
  
}