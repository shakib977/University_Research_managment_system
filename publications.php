<?php
// ================================================
// URMS — Publications (publications.php)
// PL/SQL: add_publication, update_publication,
//         delete_publication, get_pub_count()
// ================================================
require 'config.php';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title     = trim($_POST['title']     ?? '');
        $doi       = trim($_POST['doi']       ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $year      = (int)$_POST['year'];
        $c_id      = (int)$_POST['c_id'];
        $t_id      = (int)$_POST['t_id'];

        $res = callProc("add_publication(:tt,:doi,:pub,:yr,:cid,:tid)",
                        [':tt'=>$title, ':doi'=>$doi, ':pub'=>$publisher,
                         ':yr'=>$year,  ':cid'=>$c_id, ':tid'=>$t_id]);
        $msg = ($res === true) ? 'Publication added!' : 'Error: '.$res;
    }

    if ($action === 'edit') {
        $id        = (int)$_POST['p_id'];
        $title     = trim($_POST['title']     ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $year      = (int)$_POST['year'];
        $c_id      = (int)$_POST['c_id'];
        $t_id      = (int)$_POST['t_id'];

        $res = callProc("update_publication(:id,:tt,:pub,:yr,:cid,:tid)",
                        [':id'=>$id, ':tt'=>$title, ':pub'=>$publisher,
                         ':yr'=>$year, ':cid'=>$c_id, ':tid'=>$t_id]);
        $msg = ($res === true) ? 'Publication updated!' : 'Error: '.$res;
    }

    if ($action === 'delete') {
        $id  = (int)$_POST['p_id'];
        $res = callProc("delete_publication(:id)", [':id'=>$id]);
        $msg = ($res === true) ? 'Publication deleted.' : 'Error: '.$res;
    }
}

// Call stored function for total count
$totalPubs = callFunc("get_pub_count()");

$search = trim($_GET['q'] ?? '');
$q = '%'.$search.'%';

$publications = dbFetchAll(
    "SELECT p.P_Id, p.Title, p.DOI, p.Publisher, p.Year,
            c.C_Name, c.C_Id,
            pt.Type,  pt.T_Id,
            (SELECT COUNT(*) FROM Authors a WHERE a.P_Id = p.P_Id) AUTH_COUNT
     FROM Publication p
     JOIN Category         c  ON p.C_Id = c.C_Id
     JOIN Publication_Type pt ON p.T_Id = pt.T_Id
     WHERE UPPER(p.Title)       LIKE UPPER(:q)
        OR UPPER(c.C_Name)      LIKE UPPER(:q2)
        OR UPPER(pt.Type)       LIKE UPPER(:q3)
        OR UPPER(p.Publisher)   LIKE UPPER(:q4)
     ORDER BY p.P_Id DESC",
    [':q'=>$q, ':q2'=>$q, ':q3'=>$q, ':q4'=>$q]
);

$categories = dbFetchAll("SELECT C_Id, C_Name FROM Category ORDER BY C_Name");
$pub_types  = dbFetchAll("SELECT T_Id, Type FROM Publication_Type ORDER BY Type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publications — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .type-badge { display:inline-block; padding:3px 9px; border-radius:4px;
                      font-size:11px; font-weight:700; background:#e8eaf6; color:#3949ab; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">Publications</div>
            <div class="topbar-sub">Manage research publications</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-book-open" style="font-size:18px;margin-right:8px;"></i>Publications</h1>
                <!-- Using stored function get_pub_count() -->
                <p>Total: <b><?= $totalPubs ?></b> publication(s) — via PL/SQL <code>get_pub_count()</code></p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Add Publication
            </button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Publications (<?= count($publications) ?>)</h2>
            </div>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="q" value="<?= h($search) ?>"
                           placeholder="Search by title, category, type, or publisher...">
                </form>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Authors</th>
                            <th>DOI</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($publications)): ?>
                        <tr><td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <p>No publications found.</p>
                            </div>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($publications as $p): ?>
                        <tr>
                            <td><b>PUB<?= str_pad($p['P_ID'], 3, '0', STR_PAD_LEFT) ?></b></td>
                            <td style="max-width:200px;font-weight:600;color:#1a237e;">
                                <?= h($p['TITLE']) ?>
                            </td>
                            <td><?= h($p['PUBLISHER']) ?></td>
                            <td><span class="badge badge-planning"><?= h($p['YEAR']) ?></span></td>
                            <td><?= h($p['C_NAME']) ?></td>
                            <td><span class="type-badge"><?= h($p['TYPE']) ?></span></td>
                            <td><span class="badge badge-active"><?= $p['AUTH_COUNT'] ?> author(s)</span></td>
                            <td style="font-size:11px;color:#888;"><?= h($p['DOI']) ?></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-icon"
                                    onclick="openEdit(
                                        <?= $p['P_ID'] ?>,
                                        '<?= addslashes($p['TITLE']) ?>',
                                        '<?= addslashes($p['PUBLISHER']) ?>',
                                        <?= $p['YEAR'] ?>,
                                        <?= $p['C_ID'] ?>,
                                        <?= $p['T_ID'] ?>
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-icon"
                                    onclick="confirmDelete(<?= $p['P_ID'] ?>, '<?= addslashes($p['TITLE']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer"><?= count($publications) ?> publication(s) found</div>
            </div>
        </div>
    </div>
</div>

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Publication</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control"
                           placeholder="Full publication title" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Publisher *</label>
                        <input type="text" name="publisher" class="form-control"
                               placeholder="e.g. IEEE, Springer" required>
                    </div>
                    <div class="form-group">
                        <label>Year *</label>
                        <input type="number" name="year" class="form-control"
                               value="<?= date('Y') ?>" min="1990" max="2030" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>DOI *</label>
                    <input type="text" name="doi" class="form-control"
                           placeholder="e.g. 10.1000/xyz123" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="c_id" class="form-control" required>
                            <option value="">Select category...</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['C_ID'] ?>"><?= h($c['C_NAME']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Publication Type *</label>
                        <select name="t_id" class="form-control" required>
                            <option value="">Select type...</option>
                            <?php foreach ($pub_types as $t): ?>
                            <option value="<?= $t['T_ID'] ?>"><?= h($t['TYPE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Publication</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Publication</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="p_id"   id="edit_p_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Publisher *</label>
                        <input type="text" name="publisher" id="edit_publisher" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Year *</label>
                        <input type="number" name="year" id="edit_year" class="form-control"
                               min="1990" max="2030" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="c_id" id="edit_cid" class="form-control" required>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['C_ID'] ?>"><?= h($c['C_NAME']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Publication Type *</label>
                        <select name="t_id" id="edit_tid" class="form-control" required>
                            <?php foreach ($pub_types as $t): ?>
                            <option value="<?= $t['T_ID'] ?>"><?= h($t['TYPE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Publication</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="p_id"   id="delete_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(id, title, publisher, year, cid, tid) {
    document.getElementById('edit_p_id').value       = id;
    document.getElementById('edit_title').value      = title;
    document.getElementById('edit_publisher').value  = publisher;
    document.getElementById('edit_year').value       = year;
    document.getElementById('edit_cid').value        = cid;
    document.getElementById('edit_tid').value        = tid;
    openModal('editModal');
}

function confirmDelete(id, title) {
    if (confirm('Delete publication "' + title + '"?\nAuthor records for this publication will also be removed.')) {
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