<?php
// auth_helpers.php
function json_response($arr, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    // avoid stray output breaking JSON
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($arr);
    exit;
}

function make_remember_cookie($conn, $userId)
{
    // selector (public id) + token (secret)
    $selector = bin2hex(random_bytes(9));     // 18 hex chars
    $token    = bin2hex(random_bytes(32));    // 64 hex chars
    $hash     = hash('sha256', $token);

    // 30 days expiry
    $expires = (new DateTime('+30 days'))->format('Y-m-d H:i:s');

    // clear any old token for this user (optional but cleaner)
    $stmt = $conn->prepare("UPDATE users SET remember_selector=?, remember_token_hash=?, remember_expires=? WHERE id=?");
    $stmt->bind_param('sssi', $selector, $hash, $expires, $userId);
    $stmt->execute();
    $stmt->close();

    $cookieVal = $selector . ':' . $token;

    // setcookie array syntax (PHP 7.3+)
    setcookie('remember_me', $cookieVal, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clear_remember_cookie($conn, $userId)
{
    $null = null;
    $stmt = $conn->prepare("UPDATE users SET remember_selector=NULL, remember_token_hash=NULL, remember_expires=NULL WHERE id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

function try_auto_login($conn)
{
    if (!isset($_COOKIE['remember_me'])) return false;

    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) !== 2) return false;

    [$selector, $token] = $parts;
    $stmt = $conn->prepare("SELECT id, email, remember_token_hash, remember_expires FROM users WHERE remember_selector=? LIMIT 1");
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) return false;

    // check expiry
    if (empty($user['remember_expires']) || new DateTime($user['remember_expires']) < new DateTime()) {
        return false;
    }

    // verify token
    $calc = hash('sha256', $token);
    if (!hash_equals($user['remember_token_hash'], $calc)) {
        return false;
    }

    // success → rebuild session
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['email']   = $user['email'];
    return true;
}
