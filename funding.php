<?php
// ================================================
// URMS — Funding Agencies (funding.php)
// PL/SQL: add_fund_agency, update_fund_agency,
//         delete_fund_agency
// ================================================
require 'config.php';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = trim($_POST['f_name']    ?? '');
        $country = trim($_POST['country']   ?? '');
        $email   = trim($_POST['con_email'] ?? '');
        $phone   = trim($_POST['phone']     ?? '');

        $res = callProc("add_fund_agency(:n,:c,:e,:p)",
                        [':n'=>$name, ':c'=>$country, ':e'=>$email, ':p'=>$phone]);
        $msg = ($res === true) ? 'Funding agency added successfully!' : 'Error: '.$res;
    }

    if ($action === 'edit') {
        $id      = (int)$_POST['f_id'];
        $name    = trim($_POST['f_name']    ?? '');
        $country = trim($_POST['country']   ?? '');
        $email   = trim($_POST['con_email'] ?? '');
        $phone   = trim($_POST['phone']     ?? '');

        $res = callProc("update_fund_agency(:id,:n,:c,:e,:p)",
                        [':id'=>$id, ':n'=>$name, ':c'=>$country, ':e'=>$email, ':p'=>$phone]);
        $msg = ($res === true) ? 'Agency updated!' : 'Error: '.$res;
    }

    if ($action === 'delete') {
        $id  = (int)$_POST['f_id'];
        $res = callProc("delete_fund_agency(:id)", [':id'=>$id]);
        $msg = ($res === true) ? 'Agency deleted.' : 'Error: '.$res;
    }
}

$search = trim($_GET['q'] ?? '');
$q = '%'.$search.'%';

$agencies = dbFetchAll(
    "SELECT f.F_Id, f.F_Name, f.Country, f.Con_Email, f.Phone,
            COUNT(p.Pro_Id) PRJ_COUNT
     FROM Fund_Agency f
     LEFT JOIN Project p ON p.F_Id = f.F_Id
     WHERE UPPER(f.F_Name)    LIKE UPPER(:q)
        OR UPPER(f.Country)   LIKE UPPER(:q2)
        OR UPPER(f.Con_Email) LIKE UPPER(:q3)
     GROUP BY f.F_Id, f.F_Name, f.Country, f.Con_Email, f.Phone
     ORDER BY f.F_Id",
    [':q'=>$q, ':q2'=>$q, ':q3'=>$q]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Funding Agencies — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">Funding Agencies</div>
            <div class="topbar-sub">Manage funding agencies</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-dollar-sign" style="font-size:18px;margin-right:8px;"></i>Funding Agencies</h1>
                <p>All (<?= count($agencies) ?>) funding agencies</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Agency
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Funding Agencies (<?= count($agencies) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>"
                           placeholder="Search by name, country, or email...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Agency ID</th>
                            <th>Agency Name</th>
                            <th>Country</th>
                            <th>Contact Email</th>
                            <th>Phone</th>
                            <th>Projects Funded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agencies)): ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-dollar-sign"></i>
                                <p>No funding agencies found.</p>
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($agencies as $f): ?>
                        <tr>
                            <td><b>FND<?= str_pad($f['F_ID'], 3, '0', STR_PAD_LEFT) ?></b></td>
                            <td>
                                <i class="fas fa-landmark" style="color:#1a237e;margin-right:6px;"></i>
                                <?= h($f['F_NAME']) ?>
                            </td>
                            <td>
                                <i class="fas fa-globe" style="color:#888;margin-right:5px;"></i>
                                <?= h($f['COUNTRY']) ?>
                            </td>
                            <td>
                                <a href="mailto:<?= h($f['CON_EMAIL']) ?>" style="color:#1565c0;">
                                    <?= h($f['CON_EMAIL']) ?>
                                </a>
                            </td>
                            <td><?= h($f['PHONE']) ?></td>
                            <td>
                                <span class="badge badge-<?= $f['PRJ_COUNT'] > 0 ? 'active' : 'completed' ?>">
                                    <?= $f['PRJ_COUNT'] ?> project(s)
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(
                                        <?= $f['F_ID'] ?>,
                                        '<?= addslashes($f['F_NAME']) ?>',
                                        '<?= addslashes($f['COUNTRY']) ?>',
                                        '<?= addslashes($f['CON_EMAIL']) ?>',
                                        '<?= addslashes($f['PHONE']) ?>'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon"
                                    onclick="confirmDelete(<?= $f['F_ID'] ?>, '<?= addslashes($f['F_NAME']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($agencies) ?> agency/agencies found</div>
            </div>
        </div>
    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Funding Agency</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Agency Name *</label>
                    <input type="text" name="f_name" class="form-control"
                           placeholder="e.g. University Grants Commission" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" class="form-control"
                               placeholder="e.g. Bangladesh" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="phone" class="form-control"
                               placeholder="+880XXXXXXXXX" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contact Email *</label>
                    <input type="email" name="con_email" class="form-control"
                           placeholder="contact@agency.gov" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Agency</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Funding Agency</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="f_id"   id="edit_f_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Agency Name *</label>
                    <input type="text" name="f_name" id="edit_fname" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" id="edit_country" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contact Email *</label>
                    <input type="email" name="con_email" id="edit_email" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Agency</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="f_id"   id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(id, name, country, email, phone) {
    document.getElementById('edit_f_id').value    = id;
    document.getElementById('edit_fname').value   = name;
    document.getElementById('edit_country').value = country;
    document.getElementById('edit_email').value   = email;
    document.getElementById('edit_phone').value   = phone;
    openModal('editModal');
}

function confirmDelete(id, name) {
    if (confirm('Delete funding agency "' + name + '"?\nAgencies with active projects cannot be deleted.')) {
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