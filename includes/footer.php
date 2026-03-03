<?php
/**
 * Student Marketplace - Footer
 * ปรับปรุง: เพิ่มลิงก์นโยบายความเป็นส่วนตัวและระเบียบการซื้อขาย
 */
?>
</main> <footer style="margin-top: 50px; padding: 40px 0; border-top: 1px solid var(--border-color); background-color: var(--bg-card);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 30px;">
            
            <div style="flex: 1; min-width: 250px;">
                <h3 style="color: var(--primary-color); margin-bottom: 15px;">Student Marketplace</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                    พื้นที่สร้างสรรค์เพื่อการเรียนรู้และสร้างรายได้ระหว่างเรียน <br>
                    ระบบซื้อขายแลกเปลี่ยนสำหรับนักเรียนในวิทยาลัย
                </p>
            </div>
            
            <div style="flex: 1; min-width: 150px;">
                <h4 style="margin-bottom: 20px; color: var(--text-color);">เมนูหลัก</h4>
                <ul style="list-style: none; padding: 0; font-size: 0.9rem; line-height: 2.2;">
                    <li><a href="/student_marketplace/pages/index.php" style="color: var(--text-muted); text-decoration: none;">ค้นหาสินค้า</a></li>
                    <li><a href="/student_marketplace/pages/index.php" style="color: var(--text-muted); text-decoration: none;">สมัครเป็นผู้ขาย</a></li>
                    <li><a href="/student_marketplace/pages/terms.php" style="color: var(--text-muted); text-decoration: none;">ระเบียบการซื้อขาย</a></li>
                    <li><a href="/student_marketplace/pages/privacy.php" style="color: var(--text-muted); text-decoration: none;">นโยบายความเป็นส่วนตัว</a></li>
                </ul>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <h4 style="margin-bottom: 20px; color: var(--text-color);">ติดต่อเรา</h4>
                <ul style="list-style: none; padding: 0; font-size: 0.9rem; color: var(--text-muted); line-height: 2.2;">
                    <li><i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i> ห้องพักครูคอมพิวเตอร์</li>
                    <li><i class="fas fa-building" style="margin-right: 8px;"></i> ตึกกิจกรรม ชั้น 2</li>
                    <li><i class="fas fa-envelope" style="margin-right: 8px;"></i> สนับสนุนโดย ฝ่ายกิจกรรมนักเรียน</li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 40px; padding-top: 25px; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
            &copy; <?php echo date('Y'); ?> <strong>Student Marketplace</strong>. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="/student_marketplace/assets/js/script.js"></script>

</body>
</html>