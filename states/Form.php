<?php

use RedBeanPHP\R;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Form extends BaseState
{
  public $state = "Form";
  public $thisForm = null;
  public $thisFormCurrentFieldName = null;
  public $thisFormFields = null;
  public $userStateTempData = null;
  public $toNextStep = false;

  public function init($bot)
  {
    $this->bot = $bot;
    
    try {
      $this->userStateTempData = json_decode($this->bot->BotWrapperCurrentUserStore['state_temp_data'], true);
    } catch (Exception $e) {
      writeToLogFile(['data' => $e]);
    }
    if (empty($this->userStateTempData['form'])) {
      $this->thisForm = R::findOne(
        'form',
        'name = ?',
        [$this->bot->BotWrapperCurrentMessageText]
      );
      $this->thisFormFields = json_decode($this->thisForm['fields'], true);
      $this->userStateTempData['form_info'] = [
        'end_text' => $this->thisForm['end_text'],
        'form_name' => $this->bot->BotWrapperCurrentMessageText,
        'recipient_list' => $this->thisForm['recipient_list']
      ];
      $this->sendTyping();
      $this->sendMessage([
        'text' => $this->thisForm['start_text']
      ]);
      $this->userStateTempData['form'] = $this->thisFormFields;
      $this->setUserFormState($this->userStateTempData);
    }
    $this->thisFormCurrentFieldName = $this->detectCurrentField();
    $this->startPool($this->thisFormCurrentFieldName);
  }
  
  public function startPool($fieldName)
  {
    $thisField = $this->userStateTempData['form'][$fieldName];

    if ($thisField['start'] !== true) {
      $params['text'] = $thisField['text'];
      if ($thisField['keyboard']) {
        $replyKeyboard = R::getCell(
          'SELECT `markup` FROM `menu` WHERE state = ?',
          [$thisField['keyboard']]
        );
        $keyboard = json_decode($replyKeyboard, true);
        $params['keyboard'] = $keyboard;
      }
      $this->sendMessage($params);
      $thisField['start'] = true;
    } else {
      if ( is_array($thisField['test']) ) {
        $test = "(" . implode('|', $thisField['test']) . ")";
      } else {
        $test = $thisField['test'];
      }
      $re = "/{$test}/i";
      $str = $this->bot->BotWrapperCurrentMessageText;
      preg_match($re, $str, $matches);
      if (empty($matches[0])) {
        $params['text'] = $thisField['incorrect_text'];
        $this->sendMessage($params);
      } else {
        $thisField['complete'] = true;
        $thisField['result'] = $this->bot->BotWrapperCurrentMessageText;
        $this->toNextStep = true;
      }
    }

    $this->userStateTempData['form'][$fieldName] = $thisField;
    $this->setUserFormState($this->userStateTempData);
    if ($this->toNextStep)
      $this->nextStep();
  }

  public function nextStep()
  {
    $this->bot->loadUser();
    $this->userStateTempData = json_decode($this->bot->BotWrapperCurrentUserStore['state_temp_data'], true);
    $this->thisFormCurrentFieldName = $this->detectCurrentField();
    $thisField = $this->userStateTempData['form'][$this->thisFormCurrentFieldName];
    if ($this->isEnd()) {
      $this->sendTyping();
      $this->sendMessage([
        'text' => $this->userStateTempData['form_info']['end_text']
      ]);
      $this->sendMail([
        'to' => $this->userStateTempData['form_info']['recipient_list'],
        'from' => getOptions()['EMAIL_FROM'],
        'topic' => $this->userStateTempData['form_info']['form_name'],
        'msg' => "Заявка из Telegram бота: {$this->userStateTempData['form_info']['form_name']}<br>" . $this->getSendMailData()
      ]);
      return;
    }
    if ($thisField['start'] !== true) {
      $params['text'] = $thisField['text'];
      if ($thisField['keyboard']) {
        $replyKeyboard = R::getCell(
          'SELECT `markup` FROM `menu` WHERE state = ?',
          [$thisField['keyboard']]
        );
        $keyboard = json_decode($replyKeyboard, true);
        $params['keyboard'] = $keyboard;
      }
      $this->sendMessage($params);
      $thisField['start'] = true;
      $this->userStateTempData['form'][$this->thisFormCurrentFieldName] = $thisField;
      $this->setUserFormState($this->userStateTempData);
    }
  }
  
  public function detectCurrentField()
  {
    $fields = $this->userStateTempData['form'];
    foreach ($fields as $fieldName => $field) {
      if ($field['complete'] !== true) {
        return $fieldName;
      }
    }
  }
  
  public function getSendMailData()
  {
    $msg = '';
    foreach ($this->userStateTempData['form'] as $fieldName => $field) {
      $msg .= "{$field['text']} {$field['result']}<br>";
    }
    return $msg;
  }
  
  public function isEnd()
  {
    $fields = $this->userStateTempData['form'];
    $result = end($fields)['complete'];
    return $result;
  }

  public function setUserFormState(array $fields)
  {
    $this->bot->BotWrapperCurrentUserStore['state_temp_data'] = json_encode($fields);
    R::store($this->bot->BotWrapperCurrentUserStore);
  }
  
  public function sendMail(array $params)
  {
    
    if ($params['to'] && $params['msg']) {
      $mail = new PHPMailer(true);

      try {
        
        $mail->setFrom($params['from'], 'Mailer');
        
        if ( count( explode(',', $params['to']) ) !== 1 ) {
          foreach (explode(',', $params['to']) as $email)
            $mail->addAddress( trim($email) );
        } else {
          $mail->addAddress($params['to']);     // Add a recipient
        }

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $params['topic'];
        $mail->Body    = $params['msg'];

        $mail->send();
        echo "Message has been sent\r\n";
      } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }  
    }
    
  }

}