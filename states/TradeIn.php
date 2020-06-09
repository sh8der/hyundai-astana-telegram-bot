<?php

use RedBeanPHP\R;

class TradeIn
{
  
  const state = "TradeIn";
  
  public function init($bot)
  {
    return ['text' => 'Оформляемся по Tradein'];
  }
  
}