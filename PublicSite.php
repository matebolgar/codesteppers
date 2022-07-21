<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Listing\Clause;
use CodeSteppers\Generated\Listing\Filter;
use CodeSteppers\Generated\Listing\OrderBy;
use CodeSteppers\Generated\Listing\Query;
use CodeSteppers\Generated\Message\Patch\PatchedMessage;
use CodeSteppers\Generated\Message\Save\NewMessage;
use CodeSteppers\Generated\Order\Patch\PatchedOrder;
use CodeSteppers\Generated\Request;
use mysqli;
use Twig\Environment;
use CodeSteppers\Generated\Repository\Subscriber\SqlLister as SubscriberLister;
use CodeSteppers\Generated\Repository\Codestepper\SqlLister as CodestepperLister;
use CodeSteppers\Generated\Repository\Order\SqlSaver as OrderSaver;
use CodeSteppers\Generated\Repository\Order\SqlLister as OrderLister;
use CodeSteppers\Generated\Repository\Order\SqlPatcher as OrderPatcher;
use CodeSteppers\Generated\Order\Save\NewOrder;
use CodeSteppers\Generated\Repository\Message\SqlSaver as MessageSaver;
use CodeSteppers\Generated\Repository\Message\SqlPatcher as MessagePatcher;
use CodeSteppers\Generated\Repository\Message\SqlLister as MessageLister;
use CodeSteppers\Mailer\Mailer;


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
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'content' => $twig->render('home.twig', [
          'codeSteppers' => [],
          'isLoggedIn' => isset($request->vars["subscriber"])
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
        $cookieParams = session_get_cookie_params();
        setcookie("guestId", $id, time() + 60 * 60 * 24, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], isset($cookieParams['httponly']));
        $codeStepperId = CodeStepper::createSchemaForGuest($conn, $id);

        header("Location: /edit/$codeStepperId");
      }
    });


    $r->get("/status/{schemaId}", $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      @header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
      header("Access-Control-Allow-Credentials: true");
      header('Access-Control-Allow-Methods: GET');

      $getStatus = fn ($code) => json_encode(["status" => $code]);
      if ($request->query["is-embedded"] ?? "") {
        echo $getStatus(2);
        return;
      }

      $codeSteppers = (new CodestepperLister($conn))->list(new Query(
        1,
        0,
        new Clause("eq", "slug", $request->vars['schemaId'] ?? ''),
        new OrderBy('createdAt', "desc"),
        []
      ));

      if (!$codeSteppers->getCount()) {
        echo $getStatus(-1);
        http_response_code(404);
        return;
      }

      $codeStepper = $codeSteppers->getEntities()[0];
      $isLoggedIn = isset($request->vars["subscriber"]) && $codeStepper->getSubscriberId() === $request->vars["subscriber"]->getId();

      $orders = (new OrderLister($conn))->list(Router::where('subscriberId', 'eq', $codeStepper->getSubscriberId()));

      $orders = (new OrderLister($conn))->list(
        isOrderValidQuery($codeStepper->getSubscriberId())
      );

      $isPayed = (bool)$orders->getCount();

      // payed, logged in -> link to dashboard
      if ($isPayed && $isLoggedIn) {
        echo $getStatus(0);
        return;
      }

      // not payed, logged in -> X link to paywall
      if (!$isPayed && $isLoggedIn) {
        echo $getStatus(1);
        return;
      }

      // payed, not logged in -> no link
      if ($isPayed && !$isLoggedIn) {
        echo $getStatus(2);
        return;
      }

      // not payed, not logged in -> link to landing page
      if (!$isPayed && !$isLoggedIn) {
        echo $getStatus(3);
        return;
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


      if(!$subscriberId) {
        echo $twig->render('wrapper.twig', [
          'navbar' => $twig->render("navbar.twig", [
            "buttons" => $twig->render("buttons.twig", [
              'codeStepper' => $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
            ]),
            'subscriberLabel' => getNick($request->vars) ?? "",
          ]),
          'content' => $twig->render('edit-guest.twig', [
            'codeStepper' =>  $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
            'codeSteppers' => $allCodeSteppers->getEntities(),
            'siteUrl' => Router::siteUrl(),
            'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'] ?? '',
          ]),
          'metaTitle' => 'CodeSteppers - Online interactive tool for schools and teachers',
          'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
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
        return;
      }

      $orders = (new OrderLister($conn))->list(
        isOrderValidQuery($subscriberId)
      );

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          "buttons" => $twig->render("buttons.twig", [
            'codeStepper' => $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
          ]),
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'content' => $twig->render('edit.twig', [
          'plan' => $orders->getCount() ? $orders->getEntities()[0]->getPlan() : "",
          'codeStepper' =>  $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
          'codeSteppers' => $allCodeSteppers->getEntities(),
          'siteUrl' => Router::siteUrl(),
          'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'] ?? '',
        ]),
        'metaTitle' => 'CodeSteppers - Online interactive tool for schools and teachers',
        'description' => 'CodeSteppers - Online interactive tool for schools and teachers',
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

    $r->get("/api/init", function (Request $request) use ($conn, $twig) {
      echo json_encode([
        'scripts' => getCodestepperScriptsFull(1),
        'styles' => getCodestepperStylesFull(1),
      ]);
    });

    $r->get("/platform.js", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/javascript');
      echo str_replace("{{rootUrl}}", Router::siteUrl(), file_get_contents("../public/js/platform.js"));
    });

    $r->get('/active-plan', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      $id = $request->vars["subscriber"] ? $request->vars["subscriber"]->getId() : -1;

      if($id === -1) {
        header("Location: /edit");
        return;
      }
      $orders = (new OrderLister($conn))->list(
        isOrderValidQuery($id)
      );

      if(!$orders->getCount()) {
        header("Location: /edit");
        return;
      }

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'content' => $twig->render('plan-active.twig', [
          "plan" => $orders->getEntities()[0]->getPlan(),
          "activeUntil" =>  strtotime("+1 year", $orders->getEntities()[0]->getCreatedAt()),
        ]),
        'scripts' => [],
        'styles' => [
          ["path" => "css/plans.css"],
          ['path' => 'css/promo.css'],
          ['path' => 'css/login.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);

    });

    $r->get('/upgrade-plan', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');

      $id = $request->vars["subscriber"] ? $request->vars["subscriber"]->getId() : -1;

      if($id === -1) {
        header("Location: /edit");
        return;
      }

      $orders = (new OrderLister($conn))->list(
        isOrderValidQuery($id)
      );

      $isPayed = (bool)$orders->getCount();

      if($isPayed) {
        header("Location: /active-plan");
        return;
      }

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'content' => $twig->render('paywall.twig', [
          'error' => $_GET['error'] ?? '',
          'transactionSuccessful' => $_GET['transactionSuccessful'] ?? '',
          'transactionId' => $_GET['transactionId'] ?? '',
          'orderRef' => $_GET['orderRef'] ?? '',
        ]),
        'scripts' => [],
        'styles' => [
          ["path" => "css/plans.css"],
          ['path' => 'css/promo.css'],
          ['path' => 'css/login.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);
    });

    $r->post('/upgrade-plan/{type}', $initSubscriberSession, function (Request $request) use ($conn, $twig) {

      require_once 'simplepay/config.php';
      require_once 'simplepay/SimplePayV21.php';

      $trx = new \SimplePayStart;

      $trx->addData('currency', 'USD');
      $trx->addConfig($config);

      $priceMap = [
        'basic' => 5,
        'pro' => 10,
        'enterprise' => 25
      ];



      $trx->addItems(
        [
          'ref' => $request->vars["type"],
          'title' => "Plan: " . strtoupper($request->vars["type"]),
          'description' => "",
          'amount' => '1',
          'price' => $priceMap[$request->vars["type"]] * 12,
          'tax' => '0',
        ]
      );

      $orderRef = str_replace(array('.', ':', '/'), "", @$_SERVER['SERVER_ADDR']) . @date("U", time()) . rand(1000, 9999);
      $trx->addData('orderRef', $orderRef);

      $trx->addData('threeDSReqAuthMethod', '02');

      $subscriber = $request->vars["subscriber"];

      $trx->addData('customerEmail', $subscriber->getEmail());

      $trx->addData('language', 'EN');

      $timeoutInSec = 600;
      $timeout = @date("c", time() + $timeoutInSec);
      $trx->addData('timeout', $timeout);

      $trx->addData('methods', array('CARD'));

      $backUrl = Router::siteUrl() . '/api/back/' . $orderRef;
      $trx->addData('url', $backUrl);

      $trx->formDetails['element'] = 'button';
      try {
        $trx->runStart();
      } catch (\Throwable $th) {
        header("Location: /upgrade-plan?error=startFailed");
        exit;
      }

      $paymentUrl = $trx->getReturnData()['paymentUrl'] ?? '';

      (new OrderSaver($conn))->Save(new NewOrder(
        $subscriber->getId(),
        $request->vars["type"],
        $orderRef,
        "STARTED",
        time(),
      ));

      header("Location: " . $paymentUrl);
    });

    $r->get('/api/back/{orderRef}', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      require_once 'simplepay/config.php';
      require_once 'simplepay/SimplePayV21.php';

      $trx = new \SimplePayBack;
      $trx->addConfig($config);

      $result = [];
      if (isset($_REQUEST['r']) && isset($_REQUEST['s'])) {
        if ($trx->isBackSignatureCheck($_REQUEST['r'], $_REQUEST['s'])) {
          $result = $trx->getRawNotification();
        }
      }

      $orderRef = $request->vars['orderRef'];
      $orders = (new OrderLister($conn))->list(Router::where('ref', 'eq', $orderRef));
      $order = $orders->getEntities()[0];

      $to = fn ($status) => (new OrderPatcher($conn))->patch($order->getId(), new PatchedOrder($status));
      switch ($result['e']) {
        case 'SUCCESS':
          $to("SUCCESS");
          header('Location: /upgrade-plan?transactionSuccessful=1&orderRef=' . $result['o'] . '&transactionId=' . $result['t']);
          return;
          break;
        case 'FAIL':
          $to("FAIL");
          header('Location: /upgrade-plan?error=transactionFailed&transactionId=' . $result["t"]);
          return;
          break;
        case 'CANCEL':
          $to("CANCEL");
          header('Location: /upgrade-plan?error=transactionCancelled');
          return;
          break;
        case 'TIMEOUT':
          $to("TIMEOUT");
          header('Location: /upgrade-plan?error=transactionTimeout');
          return;
          break;
      }
    });

    $r->post("/api/send-mails", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      if (($request->body['key'] ?? 0) !== ($_SERVER['MASTER_PW'] ?? 1)) {
          http_response_code(401);
          echo json_encode(['error' => 'unauthorized']);
          return;
      }

      $messages = (new MessageLister($conn))->list(new Query(
          1000,
          0,
          new Filter(
              'and',
              new Clause('lt', 'numberOfAttempts', 10),
              new Clause('eq', 'status', "notSent"),
          ),
          new OrderBy('createdAt', 'asc')
      ));

      foreach ($messages->getEntities() as $message) {
          (new MessagePatcher($conn))->patch($message->getId(), new PatchedMessage(
              "sending",
              $message->getNumberOfAttempts() + 1,
              null,
          ));
          $isSent = (new Mailer())->sendMail($message->getEmail(), $message->getSubject(), $message->getBody());
          if ($isSent) {
              (new MessagePatcher($conn))->patch($message->getId(), new PatchedMessage(
                  "sent",
                  null,
                  time()
              ));
          } else {
              (new MessagePatcher($conn))->patch($message->getId(), new PatchedMessage(
                  "notSent",
                  null,
                  null,
              ));
          }
      }
  });

  }
}


