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
      $request->vars['subscriberId'] = $subscriber->getId();
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
        'isHome' => true,
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
          'buttons' => $twig->render('try-out-button.twig', ["isLoggedIn" => getNick($request->vars) ?? ""]),
        ]),
        'content' => $twig->render('home.twig', [
          'codeSteppers' => [],
          'isLoggedIn' => isset($request->vars["subscriber"]),
          'siteUrl' => Router::siteUrl(),
        ]),
        'metaTitle' => 'CodeSteppers - Embeddable presentation for teachers and online schools',
        'description' => 'CodeSteppers - Embeddable presentation for teachers and online schools',
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
        $codeStepperId = CodeStepper::createSchemaForSubscriber($conn, $id);

        header("Location: /edit/$codeStepperId");
      }
    });


    $r->get("/status/{schemaId}", $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      @header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
      header("Access-Control-Allow-Credentials: true");
      header('Access-Control-Allow-Methods: GET');
      header('Content-Type: application/json');

      $getStatus = fn ($code) => json_encode(["status" => $code]);

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
      $isLoggedIn = false;


      $order = getActiveOrder($conn, $codeStepper->getSubscriberId());

      if (!$order) {
        echo $getStatus(-1);
        http_response_code(404);
        return;
      }

      $isLite = $order->getPlan() === "lite";

      // quota exceeded
      if (planQuotaMap()[$order->getPlan()] <= $order->getCount()) {
        echo $getStatus(0);
        return;
      }

      (new OrderPatcher($conn))->patch($order->getId(), new PatchedOrder(null, $order->getCount() + 1));

      if ($isLite) {
        echo $getStatus(1);
        return;
      }

      if (!$isLite) {
        echo $getStatus(2);
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


      if (!$subscriberId) {
        echo $twig->render('wrapper.twig', [
          'navbar' => $twig->render("navbar.twig", [
            "buttons" => $twig->render("buttons.twig", [
              'codeStepper' => $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
            ]),
            'subscriberLabel' => getNick($request->vars) ?? "",
          ]),
          'content' => $twig->render('edit-guest.twig', [
            'sidebar' => $twig->render("sidebar.twig", [
              'order' => null,
              'isLoggedIn' => (bool)$subscriberId,
              'activeItem' => "editor",
              'codeSteppers' => $allCodeSteppers->getEntities(),
              'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'] ?? '',
            ]),
            'codeStepper' =>  $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
            'siteUrl' => Router::siteUrl(),
          ]),
          'metaTitle' => 'Editor - CodeSteppers',
          'description' => 'Try out the CodeSteppers editor',
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

      $order = getActiveOrder($conn, $subscriberId);

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          "buttons" => $twig->render("buttons.twig", [
            'codeStepper' => $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
          ]),
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'content' => $twig->render('edit.twig', [
          'sidebar' => $twig->render("sidebar.twig", [
            'order' => getActiveOrder($conn, $subscriberId),
            'isLoggedIn' => true,
            'codeSteppers' => $allCodeSteppers->getEntities(),
            'activeItem' => 'editor',
            'activeCodeStepperSlug' =>  $request->vars['codeStepperSlug'] ?? '',
          ]),
          'codeStepper' =>  $codeSteppersBySlug->getCount() ? $codeSteppersBySlug->getEntities()[0] : '',
          'siteUrl' => Router::siteUrl(),
        ]),
        'metaTitle' => 'Editor - CodeSteppers',
        'description' => 'Editor - CodeSteppers',
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

      if ($id === -1) {
        header("Location: /edit");
        return;
      }

      $order = getActiveOrder($conn, $id);

      if (!$order) {
        header("Location: /edit");
        return;
      }

      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Current Plan - CodeSteppers',
        'description' => 'Check out your current usage and upgrade plan if necessary',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'content' => $twig->render('plan-active.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", "plan"),
          "order" => $order,
          "planQuotaMap" => planQuotaMap(),
          'error' => $_GET['error'] ?? '',
          'transactionSuccessful' => $_GET['transactionSuccessful'] ?? '',
          'transactionId' => $_GET['transactionId'] ?? '',
          'orderRef' => $_GET['orderRef'] ?? '',
          "activeUntil" =>  strtotime("+1 year", $order->getCreatedAt()),
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
        header("Location: /active-plan?error=startFailed");
        exit;
      }

      $paymentUrl = $trx->getReturnData()['paymentUrl'] ?? '';

      (new OrderSaver($conn))->Save(new NewOrder(
        $subscriber->getId(),
        $request->vars["type"],
        $orderRef,
        "STARTED",
        0,
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

      $to = fn ($status) => (new OrderPatcher($conn))->patch($order->getId(), new PatchedOrder($status, null));
      switch ($result['e']) {
        case 'SUCCESS':
          $to("SUCCESS");
          header('Location: /active-plan?transactionSuccessful=1&orderRef=' . $result['o'] . '&transactionId=' . $result['t']);
          return;
          break;
        case 'FAIL':
          $to("FAIL");
          header('Location: /active-plan?error=transactionFailed&transactionId=' . $result["t"]);
          return;
          break;
        case 'CANCEL':
          $to("CANCEL");
          header('Location: /active-plan?error=transactionCancelled');
          return;
          break;
        case 'TIMEOUT':
          $to("TIMEOUT");
          header('Location: /active-plan?error=transactionTimeout');
          return;
          break;
      }
    });

    $r->get("/terms-and-conditions", function (Request $request) use ($conn, $twig) {

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'content' => $twig->render("terms.twig", [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", ""),
        ]),
        'metaTitle' => 'Terms and Conditions',
        'description' => 'Terms and Conditions',
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [],
        'styles' => [],
      ]);
    });


    $r->get("/support", $initSubscriberSession, function (Request $request) use ($conn, $twig) {

      echo $twig->render('wrapper.twig', [
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'content' => $twig->render("support.twig", [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", "support"),
        ]),
        'metaTitle' => 'Support',
        'description' => 'Support',
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [],
        'styles' => [],
      ]);
    });




    $r->post("/api/reset-plans", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      if (($request->body['key'] ?? 0) !== ($_SERVER['MASTER_PW'] ?? 1)) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        return;
      }

      $conn->query("UPDATE `orders` SET `count` = '0'");
    });

    $r->post("/api/prune-guest-codesteppers", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      if (($request->body['key'] ?? 0) !== ($_SERVER['MASTER_PW'] ?? 1)) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        return;
      }

      $res = $conn->query("SELECT * FROM codesteppers WHERE codesteppers.subscriberId IS NULL");
      $codeSteppers =  mysqli_fetch_all($res, MYSQLI_ASSOC);

      require __DIR__ . "/dir-utils.php";

      foreach ($codeSteppers as $codeStepper) {
        $root = __DIR__ . "/public/codestepper-files/" . $codeStepper["slug"];
        rrmdir($root);
      }

      $conn->query("DELETE FROM codesteppers WHERE codesteppers.subscriberId IS NULL");
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

function getSidebar($conn, $twig, $subscriberId, $activeItem)
{
  return $twig->render("sidebar.twig", [
    'order' => getActiveOrder($conn, $subscriberId),
    'isLoggedIn' => (bool)$subscriberId,
    'activeItem' => $activeItem
  ]);
}

function getActiveOrder($conn, $subscriberId)
{
  $orders = (new OrderLister($conn))->list(lastOrderWithinAYearQuery($subscriberId));
  if ($orders->getCount()) {
    return $orders->getEntities()[0];
  }

  $liteOrders = (new OrderLister($conn))->list(liteOrderQuery($subscriberId));

  if (!$liteOrders->getCount()) {
    return;
  }

  return $liteOrders->getEntities()[0];
}


function lastOrderWithinAYearQuery($subscriberId)
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

function liteOrderQuery($subscriberId)
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
      new Clause(
        'eq',
        'plan',
        'lite',
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


function planQuotaMap()
{
  return [
    "lite" => 200,
    "basic" => 5000,
    "pro" => 50000,
    "enterprise" => 5000000,
    "admin" => 500000000,
  ];
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
