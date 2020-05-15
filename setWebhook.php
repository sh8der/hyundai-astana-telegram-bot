<?php
require_once('functions.php');
$token = getOptions()['TELEGRAM_BOT_TOKEN'];
$currentPublicUrl = getNgrokPublicUrl() . "/bot.php";
$tgApiReqUrl = "https://api.telegram.org/bot{$token}/setWebhook?url={$currentPublicUrl}";

echo file_get_contents($tgApiReqUrl);
