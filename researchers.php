<?php
// ================================================
// URMS — Researchers (researchers.php)
// Uses: stored procedure show_researchers (PL/SQL)
// ================================================
require 'config.php';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = trim($_POST['name']        ?? '');
        $pass    = trim($_POST['password']    ?? '');
        $area    = trim($_POST['re_area']     ?? '');
        $desig   = trim($_POST['designation'] ?? '');
        $a_id    = (int)($_POST['a_id']  ?? 1);
        $x_id    = (int)($_POST['x_id']  ?? 1);
        $d_id    = (int)($_POST['d_id']  ?? 1);
        $phone   = trim($_POST['phone']       ?? '');
 
        if (!$name || !$pass || !$desig) {
            $err = 'Name, Password and Designation are required.';
        } else {
            $conn = getDB();
            // Use explicit locking: LOCK TABLE (statement-level explicit lock)
            $lockStmt = oci_parse($conn, "LOCK TABLE Researcher IN EXCLUSIVE MODE NOWAIT");
            @oci_execute($lockStmt, OCI_DEFAULT);
            oci_free_statement($lockStmt);

            $sql = "INSERT INTO Researcher VALUES
                    (seq_researcher.NEXTVAL,:nam,:pwd,:area,:des,:aid,:xid,:did)";
            $res = dbExecute($sql, [':nam'=>$name,':pwd'=>$pass,':area'=>$area,
                                    ':des'=>$desig,':aid'=>$a_id,':xid'=>$x_id,':did'=>$d_id]); 
            if ($res === true && $phone) {
                // get new R_Id
                $newId = dbFetchOne("SELECT seq_researcher.CURRVAL RID FROM DUAL")['RID'];
                dbExecute("INSERT INTO Contact VALUES(:rid,:phn)", [':rid'=>$newId,':phn'=>$phone]);
            }
            $msg = ($res === true) ? 'Researcher added!' : 'Error: '.$res;
        }
    }

    if ($action === 'edit') {
        $id    = (int)$_POST['r_id'];
        $name  = trim($_POST['name']        ?? '');
        $area  = trim($_POST['re_area']     ?? '');
        $desig = trim($_POST['designation'] ?? '');
        $d_id  = (int)$_POST['d_id'];

        $sql = "UPDATE Researcher SET Name=:nam, Re_Area=:area, Designation=:des, D_Id=:did WHERE R_Id=:rid";
        $res = dbExecute($sql, [':nam'=>$name,':area'=>$area,':des'=>$desig,':did'=>$d_id,':rid'=>$id]);
        $msg = ($res === true) ? 'Researcher updated!' : 'Error: '.$res;
    }

    if ($action === 'delete') { 
        $id  = (int)$_POST['r_id'];
        $conn = getDB();
        // Implicit row lock before delete
        $lk = oci_parse($conn, "SELECT * FROM Researcher WHERE R_Id=:id FOR UPDATE");
        oci_bind_by_name($lk,':id',$id);
        oci_execute($lk, OCI_DEFAULT);

        dbExecute("DELETE FROM Contact  WHERE R_Id=:id", [':id'=>$id]);
        dbExecute("DELETE FROM Authors  WHERE R_Id=:id", [':id'=>$id]);
        dbExecute("DELETE FROM Work     WHERE R_Id=:id", [':id'=>$id]);
        $res = dbExecute("DELETE FROM Researcher WHERE R_Id=:id", [':id'=>$id]);
        if ($res === true) { oci_commit($conn); $msg = 'Researcher deleted.'; }
        else               { oci_rollback($conn); $err = 'Error: '.$res; }
        oci_free_statement($lk);
    }
}

$search = trim($_GET['q'] ?? '');
$q = '%'.$search.'%';

// Fetch via regular query (mirrors show_researchers procedure result)
$researchers = dbFetchAll(
    "SELECT r.R_Id, r.Name, r.Designation, r.Re_Area,
            d.D_Name, r.D_Id, r.A_Id, r.X_Id,
            l.City, l.Country,
            (SELECT COUNT(*) FROM Authors a WHERE a.R_Id = r.R_Id) PUB_CNT,
            (SELECT COUNT(*) FROM Work w WHERE w.R_Id = r.R_Id) PROJ_CNT
     FROM Researcher r 
     LEFT JOIN Department d ON r.D_Id = d.D_Id
     LEFT JOIN Location   l ON r.X_Id = l.X_Id
     WHERE UPPER(r.Name) LIKE UPPER(:q)
        OR UPPER(r.Re_Area) LIKE UPPER(:q2)
        OR UPPER(d.D_Name) LIKE UPPER(:q3)
     ORDER BY r.R_Id",
    [':q'=>$q, ':q2'=>$q, ':q3'=>$q]
);

