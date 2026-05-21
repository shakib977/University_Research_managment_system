<?php
// includes/sidebar.php  — shared sidebar used by every page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'index'        => ['icon' => 'fas fa-th-large',          'label' => 'Dashboard'],
    'departments'  => ['icon' => 'fas fa-building',          'label' => 'Departments'],
    'researchers'  => ['icon' => 'fas fa-user-graduate',     'label' => 'Researchers'],
    'projects'     => ['icon' => 'fas fa-project-diagram',   'label' => 'Research Projects'],
    'supervisors'  => ['icon' => 'fas fa-chalkboard-teacher','label' => 'Supervisors'],
    'funding'      => ['icon' => 'fas fa-dollar-sign',       'label' => 'Funding Agencies'],
    'publications' => ['icon' => 'fas fa-book-open',         'label' => 'Publications'],
    'pub_types'    => ['icon' => 'fas fa-tags',              'label' => 'Publication Types'],
    'admins'       => ['icon' => 'fas fa-user-shield',       'label' => 'Administrators'],
];
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-wrap">
            <i class="fas fa-university sidebar-logo-icon"></i>
        </div>
        <div class="sidebar-title">
            <span class="title-main">University</span>
            <span class="title-sub">Research System</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $page => $item):
            $href   = ($page === 'index') ? 'index.php' : $page . '.php';
            $active = ($currentPage === $page) ? 'active' : '';
        ?>
        <a href="<?= $href ?>" class="nav-item <?= $active ?>">
            <i class="<?= $item['icon'] ?> nav-icon"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <i class="fas fa-circle sidebar-status-dot"></i>
        <span>Oracle XE &bull; Connected</span>
    </div>
</div>