function isOrderValidQuery($subscriberId)
{
  return new Query(
    1,
    0,
    new Filter(
      "and",
      new Clause(
        'eq',
        'subscriberId',
        $subscriberId,
      ),
      new Filter(
        "and",
        new Clause(
          'gt',
          'createdAt',
          strtotime("-1 year"),
        ),
        new Clause(
          'eq',
          'status',
          "SUCCESS",
        )
      ),
    ),
    new OrderBy('createdAt', 'desc')
  );
}

function getCodestepperEditorScripts()
{
  $codeAssistScripts = array_filter(scandir('../public/codestepper-editor/js'), filterExtension('js'));
  return array_values(array_map(fn ($item) => ['path' => "codestepper-editor/js/$item"], $codeAssistScripts));
}
function getCodestepperEditorStyles()
{
  $codeAssistStyles = array_filter(scandir('../public/codestepper-editor/css'), filterExtension('css'));
  return array_values(array_map(fn ($item) => ['path' => "codestepper-editor/css/$item"], $codeAssistStyles));
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

function getCodestepperScriptsFull($version)
{
  $codeAssistScripts = array_filter(scandir('../public/codestepper/js'), filterExtension('js'));
  return array_values(array_map(fn ($item) => "/public/codestepper/js/$item" . "?v=" . $version, $codeAssistScripts));
}
function getCodestepperStylesFull($version)
{
  $codeAssistStyles = array_filter(scandir('../public/codestepper/css'), filterExtension('css'));
  return array_values(array_map(fn ($item) => "/public/codestepper/css/$item" . "?v=" . $version, $codeAssistStyles));
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
  (new MessageSaver($conn))->Save(new NewMessage(
      $recipientEmail,
      $subject,
      $body,
      "notSent",
      0,
      null,
      time()
  ));
}


function getNick($vars)
{
  if (!isset($vars['subscriber'])) {
    return '';
  }

  return $vars['subscriber']->getEmail();
}
