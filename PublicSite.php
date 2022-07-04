<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Listing\Clause;
use CodeSteppers\Generated\Listing\Filter;
use CodeSteppers\Generated\Listing\OrderBy;
use CodeSteppers\Generated\Listing\Query;
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
      $request->vars['subscriber'] = null;
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

  public static function organizationStructuredData()
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
          'codeSteppers' => [],
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

    $r->get('/edit', $initSubscriberSession, function (Request $request) use ($conn, $twig) {


      if ($request->vars["subscriber"]) {
        $id = $request->vars["subscriber"]->getId();
        $allCodeSteppers = (new CodestepperLister($conn))->list(Router::where('subscriberId', 'eq', $id));
        if ($allCodeSteppers->getCount()) {
          header("Location: /edit/" . $allCodeSteppers->getEntities()[0]->getSlug());
        } else {
          $codeStepperId = CodeStepper::createSchemaForSubscriber($conn, $id);
          header("Location: /edit/$codeStepperId");
        }
        return;
      }


      if (isset($_COOKIE["guestId"])) {

        $allCodeSteppers = (new CodestepperLister($conn))->list(Router::where('guestId', 'eq', $_COOKIE["guestId"]));

        if ($allCodeSteppers->getCount()) {
          header("Location: /edit/" . $allCodeSteppers->getEntities()[0]->getSlug());
        } else {
          $codeStepperId = CodeStepper::createSchemaForGuest($conn, $_COOKIE["guestId"]);
          header("Location: /edit/$codeStepperId");
        }
      } else {
        $id = "guest-" . uniqid();
        setcookie("guestId", $id, time() + 60 * 60 * 24);
        $codeStepperId = CodeStepper::createSchemaForGuest($conn, $id);

        header("Location: /edit/$codeStepperId");
      }

    });

    $r->get('/edit/{codeStepperSlug}', $initSubscriberSession, function (Request $request) use ($conn, $twig) {

      $q = null;
      $subscriberId = null;
      $guestId = null;
      if (isset($_COOKIE["guestId"])) {
        $guestId = $_COOKIE["guestId"];
        $q = new Query(
          15,
          0,
          new Filter(
            "and",
            new Clause("eq", "guestId", $_COOKIE["guestId"]),
            new Clause("eq", "slug", $request->vars['codeStepperSlug'] ?? ''),

          ),
          new OrderBy('createdAt', "desc"),
          []
        );
      } else {
        $subscriberId = $request->vars["subscriber"] ? $request->vars["subscriber"]->getId() : "";
        $q = new Query(
          15,
          0,
          new Filter(
            "and",
            new Clause("eq", "subscriberId", $subscriberId),
            new Clause("eq", "slug", $request->vars['codeStepperSlug'] ?? ''),

          ),
          new OrderBy('createdAt', "desc"),
          []
        );
      }

      $codeSteppersBySlug = (new CodestepperLister($conn))->list($q);

      if (!$codeSteppersBySlug->getCount()) {
        header("Location: /edit");
        return;
      }


      $allCodeSteppers = null;
      if ($subscriberId) {
        $allCodeSteppers =  (new CodestepperLister($conn))->list(Router::where('subscriberId', 'eq', $subscriberId));
      } else {
        $allCodeSteppers = (new CodestepperLister($conn))->list(Router::where('guestId', 'eq', $guestId));
      }


      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('wrapper.twig', [
        'content' => $twig->render('edit.twig', [
          'codeStepper' =>  $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
          'codeSteppers' => $allCodeSteppers->getEntities(),
          'codeStepperScripts' => json_encode(getCodestepperScripts()),
          'codeStepperStyles' => json_encode(getCodestepperStyles()),
          'siteUrl' => Router::siteUrl(),
          'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'] ?? '',
        ]),
        'metaTitle' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'subscriberLabel' =>  getNick($request->vars),
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [
          [
            "path" => "https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.1/highlight.min.js",
            "isCdn" => true,
          ],
          ...getCodestepperEditorScripts(),
          ...getCodestepperScripts(),
          ["path" => "js/bootstrap.min.js"],
          ["path" => "js/modal.js"],
        ],
        'styles' => [
          ["path" => "css/dracula.css"],
          ...getCodestepperEditorStyles(),
          ...getCodestepperStyles(),
        ],
      ]);
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

function enqueueEmail($recipientEmail, $subject, $body, $conn)
{
  var_dump($recipientEmail, $subject, $body);
  // (new MessageSaver($conn))->Save(new NewMessage(
  //     $recipientEmail,
  //     $subject,
  //     $body,
  //     "notSent",
  //     0,
  //     null,
  //     time()
  // ));
}


function getNick($vars)
{
  if (!isset($vars['subscriber'])) {
    return '';
  }

  return $vars['subscriber']->getEmail();
}
