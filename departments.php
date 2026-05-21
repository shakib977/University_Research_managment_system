<?php
// ================================================
// URMS — Departments (departments.php)
// ================================================
require 'config.php';

$msg = $err = '';

// ── Handle form submissions ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
    $building   = trim($_POST['building']   ?? '');
    $dname      = trim($_POST['d_name']     ?? '');
    $office_phn = trim($_POST['office_phn'] ?? '');
    $res = callProc("add_department(:b,:n,:p)",
                    [':b'=>$building, ':n'=>$dname, ':p'=>$office_phn]);
    $msg = ($res === true) ? 'Department added!' : 'Error: '.$res;
}
if ($action === 'edit') {
    $id  = (int)$_POST['d_id'];
    $bld = trim($_POST['building']   ?? '');
    $nam = trim($_POST['d_name']     ?? '');
    $phn = trim($_POST['office_phn'] ?? '');
    $res = callProc("update_department(:id,:b,:n,:p)",
                    [':id'=>$id, ':b'=>$bld, ':n'=>$nam, ':p'=>$phn]);
    $msg = ($res === true) ? 'Department updated!' : 'Error: '.$res;
}
if ($action === 'delete') {
    $id  = (int)$_POST['d_id'];
    $res = callProc("delete_department(:id)", [':id'=>$id]);
    $msg = ($res === true) ? 'Department deleted.' : 'Error: '.$res;
}
}

// ── Fetch departments ──────────────────────────
$search = trim($_GET['q'] ?? '');
$sql = "SELECT d.D_Id, d.Building, d.D_Name, d.Office_Phn,
               COUNT(r.R_Id) RES_COUNT
        FROM Department d
        LEFT JOIN Researcher r ON r.D_Id = d.D_Id
        WHERE UPPER(d.D_Name) LIKE UPPER(:q)
           OR UPPER(d.Building) LIKE UPPER(:q2)
        GROUP BY d.D_Id, d.Building, d.D_Name, d.Office_Phn
        ORDER BY d.D_Id";
$q = '%'.$search.'%';
$departments = dbFetchAll($sql, [':q'=>$q, ':q2'=>$q]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departments — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title">Departments</div>
                <div class="topbar-sub">Manage university departments</div>
            </div>
        </div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-building" style="font-size:18px;margin-right:8px;"></i>Departments</h1>
                <p>All (<?= count($departments) ?>) departments</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Departments (<?= count($departments) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search departments by name or building...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dept ID</th>
                            <th>Department Name</th>
                            <th>Building</th>
                            <th>Office Phone</th>
                            <th>Researchers</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state"><i class="fas fa-building"></i><p>No departments found.</p></div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($departments as $d): ?>
                        <tr>
                            <td><b>DEPT<?= str_pad($d['D_ID'],3,'0',STR_PAD_LEFT) ?></b></td>
                            <td><?= h($d['D_NAME']) ?></td>
                            <td><i class="fas fa-map-marker-alt" style="color:#888;margin-right:5px;"></i><?= h($d['BUILDING']) ?></td>
                            <td><?= h($d['OFFICE_PHN']) ?></td>
                            <td><span class="badge badge-active"><?= $d['RES_COUNT'] ?> researcher(s)</span></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(<?= $d['D_ID'] ?>, '<?= addslashes($d['BUILDING']) ?>', '<?= addslashes($d['D_NAME']) ?>', '<?= addslashes($d['OFFICE_PHN']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon" onclick="confirmDelete(<?= $d['D_ID'] ?>, '<?= addslashes($d['D_NAME']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($departments) ?> department(s) found</div>
            </div>
        </div>
    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Department</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Department Name *</label>
                    <input type="text" name="d_name" class="form-control" placeholder="e.g. Computer Science" required>
                </div>
                <div class="form-group">
                    <label>Building *</label>
                    <input type="text" name="building" class="form-control" placeholder="e.g. Building A" required>
                </div>
                <div class="form-group">
                    <label>Office Phone *</label>
                    <input type="text" name="office_phn" class="form-control" placeholder="e.g. 01711000001" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Department</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Department</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="d_id" id="edit_d_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Department Name *</label>
                    <input type="text" name="d_name" id="edit_d_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Building *</label>
                    <input type="text" name="building" id="edit_building" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Office Phone *</label>
                    <input type="text" name="office_phn" id="edit_office_phn" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE Form -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="d_id"  id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(id, bld, nam, phn) {
    document.getElementById('edit_d_id').value      = id;
    document.getElementById('edit_building').value  = bld;
    document.getElementById('edit_d_name').value    = nam;
    document.getElementById('edit_office_phn').value= phn;
    openModal('editModal');
}

function confirmDelete(id, name) {
    if (confirm('Delete department "' + name + '"?\nThis cannot be undone.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
</script>
</body>
</html>