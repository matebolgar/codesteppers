<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Request;
use mysqli;
use Twig\Environment;
use CodeSteppers\Generated\Repository\Subscriber\SqlLister as SubscriberLister;


class PublicSite
{

  public static function initSubscriberSession($conn)
  {
    return function (Request $request) use ($conn) {
      if (!isset($_COOKIE[session_name()])) {
        return $request;
      }

      if (!isset($_SESSION)) {
        session_start();
      }

      $subscriber = null;
      if (!isset($_SESSION['subscriberId'])) {
        return $request;
      }

      $byId = (new SubscriberLister($conn))->list(Router::where('id', 'eq', $_SESSION['subscriberId']));
      if (!$byId->getCount()) {
        return $request;
      }

      $subscriber = $byId->getEntities()[0];
      if (!$subscriber->getIsVerified()) {
        return $request;
      }
      $request->vars['subscriber'] = $subscriber;
      return $request;
    };
  }

  private static function organizationStructuredData()
  {
    return json_encode([
      "@context" => "https://schema.org",
      "@type" => "Organization",
      "url" => Router::siteUrl(),
      "logo" => Router::siteUrl() . "/public/images/logo5.png"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }


  public static function getRoutes(Pipeline $r, mysqli $conn, Environment $twig)
  {

    $initSubscriberSession = self::initSubscriberSession($conn);

    $r->get('/', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');



      echo $twig->render('wrapper.twig', [
        'content' => $twig->render('home.twig', [
          'content' => "",
        ]),
        'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'subscriberLabel' =>  getNick($request->vars),
        'structuredData' => self::organizationStructuredData(),
        'ogTags' => [
          [
            'property' => 'og:url',
            'content' => Router::siteUrl() . parse_url(Router::siteUrl() . $_SERVER['REQUEST_URI'], PHP_URL_PATH),
          ],
          [
            'property' => 'og:title',
            'content' => 'Kódbázis - Programozás egyszerűen elmagyarázva',
          ],
          [
            'property' => 'og:image',
            'content' => Router::siteUrl() . '/public/images/home3.png',
            // 'content' => Router::siteUrl() . '/public/images/akcio.png',
          ],
          [
            'property' => 'og:type',
            'content' => 'website',
          ],
          [
            'property' => 'og:description',
            'content' => 'Webfejlesztő online kurzusok kezdőknek és haladóknak',
          ],
          [
            'property' => 'fb:app_id',
            'content' => '705894336804251',
          ],
        ],
        'scripts' => [
          ['path' => 'js/scroller.js'],
          ['path' => 'js/jquery.js'],
          ['path' => 'js/application.js'],
        ],
        'styles' => [
          ['path' => 'css/login.css'],
          ['path' => 'css/promo.css'],
          ['path' => 'css/episode-single.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);
    });
  }

}

// function enqueueEmail($recipientEmail, $subject, $body, $conn)
// {
//     (new MessageSaver($conn))->Save(new NewMessage(
//         $recipientEmail,
//         $subject,
//         $body,
//         "notSent",
//         0,
//         null,
//         time()
//     ));
// }


function getNick($vars)
{
  if (!isset($vars['subscriber'])) {
    return '';
  }

  return $vars['subscriber']->getEmail();
}
