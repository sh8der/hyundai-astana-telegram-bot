<?php

use RedBeanPHP\R;

class Promo extends BaseState
{
  public $state = "promo";
  
  public function init($bot)
  {
    $this->bot = $bot;
    $this->replyMessage = R::getAll('SELECT `url`, `title`, `img_url` FROM `promo`');
    
    for ($i = 0; $i <= count($this->replyMessage); $i++) {
      $params = [
        'chat_id' => $this->bot->BotWrapperCurrentChatId,
        'photo'   => $this->replyMessage[$i]['img_url'],
        'caption' => "{$this->replyMessage[$i]['title']}\n\n[Узнать больше ➡]({$this->replyMessage[$i]['url']})",
        'parse_mode' => "Markdown"
      ];
      if ($i == count($this->replyMessage)){
        $params['reply_markup'] = json_encode(R::getCell(
          'SELECT `markup` FROM `menu` WHERE `state` = ?',
          ['start']
        ));
      }
      $this->sendTyping();
      $this->sendPromo($params);
    }
  }
  
  public function sendPromo(array $params)
  {
    $this->bot->sendPhoto($params);
  }  
  
}