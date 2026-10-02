<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function setFlash($type, $message)
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = array();
    }

    $_SESSION['flash'][$type] = $message;
}

function getFlash($type)
{
    if (!isset($_SESSION['flash'][$type])) {
        return '';
    }

    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);

    return $message;
}

function secureToken()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        $strong = false;
        $bytes = openssl_random_pseudo_bytes(32, $strong);
        if ($bytes !== false && $strong) { return bin2hex($bytes); }
    }
    throw new RuntimeException('Sichere Zufallswerte sind nicht verfügbar.');
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = secureToken(); }
    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function isValidCsrf()
{
    $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

    return $token !== ''
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

function verifyCsrf($redirectPath)
{
    if ($redirectPath === null || $redirectPath === '') {
        $redirectPath = '/dashboard.php';
    }

    if (!isValidCsrf()) {
        setFlash('error', 'Die Anfrage konnte nicht bestätigt werden. Bitte das Formular erneut absenden.');
        redirect($redirectPath);
    }
}

function postValue($key, $default)
{
    if (!isset($_POST[$key])) {
        return $default;
    }

    if (is_string($_POST[$key])) {
        return trim($_POST[$key]);
    }

    return $default;
}

function postInt($key)
{
    return isset($_POST[$key]) && is_scalar($_POST[$key]) ? (int) $_POST[$key] : 0;
}

function postIntArray($key)
{
    if (!isset($_POST[$key]) || !is_array($_POST[$key])) {
        return array();
    }

    $ids = array();

    foreach ($_POST[$key] as $value) {
        if (!is_scalar($value)) { continue; }
        $id = (int) $value;
        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return $ids;
}

function getValue($key, $default)
{
    if (!isset($_GET[$key])) {
        return $default;
    }

    if (is_string($_GET[$key])) {
        return trim($_GET[$key]);
    }

    return $default;
}

function getInt($key)
{
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? (int) $_GET[$key] : 0;
}

function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUserId()
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }

    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT active, role, username, password_hash
         FROM users
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->execute(array(currentUserId()));
    $user = $stmt->fetch();

    if (!$user || (int) $user['active'] !== 1 || !isset($_SESSION['auth_stamp']) || !hash_equals(hash('sha256', (string) $user['password_hash']), $_SESSION['auth_stamp'])) {
        $_SESSION = array();
        if (session_id() !== '') {
            session_destroy();
        }
        redirect('/login.php');
    }
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
}

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {
        setFlash('error', 'Dieser Bereich ist nur für Administratoren.');
        redirect('/dashboard.php');
    }
}

function charLength($value)
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

function validatePassword($password)
{
    if (!is_string($password) || charLength($password) < 12 || strlen($password) > 72) {
        return 'Das Passwort muss mindestens 12 Zeichen lang sein und darf höchstens 72 Bytes umfassen.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        return 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        return 'Das Passwort muss mindestens eine Zahl enthalten.';
    }

    return '';
}

function generateActivationToken()
{
    return secureToken();
}

function hashActivationToken($token)
{
    return hash('sha256', $token);
}

function invalidatePasswordTokens($pdo, $userId)
{
    $stmt = $pdo->prepare(
        'UPDATE password_tokens
         SET used_at = NOW()
         WHERE user_id = ?
           AND used_at IS NULL'
    );
    $stmt->execute(array($userId));
}

function createPasswordToken($pdo, $userId)
{
    invalidatePasswordTokens($pdo, $userId);

    $token = generateActivationToken();
    $tokenHash = hashActivationToken($token);

    $stmt = $pdo->prepare(
        'INSERT INTO password_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
    );
    $stmt->execute(array($userId, $tokenHash));

    return $token;
}

function findValidPasswordToken($pdo, $token)
{
    if ($token === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT
            t.token_id,
            t.user_id,
            u.username,
            u.active
         FROM password_tokens t
         INNER JOIN users u ON u.user_id = t.user_id
         WHERE t.token_hash = ?
           AND t.used_at IS NULL
           AND t.expires_at > NOW()
           AND u.active = 1
         LIMIT 1'
    );
    $stmt->execute(array(hashActivationToken($token)));
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return $row;
}

