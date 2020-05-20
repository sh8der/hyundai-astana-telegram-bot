<?php

class BotWrapper
{

  private $bot;
  private $currentWhUpdate;
  private $currentChatID;

  public function __construct($bot)
  {
    $this->bot = $bot;
    $this->currentWhUpdate = $bot->getWebhookUpdates();
    $this->currentChatID = $this->currentWhUpdate->getMessage()->getChat()->getId();
  }

}