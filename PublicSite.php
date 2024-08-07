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
use CodeSteppers\Generated\Repository\Order\SqlDeleter as OrderDeleter;
use CodeSteppers\Generated\Order\Save\NewOrder;
use CodeSteppers\Generated\Repository\Message\SqlSaver as MessageSaver;
use CodeSteppers\Generated\Repository\Message\SqlPatcher as MessagePatcher;
use CodeSteppers\Generated\Repository\Message\SqlLister as MessageLister;
use CodeSteppers\Mailer\Mailer;

if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
  error_reporting(E_ALL ^ E_DEPRECATED);
}

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
      "logo" => Router::siteUrl() . "/public/images/home.png"
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
        'metaTitle' => 'CodeSteppers - Create coding presentations',
        'description' => 'Create coding presentations',
        'ogTags' => [
          [
            "property" => "og:image",
            "content" => Router::siteUrl() . "/public/images/home.png?v=5",
          ],
          [
            "property" => "og:title",
            "content" => "CodeSteppers",
          ],
          [
            "property" => "og:description",
            "content" => "Create coding presentations",
          ],
        ],
        'subscriberLabel' =>  getNick($request->vars),
        'structuredData' => self::organizationStructuredData(),
        'scripts' => [
          ...getCodestepperScripts(),
        ],
        'styles' => [
          ...getCodestepperStyles(),
        ],
      ]);
    });

    $r->get('/edit', $initSubscriberSession, function (Request $request) use ($conn, $twig) {


      if (@$request->vars["subscriberId"]) {
        $id = $request->vars["subscriberId"];
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
      header('Content-Type: application/json');

      $getStatus = fn ($code) => json_encode(["status" => $code]);
      echo $getStatus(2);
      return;

      if (isset($request->query['is-embedded'])) {
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

      $order = getActiveOrder($conn, $codeStepper->getSubscriberId());

      if (!$order) {
        echo $getStatus(1);
        return;
      }

      $isLite = $order->getPlan() === "lite";

      // quota exceeded
      if (planQuotaMap()[$order->getPlan()] <= $order->getCount()) {
        echo $getStatus(0);
        return;
      }

      (new OrderPatcher($conn))->patch(
        $order->getId(),
        new PatchedOrder(null, $order->getCount() + 1, $order->getTotalCount() + 1)
      );

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

      if (@$request->vars["subscriberId"]) {
        $subscriberId = $request->vars["subscriberId"];
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
      } else {
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
            ["path" => "js/highlight.min.js"],
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
          ["path" => "js/highlight.min.js"],
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
        'scripts' => getCodestepperScriptsFull(2),
        'styles' => getCodestepperStylesFull(2),
      ]);
    });

    $r->get("/platform.js", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/javascript');
      echo str_replace("{{rootUrl}}", Router::siteUrl(), file_get_contents("../public/js/platform.js"));
    });

    $r->get('/pricing', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Pricing - CodeSteppers',
        'description' => 'Check out plans and pricing',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'content' => $twig->render('pricing.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", "pricing"),
          "planQuotaMap" => planQuotaMap(),
          'isLoggedIn' => isset($request->vars["subscriberId"])
        ]),
        'scripts' => [],
        'styles' => [
          ["path" => "css/plans.css"],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);
    });

    $r->post("/api/prompt", function (Request $request) use ($conn) {
      header('Content-Type: application/json');
      $question = $request->body['question'] ?? '';
      $language = $request->body['language'] ?? '';
      $context = $request->body['context'] ?? '';
      
      if(!$question || !$language || !$context) {
        return json_encode(['error' => 'Invalid request']);
      }

      // $answer = "A kód egy email ellenőrző validátor függvényt definiál. A validátor aszinkron módon ellenőrzi, hogy az adott email cím már foglalt-e a https://kodbazis.hu/api/is-email-taken végponton keresztül. A validátor visszatérési értéke egy RxJS Observable, amelyben a null érték azt jelenti, hogy az email cím még nem foglalt, míg egy objektum visszatérési értéke azt jelenti, hogy az email cím már foglalt, és a 'taken' kulcs értéke true.";
      // echo json_encode(['answer' => $answer]);
      // return;

      $answer = getChatGPTAnswer($language, $question, $context);
      echo $answer;
    });

    function getChatGPTAnswer($language, $question, $context) {
      $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
          [
            'role' => 'user',
            'content' => "You are giving explanation to a programming school student who is learning $language related topics. 
            Student's question: [question: $question]  
            Broader context [context: $context]
            Your answer should be in Hungarian language!
            Your answer cannot be longer than 600 characters!"
          ]
        ],
        'temperature' => 1,
        'max_tokens' => 512,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
      ];
    
      $api_key = $_SERVER['OPENAI_API_KEY'];
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($curl, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
          'Authorization: Bearer ' . $api_key,
      ]);

      $response = curl_exec($curl);
      curl_close($curl);
      $response_data = json_decode($response, true);
      $answer = $response_data['choices'][0]['message']['content'] ?? '';
      echo json_encode(['answer' => $answer]);
    }

    $r->post("/reset-subscription", $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      $email = $request->vars["subscriber"] ? $request->vars["subscriber"]->getEmail() : "";
      if ($email !== "test@codesteppers.com") {
        return;
      }

      $order = getActiveOrder($conn, $request->vars["subscriber"]->getId());
      if($order->getPlan() === "lite") {
        header("Location: /active-plan");
        return;
      }
      (new OrderDeleter($conn))->delete($order->getId());
      header("Location: /active-plan");
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
          "email" => $request->vars["subscriber"]->getEmail(),
          "planQuotaMap" => planQuotaMap(),
          "priceMap" => priceMap(),
          'error' => $_GET['error'] ?? '',
          'transactionSuccessful' => $_GET['transactionSuccessful'] ?? '',
          'transactionId' => $_GET['transactionId'] ?? '',
          'orderRef' => $_GET['orderRef'] ?? '',
          "activeUntil" =>  strtotime("+1 year", $order->getCreatedAt()),
        ]),
        'scripts' => [
          ["path" => "js/bootstrap.min.js"],
          ["path" => "js/payment-modal.js"],
        ],
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

      $vatList = ["vatNumber", "companyName", "street", "city", "zip", "country"];

      $isVatInfoReceived = true;
      foreach ($vatList as $vatItem) {
        if (($request->body[$vatItem] ?? false) === "") {
          $isVatInfoReceived = false;
          break;
        }
      }

      if ($isVatInfoReceived) {
        $trx->addGroupData('invoice', 'name', $request->body["companyName"]);
        $trx->addGroupData('invoice', 'company', $request->body["vatNumber"]);
        $trx->addGroupData('invoice', 'country', $request->body["country"]);
        $trx->addGroupData('invoice', 'state', $request->body["state"]);
        $trx->addGroupData('invoice', 'city', $request->body["city"]);
        $trx->addGroupData('invoice', 'zip', $request->body["zip"]);
        $trx->addGroupData('invoice', 'address', $request->body["street"]);
      }

      $trx->addData('currency', 'EUR');
      $trx->addConfig($config);

      $priceMap = priceMap();

      $trx->addItems(
        [
          'ref' => $request->vars["type"],
          'title' => "One year subscription for CodeSteppers - Plan: " . strtoupper($request->vars["type"]),
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

      $to = fn ($status) => (new OrderPatcher($conn))->patch($order->getId(), new PatchedOrder($status, null, null));
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

    $r->get('/api/test', function (Request $request) use ($conn, $twig) {
      sendInvoiceOrReceipt($request->query["orderRef"], $conn);
    });

    $r->post('/api/ipn', function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json; charset=utf-8');
      error_reporting(E_ALL);
      ini_set("display_errors", 1);
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


      if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
        return;
      }
      require_once 'simplepay/config.php';

      require_once 'simplepay/SimplePayV21.php';

      $trx = new \SimplePayIpn;

      $trx->addConfig($config);
      if (!$trx->isIpnSignatureCheck(json_encode($request->body))) {
        return;
      }

      if (!$trx->runIpnConfirm()) {
        return;
      }

      sendInvoiceOrReceipt($request->body["orderRef"], $conn);
    });

    function sendInvoiceOrReceipt($orderRef, $conn)
    {
      require_once 'simplepay/config.php';

      require_once 'simplepay/SimplePayV21.php';

      $trx = new \SimplePayQuery();

      $trx->addConfig($config);
      $trx->addMerchantOrderId($orderRef);
      $trx->addConfigData('merchantAccount', $config["EUR_MERCHANT"]);
      $trx->addData('detailed', true);
      $trx->runQuery();
      $ret = $trx->getReturnData();

      $transaction = $ret["transactions"][0] ?? [];
      $name = $transaction["customer"] ?? "Customer";
      $invoice = $transaction["invoice"] ?? null;

      $order = (new OrderLister($conn))->list(Router::where("ref", "eq", $orderRef));

      if (!$order->getCount()) {
        echo "Invalid orderRef";
        return;
      }

      $plan = $order->getEntities()[0]->getPlan();
      $productName = "CodeSteppers one year subscription - Plan: " . strtoupper($plan);


      if ($invoice) {
        Invoice::sendInvoice(
          $name,
          $invoice["company"],
          $invoice["zip"],
          $invoice["city"],
          $invoice["address"],
          $transaction["customerEmail"],
          $productName,
          priceMap()[$plan] * 12,
        );
        return;
      }

      Invoice::sendReceipt(
        $name,
        $productName,
        priceMap()[$plan] * 12,
        $orderRef,
      );
    }


    $items = [
      [
        "path" => "/terms-and-conditions",
        "title" => "Terms and Conditions",
        "template" => "terms.twig",
      ],
      [
        "path" => "/cookie-policy",
        "title" => "Cookie Policy",
        "template" => "cookie-policy.twig",
      ],
      [
        "path" => "/privacy-policy",
        "title" => "Privacy Policy",
        "template" => "privacy-policy.twig",
      ],
    ];

    foreach ($items as $item) {

      $r->get($item["path"], $initSubscriberSession, function (Request $request) use ($conn, $twig, $item) {
        echo $twig->render('wrapper.twig', [
          'navbar' => $twig->render("navbar.twig", [
            'subscriberLabel' => getNick($request->vars) ?? "",
          ]),
          'content' => $twig->render($item["template"], [
            'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", ""),
          ]),
          'metaTitle' => $item["title"],
          'description' => $item["title"],
          'structuredData' => self::organizationStructuredData(),
          'scripts' => [],
          'styles' => [],
        ]);
      });
    }



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

function priceMap()
{
  return [
    'basic' => 5,
    'pro' => 10,
    'enterprise' => 25
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
