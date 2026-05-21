<?php
define('DB_USER', 'project_user');
define('DB_PASS', 'project123');
define('DB_DSN',  'localhost/XE');

function getDB() {
    $conn = oci_connect(DB_USER, DB_PASS, DB_DSN);
    if (!$conn) {
        $e = oci_error();
        die('<div style="color:red;padding:20px;font-family:sans-serif;"><b>DB Connection Failed:</b> ' . htmlspecialchars($e['message']) . '</div>');
    }
    return $conn;
}
function fetchAll($conn, $sql, $params = []) {
    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => &$val) { oci_bind_by_name($stmt, $key, $val); }
    unset($val);
    oci_execute($stmt);
    $rows = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) { $rows[] = $row; }
    oci_free_statement($stmt);
    return $rows;
}
function fetchOne($conn, $sql, $params = []) {
    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => &$val) { oci_bind_by_name($stmt, $key, $val); }
    unset($val);
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
    oci_free_statement($stmt);
    return $row;
}
function execDML($conn, $sql, $params = []) {
    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => &$val) { oci_bind_by_name($stmt, $key, $val); }
    unset($val);
    $ok = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    if ($ok) { oci_commit($conn); oci_free_statement($stmt); return true; }
    $e = oci_error($stmt); oci_rollback($conn); oci_free_statement($stmt);
    return $e['message'];
}
function runPLSQL($conn, $plsql) {
    $s = oci_parse($conn, "BEGIN DBMS_OUTPUT.ENABLE(NULL); END;");
    oci_execute($s); oci_free_statement($s);
    $s = oci_parse($conn, $plsql);
    $ok = @oci_execute($s);
    if (!$ok) { $e = oci_error($s); oci_free_statement($s); return ['error' => $e['message']]; }
    oci_free_statement($s);
    $s2 = oci_parse($conn, "BEGIN DBMS_OUTPUT.GET_LINE(:line, :status); END;");
    $line = ''; $status = 0;
    oci_bind_by_name($s2, ':line', $line, 500);
    oci_bind_by_name($s2, ':status', $status, 20);
    $out = [];
    do { oci_execute($s2); if ($status == 0) $out[] = htmlspecialchars($line); } while ($status == 0);
    oci_free_statement($s2);
    return $out;
}
/**
 * Calls a PL/SQL stored procedure.
 * Usage: callProc("add_department(:b,:n,:p)", [':b'=>$bld, ':n'=>$nam, ':p'=>$phn])
 * Returns true on success, error string on failure.
 */
function callProc($call, $bind = []) {
    $conn = getDB();
    $stmt = oci_parse($conn, "BEGIN {$call}; END;");
    foreach ($bind as $k => &$v) {
        oci_bind_by_name($stmt, $k, $v);
    }
    $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    if (!$ok) {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        // Strip ORA error code for cleaner display
        $msg = preg_replace('/ORA-\d+: /', '', $e['message']);
        $msg = explode("\n", $msg)[0];
        return $msg;
    }
    oci_free_statement($stmt);
    return true;
}

function callFunc($call, $bind = []) {
    $conn = getDB();
    $ret  = 0;
    $stmt = oci_parse($conn, "BEGIN :ret := {$call}; END;");
    oci_bind_by_name($stmt, ':ret', $ret, 20, SQLT_INT);
    foreach ($bind as $k => &$v) {
        oci_bind_by_name($stmt, $k, $v);
    }
    oci_execute($stmt, OCI_DEFAULT);
    oci_free_statement($stmt);
    return $ret;
}

function dbFetchAll($sql, $params = []) {
    $conn = getDB();
    return fetchAll($conn, $sql, $params);
}

function dbFetchOne($sql, $params = []) {
    $conn = getDB();
    return fetchOne($conn, $sql, $params);
}

function dbExecute($sql, $params = []) {
    $conn = getDB();
    return execDML($conn, $sql, $params);
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>