$departments = dbFetchAll("SELECT D_Id, D_Name FROM Department ORDER BY D_Name");
$locations   = dbFetchAll("SELECT X_Id, City, Country FROM Location ORDER BY City");
$admins      = dbFetchAll("SELECT A_Id, Name FROM Admin ORDER BY Name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Researchers — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head> 
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">Researchers</div>
            <div class="topbar-sub">Manage university researchers</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-user-graduate" style="font-size:18px;margin-right:8px;"></i>Researchers</h1>
                <p>All (<?= count($researchers) ?>) researchers</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Researcher
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Researchers (<?= count($researchers) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name, department, or research area...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Name</th><th>Designation</th>
                            <th>Department</th><th>Research Area</th>
                            <th>Location</th><th>Projects</th><th>Publications</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($researchers)): ?>
                        <tr><td colspan="9">
                            <div class="empty-state"><i class="fas fa-user-graduate"></i><p>No researchers found.</p></div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($researchers as $r): ?>
                        <tr>
                            <td><b>RES<?= str_pad($r['R_ID'],3,'0',STR_PAD_LEFT) ?></b></td>
                            <td><?= h($r['NAME']) ?></td>
                            <td><?= h($r['DESIGNATION']) ?></td>
                            <td><?= h($r['D_NAME'] ?? '—') ?></td>
                            <td><span style="color:#1a237e;font-weight:600;"><?= h($r['RE_AREA'] ?? '—') ?></span></td>
                            <td><?= h($r['CITY'] ?? '—') ?>, <?= h($r['COUNTRY'] ?? '') ?></td>
                            <td><span class="badge badge-planning"><?= $r['PROJ_CNT'] ?></span></td>
                            <td><span class="badge badge-active"><?= $r['PUB_CNT'] ?></span></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(<?= $r['R_ID'] ?>, '<?= addslashes($r['NAME']) ?>',
                                    '<?= addslashes($r['RE_AREA'] ?? '') ?>','<?= addslashes($r['DESIGNATION']) ?>',
                                    <?= $r['D_ID'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon"
                                    onclick="confirmDelete(<?= $r['R_ID'] ?>, '<?= addslashes($r['NAME']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($researchers) ?> researcher(s)</div>
            </div>
        </div>
    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Researcher</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Dr. Name" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Designation *</label>
                        <select name="designation" class="form-control" required>
                            <option value="">Select...</option>
                            <option>Professor</option>
                            <option>Associate Prof</option>
                            <option>Assistant Professor</option>
                            <option>Lecturer</option>
                            <option>Senior Researcher</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Research Area</label>
                        <input type="text" name="re_area" class="form-control" placeholder="e.g. AI, Robotics">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="d_id" class="form-control" required>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['D_ID'] ?>"><?= h($d['D_NAME']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <select name="x_id" class="form-control" required>
                            <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['X_ID'] ?>"><?= h($l['CITY']) ?>, <?= h($l['COUNTRY']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin (Managed by)</label>
                        <select name="a_id" class="form-control">
                            <?php foreach ($admins as $a): ?>
                            <option value="<?= $a['A_ID'] ?>"><?= h($a['NAME']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Researcher</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="r_id"   id="edit_r_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Designation *</label>
                        <select name="designation" id="edit_desig" class="form-control" required>
                            <option>Professor</option>
                            <option>Associate Prof</option>
                            <option>Assistant Professor</option>
                            <option>Lecturer</option>
                            <option>Senior Researcher</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Research Area</label>
                        <input type="text" name="re_area" id="edit_area" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="d_id" id="edit_did" class="form-control" required>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['D_ID'] ?>"><?= h($d['D_NAME']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
    <input type="hidden" name="r_id"  id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openEdit(id, name, area, desig, did) {
    document.getElementById('edit_r_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_area').value = area;
    document.getElementById('edit_desig').value= desig;
    document.getElementById('edit_did').value  = did;
    openModal('editModal');
}
function confirmDelete(id, name) {
    if (confirm('Delete researcher "' + name + '"?\nThis will also remove their contacts, project assignments, and authorship records.')) {
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