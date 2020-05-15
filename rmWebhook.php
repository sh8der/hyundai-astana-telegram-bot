<?php
require_once('functions.php');
$token = getOptions()['TELEGRAM_BOT_TOKEN'];
$tgApiReqUrl = "https://api.telegram.org/bot{$token}/deleteWebhook";

echo file_get_contents($tgApiReqUrl);
