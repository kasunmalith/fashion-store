<?php
declare(strict_types=1);

function routeSlug(string $value): string {
    return trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
}

function prettyUrl(string $page, array $args = []): string {
    unset($args['page']);
    $path = match ($page) {
        'home' => '',
        'shop' => 'shop/',
        'product' => 'product/',
        'policy' => 'policy/',
        'order' => 'order/',
        default => $page.'/',
    };
    if ($page === 'shop' && !empty($args['category'])) {
        $category = one('SELECT slug FROM categories WHERE id=? AND enabled=1', [(int)$args['category']]);
        if ($category) $path = 'category/'.rawurlencode($category['slug']).'/';
        unset($args['category']);
    } elseif ($page === 'shop' && !empty($args['brand'])) {
        $brand = one('SELECT name FROM brands WHERE id=? AND enabled=1', [(int)$args['brand']]);
        if ($brand) $path = 'brand/'.routeSlug($brand['name']).'/';
        unset($args['brand']);
    } elseif ($page === 'product') {
        if (!empty($args['id'])) {
            $product = one('SELECT slug FROM products WHERE id=?', [(int)$args['id']]);
            $path = 'product/'.rawurlencode($product['slug'] ?? 'unknown').'/';
        } elseif (!empty($args['slug'])) {
            $path = 'product/'.rawurlencode((string)$args['slug']).'/';
        }
        unset($args['id'], $args['slug']);
    } elseif ($page === 'policy') {
        $path = 'policy/'.rawurlencode((string)($args['slug'] ?? 'unknown')).'/';
        unset($args['slug']);
    } elseif ($page === 'order') {
        $path = 'order/'.(int)($args['id'] ?? 0).'/';
        unset($args['id']);
    }
    return base($path).($args ? '?'.http_build_query($args) : '');
}

function resolveStoreRoute(): string {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $prefix = rtrim(parse_url(config('base_url', ''), PHP_URL_PATH) ?: '', '/');
    if ($prefix !== '' && !str_starts_with($requestPath, $prefix.'/') && $requestPath !== $prefix) return 'missing';
    $path = trim(substr($requestPath, strlen($prefix)), '/');

    if ($path === 'index.php') {
        $page = (string)($_GET['page'] ?? 'home');
        $args = $_GET;
        unset($args['page']);
        if (in_array($page, ['home','shop','product','cart','checkout','login','register','account','order','track','wishlist','contact','policy','forgot','reset'], true)) {
            header('Location: '.prettyUrl($page, $args), true, 301);
            exit;
        }
        return 'missing';
    }
    if ($path === '') return 'home';

    if ($path === 'shop') {
        if (!empty($_GET['category']) || !empty($_GET['brand'])) {
            header('Location: '.prettyUrl('shop', $_GET), true, 301);
            exit;
        }
        return 'shop';
    }
    if (preg_match('~^category/([a-z0-9-]+)$~D', $path, $m)) {
        $row = one('SELECT id FROM categories WHERE slug=? AND enabled=1', [$m[1]]);
        if (!$row) return 'missing';
        $_GET['category'] = (int)$row['id'];
        return 'shop';
    }
    if (preg_match('~^brand/([a-z0-9-]+)$~D', $path, $m)) {
        foreach (all('SELECT id,name FROM brands WHERE enabled=1') as $brand) {
            if (routeSlug($brand['name']) === $m[1]) {
                $_GET['brand'] = (int)$brand['id'];
                return 'shop';
            }
        }
        return 'missing';
    }
    if (preg_match('~^product/([a-z0-9-]+)$~D', $path, $m)) {
        $row = one("SELECT id FROM products WHERE slug=? AND status='active'", [$m[1]]);
        if (!$row) return 'missing';
        $_GET['id'] = (int)$row['id'];
        return 'product';
    }
    if (preg_match('~^policy/([a-z0-9-]+)$~D', $path, $m)) {
        $_GET['slug'] = $m[1];
        return 'policy';
    }
    if (preg_match('~^order/([1-9][0-9]*)$~D', $path, $m)) {
        $_GET['id'] = (int)$m[1];
        return 'order';
    }
    if (in_array($path, ['cart','checkout','login','register','account','track','wishlist','contact','forgot','reset'], true)) return $path;
    return 'missing';
}
