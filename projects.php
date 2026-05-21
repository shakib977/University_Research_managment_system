<?php
// ================================================
// URMS — Research Projects (projects.php)
// Uses: show_projects procedure, total_projects_by_supervisor function
// ================================================
require 'config.php';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title  = trim($_POST['title']  ?? '');
        $sdate  = trim($_POST['s_date'] ?? '');
        $edate  = trim($_POST['e_date'] ?? '') ?: null;
        $status = trim($_POST['status'] ?? '');
        $s_id   = (int)($_POST['s_id'] ?? 0);
        $f_id   = (int)($_POST['f_id'] ?? 0);

        if (!$title || !$sdate || !$status) {
            $err = 'Title, Start Date and Status are required.';
        } else {
            $sql = "INSERT INTO Project VALUES
                    (seq_project.NEXTVAL,:ttl,TO_DATE(:sdt,'YYYY-MM-DD'),
                     TO_DATE(:edt,'YYYY-MM-DD'),:sts,:sid,:fid)";
            $res = dbExecute($sql, [':ttl'=>$title,':sdt'=>$sdate,':edt'=>$edate,
                                    ':sts'=>$status,':sid'=>$s_id,':fid'=>$f_id]);
            $msg = ($res === true) ? 'Project added!' : 'Error: '.$res;
        }
    }

    if ($action === 'edit') {
        $id     = (int)$_POST['pro_id'];
        $title  = trim($_POST['title']  ?? '');
        $status = trim($_POST['status'] ?? '');
        $edate  = trim($_POST['e_date'] ?? '') ?: null;

        $sql = "UPDATE Project SET Title=:ttl, Status=:sts,
                E_Date=TO_DATE(:edt,'YYYY-MM-DD') WHERE Pro_Id=:id";
        $res = dbExecute($sql, [':ttl'=>$title,':sts'=>$status,':edt'=>$edate,':id'=>$id]);
        $msg = ($res === true) ? 'Project updated!' : 'Error: '.$res;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['pro_id'];
        $conn = getDB();
        // Implicit row lock
        $lk = oci_parse($conn,"SELECT * FROM Project WHERE Pro_Id=:id FOR UPDATE");
        oci_bind_by_name($lk,':id',$id);
        oci_execute($lk, OCI_DEFAULT);

        dbExecute("DELETE FROM Work WHERE Pro_Id=:id",[':id'=>$id]);
        $res = dbExecute("DELETE FROM Project WHERE Pro_Id=:id",[':id'=>$id]);
        if ($res === true) { oci_commit($conn); $msg = 'Project deleted.'; }
        else               { oci_rollback($conn); $err = 'Error: '.$res; }
        oci_free_statement($lk);
    }
}

$search = trim($_GET['q'] ?? '');
$q = '%'.$search.'%';

$projects = dbFetchAll(
    "SELECT p.Pro_Id, p.Title, p.Status, p.S_Date, p.E_Date,
            s.S_Name, s.S_Id,
            f.F_Name,
            (SELECT COUNT(*) FROM Work w WHERE w.Pro_Id = p.Pro_Id) TEAM_SIZE
     FROM Project p
     JOIN Supervisor   s ON p.S_Id = s.S_Id
     JOIN Fund_Agency  f ON p.F_Id = f.F_Id
     WHERE UPPER(p.Title) LIKE UPPER(:q)
        OR UPPER(p.Status) LIKE UPPER(:q2)
        OR UPPER(s.S_Name) LIKE UPPER(:q3)
     ORDER BY p.Pro_Id DESC",
    [':q'=>$q,':q2'=>$q,':q3'=>$q]
);

$supervisors = dbFetchAll("SELECT S_Id, S_Name, Designation FROM Supervisor ORDER BY S_Name");
$agencies    = dbFetchAll("SELECT F_Id, F_Name FROM Fund_Agency ORDER BY F_Name");

function statusBadge($s) {
    $m = ['Active'=>'badge-active','Completed'=>'badge-completed','Pending'=>'badge-pending','Planning'=>'badge-planning'];
    return "<span class='badge ".($m[$s]??'badge-planning')."'>{$s}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Research Projects — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">Research Projects</div>
            <div class="topbar-sub">Manage research projects</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-project-diagram" style="font-size:18px;margin-right:8px;"></i>Research Projects</h1>
                <p>All (<?= count($projects) ?>) research projects</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Project
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Projects (<?= count($projects) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by title, supervisor, or status...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project ID</th><th>Title</th><th>Supervisor</th>
                            <th>Status</th><th>Start Date</th><th>End Date</th>
                            <th>Funding</th><th>Team</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                        <tr><td colspan="9">
                            <div class="empty-state"><i class="fas fa-folder-open"></i><p>No projects found.</p></div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                        <tr>
                            <td><b>PRJ<?= str_pad($p['PRO_ID'],3,'0',STR_PAD_LEFT) ?></b></td>
                            <td style="max-width:220px;"><?= h($p['TITLE']) ?></td>
                            <td><?= h($p['S_NAME']) ?></td>
                            <td><?= statusBadge($p['STATUS']) ?></td>
                            <td><?= $p['S_DATE'] ? date('M d, Y',strtotime($p['S_DATE'])) : '—' ?></td>
                            <td><?= $p['E_DATE'] ? date('M d, Y',strtotime($p['E_DATE'])) : '<i style="color:#aaa">Ongoing</i>' ?></td>
                            <td style="font-size:12px;"><?= h($p['F_NAME']) ?></td>
                            <td><span class="badge badge-planning"><?= $p['TEAM_SIZE'] ?> researcher(s)</span></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(<?= $p['PRO_ID'] ?>,'<?= addslashes($p['TITLE']) ?>','<?= h($p['STATUS']) ?>','<?= substr($p['E_DATE']??'',0,10) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon"
                                    onclick="confirmDelete(<?= $p['PRO_ID'] ?>,'<?= addslashes($p['TITLE']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($projects) ?> project(s)</div>
            </div>
        </div>
    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Project</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Project Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter project title" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="s_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="e_date" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="">Select...</option>
                            <option>Active</option><option>Pending</option>
                            <option>Planning</option><option>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supervisor *</label>
                        <select name="s_id" class="form-control" required>
                            <?php foreach ($supervisors as $s): ?>
                            <option value="<?= $s['S_ID'] ?>"><?= h($s['S_NAME']) ?> (<?= h($s['DESIGNATION']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Funding Agency *</label>
                    <select name="f_id" class="form-control" required>
                        <?php foreach ($agencies as $f): ?>
                        <option value="<?= $f['F_ID'] ?>"><?= h($f['F_NAME']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Project</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Project</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="pro_id" id="edit_pro_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Project Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option>Active</option><option>Pending</option>
                            <option>Planning</option><option>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="e_date" id="edit_edate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="pro_id" id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openEdit(id, title, status, edate) {
    document.getElementById('edit_pro_id').value = id;
    document.getElementById('edit_title').value  = title;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_edate').value  = edate;
    openModal('editModal');
}
function confirmDelete(id, title) {
    if (confirm('Delete project "' + title + '"?\nAll team assignments will also be removed.')) {
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