function markPasswordTokenUsed($pdo, $tokenId)
{
    $stmt = $pdo->prepare(
        'UPDATE password_tokens
         SET used_at = NOW()
         WHERE token_id = ?'
    );
    $stmt->execute(array($tokenId));
}

function storeOneTimeActivation($username, $token)
{
    $_SESSION['one_time_activation'] = array(
        'username' => $username,
        'token' => $token
    );
}

function takeOneTimeActivation()
{
    if (!isset($_SESSION['one_time_activation']) || !is_array($_SESSION['one_time_activation'])) {
        return null;
    }

    $data = $_SESSION['one_time_activation'];
    unset($_SESSION['one_time_activation']);

    return $data;
}

function userHasPasswordSet($user)
{
    return isset($user['password_hash']) && $user['password_hash'] !== '' && $user['password_hash'] !== null;
}

function activationUrl($token)
{
    return BASE_URL . '/set_password.php?code=' . rawurlencode($token);
}

function absoluteUrl($path)
{
    // A configured origin prevents activation-link poisoning through Host headers.
    $origin = getenv('FITFUERINFO_PUBLIC_URL');
    if ($origin && preg_match('~^https?://[a-z0-9.-]+(?::[0-9]+)?$~i', $origin)) {
        return rtrim($origin, '/') . $path;
    }
    return $path;
}

function canEditCourse($pdo, $courseId)
{
    if (isAdmin()) { return true; }
    static $allowed = array();
    $key = spl_object_hash($pdo) . ':' . currentUserId();
    if (!isset($allowed[$key])) {
        $stmt = $pdo->prepare('SELECT course_id FROM course_owners WHERE user_id = ?');
        $stmt->execute(array(currentUserId()));
        $allowed[$key] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return in_array((int) $courseId, $allowed[$key], true);
}

function canEditRoom($pdo, $roomId)
{
    if (isAdmin()) { return true; }
    static $allowed = array();
    $key = spl_object_hash($pdo) . ':' . currentUserId();
    if (!isset($allowed[$key])) {
        $stmt = $pdo->prepare('SELECT room_id FROM room_editors WHERE user_id = ?');
        $stmt->execute(array(currentUserId()));
        $allowed[$key] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return in_array((int) $roomId, $allowed[$key], true);
}

function canDeleteBooking($booking)
{
    if (isAdmin()) {
        return true;
    }

    return isset($booking['created_by'])
        && (int) $booking['created_by'] === currentUserId();
}

function courseHasFutureBookings($pdo, $courseId)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt
         FROM bookings
         WHERE course_id = ?
           AND (
                booking_date > CURDATE()
                OR (booking_date = CURDATE() AND end_time > CURTIME())
           )'
    );
    $stmt->execute(array($courseId));
    $row = $stmt->fetch();

    return $row && (int) $row['cnt'] > 0;
}

function courseHasAnyBookings($pdo, $courseId)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt
         FROM bookings
         WHERE course_id = ?'
    );
    $stmt->execute(array($courseId));
    $row = $stmt->fetch();

    return $row && (int) $row['cnt'] > 0;
}

function roomHasAnyBookings($pdo, $roomId)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt
         FROM bookings
         WHERE room_id = ?'
    );
    $stmt->execute(array($roomId));
    $row = $stmt->fetch();

    return $row && (int) $row['cnt'] > 0;
}

function fetchAllEmployees($pdo)
{
    $stmt = $pdo->query(
        "SELECT user_id, username, first_name, last_name, role, active
         FROM users
         ORDER BY last_name, first_name"
    );

    return $stmt->fetchAll();
}

function fetchSoftwareList($pdo)
{
    $stmt = $pdo->query(
        'SELECT software_id, software_name
         FROM software
         ORDER BY software_name'
    );

    return $stmt->fetchAll();
}

function fetchIdsByQuery($pdo, $sql, $params)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ids = array();

    foreach ($stmt->fetchAll() as $row) {
        $ids[] = (int) reset($row);
    }

    return $ids;
}

