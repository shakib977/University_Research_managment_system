<?php
// ================================================
// URMS — Administrators (admins.php)
// PL/SQL: add_admin, update_admin, delete_admin
//         research_utils.count_researchers (package)
// ================================================
require 'config.php';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['a_name']    ?? '');
        $email = trim($_POST['a_email']   ?? '');
        $pass  = trim($_POST['a_password']?? '');

        $res = callProc("add_admin(:n,:e,:p)", [':n'=>$name, ':e'=>$email, ':p'=>$pass]);
        $msg = ($res === true) ? 'Administrator added!' : 'Error: '.$res;
    }

    if ($action === 'edit') {
        $id    = (int)$_POST['a_id'];
        $name  = trim($_POST['a_name']  ?? '');
        $email = trim($_POST['a_email'] ?? '');

        $res = callProc("update_admin(:id,:n,:e)", [':id'=>$id, ':n'=>$name, ':e'=>$email]);
        $msg = ($res === true) ? 'Administrator updated!' : 'Error: '.$res;
    }

    if ($action === 'delete') {
        $id  = (int)$_POST['a_id'];
        $res = callProc("delete_admin(:id)", [':id'=>$id]);
        $msg = ($res === true) ? 'Administrator deleted.' : 'Error: '.$res;
    }
}

// Call package function: research_utils.count_researchers()
$totalResearchers = callFunc("research_utils.count_researchers");

$search = trim($_GET['q'] ?? '');
$q = '%'.$search.'%';

$admins = dbFetchAll(
    "SELECT a.A_Id, a.Name, a.Email, a.Password,
            COUNT(r.R_Id) RES_COUNT
     FROM Admin a
     LEFT JOIN Researcher r ON r.A_Id = a.A_Id
     WHERE UPPER(a.Name)  LIKE UPPER(:q)
        OR UPPER(a.Email) LIKE UPPER(:q2)
     GROUP BY a.A_Id, a.Name, a.Email, a.Password
     ORDER BY a.A_Id",
    [':q'=>$q, ':q2'=>$q]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrators — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">System Administrators</div>
            <div class="topbar-sub">Manage system administrators and access</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-user-shield" style="font-size:18px;margin-right:8px;"></i>System Administrators</h1>
                <!-- Package function call shown on page -->
                <p>Researchers in system: <b><?= $totalResearchers ?></b>
                   — via <code>research_utils.count_researchers()</code></p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Administrator
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Administrators (<?= count($admins) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>"
                           placeholder="Search by name or email...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Researchers Managed</th>
                            <th>Last Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-user-shield"></i>
                                <p>No administrators found.</p>
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><b>ADM<?= str_pad($a['A_ID'], 3, '0', STR_PAD_LEFT) ?></b></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;border-radius:50%;
                                                background:linear-gradient(135deg,#1a237e,#5c6bc0);
                                                display:flex;align-items:center;justify-content:center;
                                                color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                                        <?= strtoupper(substr($a['NAME'], 0, 1)) ?>
                                    </div>
                                    <span style="font-weight:600;"><?= h($a['NAME']) ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?= h($a['EMAIL']) ?>" style="color:#1565c0;">
                                    <i class="fas fa-envelope" style="margin-right:5px;"></i>
                                    <?= h($a['EMAIL']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?= $a['RES_COUNT'] > 0 ? 'active' : 'completed' ?>">
                                    <?= $a['RES_COUNT'] ?> researcher(s)
                                </span>
                            </td>
                            <td style="color:#888;"><?= date('M j, Y') ?></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(
                                        <?= $a['A_ID'] ?>,
                                        '<?= addslashes($a['NAME']) ?>',
                                        '<?= addslashes($a['EMAIL']) ?>'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon"
                                    onclick="confirmDelete(<?= $a['A_ID'] ?>, '<?= addslashes($a['NAME']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($admins) ?> administrator(s)</div>
            </div>
        </div>

        <!-- PL/SQL Info Card -->
        <div class="card" style="margin-top:0;">
            <div class="card-header">
                <h2><i class="fas fa-code"></i> PL/SQL Objects Active in This System</h2>
            </div>
            <div class="card-body" style="padding:18px 22px;">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                    <?php
                    $plsqlItems = [
                        ['icon'=>'fas fa-cogs',      'color'=>'#1565c0', 'label'=>'Stored Procedures', 'items'=>'add/update/delete for all 9 tables'],
                        ['icon'=>'fas fa-calculator','color'=>'#2e7d32', 'label'=>'Stored Functions',  'items'=>'total_researchers, total_projects_by_supervisor, get_pub_count, get_active_project_count'],
                        ['icon'=>'fas fa-box',        'color'=>'#6a1b9a', 'label'=>'Package',          'items'=>'research_utils (4 functions/procedures)'],
                        ['icon'=>'fas fa-bolt',       'color'=>'#e65100', 'label'=>'Triggers',         'items'=>'trg_check_researcher, trg_check_fund, trg_validate_project_date, trg_after_insert_res, trg_after_insert_proj'],
                        ['icon'=>'fas fa-mouse-pointer','color'=>'#00838f','label'=>'Cursors',         'items'=>'Explicit cursors inside package procedure show_researchers_with_dept'],
                        ['icon'=>'fas fa-lock',       'color'=>'#c62828', 'label'=>'Locking',         'items'=>'SELECT FOR UPDATE (implicit) + LOCK TABLE IN EXCLUSIVE MODE (explicit)'],
                    ];
                    foreach ($plsqlItems as $item): ?>
                    <div style="background:#f5f7ff;border-radius:8px;padding:14px;border-left:3px solid <?= $item['color'] ?>;">
                        <div style="font-weight:700;color:<?= $item['color'] ?>;margin-bottom:4px;">
                            <i class="<?= $item['icon'] ?>" style="margin-right:6px;"></i>
                            <?= $item['label'] ?>
                        </div>
                        <div style="font-size:12px;color:#666;"><?= $item['items'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add Administrator</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="a_name" class="form-control"
                           placeholder="Administrator name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="a_email" class="form-control"
                           placeholder="admin@university.edu" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="a_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Administrator</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="a_id"   id="edit_a_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="a_name" id="edit_aname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="a_email" id="edit_aemail" class="form-control" required>
                </div>
                <p style="font-size:12px;color:#888;margin-top:4px;">
                    <i class="fas fa-info-circle"></i> Password cannot be changed here for security.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Admin</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="a_id"   id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(id, name, email) {
    document.getElementById('edit_a_id').value    = id;
    document.getElementById('edit_aname').value   = name;
    document.getElementById('edit_aemail').value  = email;
    openModal('editModal');
}

function confirmDelete(id, name) {
    if (confirm('Delete administrator "' + name + '"?\nAdmins managing researchers cannot be deleted.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
</script>
</body>
</html>
//updated admin