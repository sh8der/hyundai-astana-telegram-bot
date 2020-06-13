<?php

use RedBeanPHP\R;

require_once '../vendor/autoload.php';
require_once '../classes/phpQuery.php';
require_once '../functions.php';
$options = getOptions('../.env');
$currentPromoPage = 1;
$promoPageURL = "https://hyundai-astana.kz/ru/promo/?page=";

function getHtml($url)
{
  $guzzle = new GuzzleHttp\Client();
  return $guzzle->request("GET", $url)->getBody();
}

function getPromoListFromPage($promoPageURL, $pageNumber = 1)
{
  $promos = [];
  $currentPage = $pageNumber;

  $res = getHtml($promoPageURL . $currentPage);
  $page = phpQuery::newDocument($res);
  unset($res);
  $lastPageNumber = (integer)array_reverse(explode(
    '=',
    $page->find('.pagination .numbers a:last-child')->attr('href')
  ))[0];

  for ($currentPage; $currentPage <= $lastPageNumber; $currentPage++) {
    if (empty($page)) {
      $res = getHtml($promoPageURL . $currentPage);
      $page = phpQuery::newDocument($res);
      unset($res);  
    }
    
    foreach ($page->find('.news__item') as $news) {
      $promoDataChips = trim(pq($news)->find('.news__item-date span')->text());
      if ($promoDataChips == 'Акция завершена') continue;
      $newsLink = pq($news)->attr('href');
      $newsImgLink = pq($news)->find('img')->attr('src');
      $newsTitle = pq($news)->find('h4')->text();
      array_push($promos, [
        'caption' => $newsTitle,
        'link' => 'https://hyundai-astana.kz' . $newsLink,
        'img' => 'https://hyundai-astana.kz' . $newsImgLink
      ]);
    }
    unset($page);
  }

  return $promos;
}

$data = getPromoListFromPage($promoPageURL, $currentPromoPage);

if (empty($data)) {
  writeToLogFile(['data' => "По какой-то причине не были получены данные с сайта"]);
  die();
}

try {
  R::ext('xdispense', function ($type) {
    return R::getRedBean()->dispense($type);
  });
} catch (\RedBeanPHP\RedException $e) {
  writeToLogFile(['data' => $e]);
}

R::setup(
  "mysql:host={$options['DB_HOST']};dbname={$options['DB_NAME']}",
  $options['DB_USER'],
  $options['DB_PASS']
);
R::freeze( $options['DB_FREEZE'] );

R::wipe('promo');

$promos = R::dispense('promo', count($data));

for ($i = 0; $i<count($data); $i++) {
  $promos[$i]['url'] = $data[$i]['link'];
  $promos[$i]['title'] = $data[$i]['caption'];
  $promos[$i]['img_url'] = $data[$i]['img'];
}

R::begin();
try{
  R::storeAll($promos);
  R::commit();
  writeToLogFile(['data' => 'Акции добавлены в базу данных']);
  echo "Акции добавлены в базу данных";
}
catch(Exception $e) {
  R::rollback();
}