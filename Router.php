<?php

namespace CodeSteppers;

use FastRoute\RouteCollector;
use mysqli;
use CodeSteppers\Generated\Auth\AuthException;
use CodeSteppers\Generated\Listing\Clause;
use CodeSteppers\Generated\Listing\OrderBy;
use CodeSteppers\Generated\Listing\Query;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;

class Router
{
    private $twig;

    function __construct()
    {
        $twig = new Environment(new FilesystemLoader(__DIR__ . DIRECTORY_SEPARATOR . 'views'), ['debug' => true]);
        $twig->getExtension(CoreExtension::class)->setTimezone('Europe/Budapest');
        $twig->addFilter(new TwigFilter('translation', [Translation::class, 'get']));
        $twig->addFilter(new TwigFilter('html_entity_decode', 'html_entity_decode'));

        if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
            $twig->addExtension(new DebugExtension());
        }

        $this->twig = $twig;
    }

    public function registerRoutes(RouteCollector $r, mysqli $conn)
    {
        $twig = $this->twig;
        if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
            $conn->set_charset('utf8');
        }

        $routes = [
            Subscriber::class,
            CodeStepper::class,
            PublicSite::class,
        ];


        foreach ($routes as $route) {
            call_user_func([$route, 'getRoutes'], new Pipeline($r), $conn, $twig);
        }

        $r->post('/save-img', function (Request $request) use ($conn, $twig) {
            echo json_encode(['default' => '/src/public/images/' . self::saveImage($request->files['file'])]);
        });



        function alignToRows($arr, $itemsPerRow)
        {
            $ret = [];
            $i = 0;
            $k = 0;
            foreach ($arr as $item) {
                $ret[$i][] = $item;
                $k++;
                if ($k === $itemsPerRow) {
                    $k = 0;
                    $i++;
                    continue;
                }
            }
            return $ret;
        }

        $r->get('/export/{resourceName}', function (Request $request) use ($conn) {
            $exportWhitelist = [
                'posts',
            ];
            if (!in_array($request->vars['resourceName'], $exportWhitelist)) {
                throw new OperationError();
            }
            $data = json_encode((new DynamicLister($conn))
                ->list(
                    $request->vars['resourceName'],
                    Router::toQuery($request->query, $request->query['exportColumns'] ?? [])
                ));

            $filename = $request->vars['resourceName'] . ".xls";
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header("Content-Disposition: attachment; filename=\"$filename\"");

            $flag = false;
            foreach (json_decode($data, true) as $row) {
                if (!$flag) {
                    echo implode("\t", array_keys($row)) . "\n";
                    $flag = true;
                }
                array_walk($row, function ($str) {
                    $str = preg_replace("/\t/", "\\t", $str);
                    $str = preg_replace("/\r?\n/", "\\n", $str);
                    if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
                });

                echo implode("\t", array_values($row)) . "\n";
            }
        });
    }

    public function registerNotFoundRoute($conn)
    {
        header('Content-Type: text/html; charset=UTF-8');
        http_response_code(404);
        echo $this->twig->render('wrapper.twig', [
          'navbar' => $this->twig->render("navbar.twig", [
            'subscriberLabel' => "",
          ]),
          'content' => $this->twig->render("404.twig", [
            'sidebar' => getSidebar($conn, $this->twig, $_SESSION['subscriberId'] ?? "", ""),
          ]),
          'metaTitle' => "Page Not Found",
          'description' => "Page Not Found",
          'structuredData' => PublicSite::organizationStructuredData(),
          'scripts' => [],
          'styles' => [],
        ]);
       
    }

    public static function saveImage($image)
    {
        if (!$image['tmp_name']) {
            return '';
        }

        $types = [
            'image/jpeg' => '.jpg',
        ];

        if (!$types[$image['type']]) {
            return '';
        }

        $fullName = uniqid(rand(), true) . $types[$image['type']];
        return file_put_contents(__DIR__ . '/public/images/' . $fullName, file_get_contents($image['tmp_name'])) ?
            $fullName :
            '';
    }

    public static function mergeQueries($url, $params)
    {
        $qs = '?' . implode('&', $params);
        $url_parsed = parse_url($url);
        $qs_parsed = parse_url($qs);

        $args = array(
            $url_parsed['query'] ?? '',
            $qs_parsed['query'] ?? '',
        );

        $res =  array_values(array_unique(explode('&', implode('&', $args))));
        return '?' . implode('&', $res);
    }


    public static function toQuery(array $query, $columns = []): Query
    {
        $filter = empty($query['filterKey']) ?
            null :
            new Clause($query['operator'] ?? 'eq', $query['filterKey'] ?? 1, $query['filterValue'] ?? 1);

        $orderBy = empty($query['orderByValue']) ?
            new OrderBy('createdAt', 'desc') :
            new OrderBy($query['orderByKey'], $query['orderByValue']);

        return new Query($query['limit'] ?? 15, $query['from'] ?? 0, $filter, $orderBy, $columns);
    }

    public static function where($key, $operator, $value, $orderBy = "desc"): Query
    {
        return new Query(
            15,
            0,
            new Clause($operator ?? 'eq', $key ?? 1, $value ?? 1),
            new OrderBy('createdAt', $orderBy),
            []
        );
    }

    public static function getPagination(Request $request, int $total)
    {
        if ($total === 0) {
            return [];
        }
        return [
            'items' => array_map(function ($num) use ($total, $request) {
                return [
                    'label' => $num,
                    'url' => '?from=' . ($num - 1) * 15 . '&limit=15',
                    'isActive' => (int)($request->query['from'] ?? 0) === (int)($num - 1) * 15
                ];
            }, range(1, ceil($total / 15))),
            'currentFrom' => $request->query['from'] ?? '',
            'currentLimit' => $request->query['limit'] ?? ''


        ];
    }

    public static function siteUrl()
    {
        return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    }

    public static function getActions($resourceName, $actions = ['create', 'read', 'edit', 'export', 'filter', 'orderBy'])
    {
        $ret = [];

        if (in_array('filter', $actions)) {
            $ret['filter'] = "filter";
        }
        if (in_array('orderBy', $actions)) {
            $ret['orderBy'] = "orderBy";
        }

        if (in_array('export', $actions)) {
            $ret['export'] = "/export/$resourceName";
        }
        if (in_array('create', $actions)) {
            $ret['create'] = "/admin/$resourceName/letrehozas";
        }
        if (in_array('read', $actions)) {
            $ret['read'] = new ClosureWrapper(function ($slug) use ($resourceName) {
                return "/admin/$resourceName/megtekintes/$slug";
            });
        }
        if (in_array('delete', $actions)) {
            $ret['delete'] = new ClosureWrapper(function ($id) use ($resourceName) {
                return "/admin/$resourceName/delete/$id";
            });
        }
        if (in_array('edit', $actions)) {
            $ret['edit'] = new ClosureWrapper(function ($id) use ($resourceName) {
                return "/admin/$resourceName/szerkesztes/$id";
            });
        }

        return $ret;
    }

    public static function setCsrfToken(Request $request)
    {
        session_start();
        if (empty($_SESSION['csrfToken'])) {
            $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
        }
        $token = $_SESSION['csrfToken'];
        $request->params['csrfToken'] = $token;
        return $request;
    }

    public static function validateCsrfToken(Request $request)
    {
        session_start();
        if (empty($_SESSION['csrfToken']) || empty($_POST['csrfToken'])) {
            throw new AuthException('invalid csrf token');
        }
        if (!hash_equals($_SESSION['csrfToken'] ?? '', $_POST['csrfToken'] ?? '')) {
            throw new AuthException('invalid csrf token');
        }

        return $request;
    }
}
