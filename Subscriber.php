<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Listing\Query;
use CodeSteppers\Generated\Order\Save\NewOrder;
use mysqli;
use Twig\Environment;
use CodeSteppers\Generated\Repository\Subscriber\SqlLister as SubscriberLister;
use CodeSteppers\Generated\Repository\Subscriber\SqlPatcher as SubscriberPatcher;
use CodeSteppers\Generated\Repository\Subscriber\SqlSaver as SubscriberSaver;
use CodeSteppers\Generated\Subscriber\Patch\PatchedSubscriber;
use CodeSteppers\Generated\Request;
use CodeSteppers\Generated\Subscriber\Save\NewSubscriber;
use CodeSteppers\Generated\Repository\Order\SqlSaver as OrderSaver;
use CodeSteppers\Generated\Repository\Order\SqlLister as OrderLister;

class Subscriber
{


  public static function getRoutes(Pipeline $r, mysqli $conn, Environment $twig)
  {

    $initSubscriberSession = PublicSite::initSubscriberSession($conn);

    $r->get('/sign-up', $initSubscriberSession, function (Request $request) use ($conn, $twig) {



      header('Content-Type: text/html; charset=UTF-8');

      $order = null;
      if (isset($request->vars["subscriber"])) {
        $order = getActiveOrder($conn, $request->vars["subscriber"]->getId());
      }

      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Sign Up - CodeSteppers',
        'description' => 'Sign up',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'noIndex' => true,
        'content' => $twig->render('subscriber-registration.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", "user"),
          "plan" => $order ? $order->getPlan() : "",
          'isLoggedIn' => isset($_SESSION['subscriberId']),
          'registrationSuccessful' => isset($_GET['registrationSuccessful']),
          'registrationEmailSent' => isset($_GET['registrationEmailSent']),
          'error' => $_GET['error'] ?? '',
          'subscriberLabel' =>  getNick($request->vars),
          'email' => $_GET['email'] ?? '',
        ]),
        'styles' => [
          ['path' => 'css/login.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);
    });

    $r->post('/api/request-membership', function (Request $request) use ($conn, $twig) {

      $byEmail = (new SubscriberLister($conn))->list(Router::where('email', 'eq', $request->body['email']));

      if ($byEmail->getCount() !== 0) {
        $params = ['error=emailTaken'];
        header('Location: ' .  $_SERVER['HTTP_REFERER']  . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));

        return;
      }

      if (!filter_var($request->body['email'], FILTER_VALIDATE_EMAIL)) {
        $params = ['error=invalidEmail'];
        header('Location: ' .  $_SERVER['HTTP_REFERER']  . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));
        return;
      }

      $token = uniqid();
      $newSubscriber = (new SubscriberSaver($conn))->Save(new NewSubscriber(
        $request->body['email'],
        password_hash($request->body['password'], PASSWORD_DEFAULT),
        0,
        $token,
        time(),
        0
      ));

      if (isset($_COOKIE["guestId"])) {
        $stmt = $conn->prepare("UPDATE `codesteppers` SET `subscriberId` = ?, `guestId` = NULL WHERE `codesteppers`.`guestId` = ?");
        $subscriberId = $newSubscriber->getId();
        $guestId = $_COOKIE["guestId"];
        $stmt->bind_param("is", $subscriberId, $guestId);
        $stmt->execute();

        $cookieParams = session_get_cookie_params();
        setcookie("guestId", '', 0, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], isset($cookieParams['httponly']));
      }

      $body = $twig->render('verification-email.twig', [
        'email' => $request->body['email'],
        'link' => Router::siteUrl() . "/verification/" . $token . "?referer=" . $_SERVER['HTTP_REFERER'],
      ]);

      $params = ['registrationEmailSent=1'];
      header('Location: ' .  $_SERVER['HTTP_REFERER']  . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));

