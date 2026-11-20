<?php
require_once __DIR__ . '/../includes/role.php';
require_role('pic');
$page_title='Kelola Data Peserta';
$user_id=(int)current_user()['id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf_token($_POST['csrf_token']??null)) { set_flash('error','Token keamanan tidak valid.'); redirect('pic/kelola_data_peserta.php'); }
    $pendaftaran_id=filter_input(INPUT_POST,'pendaftaran_id',FILTER_VALIDATE_INT);
    $status=$_POST['status']??'';
    $catatan=trim($_POST['catatan']??'');
    if (!$pendaftaran_id || !in_array($status,['diterima','ditolak'],true) || mb_strlen($catatan)>2000) { set_flash('error','Data tindakan peserta tidak valid.'); redirect('pic/kelola_data_peserta.php'); }
    $stmt=$pdo->prepare("UPDATE pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id SET p.status=:status,p.catatan=:catatan WHERE p.id=:id AND l.pic_id=:pic_id AND l.status='dipublikasikan' AND p.status='menunggu'");
    $stmt->execute([':status'=>$status,':catatan'=>$catatan!==''?$catatan:null,':id'=>$pendaftaran_id,':pic_id'=>$user_id]);
    if ($stmt->rowCount()>0) set_flash('success','Status pendaftaran mahasiswa berhasil diperbarui.');
    else set_flash('error','Pendaftaran tidak ditemukan, lomba belum dipublikasikan, atau sudah diproses.');
    $back_id=filter_input(INPUT_POST,'lomba_id',FILTER_VALIDATE_INT);
    redirect('pic/kelola_data_peserta.php'.($back_id?'?lomba_id='.$back_id:''));
}
$lomba_id=filter_input(INPUT_GET,'lomba_id',FILTER_VALIDATE_INT);
$status_filter=$_GET['status']??'';
$allowed_status=['','menunggu','diterima','ditolak','selesai'];
if (!in_array($status_filter,$allowed_status,true)) $status_filter='';
$sql="SELECT p.id,p.lomba_id,p.tanggal_daftar,p.status,p.catatan,u.nama AS nama_mahasiswa,u.nim,u.email,l.judul AS judul_lomba,l.status AS status_lomba FROM pendaftaran_lomba p JOIN lomba l ON l.id=p.lomba_id JOIN users u ON u.id=p.mahasiswa_id WHERE l.pic_id=:pic_id";
$params=[':pic_id'=>$user_id];
if ($lomba_id) { $sql.=" AND l.id=:lomba_id"; $params[':lomba_id']=$lomba_id; }
if ($status_filter!=='') { $sql.=" AND p.status=:status"; $params[':status']=$status_filter; }
$sql.=" ORDER BY p.tanggal_daftar DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $peserta=$stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt=$pdo->prepare("SELECT id,judul FROM lomba WHERE pic_id=? ORDER BY judul"); $stmt->execute([$user_id]); $daftar_lomba=$stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pic.php';
?>
<section class="page-heading"><div><h1>Kelola Data Peserta</h1><p>Lihat pendaftaran mahasiswa dan proses statusnya. Hanya pendaftar pada lomba yang sudah dipublikasikan yang dapat diproses.</p></div></section>
<section class="card"><form method="GET" class="form-actions">
<div class="form-group"><label for="lomba_id">Filter Lomba</label><select class="form-control" id="lomba_id" name="lomba_id"><option value="">Semua Lomba</option><?php foreach($daftar_lomba as $l): ?><option value="<?= (int)$l['id'] ?>" <?= $lomba_id===(int)$l['id']?'selected':'' ?>><?= e($l['judul']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="status">Status</label><select class="form-control" id="status" name="status"><option value="">Semua Status</option><?php foreach(['menunggu'=>'Menunggu','diterima'=>'Diterima','ditolak'=>'Ditolak','selesai'=>'Selesai'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= $status_filter===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<div class="form-group" style="align-self:end"><button class="btn btn-primary" type="submit">Terapkan</button> <a class="btn btn-outline" href="<?= e(url('pic/kelola_data_peserta.php')) ?>">Reset</a></div>
</form></section>
<section class="card"><div class="table-wrapper"><table><thead><tr><th>No.</th><th>Mahasiswa</th><th>NIM</th><th>Lomba</th><th>Tanggal Daftar</th><th>Status</th><th>Catatan</th><th>Tindakan</th></tr></thead><tbody>
<?php if (!$peserta): ?><tr><td colspan="8" class="empty-state">Belum ada peserta sesuai filter.</td></tr><?php else: foreach($peserta as $i=>$item): ?>
<tr><td><?= $i+1 ?></td><td><?= e($item['nama_mahasiswa']) ?><br><small><?= e($item['email']) ?></small></td><td><?= e($item['nim']?:'-') ?></td><td><?= e($item['judul_lomba']) ?></td><td><?= e($item['tanggal_daftar']) ?></td><td><span class="badge <?= $item['status']==='ditolak'?'badge-danger':($item['status']==='menunggu'?'badge-warning':'badge-success') ?>"><?= e(ucfirst($item['status'])) ?></span></td><td><?= e($item['catatan']?:'-') ?></td><td>
<?php if ($item['status']==='menunggu' && $item['status_lomba']==='dipublikasikan'): ?>
<form method="POST" class="form-actions"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="pendaftaran_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="lomba_id" value="<?= (int)$item['lomba_id'] ?>"><input type="text" name="catatan" class="form-control" maxlength="2000" placeholder="Catatan (opsional)"><button class="btn btn-sm btn-primary" name="status" value="diterima">Terima</button><button class="btn btn-sm btn-danger" name="status" value="ditolak">Tolak</button></form>
<?php else: ?>-<?php endif; ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