function fetchCourseOwnerIds($pdo, $courseId)
{
    return fetchIdsByQuery(
        $pdo,
        'SELECT user_id FROM course_owners WHERE course_id = ?',
        array($courseId)
    );
}

function fetchCourseSoftwareIds($pdo, $courseId)
{
    return fetchIdsByQuery(
        $pdo,
        'SELECT software_id FROM course_software WHERE course_id = ?',
        array($courseId)
    );
}

function fetchRoomSoftwareIds($pdo, $roomId)
{
    return fetchIdsByQuery(
        $pdo,
        'SELECT software_id FROM room_software WHERE room_id = ?',
        array($roomId)
    );
}

function fetchRoomEditorIds($pdo, $roomId)
{
    return fetchIdsByQuery(
        $pdo,
        'SELECT user_id FROM room_editors WHERE room_id = ?',
        array($roomId)
    );
}

function replaceRelationRows($pdo, $deleteSql, $deleteId, $insertSql, $ids)
{
    $stmt = $pdo->prepare($deleteSql);
    $stmt->execute(array($deleteId));

    if (count($ids) === 0) {
        return;
    }

    $stmt = $pdo->prepare($insertSql);

    foreach ($ids as $relatedId) {
        $stmt->execute(array($deleteId, $relatedId));
    }
}

function replaceCourseSoftware($pdo, $courseId, $softwareIds)
{
    replaceRelationRows(
        $pdo,
        'DELETE FROM course_software WHERE course_id = ?',
        $courseId,
        'INSERT INTO course_software (course_id, software_id) VALUES (?, ?)',
        $softwareIds
    );
}

function replaceCourseOwners($pdo, $courseId, $ownerIds)
{
    replaceRelationRows(
        $pdo,
        'DELETE FROM course_owners WHERE course_id = ?',
        $courseId,
        'INSERT INTO course_owners (course_id, user_id) VALUES (?, ?)',
        $ownerIds
    );
}

function replaceRoomSoftware($pdo, $roomId, $softwareIds)
{
    replaceRelationRows(
        $pdo,
        'DELETE FROM room_software WHERE room_id = ?',
        $roomId,
        'INSERT INTO room_software (room_id, software_id) VALUES (?, ?)',
        $softwareIds
    );
}

function replaceRoomEditors($pdo, $roomId, $editorIds)
{
    replaceRelationRows(
        $pdo,
        'DELETE FROM room_editors WHERE room_id = ?',
        $roomId,
        'INSERT INTO room_editors (room_id, user_id) VALUES (?, ?)',
        $editorIds
    );
}

function isValidDate($date)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
}

function isValidTime($time)
{
    return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time) === 1;
}

function normalizeTime($time)
{
    if (strlen($time) === 5) {
        return $time . ':00';
    }

    return $time;
}

function formatDateDe($date)
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y', $timestamp);
}

function formatTimeDe($time)
{
    return substr($time, 0, 5);
}

function roleLabel($role)
{
    if ($role === 'admin') {
        return 'Administrator';
    }

    return 'Mitarbeiter';
}

