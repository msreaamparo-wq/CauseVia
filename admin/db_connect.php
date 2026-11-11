<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function executeQuery($query, $params = []) {
    global $pdo;

    try {
        if (preg_match('/\$\d+/', $query)) {
            preg_match_all('/\$(\d+)/', $query, $matches);
            $orderedParams = [];

            foreach ($matches[1] as $number) {
                $index = (int)$number - 1;

                if (!array_key_exists($index, $params)) {
                    throw new InvalidArgumentException('Missing SQL parameter.');
                }

                $orderedParams[] = $params[$index];
            }

            $query = preg_replace('/\$\d+/', '?', $query);
            $params = $orderedParams;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;

    } catch (Throwable $e) {
        error_log($e->getMessage());
        return false;
    }
}

function fetchSingle($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: null) : null;
}

function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function requireAdmin() {
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        header('Location: ../public/login.php');
        exit();
    }

    $user = fetchSingle(
        "SELECT role FROM users WHERE id = :id",
        [':id' => $user_id]
    );

    if (!$user || !in_array(strtolower($user['role']), ['admin', 'superadmin'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
?>