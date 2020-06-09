<?php

use RedBeanPHP\R;

class BotWrapper extends \Telegram\Bot\Api
{

  const StateControllerPath = ABS . "/states";
  public $BotWrapperCurrentChatId;
  public $BotWrapperCurrentMessageText;
  public $BotWrapperCurrentUserID = null;
  public $BotWrapperCurrentUserStore = null;

  public function __construct($token)
  {
    parent::__construct($token);
    $this->BotWrapperCurrentUserID = $this->getWebhookUpdates()->getMessage()->getFrom()->getId();
    $this->storeUser($this->BotWrapperCurrentUserID);
    $this->BotWrapperCurrentChatId = $this->getWebhookUpdates()->getMessage()->getChat()->getId();
    $this->BotWrapperCurrentMessageText = $this->getWebhookUpdates()->getMessage()->getText();
    $this->BotWrapperCurrentUserStore = $this->getUser($this->BotWrapperCurrentUserID);
    $this->storeMessage();
  }

  public function storeMessage()
  {
    $chatMessage = R::xdispense('chat_messages');
    $chatMessage['chat_id'] = $this->BotWrapperCurrentChatId;
    $chatMessage['message_id'] = $this->getWebhookUpdates()->getMessage()->getMessageId();
    $chatMessage['text'] = $this->BotWrapperCurrentMessageText;
    $chatMessage['date'] = date('Y-m-d H:i:s', $this->getWebhookUpdates()->getMessage()->getDate());
    return R::store($chatMessage);
  }

  public function updateUserState($state)
  {
    $this->BotWrapperCurrentUserStore['current_state'] = $state;
    R::store($this->BotWrapperCurrentUserStore);
  }

  public function getStateControllerName($phrase, $lang)
  {
    return R::getCell(
      'SELECT `bounded_state` FROM `phrases_list` WHERE `lang` IN(?, "*") AND `phrase` = ? LIMIT 1',
      [$lang, $phrase]
    );
  }

  public function startReply()
  {

    $stateControllerName = ucfirst(
      $this->getStateControllerName(
        $this->BotWrapperCurrentMessageText,
        $this->BotWrapperCurrentUserStore['lang']
      )
    );

    try {
      require_once(self::StateControllerPath . "/{$stateControllerName}.php");
      $stateController = new $stateControllerName;
    } catch (Exception $e) {
      writeToLogFile(['data' => $e]);
      throw new Exception('Не получается подключить контроллер состояния, и создать экземпляр класса');
    }

    if (class_exists($stateControllerName)) {
      $replyData = $stateController->init($this);
      $this->updateUserState($stateControllerName);
    } else {
      writeToLogFile(['data' => 'Почему-то не получилось отправить сообщенеи пользователю']);
    }

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
      $newUser['current_state'] = 'start';
      $newUser['lang'] = 'ru';
      return R::store($newUser);
    }
    return $user;
  }

  /**
   * Get user from database
   * @param integer $userID Telegram user id
   * @return NULL|\RedBeanPHP\OODBBean
   */
  private function getUser($userID)
  {
    return R::findOne('users', 'telegram_id = ?', [$userID]);
  }

  /**
   * Get text to message reply from database
   * @param string $screenName Current user screen name
   * @param string $lang Current user language
   * @return string
   */
  public function getReplyMessage($screenName, $lang)
  {
    return R::getCell(
      'SELECT `message` FROM `reply_dictionary` WHERE `lang` = ? AND `screen` = ? LIMIT 1',
      [$lang, $screenName]
    );
  }

}