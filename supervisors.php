<?php
// ================================================
// URMS — Supervisors (supervisors.php)
// ================================================
require 'config.php';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $email = trim($_POST['email']??'');  $name = trim($_POST['s_name']??'');
        $phn   = trim($_POST['phn']  ??'');  $desig= trim($_POST['designation']??'');
        if (!$email||!$name||!$phn||!$desig) { $err='All fields required.'; }
        else {
            $res = dbExecute("INSERT INTO Supervisor VALUES(seq_supervisor.NEXTVAL,:em,:nm,:ph,:ds)",
                             [':em'=>$email,':nm'=>$name,':ph'=>$phn,':ds'=>$desig]);
            $msg = ($res===true)?'Supervisor added!':'Error: '.$res;
        }
    }
    if ($action==='edit') {
        $id=$_POST['s_id']; $email=trim($_POST['email']??'');
        $name=trim($_POST['s_name']??''); $phn=trim($_POST['phn']??'');
        $desig=trim($_POST['designation']??'');
        $res=dbExecute("UPDATE Supervisor SET Email=:em,S_Name=:nm,Phn=:ph,Designation=:ds WHERE S_Id=:id",
                       [':em'=>$email,':nm'=>$name,':ph'=>$phn,':ds'=>$desig,':id'=>$id]);
        $msg=($res===true)?'Updated!':'Error: '.$res;
    }
    if ($action==='delete') {
        $id=(int)$_POST['s_id'];
        $res=dbExecute("DELETE FROM Supervisor WHERE S_Id=:id",[':id'=>$id]);
        $msg=($res===true)?'Deleted.':'Error: '.$res.' (supervisor may have projects assigned)';
    }
}
$search=trim($_GET['q']??''); $q='%'.$search.'%';
$supervisors=dbFetchAll(
    "SELECT s.S_Id,s.Email,s.S_Name,s.Phn,s.Designation,
            (SELECT COUNT(*) FROM Project p WHERE p.S_Id=s.S_Id) PRJ_CNT
     FROM Supervisor s
     WHERE UPPER(s.S_Name) LIKE UPPER(:q) OR UPPER(s.Designation) LIKE UPPER(:q2)
     ORDER BY s.S_Id", [':q'=>$q,':q2'=>$q]);
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><title>Supervisors — URMS</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
  <div class="topbar">
    <div class="topbar-left"><div>
      <div class="topbar-title">Faculty Supervisors</div>
      <div class="topbar-sub">Manage project supervisors</div>
    </div></div>
    <div class="topbar-date"><i class="far fa-calendar-alt"></i> <?=date('l, F j, Y')?></div>
  </div>
  <div class="page-content">
    <div class="page-header">
      <div><h1><i class="fas fa-chalkboard-teacher" style="font-size:18px;margin-right:8px;"></i>Faculty Supervisors</h1>
      <p>All (<?=count($supervisors)?>) supervisors</p></div>
      <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Supervisor</button>
    </div>
    <?php if($msg):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=h($msg)?></div><?php endif;?>
    <?php if($err):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?=h($err)?></div><?php endif;?>
    <div class="card">
      <div class="card-header"><h2><i class="fas fa-list"></i> All Supervisors (<?=count($supervisors)?>)</h2></div>
      <div class="search-bar"><form method="GET">
        <input type="text" name="q" value="<?=h($search)?>" placeholder="Search by name or designation...">
      </form></div>
      <div class="card-body">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Name</th><th>Designation</th><th>Email</th><th>Phone</th><th>Projects Supervised</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($supervisors)):?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-chalkboard-teacher"></i><p>No supervisors found.</p></div></td></tr>
          <?php else: foreach($supervisors as $s):?>
          <tr>
            <td><b>SUP<?=str_pad($s['S_ID'],3,'0',STR_PAD_LEFT)?></b></td>
            <td><?=h($s['S_NAME'])?></td>
            <td><?=h($s['DESIGNATION'])?></td>
            <td><a href="mailto:<?=h($s['EMAIL'])?>" style="color:#1a237e;"><?=h($s['EMAIL'])?></a></td>
            <td><?=h($s['PHN'])?></td>
            <td><span class="badge badge-active"><?=$s['PRJ_CNT']?> project(s)</span></td>
            <td>
              <button class="btn btn-info btn-sm btn-icon" onclick="openEdit(<?=$s['S_ID']?>,'<?=addslashes($s['EMAIL'])?>','<?=addslashes($s['S_NAME'])?>','<?=addslashes($s['PHN'])?>','<?=addslashes($s['DESIGNATION'])?>')"><i class="fas fa-edit"></i></button>
              <button class="btn btn-danger btn-sm btn-icon" onclick="confirmDelete(<?=$s['S_ID']?>,'<?=addslashes($s['S_NAME'])?>')"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
        <div class="table-footer"><?=count($supervisors)?> supervisor(s)</div>
      </div>
    </div>
  </div>
</div>
<!-- ADD -->
<div class="modal-overlay" id="addModal"><div class="modal">
  <div class="modal-header"><h3><i class="fas fa-plus-circle"></i> Add Supervisor</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" name="s_name" class="form-control" required></div>
        <div class="form-group"><label>Designation *</label>
          <select name="designation" class="form-control" required>
            <option>Professor</option><option>Associate Professor</option><option>Senior Researcher</option>
            <option>Dean</option><option>Head of Research</option><option>Director</option><option>Lead Engineer</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label>Phone *</label><input type="text" name="phn" class="form-control" required></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-light" onclick="closeModal('addModal')">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    </div>
  </form>
</div></div>
<!-- EDIT -->
<div class="modal-overlay" id="editModal"><div class="modal">
  <div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Supervisor</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
  <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="s_id" id="edit_s_id">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" name="s_name" id="edit_sname" class="form-control" required></div>
        <div class="form-group"><label>Designation *</label>
          <select name="designation" id="edit_sdesig" class="form-control">
            <option>Professor</option><option>Associate Professor</option><option>Senior Researcher</option>
            <option>Dean</option><option>Head of Research</option><option>Director</option><option>Lead Engineer</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email *</label><input type="email" name="email" id="edit_semail" class="form-control" required></div>
        <div class="form-group"><label>Phone *</label><input type="text" name="phn" id="edit_sphn" class="form-control" required></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-light" onclick="closeModal('editModal')">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
    </div>
  </form>
</div></div>
<form method="POST" id="deleteForm"><input type="hidden" name="action" value="delete"><input type="hidden" name="s_id" id="delete_id"></form>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function openEdit(id,email,name,phn,desig){
  document.getElementById('edit_s_id').value=id;
  document.getElementById('edit_semail').value=email;
  document.getElementById('edit_sname').value=name;
  document.getElementById('edit_sphn').value=phn;
  document.getElementById('edit_sdesig').value=desig;
  openModal('editModal');
}
function confirmDelete(id,name){
  if(confirm('Delete supervisor "'+name+'"?')){
    document.getElementById('delete_id').value=id;
    document.getElementById('deleteForm').submit();
  }
}
document.querySelectorAll('.modal-overlay').forEach(el=>{
  el.addEventListener('click',e=>{if(e.target===el)el.classList.remove('open');});
});
</script>
</body></html>