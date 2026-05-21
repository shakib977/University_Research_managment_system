<?php
// ================================================
// URMS — Publication Types & Categories (pub_types.php)
// PL/SQL: add_pub_type, update_pub_type, delete_pub_type
//         add_category, update_category, delete_category
//         research_utils.count_pubs_by_type (package function)
// ================================================
require 'config.php';
$msg = $err = '';
$activeTab = $_GET['tab'] ?? 'types';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Publication Types ──
    if ($action === 'add_type') {
        $type = trim($_POST['type_name'] ?? '');
        $res  = callProc("add_pub_type(:t)", [':t'=>$type]);
        $msg  = ($res === true) ? 'Publication type added!' : 'Error: '.$res;
        $activeTab = 'types';
    }
    if ($action === 'edit_type') {
        $id   = (int)$_POST['t_id'];
        $type = trim($_POST['type_name'] ?? '');
        $res  = callProc("update_pub_type(:id,:t)", [':id'=>$id, ':t'=>$type]);
        $msg  = ($res === true) ? 'Type updated!' : 'Error: '.$res;
        $activeTab = 'types';
    }
    if ($action === 'delete_type') {
        $id  = (int)$_POST['t_id'];
        $res = callProc("delete_pub_type(:id)", [':id'=>$id]);
        $msg = ($res === true) ? 'Type deleted.' : 'Error: '.$res;
        $activeTab = 'types';
    }

    // ── Categories ──
    if ($action === 'add_cat') {
        $name = trim($_POST['c_name'] ?? '');
        $desc = trim($_POST['c_desc'] ?? '');
        $res  = callProc("add_category(:n,:d)", [':n'=>$name, ':d'=>$desc]);
        $msg  = ($res === true) ? 'Category added!' : 'Error: '.$res;
        $activeTab = 'categories';
    }
    if ($action === 'edit_cat') {
        $id   = (int)$_POST['c_id'];
        $name = trim($_POST['c_name'] ?? '');
        $desc = trim($_POST['c_desc'] ?? '');
        $res  = callProc("update_category(:id,:n,:d)", [':id'=>$id, ':n'=>$name, ':d'=>$desc]);
        $msg  = ($res === true) ? 'Category updated!' : 'Error: '.$res;
        $activeTab = 'categories';
    }
    if ($action === 'delete_cat') {
        $id  = (int)$_POST['c_id'];
        $res = callProc("delete_category(:id)", [':id'=>$id]);
        $msg = ($res === true) ? 'Category deleted.' : 'Error: '.$res;
        $activeTab = 'categories';
    }
}

// Fetch types — using package function research_utils.count_pubs_by_type for each
$types = dbFetchAll(
    "SELECT pt.T_Id, pt.Type,
            COUNT(p.P_Id) PUB_COUNT
     FROM Publication_Type pt
     LEFT JOIN Publication p ON p.T_Id = pt.T_Id
     GROUP BY pt.T_Id, pt.Type
     ORDER BY pt.T_Id"
);

