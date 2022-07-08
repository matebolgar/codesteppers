<?php

namespace CodeSteppers;

use CodeSteppers\Generated\Codestepper\Patch\PatchedCodestepper;
use CodeSteppers\Generated\Codestepper\Save\NewCodestepper;
use CodeSteppers\Generated\Codestepper\Update\UpdatedCodestepper;
use Exception;
use CodeSteppers\Generated\Request;
use mysqli;
use Twig\Environment;
use CodeSteppers\Generated\Repository\Codestepper\SqlSaver as CodestepperSaver;
use CodeSteppers\Generated\Repository\Codestepper\SqlUpdater as CodestepperUpdater;
use CodeSteppers\Generated\Repository\Codestepper\SqlPatcher as CodestepperPatcher;
use CodeSteppers\Generated\Repository\Codestepper\SqlLister as CodestepperLister;
use CodeSteppers\Generated\Repository\Codestepper\SqlDeleter as CodestepperDeleter;

class CodeStepper
{
  public static function getRoutes(Pipeline $r, mysqli $conn, Environment $twig)
  {

    $initSubscriberSession = PublicSite::initSubscriberSession($conn);

    /*
    * Schema routes 
    */

    // Create CodeStepper
    $r->post("/schema", $initSubscriberSession, function (Request $request) use ($conn, $twig) {

      $codeStepperId = "";
      if ($request->vars["subscriber"]) {
        $id =  $request->vars["subscriber"]->getId();
        $codeStepperId = self::createSchemaForSubscriber($conn, $id);
      } else {
        $codeStepperId = CodeStepper::createSchemaForGuest($conn, $_COOKIE["guestId"]);
      }

      header("Location: /edit/$codeStepperId");
    });

    // Delete CodeStepper
    $r->post("/codestepper-delete/{slug}", $initSubscriberSession, function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $root = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'];
      self::deleteDir($root);

      $codeSteppers = (new CodestepperLister($conn))->list(Router::where('slug', 'eq', $request->vars['slug']));
      if ($codeSteppers->getCount()) {
        $id = $codeSteppers->getEntities()[0]->getId();
        (new CodestepperDeleter($conn))->delete($id);
      }

      $subscriberId = "";
      $guestId = null;
      $codeSteppers = [];
      if ($request->vars["subscriber"]) {
        $subscriberId = $request->vars["subscriber"]->getId();
        $codeSteppers = (new CodestepperLister($conn))->list(Router::where('subscriberId', 'eq', $subscriberId))->getEntities();
      } else {
        $guestId = $_COOKIE["guestId"];
        $codeSteppers = (new CodestepperLister($conn))->list(Router::where('guestId', 'eq', $guestId))->getEntities();
      }

      if (count($codeSteppers)) {
        $slug = $codeSteppers[0]->getSlug();
        header("Location: /edit/$slug");
        return;
      }

      header("Location: /");
    });

