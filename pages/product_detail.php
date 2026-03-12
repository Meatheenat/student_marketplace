<?php
require_once '../includes/functions.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) redirect('index.php');

$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

$stmt = $db->prepare("
SELECT 
p.*,
s.shop_name,
s.contact_line,
s.contact_ig,
s.line_user_id,
s.user_id as owner_id,
u.role as owner_role
FROM products p
JOIN shops s ON p.shop_id = s.id
JOIN users u ON s.user_id = u.id
WHERE p.id=? AND p.is_deleted=0
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

$product_exists = true;
if(!$product){
$product_exists=false;
}

$product_images=[];
$main_image="";

if($product_exists){

$img_stmt=$db->prepare("
SELECT image_path,is_main
FROM product_images
WHERE product_id=?
ORDER BY is_main DESC,id ASC
");
$img_stmt->execute([$product_id]);
$product_images=$img_stmt->fetchAll();

if(count($product_images)===0){
$product_images[]=[
"image_path"=>$product['image_url'] ?? "no-image.png",
"is_main"=>1
];
}

$main_image=$product_images[0]['image_path'];

if(!isset($_SESSION['viewed_products'])){
$_SESSION['viewed_products']=[];
}

if(!in_array($product_id,$_SESSION['viewed_products'])){
$update_views=$db->prepare("UPDATE products SET views=views+1 WHERE id=?");
$update_views->execute([$product_id]);
$_SESSION['viewed_products'][]=$product_id;
}

$rating_summary_stmt=$db->prepare("
SELECT AVG(rating) as avg_rating,
COUNT(*) as total_reviews
FROM reviews
WHERE product_id=? AND is_deleted=0
");
$rating_summary_stmt->execute([$product_id]);
$rating_info=$rating_summary_stmt->fetch();

$avg_p_rating=round($rating_info['avg_rating'] ?? 0,1);
$total_p_reviews=$rating_info['total_reviews'];

$tag_stmt=$db->prepare("
SELECT t.tag_name
FROM tags t
JOIN product_tag_map ptm
ON t.id=ptm.tag_id
WHERE ptm.product_id=?
");
$tag_stmt->execute([$product_id]);
$product_tags=$tag_stmt->fetchAll();

$is_wishlisted=false;
if(isLoggedIn()){
$check_wish=$db->prepare("
SELECT id
FROM wishlist
WHERE user_id=? AND product_id=?
");
$check_wish->execute([$user_id,$product_id]);
$is_wishlisted=$check_wish->fetch()?true:false;
}

}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])){
if(!isLoggedIn()) redirect('../auth/login.php');

if($user_id==$product['owner_id']){
$_SESSION['flash_message']="คุณไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้";
$_SESSION['flash_type']="error";
}else{

$ins_order=$db->prepare("
INSERT INTO orders
(buyer_id,shop_id,product_id)
VALUES (?,?,?)
");

if($ins_order->execute([$user_id,$product['shop_id'],$product_id])){

$notif_msg="🛒 มีคำสั่งซื้อใหม่สำหรับสินค้า {$product['title']} จากคุณ {$_SESSION['fullname']}";

sendNotification(
$product['owner_id'],
'order',
$notif_msg,
"../seller/dashboard.php"
);

if(!empty($product['line_user_id'])){

$msg="🛒 มีคำสั่งซื้อใหม่!\n";
$msg.="สินค้า: ".$product['title']."\n";
$msg.="จากคุณ: ".$_SESSION['fullname']."\n";
$msg.="กรุณาตรวจสอบใน Dashboard";

sendLineMessagingAPI(
$product['line_user_id'],
$msg
);

}

$_SESSION['flash_message']="ส่งคำสั่งซื้อสำเร็จ";
$_SESSION['flash_type']="success";

}

}

redirect("product_detail.php?id=$product_id");
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review'])){

$rating=$_POST['rating'];
$comment=trim($_POST['comment']);

$spam_check=canUserReview($user_id,$product_id);

if(!$spam_check['status']){

$_SESSION['flash_message']=$spam_check['message'];
$_SESSION['flash_type']="danger";

}else{

$ins=$db->prepare("
INSERT INTO reviews
(product_id,user_id,rating,comment)
VALUES (?,?,?,?)
");

if($ins->execute([$product_id,$user_id,$rating,$comment])){

$notif_msg="⭐ มีรีวิวใหม่ ({$rating} ดาว) ในสินค้า {$product['title']}";

sendNotification(
$product['owner_id'],
'review',
$notif_msg,
"product_detail.php?id=$product_id"
);

if(!empty($product['line_user_id'])){

$message="📢 มีรีวิวใหม่ถึงสินค้าของคุณ!\n";
$message.="📦 สินค้า: ".$product['title']."\n";
$message.="⭐️ คะแนน: ".$rating." ดาว\n";
$message.="💬 ความเห็น: ".$comment."\n";
$message.="🔗 ดูรีวิว: ".BASE_URL."/pages/product_detail.php?id=".$product_id;

sendLineMessagingAPI(
$product['line_user_id'],
$message
);

}

$_SESSION['flash_message']="บันทึกรีวิวสำเร็จ";
$_SESSION['flash_type']="success";

}

}

redirect("product_detail.php?id=$product_id");

}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_review_submit'])){

$r_id=(int)$_POST['review_id'];
$r_rating=(int)$_POST['rating'];
$r_comment=trim($_POST['comment']);

$stmt=$db->prepare("
UPDATE reviews
SET rating=?,comment=?
WHERE id=? AND user_id=?
");

if($stmt->execute([$r_rating,$r_comment,$r_id,$user_id])){

$_SESSION['flash_message']="แก้ไขรีวิวเรียบร้อย";
$_SESSION['flash_type']="success";

}

redirect("product_detail.php?id=$product_id");

}

if(isset($_GET['action']) && $_GET['action']==='delete_my_review'){

$del_id=(int)$_GET['rev_id'];

$stmt=$db->prepare("
UPDATE reviews
SET is_deleted=1
WHERE id=? AND user_id=?
");

if($stmt->execute([$del_id,$user_id])){

$_SESSION['flash_message']="ลบรีวิวแล้ว";
$_SESSION['flash_type']="success";

}

redirect("product_detail.php?id=$product_id");

}

$all_reviews=[];

if($product_exists){

$rev_stmt=$db->prepare("
SELECT 
r.*,
u.fullname,
u.profile_img,
u.id as author_id
FROM reviews r
JOIN users u
ON r.user_id=u.id
WHERE r.product_id=? AND r.is_deleted=0
ORDER BY r.created_at DESC
");

$rev_stmt->execute([$product_id]);
$all_reviews=$rev_stmt->fetchAll();

}

require_once '../includes/header.php';
?>

<style>

:root{
--bg:#f8fafc;
--card:#ffffff;
--text:#0f172a;
--border:#cbd5e1;
--primary:#6366f1;
--danger:#ef4444;
--success:#10b981;
--warning:#f59e0b;
}

body{
background:var(--bg);
color:var(--text);
}

.page-wrap{
max-width:1300px;
margin:auto;
padding:40px 25px;
animation:fadePage .7s ease;
}

@keyframes fadePage{
from{
opacity:0;
transform:translateY(30px);
}
to{
opacity:1;
transform:translateY(0);
}
}

.product-grid{
display:grid;
grid-template-columns:1.2fr 0.8fr;
gap:50px;
background:var(--card);
border:2px solid var(--border);
border-radius:30px;
padding:40px;
margin-bottom:60px;
}

.gallery{
display:flex;
flex-direction:column;
gap:15px;
}

.main-image{
width:100%;
height:500px;
border-radius:22px;
overflow:hidden;
background:#000;
position:relative;
}

.main-image img{
width:100%;
height:100%;
object-fit:cover;
transition:transform .6s cubic-bezier(.2,.8,.2,1);
}

.main-image:hover img{
transform:scale(1.05);
}

.thumb-row{
display:flex;
gap:12px;
overflow:auto;
}

.thumb{
width:85px;
height:85px;
border-radius:14px;
overflow:hidden;
cursor:pointer;
border:3px solid transparent;
opacity:.6;
transition:.3s;
}

.thumb.active{
border-color:var(--primary);
opacity:1;
}

.thumb img{
width:100%;
height:100%;
object-fit:cover;
}

.product-title{
font-size:2.6rem;
font-weight:900;
line-height:1.1;
margin-bottom:10px;
}

.price{
font-size:2.3rem;
font-weight:900;
color:var(--primary);
margin:25px 0;
}

.tags{
display:flex;
flex-wrap:wrap;
gap:8px;
margin-bottom:25px;
}

.tag{
padding:6px 14px;
border-radius:10px;
border:1px solid var(--border);
font-size:.8rem;
font-weight:700;
}

.buy-btn{
display:flex;
align-items:center;
justify-content:center;
gap:10px;
background:var(--primary);
color:#fff;
padding:20px;
border-radius:16px;
font-weight:800;
font-size:1.1rem;
border:none;
cursor:pointer;
transition:.3s;
}

.buy-btn:hover{
transform:translateY(-4px);
box-shadow:0 12px 20px rgba(0,0,0,.15);
}

.shop-box{
margin-top:40px;
display:flex;
justify-content:space-between;
align-items:center;
border:2px solid var(--border);
border-radius:20px;
padding:20px;
}

.review-section{
margin-top:70px;
}

.review-card{
border:2px solid var(--border);
border-radius:20px;
padding:25px;
margin-bottom:18px;
background:var(--card);
}

.no-product{
text-align:center;
padding:120px 20px;
border:3px dashed var(--border);
border-radius:30px;
}

</style>

<div class="page-wrap">

<?php echo displayFlashMessage(); ?>

<?php if(!$product_exists): ?>

<div class="no-product">

<h2>ไม่พบสินค้า</h2>
<p>สินค้านี้อาจถูกลบออกจากระบบ</p>

<a href="../index.php" class="buy-btn" style="margin:auto;margin-top:20px;width:250px;text-decoration:none;">
กลับหน้าหลัก
</a>

</div>

<?php else: ?>

<div class="product-grid">

<div class="gallery">

<div class="main-image">
<img id="mainImage" src="../assets/images/products/<?=e($main_image)?>">
</div>

<div class="thumb-row">

<?php foreach($product_images as $i=>$img): ?>

<div class="thumb <?= $i==0?'active':'' ?>"
onclick="changeImage('../assets/images/products/<?=e($img['image_path'])?>',this)">
<img src="../assets/images/products/<?=e($img['image_path'])?>">
</div>

<?php endforeach; ?>

</div>

</div>

<div>

<div class="product-title">
<?=e($product['title'])?>
</div>

<div class="tags">

<?php foreach($product_tags as $tag): ?>

<div class="tag">
#<?=e($tag['tag_name'])?>
</div>

<?php endforeach; ?>

</div>

<div class="price">
฿<?=number_format($product['price'],2)?>
</div>

<p style="line-height:1.8;font-size:1.05rem;margin-bottom:30px;">
<?=nl2br(e($product['description']))?>
</p>

<?php if($user_id && $user_id!=$product['owner_id']): ?>

<a href="checkout.php?id=<?=$product_id?>" class="buy-btn" style="text-decoration:none;">
ซื้อสินค้า
</a>

<?php elseif(!$user_id): ?>

<a href="../auth/login.php" class="buy-btn" style="text-decoration:none;">
เข้าสู่ระบบก่อนซื้อ
</a>

<?php endif; ?>

<div class="shop-box">

<div>

<div style="font-weight:800;">
<?=e($product['shop_name'])?>
</div>

<div style="font-size:.8rem;color:#777;">
<?=getUserBadge($product['owner_role'])?>
</div>

</div>

<div style="display:flex;gap:12px;font-size:1.6rem;">

<?php if(!empty($product['contact_line'])): ?>

<a href="https://line.me/ti/p/~<?=e($product['contact_line'])?>" target="_blank">
<i class="fab fa-line"></i>
</a>

<?php endif; ?>

<?php if(!empty($product['contact_ig'])): ?>

<a href="https://instagram.com/<?=e($product['contact_ig'])?>" target="_blank">
<i class="fab fa-instagram"></i>
</a>

<?php endif; ?>

</div>

</div>

</div>

</div>
<div class="review-section">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:35px;flex-wrap:wrap;gap:10px;">

<h2 style="font-size:2rem;font-weight:900;">
Customer Reviews (<?=count($all_reviews)?>)
</h2>

<div style="background:var(--warning);color:#000;padding:8px 18px;border-radius:40px;font-weight:900;">
★ <?=$avg_p_rating?>
</div>

</div>

<?php if(isLoggedIn()): ?>

<?php 
$spam_check_ui = canUserReview($user_id,$product_id);
if($spam_check_ui['status']):
?>

<div class="review-card" style="margin-bottom:40px;">

<h3 style="font-weight:800;margin-bottom:20px;">
แชร์ประสบการณ์ของคุณ
</h3>

<form method="POST">

<div style="margin-bottom:20px;">

<label style="font-weight:800;display:block;margin-bottom:10px;">
Rating
</label>

<div class="star-box">

<?php for($i=5;$i>=1;$i--): ?>

<input type="radio" id="star<?=$i?>" name="rating" value="<?=$i?>" required>

<label for="star<?=$i?>">★</label>

<?php endfor; ?>

</div>

</div>

<textarea
name="comment"
required
placeholder="ความคิดเห็นของคุณ..."
style="
width:100%;
min-height:140px;
border-radius:14px;
border:2px solid var(--border);
padding:16px;
font-weight:600;
margin-bottom:15px;
"></textarea>

<button type="submit" name="submit_review" class="buy-btn" style="width:220px;">
POST REVIEW
</button>

</form>

</div>

<?php else: ?>

<div style="
text-align:center;
padding:30px;
background:#eef2ff;
border-radius:18px;
border:2px solid var(--border);
margin-bottom:40px;
">

<b><?=$spam_check_ui['message']?></b>

</div>

<?php endif; ?>

<?php endif; ?>

<div>

<?php if(count($all_reviews)>0): ?>

<?php foreach($all_reviews as $rev): ?>

<?php
$avatar = !empty($rev['profile_img'])
? "../assets/images/profiles/".$rev['profile_img']
: "../assets/images/profiles/default_profile.png";
?>

<div class="review-card">

<div style="display:flex;gap:18px;">

<a href="view_profile.php?id=<?=$rev['author_id']?>">

<img
src="<?=$avatar?>"
style="
width:60px;
height:60px;
border-radius:16px;
object-fit:cover;
border:2px solid var(--border);
">

</a>

<div style="flex:1;">

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">

<div>

<b style="font-size:1.1rem;">
<?=$rev['fullname']?>
</b>

<div style="color:#f59e0b;font-size:.9rem;">

<?php for($s=0;$s<$rev['rating'];$s++) echo "★"; ?>

</div>

</div>

<div style="font-size:.8rem;color:#888;">

<?=date("d M Y H:i",strtotime($rev['created_at']))?>

</div>

</div>

<p style="margin-top:10px;line-height:1.7;">
<?=nl2br(e($rev['comment']))?>
</p>

<?php if($user_id==$rev['author_id']): ?>

<div style="margin-top:10px;display:flex;gap:15px;">

<button
onclick="openEditReview(
<?=$rev['id']?>,
<?=$rev['rating']?>,
'<?=e(str_replace(["\r","\n"],' ',$rev['comment']))?>'
)"
style="background:none;border:none;color:var(--primary);font-weight:800;cursor:pointer;">
แก้ไข
</button>

<a
href="product_detail.php?id=<?=$product_id?>&action=delete_my_review&rev_id=<?=$rev['id']?>"
onclick="return confirm('ลบรีวิวนี้?')"
style="color:var(--danger);font-weight:800;text-decoration:none;">
ลบ
</a>

</div>

<?php endif; ?>

</div>

</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="no-product">

<h3>No reviews yet</h3>

</div>

<?php endif; ?>

</div>

</div>

<div id="editReviewModal" class="modal-ui">

<div class="modal-card">

<h3 style="font-weight:900;margin-bottom:15px;">
แก้ไขรีวิว
</h3>

<form method="POST">

<input type="hidden" name="review_id" id="edit_rev_id">

<select name="rating" id="edit_rev_rating" style="
width:100%;
padding:10px;
border-radius:10px;
border:2px solid var(--border);
margin-bottom:12px;
">

<option value="5">5 ดาว</option>
<option value="4">4 ดาว</option>
<option value="3">3 ดาว</option>
<option value="2">2 ดาว</option>
<option value="1">1 ดาว</option>

</select>

<textarea
name="comment"
id="edit_rev_comment"
required
style="
width:100%;
min-height:120px;
border-radius:12px;
border:2px solid var(--border);
padding:12px;
"></textarea>

<div style="display:flex;gap:10px;margin-top:15px;">

<button type="button"
onclick="closeEditReview()"
class="modal-btn">
Cancel
</button>

<button type="submit"
name="edit_review_submit"
class="buy-btn"
style="flex:1;">
Save
</button>

</div>

</form>

</div>

</div>

<style>

.star-box{
display:flex;
flex-direction:row-reverse;
justify-content:flex-end;
gap:6px;
}

.star-box input{
display:none;
}

.star-box label{
font-size:28px;
color:#cbd5e1;
cursor:pointer;
transition:.2s;
}

.star-box input:checked ~ label,
.star-box label:hover,
.star-box label:hover ~ label{
color:#f59e0b;
}

.modal-ui{
position:fixed;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
display:none;
align-items:center;
justify-content:center;
z-index:9999;
}

.modal-card{
background:#fff;
padding:30px;
border-radius:20px;
width:90%;
max-width:420px;
}

.modal-btn{
flex:1;
padding:12px;
border-radius:10px;
border:2px solid var(--border);
background:#fff;
cursor:pointer;
}

</style>

<script>

function changeImage(url,el){

let main=document.getElementById("mainImage");

main.style.opacity="0";

setTimeout(()=>{
main.src=url;
main.style.opacity="1";
},150);

document.querySelectorAll(".thumb").forEach(t=>{
t.classList.remove("active");
});

el.classList.add("active");

}

function openEditReview(id,rating,comment){

document.getElementById("edit_rev_id").value=id;
document.getElementById("edit_rev_rating").value=rating;
document.getElementById("edit_rev_comment").value=comment;

document.getElementById("editReviewModal").style.display="flex";

}

function closeEditReview(){

document.getElementById("editReviewModal").style.display="none";

}

window.onclick=function(e){

let modal=document.getElementById("editReviewModal");

if(e.target==modal){
closeEditReview();
}

};

</script>

<?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