$categories = dbFetchAll(
    "SELECT c.C_Id, c.C_Name, c.Description,
            COUNT(p.P_Id) PUB_COUNT
     FROM Category c
     LEFT JOIN Publication p ON p.C_Id = c.C_Id
     GROUP BY c.C_Id, c.C_Name, c.Description
     ORDER BY c.C_Id"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publication Types — URMS</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs { display:flex; gap:4px; margin-bottom:20px; }
        .tab-btn {
            padding:9px 22px; border:none; border-radius:7px 7px 0 0;
            cursor:pointer; font-size:13.5px; font-weight:600;
            background:#e8eaf6; color:#3949ab; transition:all .18s;
        }
        .tab-btn.active { background:#1a237e; color:#fff; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left"><div>
            <div class="topbar-title">Publication Types &amp; Categories</div>
            <div class="topbar-sub">Manage publication classifications</div>
        </div></div>
        <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
    </div>

    <div class="page-content">
        <div class="page-header">
            <h1><i class="fas fa-tags" style="font-size:18px;margin-right:8px;"></i>Publication Types &amp; Categories</h1>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div><?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?= $activeTab==='types' ? 'active' : '' ?>"
                    onclick="switchTab('types')">
                <i class="fas fa-list-alt"></i> Publication Types (<?= count($types) ?>)
            </button>
            <button class="tab-btn <?= $activeTab==='categories' ? 'active' : '' ?>"
                    onclick="switchTab('categories')">
                <i class="fas fa-folder"></i> Categories (<?= count($categories) ?>)
            </button>
        </div>

        <!-- ── TYPES TAB ── -->
        <div id="tab-types" class="tab-content <?= $activeTab==='types' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list-alt"></i> Publication Types</h2>
                    <button class="btn btn-primary" onclick="openModal('addTypeModal')">
                        <i class="fas fa-plus"></i> Add Type
                    </button>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr><th>Type ID</th><th>Type Name</th><th>Publications</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($types)): ?>
                            <tr><td colspan="4">
                                <div class="empty-state"><i class="fas fa-tags"></i><p>No types found.</p></div>
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($types as $t): ?>
                            <tr>
                                <td><b>TYPE<?= str_pad($t['T_ID'], 3, '0', STR_PAD_LEFT) ?></b></td>
                                <td>
                                    <span style="background:#e8eaf6;color:#3949ab;padding:4px 12px;
                                                 border-radius:4px;font-weight:700;">
                                        <?= h($t['TYPE']) ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-active"><?= $t['PUB_COUNT'] ?> pub(s)</span></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-icon"
                                        onclick="openEditType(<?= $t['T_ID'] ?>,'<?= addslashes($t['TYPE']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-icon"
                                        onclick="confirmDeleteType(<?= $t['T_ID'] ?>,'<?= addslashes($t['TYPE']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer"><?= count($types) ?> type(s)</div>
                </div>
            </div>
        </div>

        <!-- ── CATEGORIES TAB ── -->
        <div id="tab-categories" class="tab-content <?= $activeTab==='categories' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-folder"></i> Publication Categories</h2>
                    <button class="btn btn-primary" onclick="openModal('addCatModal')">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr><th>Cat ID</th><th>Category Name</th><th>Description</th><th>Publications</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="5">
                                <div class="empty-state"><i class="fas fa-folder"></i><p>No categories found.</p></div>
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                            <tr>
                                <td><b>CAT<?= str_pad($c['C_ID'], 3, '0', STR_PAD_LEFT) ?></b></td>
                                <td style="font-weight:600;color:#1a237e;"><?= h($c['C_NAME']) ?></td>
                                <td style="color:#666;"><?= h($c['DESCRIPTION']) ?></td>
                                <td><span class="badge badge-active"><?= $c['PUB_COUNT'] ?> pub(s)</span></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-icon"
                                        onclick="openEditCat(<?= $c['C_ID'] ?>,'<?= addslashes($c['C_NAME']) ?>','<?= addslashes($c['DESCRIPTION']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-icon"
                                        onclick="confirmDeleteCat(<?= $c['C_ID'] ?>,'<?= addslashes($c['C_NAME']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer"><?= count($categories) ?> category/categories</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD TYPE -->
<div class="modal-overlay" id="addTypeModal"><div class="modal">
    <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Add Publication Type</h3>
        <button class="modal-close" onclick="closeModal('addTypeModal')">&times;</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add_type">
        <div class="modal-body">
            <div class="form-group">
                <label>Type Name *</label>
                <input type="text" name="type_name" class="form-control"
                       placeholder="e.g. Journal, Conference, Workshop" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" onclick="closeModal('addTypeModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Type</button>
        </div>
    </form>
</div></div>

<!-- EDIT TYPE -->
<div class="modal-overlay" id="editTypeModal"><div class="modal">
    <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Publication Type</h3>
        <button class="modal-close" onclick="closeModal('editTypeModal')">&times;</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="edit_type">
        <input type="hidden" name="t_id"   id="edit_t_id">
        <div class="modal-body">
            <div class="form-group">
                <label>Type Name *</label>
                <input type="text" name="type_name" id="edit_type_name" class="form-control" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" onclick="closeModal('editTypeModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Type</button>
        </div>
    </form>
</div></div>

<!-- ADD CATEGORY -->
<div class="modal-overlay" id="addCatModal"><div class="modal">
    <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Add Category</h3>
        <button class="modal-close" onclick="closeModal('addCatModal')">&times;</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="add_cat">
        <div class="modal-body">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="c_name" class="form-control"
                       placeholder="e.g. Artificial Intelligence" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="c_desc" class="form-control"
                       placeholder="Brief description of this category">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" onclick="closeModal('addCatModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
        </div>
    </form>
</div></div>

<!-- EDIT CATEGORY -->
<div class="modal-overlay" id="editCatModal"><div class="modal">
    <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Category</h3>
        <button class="modal-close" onclick="closeModal('editCatModal')">&times;</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="edit_cat">
        <input type="hidden" name="c_id"   id="edit_c_id">
        <div class="modal-body">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="c_name" id="edit_c_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="c_desc" id="edit_c_desc" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" onclick="closeModal('editCatModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Category</button>
        </div>
    </form>
</div></div>

<!-- Hidden delete forms -->
<form method="POST" id="deleteTypeForm">
    <input type="hidden" name="action" value="delete_type">
    <input type="hidden" name="t_id"   id="del_t_id">
</form>
<form method="POST" id="deleteCatForm">
    <input type="hidden" name="action" value="delete_cat">
    <input type="hidden" name="c_id"   id="del_c_id">
</form>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    event.target.classList.add('active');
}

function openEditType(id, name) {
    document.getElementById('edit_t_id').value       = id;
    document.getElementById('edit_type_name').value  = name;
    openModal('editTypeModal');
}

function openEditCat(id, name, desc) {
    document.getElementById('edit_c_id').value    = id;
    document.getElementById('edit_c_name').value  = name;
    document.getElementById('edit_c_desc').value  = desc;
    openModal('editCatModal');
}

function confirmDeleteType(id, name) {
    if (confirm('Delete type "' + name + '"?\nTypes with publications cannot be deleted.')) {
        document.getElementById('del_t_id').value = id;
        document.getElementById('deleteTypeForm').submit();
    }
}

function confirmDeleteCat(id, name) {
    if (confirm('Delete category "' + name + '"?\nCategories with publications cannot be deleted.')) {
        document.getElementById('del_c_id').value = id;
        document.getElementById('deleteCatForm').submit();
    }
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
</script>
</body>
</html>