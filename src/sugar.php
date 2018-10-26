<?php

function alert($var) {
    \Sinevia\Utils::alert($var);
}

function appPath($path) {
    return rtrim(ROOT_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function basePath($path) {
    return rtrim(ROOT_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function csrfToken() {
    return \App\Helpers\SecurityHelper::csrfToken();
}

function csrfValidate($token) {
    return \App\Helpers\SecurityHelper::csrfValidate($token);
}

/**
 * Returns a database connection 
 * @return Sinevia\SqlDb
 */
function db($key = 'default') {
    return \App\Helpers\AppHelper::getDatabase($key);
}

if (function_exists('dd') == false) {

    function dd($var) {
        \Sinevia\Utils::alert($var);
        exit;
    }

}

function isCli() {
    if (defined('STDIN')) {
        return true;
    }

    if (empty($_SERVER['REMOTE_ADDR']) and ! isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
        return true;
    }

    return false;
}

function isGet() {
    $isPost = $_SERVER['REQUEST_METHOD'] == "GET" ? true : false;
    return $isPost;
}

function isPost() {
    $isPost = $_SERVER['REQUEST_METHOD'] == "POST" ? true : false;
    return $isPost;
}

function post($url, $data) {
    return Sinevia\Utils::redirectAndPostData($url, $data);
}
/**
 * Redirects with options
 * - error
 * - errors
 * - success
 * - warnings
 * - request
 * @param string $url
 * @param array $options
 * @return string
 */
function redirect($url, $options = []) {
    if (isset($options['info'])) {
        $_SESSION["info"] = $options['info'];
    }
    if (isset($options['error'])) {
        $_SESSION["error"] = $options['error'];
    }
    if (isset($options['errors'])) {
        $_SESSION["errors"] = $options['errors'];
    }
    if (isset($options['success'])) {
        $_SESSION["success"] = $options['success'];
    }
    if (isset($options['warning'])) {
        $_SESSION["warning"] = $options['warning'];
    }
    if (isset($options['request'])) {
        foreach ($options['request'] as $key => $value) {
            $_SESSION['__old__' . $key] = $value;
        }
    }
    return Sinevia\Utils::redirect($url);
}

function back($options = []) {
    $history = history();
    //$currentHistory = array_pop($history);
    $lastHistory = array_pop($history);
    if (is_array($lastHistory)) {
        $lastUrl = $lastHistory['url'];
    } else {
        $lastUrl = '/';
    }
    return redirect($lastUrl, $options);
}

function env($name, $default = null, $functions = []) {
    $value = (getenv($name) == false) ? $default : getenv($name);
    foreach ($functions as $fn) {
        $value = $fn($value);
    }
    return $value;
}

function req($name, $default = null, $functions = []) {
    $value = (isset($_REQUEST[$name]) == false) ? $default : $_REQUEST[$name];
    foreach ($functions as $fn) {
        $value = $fn($value);
    }
    return $value;
}

function old($name, $default = null) {
    $oldInput = \Sinevia\Registry::has('old_input') ? \Sinevia\Registry::get('old_input') : array();
    if (isset($oldInput[$name])) {
        return $oldInput[$name];
    }
    return $default;
}

function sess($name, $default = null, $functions = [], $options = []) {
    $value = (isset($_SESSION[$name]) == false) ? $default : $_SESSION[$name];
    foreach ($functions as $fn) {
        $value = $fn($value);
    }
    return $value;
}

function once($name, $default = null, $functions = [], $options = []) {
    $value = (isset($_SESSION[$name]) == false) ? $default : $_SESSION[$name];
    foreach ($functions as $fn) {
        $value = $fn($value);
    }
    if (isset($_SESSION[$name])) {
        unset($_SESSION[$name]);
    }
    return $value;
}

function reqOrSess($name, $default = null, $functions = []) {
    if (req($name, $default, $functions) !== null) {
        return req($name, $default, $functions);
    }
    if (sess($name, $default, $functions) !== null) {
        return sess($name, $default, $functions);
    }
    return $default;
}

function url($action, $data = []) {
    if (\Sinevia\StringUtils::startsWith($action, 'https://') == false AND \Sinevia\StringUtils::startsWith($action, 'http://') == false) {
        $url = rtrim(ROOT_URL, '/');
        $url .= '/' . ltrim($action, '/');
    }
    $query = count($data) > 0 ? '?' . http_build_query($data) : '';
    return $url . $query;
}

function ui($view, $vars = array(), $options = array()) {
    $ext = pathinfo($view, PATHINFO_EXTENSION);
    if ($ext == '') {
        $view .= '.phtml';
    }
    return \Sinevia\Template::fromFile(VIEWS_DIR . '/' . ltrim($view, '/'), $vars, $options);
}

function history() {
    $history = [];
    if (isset($_SESSION['__history__']) AND is_array($_SESSION['__history__'])) {
        $history = $_SESSION['__history__'];
    }
    return $history;
}

/**
 * Web engine. Keeps track of history and old input before redirects
 */
function web() {
    $olds = [];

    foreach ($_SESSION as $key => $value) {
        if (\Sinevia\Utils::stringStartsWith($key, '__old__')) {
            $olds[str_replace('__old__', '', $key)] = $value;
            unset($_SESSION[$key]);
        }
    }

    \Sinevia\Registry::set('old_input', $olds);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        $history = history();
        $history[] = [
            'method' => $method,
            'time' => date('Y-m-d H:i:s'),
            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                //'request' => $_REQUEST,
        ];
        $_SESSION['__history__'] = $history;
    }
}

if (isCli() == false) {
    web();
}