      enqueueEmail(
        $request->body['email'],
        'Verification by one click',
        $body,
        $conn
      );
    });

    $r->get('/verification/{token}', function (Request $request) use ($conn, $twig) {
      $byToken = (new SubscriberLister($conn))->list(Router::where('verificationToken', 'eq', $request->vars['token']));

      if ($byToken->getCount() === 0) {
        header('Location: /');
        return;
      }

      $subscriber = $byToken->getEntities()[0];

      if ($subscriber->getIsVerified()) {
        header('Location: /');
        return;
      }

      $byToken = (new SubscriberPatcher($conn))->patch($subscriber->getId(), new PatchedSubscriber(null, null, 1, '', null, null));
      
      (new OrderSaver($conn))->Save(new NewOrder(
        $subscriber->getId(),
        "lite",
        "",
        "SUCCESS",
        0,
        time() - 60,
      ));
      
      // Initial offer - remove after beta period
      (new OrderSaver($conn))->Save(new NewOrder(
        $subscriber->getId(),
        "basic",
        "",
        "SUCCESS",
        0,
        time(),
      ));

      session_start();
      $_SESSION['subscriberId'] = $subscriber->getId();
      $requestUri = parse_url($_GET['referer'])['path'];
      $params = ['registrationSuccessful=1'];
      header('Location: ' .  $requestUri . Router::mergeQueries($_GET['referer'], $params));
    });


    $r->get('/forgot-password', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');
      if (isset($request->vars['subscriber'])) {
        header('Location: /');
        return;
      }

      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Forgot Password',
        'description' => 'Forgot Password',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => "",
          'isLoginButtonHidden' => true
        ]),
        "title" => "Elfelejtett jelszó",
        'content' => $twig->render('forgot-password.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", "user"),
          'isError' => isset($_GET['isError']),
          'emailSent' => isset($_GET['emailSent']),
          'referer' => $_SERVER['HTTP_REFERER'] ?? '',
        ]),
        'subscriberLabel' =>  getNick($request->vars),
        'styles' => [
          ['path' => 'css/login.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
        'noIndex' => true,
      ]);
    });

    $r->get('/api/forgot-password', function (Request $request) {
      header('Location: /forgot-password?isError=1');
    });

    $r->post('/api/forgot-password', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');
      if (isset($request->vars['subscriber'])) {
        header('Location: /');
        return;
      }

      $byEmail = (new SubscriberLister($conn))->list(Router::where('email', 'eq', $request->body['email']));

      if ($byEmail->getCount() === 0) {
        header('Location: /forgot-password?isError=1');
        return;
      }
      header('Location: /forgot-password?emailSent=1');

      $subscriber = $byEmail->getEntities()[0];
      $token = uniqid();

      (new SubscriberPatcher($conn))->patch(
        $subscriber->getId(),
        new PatchedSubscriber(null, null, 1, $token, null)
      );

      $body = $twig->render('forgot-password-email.twig', [
        'email' => $subscriber->getEmail(),
        'link' => Router::siteUrl() . "/password-change/" . $token . "?referer=" . $_GET['referer'],
      ]);

      enqueueEmail(
        $subscriber->getEmail(),
        'Password change',
        $body,
        $conn
      );
    });


    $r->get('/password-change/{token}', function (Request $request) use ($conn, $twig) {
      $byToken = (new SubscriberLister($conn))->list(Router::where('verificationToken', 'eq', $request->vars['token']));

      if ($byToken->getCount() === 0) {
        header('Location: /');
        return;
      }
      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Password change',
        'description' => 'Password change',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => "",
          'isLoginButtonHidden' => true
        ]),
        'content' => $twig->render('create-new-password.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", ""),
          'token' => $request->vars['token'],
          'referer' => $_GET['referer'] ?? '',
        ]),
        'styles' => [
          ['path' => 'css/login.css']
        ],
      ]);
    });

    $r->post('/api/patch-subscriber-password', function (Request $request) use ($conn) {
      $byToken = (new SubscriberLister($conn))->list(Router::where('verificationToken', 'eq', $request->body['token']));

      if ($byToken->getCount() === 0) {
        header('Location: /');
        return;
      }

      $subscriber = $byToken->getEntities()[0];

      if (!$subscriber->getIsVerified()) {
        header('Location: /');
        return;
      }

      $password = password_hash($request->body['password'], PASSWORD_DEFAULT);
      $byToken = (new SubscriberPatcher($conn))->patch($subscriber->getId(), new PatchedSubscriber(null, $password, 1, '', null));
      if ($_GET['referer']) {
        $parsedUrl = parse_url($_GET['referer']);
        $params = ['isPasswordModificationSuccess=1'];
        header('Location: ' .  $parsedUrl['path'] . Router::mergeQueries($_GET['referer'], $params));
        return;
      }
      header('Location: /login?isPasswordModificationSuccess=1');
    });

    $r->get('/password-modification-successful', function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Password modification successful',
        'description' => 'Password modification successful',
        'content' => $twig->render('subscriber-password-modification-success.twig', []),
      ]);
    });

    $r->get('/login', $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: text/html; charset=UTF-8');
      echo $twig->render('wrapper.twig', [
        'metaTitle' => 'Login - CodeSteppers',
        'description' => 'Login - CodeSteppers',
        'navbar' => $twig->render("navbar.twig", [
          'subscriberLabel' => getNick($request->vars) ?? "",
        ]),
        'structuredData' => PublicSite::organizationStructuredData(),
        'subscriberLabel' =>  getNick($request->vars),
        'content' => $twig->render('subscriber-login.twig', [
          'sidebar' => getSidebar($conn, $twig, $request->vars["subscriberId"] ?? "", ""),
          'subscriberLabel' =>  getNick($request->vars),
          'isLoggedIn' => isset($_SESSION['subscriberId']),
          'error' => $_GET['error'] ?? '',
          'loginSuccess' => isset($_GET['loginSuccess']),
          'isPasswordModificationSuccess' => isset($_GET['isPasswordModificationSuccess']),
          'email' => $_GET['email'] ?? '',
        ]),
        'scripts' => [],
        'styles' => [
          ['path' => 'css/login.css'],
          ['path' => 'css/fonts/fontawesome/css/fontawesome-all.css'],
        ],
      ]);
    });

    $r->post('/api/subscriber-logout', function (Request $request) use ($conn, $twig) {
      session_start();
      $params = session_get_cookie_params();
      setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
      session_destroy();

      $requestUri = parse_url($_SERVER['HTTP_REFERER'])['path'];
      header('Location: /');
    });

    $r->post('/api/subscriber-login', function (Request $request) use ($conn, $twig) {
      $byEmail = (new SubscriberLister($conn))->list(Router::where('email', 'eq', $request->body['email']));

      $parsed = parse_url($_SERVER['HTTP_REFERER']);
      $requestUri = $parsed['path'];


      if (!$byEmail->getCount()) {
        $params = [
          'error=invalidCredentials',
          'email=' . $request->body['email'],
        ];
        header('Location: ' .  $requestUri . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));
        return;
      }

      $subscriber = $byEmail->getEntities()[0];

      if (!$subscriber->getIsVerified()) {
        $params = [
          'error=notVerified',
          'email=' . $request->body['email'],
        ];
        header('Location: ' .  $requestUri . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));
        return;
      }

      if (!password_verify($request->body['password'], $subscriber->getPassword())) {
        $params = [
          'error=invalidCredentials',
          'email=' . $request->body['email'],
        ];

        header('Location: ' .  $requestUri . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));
        return;
      }

      session_start();
      $_SESSION['subscriberId'] = $subscriber->getId();
      $params = [
        'loginSuccess=1',
      ];


      if (isset($_COOKIE["guestId"])) {
        $cookieParams = session_get_cookie_params();
        setcookie("guestId", '', 0, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], isset($cookieParams['httponly']));
      }

      header('Location: /edit' . Router::mergeQueries($_SERVER['HTTP_REFERER'], $params));
    });
  }
}
