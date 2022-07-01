<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Request;
use mysqli;
use Twig\Environment;
use CodeSteppers\Generated\Repository\Subscriber\SqlLister as SubscriberLister;
use CodeSteppers\Generated\Repository\Codestepper\SqlLister as CodestepperLister;


class PublicSite
{

  public static function initSubscriberSession($conn)
  {
    return function (Request $request) use ($conn) {
      // if (!isset($_COOKIE[session_name()])) {
      //   return $request;
      // }

      // if (!isset($_SESSION)) {
      //   session_start();
      // }

      // $subscriber = null;
      // if (!isset($_SESSION['subscriberId'])) {
      //   return $request;
      // }

      // $byId = (new SubscriberLister($conn))->list(Router::where('id', 'eq', $_SESSION['subscriberId']));
      $byId = (new SubscriberLister($conn))->list(Router::where('id', 'eq', 1));
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



      $codeSteppers = (new CodestepperLister($conn))->list(Router::where('subscriberId', 'eq', 1));

      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('wrapper.twig', [
        'content' => $twig->render('home.twig', [
          'codeSteppers' => alignToRows($codeSteppers->getEntities(), 4),
        ]),
        'metaTitle' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'subscriberLabel' =>  getNick($request->vars),
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [
          ...getCodestepperEditorScripts(),
          ...getCodestepperScripts(),
        ],
        'styles' => [
          // ...getCodestepperEditorStyles(),
          // ...getCodestepperStyles(),
        ],
      ]);
    });

    $r->get('/edit/{codeStepperSlug}', $initSubscriberSession, function (Request $request) use ($conn, $twig) {

      $codeSteppers = (new CodestepperLister($conn))->list(Router::where('slug', 'eq', $request->vars['codeStepperSlug']));

      if(!$codeSteppers->getCount()) {
        return;
      }

      $allCodeSteppers = (new CodestepperLister($conn))->list(Router::where('subscriberId', 'eq', 1));
      // var_dump($codeSteppers);
      // exit;

      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('wrapper.twig', [
        'content' => $twig->render('edit.twig', [
          'codeStepper' =>  $codeSteppers->getEntities()[0],
          'codeSteppers' => $allCodeSteppers->getEntities(),
          'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'],
        ]),
        'metaTitle' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'subscriberLabel' =>  getNick($request->vars),
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [
          ...getCodestepperEditorScripts(),
          ...getCodestepperScripts(),
        ],
        'styles' => [
          ...getCodestepperEditorStyles(),
          ...getCodestepperStyles(),
        ],
      ]);
    });


    $r->get('/test', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('test.html');
    });
  }
}


function getCodestepperEditorScripts()
{
  $codeAssistScripts = array_filter(scandir('../public/codestepper-editor/js'), filterExtension('js'));
  return array_values(array_map(fn ($item) => ['path' => "/public/codestepper-editor/js/$item"], $codeAssistScripts));
}
function getCodestepperEditorStyles()
{
  $codeAssistStyles = array_filter(scandir('../public/codestepper-editor/css'), filterExtension('css'));
  return array_values(array_map(fn ($item) => ['path' => "/public/codestepper-editor/css/$item"], $codeAssistStyles));
}

function getCodestepperScripts()
{
  $codeAssistScripts = array_filter(scandir('../public/codestepper/js'), filterExtension('js'));
  return array_values(array_map(fn ($item) => ['path' => "codestepper/js/$item"], $codeAssistScripts));
}
function getCodestepperStyles()
{
  $codeAssistStyles = array_filter(scandir('../public/codestepper/css'), filterExtension('css'));
  return array_values(array_map(fn ($item) => ['path' => "codestepper/css/$item"], $codeAssistStyles));
}


function filterExtension($ext)
{
  return function ($item) use ($ext) {
    return getFileExtension($item) === $ext;
  };
}

function getFileExtension($fileName)
{
  $info = pathinfo($fileName);
  return $info['extension'] ?? '';
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
