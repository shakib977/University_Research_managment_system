<?php
// ================================================
// URMS — Dashboard (index.php)
// ================================================
require 'config.php';
// ── Stats using PL/SQL stored function ──────────
// Calls total_researchers() Oracle function
$conn = getDB();

// Total Researchers — via stored function
$stmt = oci_parse($conn, 'BEGIN :res := total_researchers(); END;');
$totalResearchers = 0;
oci_bind_by_name($stmt, ':res', $totalResearchers, 10, SQLT_INT);
oci_execute($stmt);
oci_free_statement($stmt);

// Other stats via regular queries
$activeProjects  = dbFetchOne("SELECT COUNT(*) CNT FROM Project WHERE Status='Active'")['CNT'] ?? 0;
$totalPubs       = dbFetchOne("SELECT COUNT(*) CNT FROM Publication")['CNT'] ?? 0;
$totalDepts      = dbFetchOne("SELECT COUNT(*) CNT FROM Department")['CNT'] ?? 0;

// Recent projects (joined with supervisor)
$recentProjects = dbFetchAll(
    "SELECT p.Pro_Id, p.Title, s.S_Name, p.Status,
            p.S_Date, p.E_Date, p.F_Id
     FROM Project p
     JOIN Supervisor s ON p.S_Id = s.S_Id
     ORDER BY p.Pro_Id DESC"
);

$today = date('l, F j, Y');

function statusBadge($status) {
    $map = [
        'Active'    => 'badge-active',
        'Completed' => 'badge-completed',
        'Pending'   => 'badge-pending',
        'Planning'  => 'badge-planning',
    ];
    $cls = $map[$status] ?? 'badge-planning';
    return "<span class='badge {$cls}'>{$status}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">University Research Management System</div>
                <div class="topbar-sub">Advanced Database Management System &mdash; Spring 2025-26</div>
            </div>
        </div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= $today ?></div>
    </div>

    <div class="page-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-th-large" style="font-size:18px;margin-right:8px;"></i>Dashboard</h1>
                <p>Overview of the University Research Management System</p>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalResearchers ?></div>
                    <div class="stat-label">Total Researchers</div>
                    <div class="stat-change"><i class="fas fa-arrow-up"></i> via PL/SQL function</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $activeProjects ?></div>
                    <div class="stat-label">Active Projects</div>
                    <div class="stat-change"><i class="fas fa-circle" style="color:#43a047;font-size:8px"></i> Currently running</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-book-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalPubs ?></div>
                    <div class="stat-label">Publications</div>
                    <div class="stat-change"><i class="fas fa-arrow-up"></i> Research papers</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= $totalDepts ?></div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-change"><i class="fas fa-arrow-up"></i> University wide</div>
                </div>
            </div>
        </div>

        <!-- Recent Projects Table -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-clock"></i> Recent Research Projects</h2>
                <a href="projects.php" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View All</a>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project ID</th>
                            <th>Title</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProjects)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No projects found.</p>
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($recentProjects as $p): ?>
                        <tr>
                            <td><b>PRJ<?= str_pad($p['PRO_ID'],3,'0',STR_PAD_LEFT) ?></b></td>
                            <td><?= h($p['TITLE']) ?></td>
                            <td><?= h($p['S_NAME']) ?></td>
                            <td><?= statusBadge($p['STATUS']) ?></td>
                            <td><?= $p['S_DATE'] ? date('M d, Y', strtotime($p['S_DATE'])) : '—' ?></td>
                            <td><?= $p['E_DATE'] ? date('M d, Y', strtotime($p['E_DATE'])) : '<span style="color:#aaa">Ongoing</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($recentProjects) ?> project(s) in database</div>
            </div>
        </div>

        <!-- Quick Stats Cards Row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <!-- Publications by Type -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Publications by Type</h2>
                </div>
                <div class="card-body" style="padding:16px 22px;">
                    <?php
                    $pubByType = dbFetchAll(
                        "SELECT pt.Type, COUNT(p.P_Id) CNT
                         FROM Publication_Type pt
                         LEFT JOIN Publication p ON p.T_Id = pt.T_Id
                         GROUP BY pt.Type ORDER BY CNT DESC"
                    );
                    $maxPub = max(1, array_max(array_column($pubByType, 'CNT')));
                    foreach ($pubByType as $row):
                        $pct = round(($row['CNT'] / $maxPub) * 100);
                    ?>
                    <div style="margin-bottom:14px;">
                        <div class="progress-label">
                            <span><?= h($row['TYPE']) ?></span>
                            <span><?= $row['CNT'] ?> pub(s)</span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Projects by Status -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Projects by Status</h2>
                </div>
                <div class="card-body" style="padding:16px 22px;">
                    <?php
                    $projByStatus = dbFetchAll(
                        "SELECT Status, COUNT(*) CNT FROM Project GROUP BY Status ORDER BY CNT DESC"
                    );
                    $totalP = max(1, array_sum(array_column($projByStatus, 'CNT')));
                    $statusColors = ['Active'=>'#2e7d32','Completed'=>'#616161','Pending'=>'#e65100','Planning'=>'#1565c0'];
                    foreach ($projByStatus as $row):
                        $pct = round(($row['CNT'] / $totalP) * 100);
                        $col = $statusColors[$row['STATUS']] ?? '#333';
                    ?>
                    <div style="margin-bottom:14px;">
                        <div class="progress-label">
                            <span style="color:<?= $col ?>;font-weight:600;"><?= h($row['STATUS']) ?></span>
                            <span><?= $row['CNT'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div><!-- .page-content -->
</div><!-- .main-wrapper -->

<?php
function array_max($arr) {
    return empty($arr) ? 0 : max($arr);
}
?>
</body>
</html>