function validateBooking($pdo, $roomId, $courseId, $date, $startTime, $endTime, $ignoreBookingId = 0)
{
    $errors = array();

    if ($roomId <= 0) {
        $errors[] = 'Bitte einen Raum auswählen.';
    }

    if ($courseId <= 0) {
        $errors[] = 'Bitte einen Kurs auswählen.';
    }

    if (!isValidDate($date)) {
        $errors[] = 'Bitte ein gültiges Datum angeben.';
    }

    if (!isValidTime($startTime) || !isValidTime($endTime)) {
        $errors[] = 'Bitte gültige Start- und Endzeiten angeben.';
    }

    if (count($errors) > 0) {
        return $errors;
    }

    $startTime = normalizeTime($startTime);
    $endTime = normalizeTime($endTime);

    if ($startTime >= $endTime) {
        $errors[] = 'Die Startzeit muss vor der Endzeit liegen.';
    }

    $stmt = $pdo->prepare(
        'SELECT course_id, course_name, max_participants, active
         FROM courses
         WHERE course_id = ?
         LIMIT 1'
    );
    $stmt->execute(array($courseId));
    $course = $stmt->fetch();

    if (!$course) {
        $errors[] = 'Der ausgewählte Kurs wurde nicht gefunden.';
        return $errors;
    }

    if ((int) $course['active'] !== 1) {
        $errors[] = 'Dieser Kurs ist deaktiviert und kann nicht gebucht werden.';
    }

    $stmt = $pdo->prepare(
        'SELECT room_id, room_name, computer_count
         FROM rooms
         WHERE room_id = ?
         LIMIT 1'
    );
    $stmt->execute(array($roomId));
    $room = $stmt->fetch();

    if (!$room) {
        $errors[] = 'Der ausgewählte Raum wurde nicht gefunden.';
        return $errors;
    }

    if ((int) $room['computer_count'] < (int) $course['max_participants']) {
        $errors[] = 'Der Raum besitzt nur '
            . (int) $room['computer_count']
            . ' von '
            . (int) $course['max_participants']
            . ' benötigten Arbeitsplätzen.';
    }

    $stmt = $pdo->prepare(
        'SELECT s.software_name
         FROM course_software cs
         INNER JOIN software s ON s.software_id = cs.software_id
         LEFT JOIN room_software rs
            ON rs.software_id = cs.software_id
           AND rs.room_id = ?
         WHERE cs.course_id = ?
           AND rs.software_id IS NULL
         ORDER BY s.software_name'
    );
    $stmt->execute(array($roomId, $courseId));
    $missingSoftware = $stmt->fetchAll();

    foreach ($missingSoftware as $softwareRow) {
        $errors[] = 'Im Raum fehlt die Software ' . $softwareRow['software_name'] . '.';
    }

    $stmt = $pdo->prepare(
        'SELECT booking_id
         FROM bookings
         WHERE room_id = ?
           AND booking_date = ?
           AND start_time < ?
           AND end_time > ?
           AND booking_id <> ?
         LIMIT 1'
    );
    $stmt->execute(array($roomId, $date, $endTime, $startTime, $ignoreBookingId));

    if ($stmt->fetch()) {
        $errors[] = 'Der Raum ist zu diesem Zeitpunkt bereits gebucht.';
    }

    return $errors;
}

function validateFutureBookings($pdo, $column, $id)
{
    if (!in_array($column, array('course_id', 'room_id'), true)) { throw new InvalidArgumentException(); }
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE ' . $column . ' = ? AND (booking_date > CURDATE() OR (booking_date = CURDATE() AND end_time > CURTIME()))');
    $stmt->execute(array($id));
    foreach ($stmt->fetchAll() as $b) {
        $errors = validateBooking($pdo, $b['room_id'], $b['course_id'], $b['booking_date'], $b['start_time'], $b['end_time'], $b['booking_id']);
        if ($errors) { throw new RuntimeException('Bestehende Buchung am ' . formatDateDe($b['booking_date']) . ': ' . implode(' ', $errors)); }
    }
}

function loginRateKeys($username)
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    return array(hash('sha256', 'ip:' . $ip) => 40, hash('sha256', 'user:' . strtolower($username)) => 10);
}

function loginRateLimited($pdo, $username)
{
    $stmt = $pdo->prepare('SELECT attempts FROM login_attempts WHERE bucket = ? AND expires_at > NOW()');
    foreach (loginRateKeys($username) as $key => $limit) {
        $stmt->execute(array($key));
        if ((int) $stmt->fetchColumn() >= $limit) { return true; }
    }
    return false;
}

function recordLoginFailure($pdo, $username)
{
    $pdo->exec('DELETE FROM login_attempts WHERE expires_at <= NOW()');
    $stmt = $pdo->prepare('INSERT INTO login_attempts (bucket, attempts, expires_at) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts = attempts + 1');
    foreach (loginRateKeys($username) as $key => $limit) { $stmt->execute(array($key)); }
}
