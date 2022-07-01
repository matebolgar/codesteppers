<?php

namespace CodeSteppers;

use Exception;
use CodeSteppers\Generated\Request;
use mysqli;
use Twig\Environment;

class CodeStepper
{
  public static function getRoutes(Pipeline $r, mysqli $conn, Environment $twig)
  {

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


    /*
         * Schema routes 
        */

    // update project
    $r->put("/schema/{slug}", function (Request $request) use ($conn, $twig) {
      header('Content-Type: application/json');

      $isHexColor = fn ($color) => (bool)preg_match('/^#[a-f0-9]{6}$/i', $color);

      $path = __DIR__ . "/public/codestepper-files/" . $request->vars['slug'] . "/schema.json";
      $content = file_get_contents($path);
      $schema = json_decode($content, true);


      $primary = $isHexColor($request->body["primaryColor"]) ? $request->body["primaryColor"] : $schema["primaryColor"];
      $secondary = $isHexColor($request->body["secondaryColor"]) ? $request->body["secondaryColor"] : $schema["secondaryColor"];

      $schema["title"] = $request->body["title"] ?? $schema["title"];
      $schema["logoUrl"] = $request->body["logoUrl"] ?? $schema["logoUrl"];
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
      @file_put_contents(
        $root . "/" . $request->vars['partSlug'] . "/" . $newModule['id'] . ".json",
        json_encode($newModule, JSON_UNESCAPED_UNICODE)
      );

      $prev = $schema["parts"][$partIndex]["modulePaths"];
      $schema["parts"][$partIndex]["modulePaths"] = array_merge($prev, [$newModule['id'] . ".json"]);

      $res = json_encode($schema, JSON_UNESCAPED_UNICODE);
      @file_put_contents($schemaPath, $res);
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

      $newStep = [
        "fileName" => $code["steps"][$stepIndex]["fileName"] ?? "",
        "showNumbers" => true,
        "title" => "",
        "focus" => "",
        "label" => "",
        "content" => "/*\n*\n*  Új slide\n*\n*\n*\n*\n*\n*\n*/",
      ];

      $newSteps = array_merge(array_slice($code["steps"], 0, $stepIndex + 1), [$newStep], array_slice($code["steps"], $stepIndex + 1));
      $code["steps"] = $newSteps;

      $res = json_encode($code, JSON_UNESCAPED_UNICODE);
      @file_put_contents($path, $res);
      echo $res;
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
        "showNumbers" => true,
        "title" => $request->body["title"] ?? $code[$stepIndex]['title'],
        "focus" => $request->body["focus"] ?? $code[$stepIndex]['focus'],
        "label" => $request->body["label"] ?? $code[$stepIndex]['label'],
        "content" => $request->body["content"] ?? $code[$stepIndex]['content'],
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
      echo $res;
    });
  }
  public static function getInitialProject($projectId, $partId)
  {

    return [
      "id" => $projectId,
      "slug" => "Új projekt",
      "title" => "OKKKKK",
      "logoUrl" => "",
      "primaryColor" => "#827717",
      "secondaryColor" => "#172282",
      "isDrawerOpenByDefault" => false,
      "parts" => [
        [
          "slug" => $partId,
          "title" => "Feladat címe",
          "layout" => "cr-4",
          "modulePaths" => [],
        ],
      ]
    ];
  }




  public static function getInitialModuleContent($type)
  {
    return [
      'html' => [
        "id" => uniqid(),
        "type" => "html",
        "content" => "<h1 class=\"text-center display-2\">\n    HTML tartalom\n</h1>",
      ],
      'htmlArray' => [
        "id" => uniqid(),
        "type" => "htmlArray",
        "scriptUrls" => [],
        "styleUrls" => [],
        "items" => [
          [
            "start" => 21,
            "end" => 24,
            "content" => "/*\n* Második slide\n*/\n\nconsole.log(\"teszt\");"
          ]
        ]
      ],
      "codeSurfer" => [
        "id" => uniqid(),
        "type" => "codeSurfer",
        "steps" => [
          [
            "fileName" => "app.js",
            "title" => "Első slide címe",
            "focus" => "",
            "label" => "Első slide",
            "showNumbers" => true,
            "content" => "/*\n* Első slide\n*/\n\nconsole.log(\"teszt\");",
          ],
          [
            "fileName" => "app.js",
            "title" => "Második slide címe",
            "focus" => "",
            "label" => "Második slide",
            "showNumbers" => true,
            "content" => "/*\n* Második slide\n*/\n\nconsole.log(\"második slide\");",
          ]
        ]
      ],
      "app" => [
        "id" => uniqid(),
        "type" => "app",
        "scriptUrls" => [],
        "styleUrls" => [],
        "content" => "<h1 class=\"text-center display-2\">\n    App modul HTML tartalom\n</h1>"
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