    // update project
    $r->put("/schema/{slug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $isHexColor = fn ($color) => (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);

      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/schema.json";
      $content = file_get_contents($path);
      $schema = json_decode($content, true);


      $primary = $isHexColor($request->body["primaryColor"]) ? $request->body["primaryColor"] : $schema["primaryColor"];
      $secondary = $isHexColor($request->body["secondaryColor"]) ? $request->body["secondaryColor"] : $schema["secondaryColor"];

      if ($request->body["title"] !== $schema["title"]) {
        $codeSteppers = (new CodestepperLister($conn))->list(Router::where('slug', 'eq', $request->vars['slug']));
        if ($codeSteppers->getCount()) {
          (new CodestepperPatcher($conn))->patch(
            $codeSteppers->getEntities()[0]->getId(),
            new PatchedCodestepper(null, null, $request->body["title"])
          );
        }
      }

      $schema["title"] = $request->body["title"] ?? $schema["title"];
      $schema["logoUrl"] = $request->body["logoUrl"] ?? $schema["logoUrl"];
      $schema["colorMode"] = $request->body["colorMode"] ?? $schema["colorMode"];
      $schema["secondaryColorMode"] = $request->body["secondaryColorMode"] ?? $schema["secondaryColorMode"];
      $schema["primaryColor"] = $primary;
      $schema["secondaryColor"] = $secondary;
      $schema["isDrawerOpenByDefault"] = $request->body["isDrawerOpenByDefault"] ?? $schema["isDrawerOpenByDefault"];

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });

    // Create part
    $r->post("/schema/{slug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $root = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'];
      $path = $root . "/schema.json";
      $content = file_get_contents($path);
      $schema = json_decode($content, true);


      $id = uniqid();
      $folderPath = $root . "/" . $id;
      mkdir($folderPath);

      $names = [
        "Első", "Második", "Harmadik", "Negyedik", "Ötödik", "Hatodik", "Hetedik", "Nyolcadik", "Kilencedik", "Tizedik"
      ];
      $i =  count($schema["parts"]);

      $schema["parts"][] = [
        "slug" => $id,
        "title" => ($names[$i] ?? "Új")  . " rész",
        "layout" => "cr-4",
        "modulePaths" => [],
      ];

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });

    // delete part
    $r->delete("/schema/{slug}/{partSlug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars['partSlug'];
      self::deleteDir($path);

      $schemaPath = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/schema.json";
      $content = file_get_contents($schemaPath);
      $schema = json_decode($content, true);
      $partIndex = -1;
      foreach ($schema["parts"] as $i => $part) {
        if ($part["slug"] ===  $request->vars['partSlug']) {
          $partIndex = $i;
          break;
        }
      }

      array_splice($schema["parts"], $partIndex, 1);

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($schemaPath, $res);
      echo $res;
    });

    // update part
    $r->put("/schema/{slug}/{partSlug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/schema.json";
      $content = file_get_contents($path);
      $schema = json_decode($content, true);

      $partIndex = -1;
      foreach ($schema["parts"] as $i => $part) {
        if ($part["slug"] ===  $request->vars['partSlug']) {
          $partIndex = $i;
          break;
        }
      }

      $schema["parts"][$partIndex]["title"] = $request->body["title"] ?? $schema["parts"][$partIndex]["title"];
      $schema["parts"][$partIndex]["layout"] = $request->body["layout"] ?? $schema["parts"][$partIndex]["layout"];
      $schema["parts"][$partIndex]["modulePaths"] = $request->body["modulePaths"] ?? $schema["parts"][$partIndex]["modulePaths"];

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });


    /*
         * Module routes 
        */

    // Create module
    $r->post("/schema/{slug}/{partSlug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $root = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'];
      $schemaPath = $root . "/schema.json";
      $content = file_get_contents($schemaPath);
      $schema = json_decode($content, true);

      $partIndex = -1;
      foreach ($schema["parts"] as $i => $part) {
        if ($part["slug"] ===  $request->vars['partSlug']) {
          $partIndex = $i;
          break;
        }
      }

      $newModule = self::getInitialModuleContent($request->body['type']);

      if (!is_dir($root . "/" . $request->vars['partSlug'])) {
        mkdir($root . "/" . $request->vars['partSlug']);
      }

      file_put_contents(
        $root . "/" . $request->vars['partSlug'] . "/" . $newModule['id'] . ".json",
        json_encode($newModule, JSON_UNESCAPED_UNICODE)
      );

      $prev = $schema["parts"][$partIndex]["modulePaths"];
      $schema["parts"][$partIndex]["modulePaths"] = array_merge($prev, [$newModule['id'] . ".json"]);

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      file_put_contents($schemaPath, $res);
      echo $res;
    });


    // Update module 
    $r->put("/schema/{projectSlug}/{partSlug}/{moduleId}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $d = fn ($n) => $request->vars[$n];

      $path = __DIR__ . "/public/codestepper-files/" . $d('projectSlug') . "/" . $d('partSlug') . "/" . $d('moduleId') . ".json";

      $res = json_encode($request->body, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });

    // Delete module
    $r->delete("/schema/{slug}/{partSlug}/{moduleId}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $root = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'];
      $path = $root . "/schema.json";
      $content = file_get_contents($path);
      $schema = json_decode($content, true);

      $partIndex = -1;
      foreach ($schema["parts"] as $i => $part) {
        if ($part["slug"] ===  $request->vars['partSlug']) {
          $partIndex = $i;
          break;
        }
      }

      $id = $request->vars['moduleId'];

      $folderPath = $root . "/" . $request->vars['partSlug'];
      unlink($folderPath . "/" . $id . ".json");

      $prev = $schema["parts"][$partIndex]["modulePaths"];
      $schema["parts"][$partIndex]["modulePaths"] = array_values(array_diff($prev, [$id . ".json"]));

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });


    /**
     * Code Surfer step
     * */


    // create codesurfer step
    $r->post("/schema/{slug}/{partSlug}/{codeSurferId}/{index}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars["partSlug"] . "/" . $request->vars["codeSurferId"] . ".json";
      $content = file_get_contents($path);
      $code = json_decode($content, true);
      $stepIndex = (int)$request->vars['index'];

      // Add CodeSurfer step
      $newStep = [
        "fileName" => "",
        "language" => $code["steps"][$stepIndex]["language"] ?? "",
        "showNumbers" => true,
        "title" => "",
        "focus" => "",
        "label" => "",
        "content" => "",
        "jumpFromPrev" => false,
      ];

      $newSteps = array_merge(array_slice($code["steps"], 0, $stepIndex + 1), [$newStep], array_slice($code["steps"], $stepIndex + 1));
      $code["steps"] = $newSteps;

      $res = json_encode($code, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);

      // Modify HTMLArray start and end values
      $htmlArray = [];
      if (isset($request->body["htmlArrayModuleId"])) {
        $htmlArrayPath = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars["partSlug"] . "/" . $request->body["htmlArrayModuleId"] . ".json";
        $content = file_get_contents($htmlArrayPath);
        $htmlArray = json_decode($content, true);


        $htmlArray["items"] = array_map(function ($item) use ($stepIndex) {
          // Ha start end között van az új index -> Csak endet kell növelni
          if ($stepIndex >= $item["start"] && $stepIndex < $item["end"]) {
            $item["end"] += 1;
            return $item;
          }

          if ($stepIndex === $item["start"] && $stepIndex === $item["end"]) {
            $item["end"] += 1;
            return $item;
          }

          if ($stepIndex < $item["start"] && $stepIndex < $item["end"]) {
            $item["end"] += 1;
            $item["start"] += 1;
            return $item;
          }

          return $item;
        }, $htmlArray["items"]);


        $refreshedHtmlArray = json_encode($htmlArray, JSON_UNESCAPED_UNICODE);
        @file_put_contents($htmlArrayPath, $refreshedHtmlArray);
      }

      echo json_encode([
        'codeSurfer' => $code,
        'htmlArray' => $htmlArray,
      ], JSON_UNESCAPED_UNICODE);
    });

    // update codesurfer step
    $r->put("/schema/{slug}/{partSlug}/{codeSurferId}/{index}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars["partSlug"] . "/" . $request->vars["codeSurferId"] . ".json";
      $content = file_get_contents($path);
      $code = json_decode($content, true);
      $stepIndex = (int)$request->vars['index'];


      $newStep = [
        "fileName" => $request->body["fileName"] ?? $code[$stepIndex]["fileName"],
        "language" => $request->body["language"] ?? $code[$stepIndex]["language"],
        "title" => $request->body["title"] ?? $code[$stepIndex]['title'],
        "focus" => $request->body["focus"] ?? $code[$stepIndex]['focus'],
        "label" => $request->body["label"] ?? $code[$stepIndex]['label'],
        "content" => $request->body["content"] ?? $code[$stepIndex]['content'],
        "jumpFromPrev" => $request->body["jumpFromPrev"] ?? $code[$stepIndex]['jumpFromPrev'],
      ];

      $code["steps"][$stepIndex] = $newStep;

      $res = json_encode($code, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
    });

    // delete codesurfer step
    $r->delete("/schema/{slug}/{partSlug}/{codeSurferId}/{index}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');
      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars["partSlug"] . "/" . $request->vars["codeSurferId"] . ".json";
      $content = file_get_contents($path);
      $code = json_decode($content, true);
      $stepIndex = (int)$request->vars['index'];

      array_splice($code["steps"], $stepIndex, 1);

      $res = json_encode($code, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);

      // Modify HTMLArray start and end values
      $htmlArray = [];
      if (isset($request->body["htmlArrayModuleId"])) {
        $htmlArrayPath = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/" . $request->vars["partSlug"] . "/" . $request->body["htmlArrayModuleId"] . ".json";
        $content = file_get_contents($htmlArrayPath);
        $htmlArray = json_decode($content, true);


        $htmlArray["items"] = array_map(function ($item) use ($stepIndex) {
          // Ha start end között van az új index -> Csak endet kell növelni
          if ($stepIndex >= $item["start"] && $stepIndex <= $item["end"]) {
            $item["end"] -= 1;
            return $item;
          }

          if ($stepIndex === $item["start"] && $stepIndex === $item["end"]) {
            $item["end"] -= 1;
            return $item;
          }

          if ($stepIndex < $item["start"] && $stepIndex < $item["end"]) {
            $item["end"] -= 1;
            $item["start"] -= 1;
            return $item;
          }

          return $item;
        }, $htmlArray["items"]);


        $refreshedHtmlArray = json_encode($htmlArray, JSON_UNESCAPED_UNICODE);
        @file_put_contents($htmlArrayPath, $refreshedHtmlArray);
      }


      echo json_encode([
        'codeSurfer' => $code,
        'htmlArray' => $htmlArray,
      ], JSON_UNESCAPED_UNICODE);
    });

    /*
         * Admin routes 
        */

    $r->get(
      '/admin/codesteppers',
      [Auth::class, 'validate'],
      function (Request $request) use ($conn, $twig) {
        $path = __DIR__ . "/public/codestepper-files/";
        $dir = array_filter(scandir($path), fn ($item) => $item !== "." && $item !== "..");
        $entities = array_map(fn ($item) => ["id" => $item], $dir);


        $rendered = "";
        foreach (array_slice($entities, $_GET["offset"] ?? 0, $_GET["limit"] ?? 10) as $entity) {
          if ($entity["id"] === ".DS_Store") {
            continue;
          }
          $rendered .= $twig->render('codestepper-editor-admin.twig', [
            'isDev' => true,
            'init' => isset($_GET['ep']) ? $_GET['ep'] : -1,
            'url' => Router::siteUrl() . parse_url(Router::siteUrl() . $_SERVER['REQUEST_URI'])['path'],
            'version' => uniqid(),
            'userIdentifier' => $entity["id"],
          ]);
        }




        header("Content-Type: text/html");
        $twig->display('dashboard.twig', [
          'entities' => $entities,
          'mainLabel' => 'CodeSteppers',
          'innerTemplate' => $rendered,
          'activePath' => '/admin/codesteppers',
          'path' => $request->path,
          'query' => $request->query,
          'pagination' => Router::getPagination($request, count($entities)),
          'actions' => Router::getActions('codesteppers', ['read']),
          'columns' => [
            ['label' => '#', 'key' => 'id'],
          ],
          'styles' => [
            ['path' => 'css/promo.css'],
            ['path' => 'css/episode-single.css'],
            ...Embeddables::getCodestepperEditorStyles(),
          ],
          'scripts' => [
            ...Embeddables::getCodestepperEditorScripts(),
          ],
        ]);
      }
    );

    $r->get(
      '/admin/codesteppers/megtekintes/{id}',
      [Auth::class, 'validate'],
      function (Request $request) use ($conn, $twig) {
        header("Content-Type: text/html");
        echo $twig->render('wrapper.twig', [
          'subscriberLabel' =>  getNick($request->vars),
          'title' => "CodeStepper szerkesztő",
          'description' => "Interaktív feladatsorok és megoldások létrehozására!",
          'metaDescription' => "Interaktív feladatsorok és megoldások létrehozására!",
          'content' => $twig->render('codestepper-editor.twig', [
            'isDev' => isset($_GET['dev']),
            'init' => isset($_GET['ep']) ? $_GET['ep'] : -1,
            'url' => Router::siteUrl() . parse_url(Router::siteUrl() . $_SERVER['REQUEST_URI'])['path'],
            'version' => uniqid(),
            'userIdentifier' => $request->vars["id"],
          ]),
          'styles' => [
            ['path' => 'css/promo.css'],
            ['path' => 'css/episode-single.css'],
            ...Embeddables::getCodestepperEditorStyles(),
          ],
          'scripts' => [
            ...Embeddables::getCodestepperEditorScripts(),
          ],
        ]);
      }
    );

    $r->post(
      '/admin/delete-codestepper/{id}',
      [Auth::class, 'validate'],
      function (Request $request) use ($conn, $twig) {
        $path = __DIR__ . "/public/codestepper-files/" . $request->vars['id'];
        self::deleteDir($path);
        header("Location:" . $_SERVER["HTTP_REFERER"]);
      }
    );
  }
  public static function getInitialProject($projectId, $partId)
  {

    return [
      "id" => $projectId,
      "slug" => $projectId,
      "title" => "",
      "logoUrl" => "",
      "primaryColor" => "#008080",
      "colorMode" => "dark",
      "secondaryColorMode" => "auto",
      "isDrawerOpenByDefault" => false,
      "parts" => [
        [
          "slug" => $partId,
          "title" => "First page",
          "layout" => "cr-4",
          "modulePaths" => [],
        ],
      ]
    ];
  }

  public static function createSchemaForSubscriber($conn, $subscriberId)
  {
    $codeStepperId = uniqid();
    $partId = uniqid();

    $root = __DIR__ . "/public/codestepper-files/" . $codeStepperId;
    $schemaPath = $root . "/schema.json";

    mkdir($root);

    $init = self::getInitialProject($codeStepperId, $partId);
    file_put_contents($schemaPath, json_encode($init, JSON_UNESCAPED_UNICODE));

    (new CodestepperSaver($conn))->Save(new NewCodestepper($codeStepperId, $subscriberId, null, "New CodeStepper", time()));
    return $codeStepperId;
  }

  public static function createSchemaForGuest($conn, $guestId)
  {
    $codeStepperId = uniqid();
    $partId = uniqid();

    $root = __DIR__ . "/public/codestepper-files/" . $codeStepperId;
    $schemaPath = $root . "/schema.json";

    mkdir($root);

    $init = self::getInitialProject($codeStepperId, $partId);
    file_put_contents($schemaPath, json_encode($init, JSON_UNESCAPED_UNICODE));

    (new CodestepperSaver($conn))->Save(new NewCodestepper($codeStepperId, null, $guestId, "New CodeStepper", time()));
    return $codeStepperId;
  }

  public static function getInitialModuleContent($type)
  {
    return [
      'html' => [
        "id" => uniqid(),
        "type" => "html",
        "content" => "<h1 class=\"text-center display-2\">\n    HTML content\n</h1>",
      ],
      'htmlArray' => [
        "id" => uniqid(),
        "type" => "htmlArray",
        "scriptUrls" => [],
        "styleUrls" => [],
        "items" => [
          [
            "start" => 0,
            "end" => 0,
            "content" => ""
          ]
        ]
      ],
      "codeSurfer" => [
        "id" => uniqid(),
        "type" => "codeSurfer",
        "theme" => "dark",
        "showNumbers" => true,
        "jumpFromPrev" => false,
        "steps" => [
          [
            "fileName" => "subtitle",
            "language" => "javascript",
            "title" => "Title",
            "focus" => "",
            "label" => "First slide",
            "content" => "/*\n* First slide\n*/\n\nconsole.log(\"Hello!\");",
          ],
        ]
      ],
      "app" => [
        "id" => uniqid(),
        "type" => "app",
        "scriptUrls" => [],
        "styleUrls" => [],
        "content" => "<h1 class=\"text-center display-2\">\n    App module HTML content\n</h1>"
      ]
    ][$type];
  }

  public static function deleteDir($dirPath)
  {
    if (!is_dir($dirPath)) {
      return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
      if (is_dir($file)) {
        self::deleteDir($file);
      } else {
        unlink($file);
      }
    }
    rmdir($dirPath);
  }
}
