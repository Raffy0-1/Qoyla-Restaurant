<?php
// ============================================================
// QOYLA — ADMIN PANEL (Main Hub)
// File: admin/index.php
// All sections: Dashboard, Customers, Inventory, Workers,
//               Menu Items, Deals, Messages
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/admin_check.php';

$pageTitle  = 'Admin Panel | Qoyla';
$activePage = $_GET['page'] ?? 'dashboard'; // ?page=customers etc.

// ============================================================
// POST ACTION HANDLER — runs before any HTML output
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';

    // --- ADD CUSTOMER ---
    if ($action === 'add_customer') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cnic  = trim($_POST['cnic']  ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Check duplicate
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            setFlash('error', "Phone number '{$phone}' is already registered.");
            header("Location: ' . BASE_URL . 'admin/index.php?page=customers"); exit;
        }

        $pass  = password_hash('qoyla123', PASSWORD_BCRYPT); // default password
        $maxSr = $pdo->query("SELECT COALESCE(MAX(sr_no),0)+1 FROM customers")->fetchColumn();
        $pdo->prepare("INSERT INTO customers (sr_no,name,phone,cnic,email,password) VALUES(?,?,?,?,?,?)")
            ->execute([$maxSr, $name, $phone, $cnic ?: null, $email ?: null, $pass]);
        setFlash('success', "Customer '{$name}' added. Default password: qoyla123");
        header("Location: ' . BASE_URL . 'admin/index.php?page=customers"); exit;
    }

    // --- ADD POINTS to customer ---
    if ($action === 'add_points') {
        $customerId = (int)$_POST['customer_id'];
        $points     = (int)$_POST['points'];
        
        if ($points < 0) {
            setFlash('error', 'Points must be a positive number.');
            header("Location: ' . BASE_URL . 'admin/index.php?page=customers"); exit;
        }
        $type       = $_POST['type'] ?? 'earned';    // earned / adjusted / redeemed
        $desc       = trim($_POST['description'] ?? 'Admin adjustment');

        if ($type === 'redeemed') {
            $pdo->prepare("UPDATE customers SET total_points = GREATEST(0, total_points - ?) WHERE id=?")
                ->execute([$points, $customerId]);
        } else {
            $pdo->prepare("UPDATE customers SET total_points = total_points + ? WHERE id=?")
                ->execute([$points, $customerId]);
        }
        $pdo->prepare("INSERT INTO points_history (customer_id,points,type,description,date,added_by) VALUES(?,?,?,?,CURDATE(),?)")
            ->execute([$customerId, $points, $type, $desc, $_SESSION['admin_id']]);
        
        // Log explicitly in points_log
        $signedPoints = $type === 'redeemed' ? -$points : $points;
        $pdo->prepare("INSERT INTO points_log (customer_id,change_amount,reason,expires_at) VALUES(?,?,?,?)")
            ->execute([$customerId, $signedPoints, $desc . ' (by Admin)', $type === 'redeemed' ? null : date('Y-m-d', strtotime('+6 months'))]);

        // Also record as a visit if type is 'earned'
        if ($type === 'earned') {
            $pdo->prepare("INSERT INTO visits (customer_id,visit_date,points_earned,notes,added_by) VALUES(?,CURDATE(),?,?,?)")
                ->execute([$customerId, $points, $desc, $_SESSION['admin_id']]);
        }
        setFlash('success', "Points updated for customer.");
        header("Location: ' . BASE_URL . 'admin/index.php?page=customers"); exit;
    }

    // --- DELETE CUSTOMER ---
    if ($action === 'delete_customer') {
        $id = (int)$_POST['customer_id'];
        $pdo->prepare("DELETE FROM points_history WHERE customer_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM visits WHERE customer_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
        setFlash('success', 'Customer deleted.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=customers"); exit;
    }

    // --- ADD INVENTORY ITEM ---
    if ($action === 'add_inventory') {
        $d = $_POST;
        if ((float)$d['quantity'] < 0 || (float)($d['par_level']??0) < 0) {
            setFlash('error', 'Values cannot be negative.');
            header("Location: ' . BASE_URL . 'admin/index.php?page=inventory"); exit;
        }
        $pdo->prepare("INSERT INTO inventory (category,product_name,quantity,unit,par_level,reorder_level,description,supplier) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$d['category'],$d['product_name'],(float)$d['quantity'],$d['unit'],(float)($d['par_level']??0),(float)($d['reorder_level']??0),$d['description']??null,$d['supplier']??null]);
        setFlash('success', 'Inventory item added.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=inventory"); exit;
    }

    // --- UPDATE INVENTORY ITEM ---
    if ($action === 'update_inventory') {
        $d = $_POST;
        if ((float)$d['quantity'] < 0 || (float)($d['par_level']??0) < 0) {
            setFlash('error', 'Values cannot be negative.');
            header("Location: ' . BASE_URL . 'admin/index.php?page=inventory"); exit;
        }
        [$status] = inventoryStatus((float)$d['quantity'],(float)($d['par_level']??0),(float)($d['reorder_level']??0));
        $pdo->prepare("UPDATE inventory SET product_name=?,quantity=?,unit=?,par_level=?,reorder_level=?,status=?,description=?,supplier=? WHERE id=?")
            ->execute([$d['product_name'],(float)$d['quantity'],$d['unit'],(float)($d['par_level']??0),(float)($d['reorder_level']??0),$status,$d['description']??null,$d['supplier']??null,(int)$d['item_id']]);
        setFlash('success', 'Item updated.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=inventory"); exit;
    }

    // --- DELETE INVENTORY ---
    if ($action === 'delete_inventory') {
        $pdo->prepare("DELETE FROM inventory WHERE id=?")->execute([(int)$_POST['item_id']]);
        setFlash('success', 'Item deleted.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=inventory"); exit;
    }

    // --- ADD WORKER ---
    if ($action === 'add_worker') {
        $d = $_POST;
        $pdo->prepare("INSERT INTO workers (name,role,cnic,phone1,phone2,bank_account,referral,emergency_contact,description,joined_date,caution_reset_date) VALUES(?,?,?,?,?,?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL 3 MONTH))")
            ->execute([$d['name'],$d['role']??null,$d['cnic']??null,$d['phone1']??null,$d['phone2']??null,$d['bank_account']??null,$d['referral']??null,$d['emergency_contact']??null,$d['description']??null,$d['joined_date']??date('Y-m-d')]);
        setFlash('success', 'Worker added.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=workers"); exit;
    }

    // --- ADD WORKER COMPLAINT ---
    if ($action === 'add_complaint') {
        $workerId  = (int)$_POST['worker_id'];
        $complaint = trim($_POST['complaint'] ?? '');
        $filedBy   = trim($_POST['filed_by']  ?? 'Admin');
        $pdo->prepare("INSERT INTO worker_complaints (worker_id,complaint,filed_date,filed_by) VALUES(?,?,CURDATE(),?)")
            ->execute([$workerId, $complaint, $filedBy]);
        // Increment caution level (max 3)
        $pdo->prepare("UPDATE workers SET caution_level = LEAST(caution_level+1,3) WHERE id=?")
            ->execute([$workerId]);
        // Check and reset caution if past reset date
        $pdo->prepare("UPDATE workers SET caution_level=0, caution_reset_date=DATE_ADD(CURDATE(),INTERVAL 3 MONTH) WHERE id=? AND caution_reset_date < CURDATE()")
            ->execute([$workerId]);
        setFlash('success', 'Complaint filed and caution level updated.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=workers"); exit;
    }

    // --- ADD MENU ITEM ---
    if ($action === 'add_menu_item') {
        $d = $_POST;
        $imagePath = null;
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
            
            if (array_key_exists($mime, $allowed) && $imageInfo !== false && $_FILES['image']['size'] <= 5*1024*1024) {
                $ext  = $allowed[$mime];
                $name = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/menu/' . $name;
                if (!is_dir(__DIR__ . '/../uploads/menu')) mkdir(__DIR__ . '/../uploads/menu', 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = '' . BASE_URL . 'uploads/menu/' . $name;
                }
            }
        }
        $pdo->prepare("INSERT INTO menu_items (category,name,description,price,is_available,is_featured,image_path) VALUES(?,?,?,?,?,?,?)")
            ->execute([$d['category'],$d['name'],$d['description']??null,(float)$d['price'],isset($d['is_available'])?1:0,isset($d['is_featured'])?1:0,$imagePath]);
        setFlash('success', 'Menu item added.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=menu-mgmt"); exit;
    }

    // --- TOGGLE MENU ITEM AVAILABILITY ---
    if ($action === 'toggle_menu') {
        $id  = (int)$_POST['item_id'];
        $val = (int)$_POST['current'];
        $pdo->prepare("UPDATE menu_items SET is_available=? WHERE id=?")->execute([$val ? 0 : 1, $id]);
        header("Location: ' . BASE_URL . 'admin/index.php?page=menu-mgmt"); exit;
    }

    // --- DELETE MENU ITEM ---
    if ($action === 'delete_menu') {
        $pdo->prepare("DELETE FROM menu_items WHERE id=?")->execute([(int)$_POST['item_id']]);
        setFlash('success', 'Menu item deleted.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=menu-mgmt"); exit;
    }

    // --- EDIT MENU ITEM ---
    if ($action === 'edit_menu_item') {
        $d = $_POST;
        $itemId = (int)$d['item_id'];
        // Fetch existing image path
        $existing = $pdo->prepare("SELECT image_path FROM menu_items WHERE id=?");
        $existing->execute([$itemId]);
        $imagePath = $existing->fetchColumn();

        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $allowed   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
            if (array_key_exists($mime, $allowed) && $imageInfo !== false && $_FILES['image']['size'] <= 5*1024*1024) {
                $ext  = $allowed[$mime];
                $name = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/menu/' . $name;
                if (!is_dir(__DIR__ . '/../uploads/menu')) mkdir(__DIR__ . '/../uploads/menu', 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = '' . BASE_URL . 'uploads/menu/' . $name;
                }
            }
        }
        $pdo->prepare("UPDATE menu_items SET category=?,name=?,description=?,price=?,is_available=?,is_featured=?,image_path=? WHERE id=?")
            ->execute([$d['category'],$d['name'],$d['description']??null,(float)$d['price'],isset($d['is_available'])?1:0,isset($d['is_featured'])?1:0,$imagePath,$itemId]);
        setFlash('success', 'Menu item updated.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=menu-mgmt"); exit;
    }

    // --- ADD DEAL ---
    if ($action === 'add_deal') {
        $d = $_POST;
        $imagePath = null;
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
            
            if (array_key_exists($mime, $allowed) && $imageInfo !== false && $_FILES['image']['size'] <= 5*1024*1024) {
                $ext  = $allowed[$mime];
                $name = 'deal_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/deals/' . $name;
                if (!is_dir(__DIR__ . '/../uploads/deals')) mkdir(__DIR__ . '/../uploads/deals', 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = '' . BASE_URL . 'uploads/deals/' . $name;
                }
            }
        }
        $pdo->prepare("INSERT INTO deals (title,description,deal_type,discount_percent,points_multiplier,is_active,start_date,end_date,image_path) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$d['title'],$d['description']??null,$d['deal_type'],(int)($d['discount_percent']??0),(float)($d['points_multiplier']??1),isset($d['is_active'])?1:0,$d['start_date']??null,$d['end_date']??null,$imagePath]);
        setFlash('success', 'Deal added.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=deals-mgmt"); exit;
    }

    // --- TOGGLE DEAL ---
    if ($action === 'toggle_deal') {
        $id  = (int)$_POST['deal_id'];
        $val = (int)$_POST['current'];
        $pdo->prepare("UPDATE deals SET is_active=? WHERE id=?")->execute([$val ? 0 : 1, $id]);
        header("Location: ' . BASE_URL . 'admin/index.php?page=deals-mgmt"); exit;
    }

    // --- DELETE DEAL ---
    if ($action === 'delete_deal') {
        $pdo->prepare("DELETE FROM deals WHERE id=?")->execute([(int)$_POST['deal_id']]);
        setFlash('success', 'Deal deleted.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=deals-mgmt"); exit;
    }

    // --- EDIT DEAL ---
    if ($action === 'edit_deal') {
        $d = $_POST;
        $dealId = (int)$d['deal_id'];
        $existing = $pdo->prepare("SELECT image_path FROM deals WHERE id=?");
        $existing->execute([$dealId]);
        $imagePath = $existing->fetchColumn();

        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $allowed   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $imageInfo = @getimagesize($_FILES['image']['tmp_name']);
            if (array_key_exists($mime, $allowed) && $imageInfo !== false && $_FILES['image']['size'] <= 5*1024*1024) {
                $ext  = $allowed[$mime];
                $name = 'deal_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/deals/' . $name;
                if (!is_dir(__DIR__ . '/../uploads/deals')) mkdir(__DIR__ . '/../uploads/deals', 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = '' . BASE_URL . 'uploads/deals/' . $name;
                }
            }
        }
        $pdo->prepare("UPDATE deals SET title=?,description=?,deal_type=?,discount_percent=?,points_multiplier=?,is_active=?,start_date=?,end_date=?,image_path=? WHERE id=?")
            ->execute([$d['title'],$d['description']??null,$d['deal_type'],(int)($d['discount_percent']??0),(float)($d['points_multiplier']??1),isset($d['is_active'])?1:0,$d['start_date']??null,$d['end_date']??null,$imagePath,$dealId]);
        setFlash('success', 'Deal updated.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=deals-mgmt"); exit;
    }

    // --- MARK MESSAGE AS READ ---
    if ($action === 'mark_read') {
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_POST['msg_id']]);
        header("Location: ' . BASE_URL . 'admin/index.php?page=messages"); exit;
    }

    // --- EDIT WORKER ---
    if ($action === 'edit_worker') {
        $d = $_POST;
        $pdo->prepare("UPDATE workers SET name=?, role=?, phone1=?, phone2=? WHERE id=?")
            ->execute([$d['name'], $d['role']??null, $d['phone1']??null, $d['phone2']??null, (int)$d['worker_id']]);
        setFlash('success', 'Worker details updated.');
        header("Location: ' . BASE_URL . 'admin/index.php?page=workers"); exit;
    }

    // --- SOFT DELETE WORKER ---
    if ($action === 'delete_worker') {
        $id = (int)$_POST['worker_id'];
        $pdo->prepare("UPDATE workers SET is_active=0 WHERE id=?")->execute([$id]);
        setFlash('success', 'Worker has been deactivated (soft delete).');
        header("Location: ' . BASE_URL . 'admin/index.php?page=workers"); exit;
    }

    // --- REPLY MESSAGE ---
    if ($action === 'reply_message') {
        $msgId = (int)$_POST['message_id'];
        $reply = trim($_POST['reply_text'] ?? '');
        
        $msgRow = $pdo->prepare("SELECT * FROM contact_messages WHERE id=?");
        $msgRow->execute([$msgId]);
        $msg = $msgRow->fetch();

        if ($msg && !empty($reply)) {
            $pdo->prepare("INSERT INTO message_replies (message_id, reply_text) VALUES (?, ?)")
                ->execute([$msgId, $reply]);
            
            // Send email to customer instantly
            if (!empty($msg['email'])) {
                $bodyHTML = "
                    <h2 style='color:#E8500A'>Qoyla Management Reply</h2>
                    <p>Dear " . e($msg['name']) . ",</p>
                    <p>Regarding your recent message:</p>
                    <blockquote style='border-left:3px solid #666;padding-left:10px;color:#999;'><i>" . nl2br(e($msg['message'])) . "</i></blockquote>
                    <p><strong>Reply from our team:</strong></p>
                    <p style='font-size:16px;'>" . nl2br(e($reply)) . "</p>
                    <p>Best regards,<br>Qoyla Management</p>
                ";
                sendQoylaEmail($msg['email'], 'Qoyla: Reply to your message', $bodyHTML);
            }

            // Also mark as read
            $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$msgId]);

            setFlash('success', 'Reply sent and saved successfully.');
        } else {
            setFlash('error', 'Message not found or reply is empty.');
        }
        header("Location: ' . BASE_URL . 'admin/index.php?page=messages"); exit;
    }
}

// ============================================================
// FETCH DATA FOR CURRENT PAGE
// ============================================================
$data = [];

if ($activePage === 'dashboard') {
    $data['customer_count'] = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $data['total_points']   = $pdo->query("SELECT COALESCE(SUM(total_points),0) FROM customers")->fetchColumn();
    $data['worker_count']   = $pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn();
    $data['unread_msgs']    = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
    $data['low_stock']      = $pdo->query("SELECT * FROM inventory WHERE status != 'in_stock' ORDER BY status DESC LIMIT 5")->fetchAll();
}
if ($activePage === 'customers') {
    $data['customers'] = $pdo->query("SELECT * FROM customers ORDER BY sr_no ASC")->fetchAll();
}
if ($activePage === 'inventory') {
    foreach (['meats','dairy','grocery','mandi'] as $cat) {
        $data['inv'][$cat] = $pdo->prepare("SELECT * FROM inventory WHERE category=? ORDER BY product_name");
        $data['inv'][$cat]->execute([$cat]);
        $data['inv'][$cat] = $data['inv'][$cat]->fetchAll();
    }
}
if ($activePage === 'workers') {
    $data['workers'] = $pdo->query("SELECT * FROM workers WHERE is_active = 1 ORDER BY name")->fetchAll();
}
if ($activePage === 'menu-mgmt') {
    $data['menu_items'] = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();
}
if ($activePage === 'deals-mgmt') {
    $data['deals'] = $pdo->query("SELECT * FROM deals ORDER BY is_active DESC, id DESC")->fetchAll();
}
if ($activePage === 'messages') {
    $data['messages'] = $pdo->query("
        SELECT cm.*, mr.reply_text, mr.replied_at 
        FROM contact_messages cm 
        LEFT JOIN message_replies mr ON cm.id = mr.message_id 
        ORDER BY cm.is_read ASC, cm.submitted_at DESC
    ")->fetchAll();
}
if ($activePage === 'customers') {
    $data['points_logs'] = $pdo->query("SELECT * FROM points_log ORDER BY created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body style="background:#F0EBE4;">

<div class="admin-wrap">

<!-- ============================================================
     SIDEBAR
     ============================================================ -->
<aside class="admin-sidebar">
  <div class="admin-sidebar-brand" style="position:relative;">
    <div class="brand-name">QOYLA</div>
    <div class="brand-sub">Admin Panel</div>
    <button class="admin-hamburger" onclick="document.getElementById('adminSidebar').classList.remove('open')" style="color:white;position:absolute;right:1.5rem;top:1.5rem;background:none;border:none;font-size:1.5rem;cursor:pointer;"><i class="fas fa-times"></i></button>
  </div>
  <nav class="admin-nav">
    <div class="admin-nav-section-label">Overview</div>
    <a href="?page=dashboard"  class="admin-nav-link <?= $activePage==='dashboard'  ?'active':'' ?>"><i class="fas fa-home"></i> Dashboard</a>

    <div class="admin-nav-section-label" style="margin-top:1rem;">Manage</div>
    <a href="?page=customers"  class="admin-nav-link <?= $activePage==='customers'  ?'active':'' ?>"><i class="fas fa-users"></i> Customers</a>
    <a href="?page=inventory"  class="admin-nav-link <?= $activePage==='inventory'  ?'active':'' ?>"><i class="fas fa-boxes"></i> Inventory</a>
    <a href="?page=workers"    class="admin-nav-link <?= $activePage==='workers'    ?'active':'' ?>"><i class="fas fa-hard-hat"></i> Workers</a>
    <a href="?page=menu-mgmt" class="admin-nav-link <?= $activePage==='menu-mgmt' ?'active':'' ?>"><i class="fas fa-utensils"></i> Menu Items</a>
    <a href="?page=deals-mgmt"class="admin-nav-link <?= $activePage==='deals-mgmt'?'active':'' ?>"><i class="fas fa-tags"></i> Deals</a>

    <div class="admin-nav-section-label" style="margin-top:1rem;">Incoming</div>
    <a href="?page=messages" class="admin-nav-link <?= $activePage==='messages'?'active':'' ?>">
      <i class="fas fa-envelope"></i> Messages
      <?php if(($data['unread_msgs']??0)>0): ?>
        <span style="background:var(--flame-orange);color:white;font-size:0.65rem;padding:0.15rem 0.45rem;border-radius:50px;margin-left:auto;">
          <?= $data['unread_msgs'] ?>
        </span>
      <?php endif; ?>
    </a>
  </nav>
  <div class="admin-sidebar-footer">
    <div style="font-size:0.8rem;color:rgba(255,255,255,0.35);padding:0 0.75rem;margin-bottom:0.5rem;">
      Logged in as <strong style="color:rgba(255,255,255,0.6);"><?= e($_SESSION['admin_name']) ?></strong>
    </div>
    <a href="<?= BASE_URL ?>auth/logout.php" class="admin-nav-link" style="color:#EF4444;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<main class="admin-main">
  <?= getFlash() ?>

<!-- ===== DASHBOARD ===== -->
<?php if ($activePage === 'dashboard'): ?>
  <div class="admin-topbar">
    <div>
      <h1>Dashboard</h1>
      <p style="font-size:0.85rem;color:var(--text-muted);margin-top:2px;"><?= date('l, d F Y') ?></p>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <div style="text-align:right;">
        <div style="font-weight:700;font-size:0.92rem;"><?= e($_SESSION['admin_name']) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted);"><?= e($_SESSION['admin_role']) ?></div>
      </div>
      <div style="width:40px;height:40px;border-radius:50%;background:var(--flame-orange);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-family:'Cinzel',serif;">
        <?= strtoupper(substr($_SESSION['admin_name'],0,1)) ?>
      </div>
    </div>
  </div>
  <div class="admin-stat-grid">
    <div class="admin-stat-card">
      <div class="admin-stat-label">Total Customers</div>
      <div class="admin-stat-num"><?= number_format($data['customer_count']) ?></div>
      <div class="admin-stat-icon"><i class="fas fa-users"></i></div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-label">Total Points Issued</div>
      <div class="admin-stat-num"><?= number_format($data['total_points']) ?></div>
      <div class="admin-stat-icon"><i class="fas fa-fire"></i></div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-label">Active Workers</div>
      <div class="admin-stat-num"><?= $data['worker_count'] ?></div>
      <div class="admin-stat-icon"><i class="fas fa-hard-hat"></i></div>
    </div>
    <div class="admin-stat-card">
      <div class="admin-stat-label">Unread Messages</div>
      <div class="admin-stat-num"><?= $data['unread_msgs'] ?></div>
      <div class="admin-stat-icon"><i class="fas fa-envelope"></i></div>
    </div>
  </div>
  <?php if (!empty($data['low_stock'])): ?>
  <div style="background:white;border-radius:var(--radius-md);padding:1.5rem;box-shadow:var(--shadow-sm);margin-bottom:1.5rem;border-left:5px solid #F59E0B;">
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
      <i class="fas fa-exclamation-triangle" style="color:#F59E0B;"></i>
      <h3 style="font-family:'Cinzel',serif;font-size:0.95rem;font-weight:700;">Stock Alerts</h3>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
      <?php foreach ($data['low_stock'] as $item):
        [,$label,$cls] = inventoryStatus($item['quantity'],$item['par_level'],$item['reorder_level']);
      ?>
        <span class="status <?= $cls ?>"><?= e($item['product_name']) ?> — <?= $label ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
    <a href="?page=customers" class="btn-qoyla" style="justify-content:center;padding:1rem;"><i class="fas fa-users"></i> Customers</a>
    <a href="?page=inventory" class="btn-qoyla-outline" style="justify-content:center;padding:1rem;"><i class="fas fa-boxes"></i> Inventory</a>
    <a href="?page=workers"   class="btn-qoyla-outline" style="justify-content:center;padding:1rem;"><i class="fas fa-hard-hat"></i> Workers</a>
    <a href="?page=messages"  class="btn-qoyla-outline" style="justify-content:center;padding:1rem;"><i class="fas fa-envelope"></i> Messages</a>
  </div>

<!-- ===== CUSTOMERS ===== -->
<?php elseif ($activePage === 'customers'): ?>
  <div class="admin-topbar">
    <h1>Customers</h1>
    <button class="btn-qoyla" onclick="openModal('addCustomerModal')">
      <i class="fas fa-user-plus"></i> Add Customer
    </button>
  </div>
  <div class="admin-table-card">
    <div class="admin-table-header">
      <h3>All Members (<?= count($data['customers']) ?>)</h3>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="admin-search-input" placeholder="Search name, phone, CNIC...">
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr>
          <th>Sr No</th><th>Name</th><th>Phone</th><th>CNIC</th>
          <th>Points</th><th>Joined</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($data['customers'] as $c): ?>
          <tr>
            <td style="font-weight:700;color:var(--flame-orange);">
              #<?= str_pad($c['sr_no'],3,'0',STR_PAD_LEFT) ?>
            </td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td><?= e($c['phone']) ?></td>
            <td><?= e($c['cnic'] ?: '—') ?></td>
            <td><span class="badge-orange"><?= number_format($c['total_points']) ?> pts</span></td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
              <div class="admin-actions">
                <button class="btn-sm-action" style="background:var(--flame-orange);border-color:var(--flame-orange);"
                        onclick="openPointsModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', <?= $c['total_points'] ?>)">
                  <i class="fas fa-plus"></i> Points
                </button>
                <button class="btn-sm-action"
                        onclick="openCustomerLedger(<?= $c['id'] ?>, '<?= e($c['name']) ?>', <?= $c['total_points'] ?>)">
                  <i class="fas fa-list"></i> Ledger
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this customer? This cannot be undone.')">
                  <input type="hidden" name="action" value="delete_customer">
                  <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-sm-outline" style="color:#DC2626;border-color:#DC2626;">Del</button>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($data['customers'])): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">
            No customers yet. Add your first one!
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ===== INVENTORY ===== -->
<?php elseif ($activePage === 'inventory'): ?>
  <?php $invTabs = [
    'meats'   => '<i class="fas fa-drumstick-bite"></i> Meats',
    'dairy'   => '<i class="fas fa-glass-milk"></i> Dairy',
    'grocery' => '<i class="fas fa-shopping-basket"></i> Grocery',
    'mandi'   => '<i class="fas fa-seedling"></i> Mandi',
  ]; ?>
  <div class="admin-topbar">
    <h1>Inventory</h1>
    <button class="btn-qoyla" onclick="openModal('addItemModal')">
      <i class="fas fa-plus"></i> Add Item
    </button>
  </div>
  <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <?php $first=true; foreach($invTabs as $cat=>$label): ?>
      <button class="menu-filter-btn <?= $first?'active':'' ?>"
              onclick="switchInvTab('inv-<?= $cat ?>',this)">
        <?= $label ?>
      </button>
    <?php $first=false; endforeach; ?>
  </div>
  <?php foreach ($invTabs as $cat => $label): ?>
  <div id="inv-<?= $cat ?>" class="admin-tab-content admin-table-card"
       style="<?= $cat!=='meats'?'display:none;':'' ?>">
    <div class="admin-table-header">
      <h3><?= $label ?> (<?= count($data['inv'][$cat]) ?>)</h3>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr>
          <th>Product</th><th>Qty</th><th>Unit</th>
          <th>Par Level</th><th>Reorder</th><th>Status</th><th>Supplier</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($data['inv'][$cat] as $item):
            [,$label2,$cls] = inventoryStatus($item['quantity'],$item['par_level'],$item['reorder_level']);
          ?>
          <tr>
            <td><strong><?= e($item['product_name']) ?></strong>
              <?php if($item['description']): ?>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($item['description']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= $item['quantity'] ?></td>
            <td><?= e($item['unit']) ?></td>
            <td><?= $item['par_level'] ?: '—' ?></td>
            <td><?= $item['reorder_level'] ?: '—' ?></td>
            <td><span class="status <?= $cls ?>"><?= $label2 ?></span></td>
            <td><?= e($item['supplier'] ?: '—') ?></td>
            <td>
              <div class="admin-actions">
                <button class="btn-sm-action"
                        onclick="openEditItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                  Edit
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?')">
                  <input type="hidden" name="action" value="delete_inventory">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn-sm-outline" style="color:#DC2626;border-color:#DC2626;">Del</button>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($data['inv'][$cat])): ?>
          <tr><td colspan="8" style="text-align:center;padding:1.5rem;color:var(--text-muted);">
            No <?= strtolower($label) ?> items. Add one above.
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

<!-- ===== WORKERS ===== -->
<?php elseif ($activePage === 'workers'): ?>
  <div class="admin-topbar">
    <h1>Workers</h1>
    <button class="btn-qoyla" onclick="openModal('addWorkerModal')">
      <i class="fas fa-user-plus"></i> Add Worker
    </button>
  </div>
  <div class="admin-table-card">
    <div class="admin-table-header">
      <h3>All Staff (<?= count($data['workers']) ?>)</h3>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="admin-search-input" placeholder="Search worker...">
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr>
          <th>Name / Role</th><th>CNIC</th><th>Phone 1</th><th>Phone 2</th>
          <th>Bank Account</th><th>Caution</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($data['workers'] as $w): ?>
          <tr>
            <td>
              <strong><?= e($w['name']) ?></strong>
              <?php if($w['role']): ?>
                <div style="font-size:0.75rem;color:var(--flame-orange);font-weight:700;"><?= e($w['role']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($w['cnic'] ?: '—') ?></td>
            <td><?= e($w['phone1'] ?: '—') ?></td>
            <td><?= e($w['phone2'] ?: '—') ?></td>
            <td><?= e($w['bank_account'] ?: '—') ?></td>
            <td><?= cautionBadge($w['caution_level']) ?></td>
            <td>
              <div class="admin-actions">
                <button class="btn-sm-action"
                        onclick="openComplaintModal(<?= $w['id'] ?>, '<?= e($w['name']) ?>')">
                  <i class="fas fa-flag"></i> Complaint
                </button>
                <button class="btn-sm-action"
                        onclick="openEditWorker(<?= htmlspecialchars(json_encode($w)) ?>)">
                  <i class="fas fa-pen"></i> Edit
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Soft-delete this worker? They will no longer appear in active lists.')">
                  <input type="hidden" name="action" value="delete_worker">
                  <input type="hidden" name="worker_id" value="<?= $w['id'] ?>">
                  <button type="submit" class="btn-sm-outline" style="color:#DC2626;border-color:#DC2626;">Del</button>
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($data['workers'])): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No workers yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.75rem;">
    <i class="fas fa-info-circle" style="color:var(--flame-orange);"></i>
    Caution resets automatically every 3 months. Green=0, Yellow=1, Orange=2, Red=3+
  </p>

<!-- ===== MENU ITEMS ===== -->
<?php elseif ($activePage === 'menu-mgmt'): ?>
  <div class="admin-topbar">
    <h1>Menu Items</h1>
    <button class="btn-qoyla" onclick="openModal('addMenuModal')">
      <i class="fas fa-plus"></i> Add Dish
    </button>
  </div>
  <div class="admin-table-card">
    <div class="admin-table-header">
      <h3>All Items (<?= count($data['menu_items']) ?>)</h3>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="admin-search-input" placeholder="Search dish...">
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Featured</th><th>Available</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($data['menu_items'] as $item): ?>
          <tr>
            <td>
              <?php if(!empty($item['image_path'])): ?>
                <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
              <?php else: ?>
                <div style="width:48px;height:48px;border-radius:8px;background:var(--off-white);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.7rem;"><i class="fas fa-image"></i></div>
              <?php endif; ?>
            </td>
            <td><strong><?= e($item['name']) ?></strong>
              <?php if($item['description']): ?>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e(substr($item['description'],0,60)) ?>...</div>
              <?php endif; ?>
            </td>
            <td><span class="badge-dark"><?= ucfirst(str_replace('_',' ',$item['category'])) ?></span></td>
            <td>Rs. <?= number_format($item['price']) ?></td>
            <td><?= $item['is_featured'] ? '⭐ Yes' : '—' ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_menu">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <input type="hidden" name="current" value="<?= $item['is_available'] ?>">
                <button type="submit" class="status <?= $item['is_available'] ? 'status-green' : 'status-red' ?>" style="border:none;cursor:pointer;">
                  <?= $item['is_available'] ? 'Available' : 'Hidden' ?>
                </button>
              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
            </td>
            <td>
              <div class="admin-actions">
                <button class="btn-sm-action" onclick="openEditMenuItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                  <i class="fas fa-pen"></i> Edit
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?')">
                  <input type="hidden" name="action" value="delete_menu">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn-sm-outline" style="color:#DC2626;border-color:#DC2626;">Del</button>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($data['menu_items'])): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No menu items yet. Add your first dish!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ===== DEALS ===== -->
<?php elseif ($activePage === 'deals-mgmt'): ?>
  <div class="admin-topbar">
    <h1>Deals</h1>
    <button class="btn-qoyla" onclick="openModal('addDealModal')">
      <i class="fas fa-plus"></i> Add Deal
    </button>
  </div>
  <div class="admin-table-card">
    <div class="admin-table-header"><h3>All Deals</h3></div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Image</th><th>Title</th><th>Type</th><th>Discount</th><th>Points Mult.</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($data['deals'] as $d): ?>
          <tr>
            <td>
              <?php if(!empty($d['image_path'])): ?>
                <img src="<?= e($d['image_path']) ?>" alt="<?= e($d['title']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
              <?php else: ?>
                <div style="width:48px;height:48px;border-radius:8px;background:var(--off-white);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.7rem;"><i class="fas fa-image"></i></div>
              <?php endif; ?>
            </td>
            <td><strong><?= e($d['title']) ?></strong>
              <div style="font-size:0.75rem;color:var(--text-muted);"><?= e(substr($d['description']??'',0,55)) ?></div>
            </td>
            <td><?= ucfirst($d['deal_type']) ?></td>
            <td><?= $d['discount_percent'] > 0 ? $d['discount_percent'].'%' : '—' ?></td>
            <td><?= $d['points_multiplier'] > 1 ? $d['points_multiplier'].'×' : '—' ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_deal">
                <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                <input type="hidden" name="current" value="<?= $d['is_active'] ?>">
                <button type="submit" class="status <?= $d['is_active'] ? 'status-green' : 'status-red' ?>" style="border:none;cursor:pointer;">
                  <?= $d['is_active'] ? 'Active' : 'Off' ?>
                </button>
              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
            </td>
            <td>
              <div class="admin-actions">
                <button class="btn-sm-action" onclick="openEditDeal(<?= htmlspecialchars(json_encode($d)) ?>)">
                  <i class="fas fa-pen"></i> Edit
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete deal?')">
                  <input type="hidden" name="action" value="delete_deal">
                  <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                  <button type="submit" class="btn-sm-outline" style="color:#DC2626;border-color:#DC2626;">Del</button>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($data['deals'])): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No deals yet. Add your first deal!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ===== MESSAGES ===== -->
<?php elseif ($activePage === 'messages'): ?>
  <div class="admin-topbar"><h1>Messages & Complaints</h1></div>
  <div class="admin-table-card">
    <div class="admin-table-header"><h3>Inbox (<?= count($data['messages']) ?>)</h3></div>
    <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Type</th><th>From</th><th>Phone</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($data['messages'] as $msg): ?>
          <tr style="<?= !$msg['is_read'] ? 'background:#FFFBF8;' : '' ?>">
            <td>
              <span class="status <?= $msg['form_type']==='complaint' ? 'status-red' : 'status-green' ?>">
                <?= ucfirst($msg['form_type']) ?>
              </span>
              <?php if(!$msg['is_read']): ?>
                <span style="background:var(--flame-orange);color:white;font-size:0.62rem;padding:0.1rem 0.4rem;border-radius:50px;margin-left:4px;">NEW</span>
              <?php endif; ?>
            </td>
            <td><strong><?= e($msg['name'] ?: '—') ?></strong></td>
            <td><?= e($msg['phone'] ?: '—') ?></td>
            <td style="max-width:280px;white-space:normal;font-size:0.85rem;">
              <?= e(substr($msg['message'],0,120)) ?><?= strlen($msg['message'])>120?'...':'' ?>
            </td>
            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($msg['submitted_at'])) ?></td>
            <td>
              <div class="admin-actions">
                <?php if(!$msg['reply_text']): ?>
                  <button class="btn-sm-action" onclick="openReplyModal(<?= $msg['id'] ?>, '<?= e($msg['name'] ?: 'Customer') ?>', `<?= htmlspecialchars($msg['message']) ?>`)">
                    <i class="fas fa-reply"></i> Reply
                  </button>
                <?php else: ?>
                  <span style="font-size:0.75rem;color:var(--flame-orange);font-weight:700;"><i class="fas fa-check-double"></i> Replied</span>
                <?php endif; ?>
                
                <?php if(!$msg['is_read']): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                    <button type="submit" class="btn-sm-outline">Mark Read</button>
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($data['messages'])): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No messages yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>
</main>
</div><!-- end admin-wrap -->

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Add Customer -->
<div class="modal-overlay" id="addCustomerModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Add New Customer</div><button class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_customer">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Phone *</label><input type="tel" name="phone" class="form-input" required placeholder="03001234567"></div>
        <div class="form-group"><label class="form-label">CNIC</label><input type="text" name="cnic" class="form-input" placeholder="XXXXX-XXXXXXX-X"></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input"></div>
      <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">Default password will be <strong>qoyla123</strong> — customer can change it after login.</p>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Add Customer</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Points Modal (populated by JS) -->
<div class="modal-overlay" id="pointsModal">
  <div class="modal-box" style="max-width:520px;">
    <div class="modal-header"><div class="modal-title" id="pointsModalTitle">Manage Points</div><button class="modal-close" onclick="closeModal('pointsModal')">&times;</button></div>
    <div style="background:var(--off-white);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.5rem;">
      <div style="font-size:0.75rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.4rem;">Current Balance</div>
      <div class="points-display" id="pointsModalBalance" style="font-size:1.8rem;"></div>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_points">
      <input type="hidden" name="customer_id" id="pointsCustomerId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label class="form-label">Action</label>
          <select name="type" class="form-select" style="appearance:none;">
            <option value="earned">Add Points (Visit)</option>
            <option value="adjusted">Add Points (Adjustment)</option>
            <option value="redeemed">Remove Points (Redeem)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Points *</label>
          <input type="number" name="points" class="form-input" placeholder="e.g. 100" min="1" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Note / Reason</label>
        <input type="text" name="description" class="form-input" placeholder="e.g. Visit on <?= date('d M Y') ?>">
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Save Points</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Add Inventory Item -->
<div class="modal-overlay" id="addItemModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Add Inventory Item</div><button class="modal-close" onclick="closeModal('addItemModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_inventory">
      <div class="form-group"><label class="form-label">Category</label>
        <select name="category" class="form-select" style="appearance:none;">
          <option value="meats">Meats</option><option value="dairy">Dairy</option>
          <option value="grocery">Grocery</option><option value="mandi">Mandi</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Product Name *</label><input type="text" name="product_name" class="form-input" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Quantity *</label><input type="number" name="quantity" class="form-input" step="0.01" min="0" required></div>
        <div class="form-group"><label class="form-label">Unit</label>
          <select name="unit" class="form-select" style="appearance:none;"><option>kg</option><option>g</option><option>L</option><option>pieces</option><option>dozen</option><option>packet</option></select>
        </div>
        <div class="form-group"><label class="form-label">Par Level</label><input type="number" name="par_level" class="form-input" step="0.01" min="0" value="0"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-input" step="0.01" min="0" value="0"></div>
        <div class="form-group"><label class="form-label">Supplier</label><input type="text" name="supplier" class="form-input"></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" style="min-height:70px;"></textarea></div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Add Item</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Edit Inventory Item (populated by JS) -->
<div class="modal-overlay" id="editItemModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Edit Inventory Item</div><button class="modal-close" onclick="closeModal('editItemModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="update_inventory">
      <input type="hidden" name="item_id" id="editItemId">
      <div class="form-group"><label class="form-label">Product Name *</label><input type="text" name="product_name" id="editProductName" class="form-input" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Quantity *</label><input type="number" name="quantity" id="editQty" class="form-input" step="0.01" min="0" required></div>
        <div class="form-group"><label class="form-label">Unit</label>
          <select name="unit" id="editUnit" class="form-select" style="appearance:none;"><option>kg</option><option>g</option><option>L</option><option>pieces</option><option>dozen</option><option>packet</option></select>
        </div>
        <div class="form-group"><label class="form-label">Par Level</label><input type="number" name="par_level" id="editParLevel" class="form-input" step="0.01" min="0"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" id="editReorderLevel" class="form-input" step="0.01" min="0"></div>
        <div class="form-group"><label class="form-label">Supplier</label><input type="text" name="supplier" id="editSupplier" class="form-input"></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="editDescription" class="form-textarea" style="min-height:70px;"></textarea></div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Save Changes</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Add Worker -->
<div class="modal-overlay" id="addWorkerModal">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-header"><div class="modal-title">Add Worker</div><button class="modal-close" onclick="closeModal('addWorkerModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_worker">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Role / Position</label><input type="text" name="role" class="form-input" placeholder="e.g. Chef, Waiter"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">CNIC</label><input type="text" name="cnic" class="form-input" placeholder="XXXXX-XXXXXXX-X"></div>
        <div class="form-group"><label class="form-label">Joined Date</label><input type="date" name="joined_date" class="form-input" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Phone 1 *</label><input type="tel" name="phone1" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Phone 2</label><input type="tel" name="phone2" class="form-input"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Bank Account</label><input type="text" name="bank_account" class="form-input"></div>
        <div class="form-group"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_contact" class="form-input"></div>
      </div>
      <div class="form-group"><label class="form-label">Referral</label><input type="text" name="referral" class="form-input" placeholder="Who referred this worker?"></div>
      <div class="form-group"><label class="form-label">Description / Notes</label><textarea name="description" class="form-textarea" style="min-height:70px;"></textarea></div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Add Worker</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Complaint Modal (populated by JS) -->
<div class="modal-overlay" id="complaintModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title" id="complaintModalTitle">File Complaint</div><button class="modal-close" onclick="closeModal('complaintModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_complaint">
      <input type="hidden" name="worker_id" id="complaintWorkerId">
      <div class="form-group"><label class="form-label">Complaint Details *</label><textarea name="complaint" class="form-textarea" placeholder="Describe the issue in detail..." required></textarea></div>
      <div class="form-group"><label class="form-label">Filed By</label><input type="text" name="filed_by" class="form-input" value="<?= e($_SESSION['admin_name']) ?>"></div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;background:#DC2626;border-color:#DC2626;">
        Submit Complaint
      </button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Add Menu Item -->
<div class="modal-overlay" id="addMenuModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Add Menu Item</div><button class="modal-close" onclick="closeModal('addMenuModal')">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_menu_item">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Dish Name *</label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Category</label>
          <select name="category" class="form-select" style="appearance:none;">
            <option value="meats">Meats</option><option value="main_course">Main Course</option>
            <option value="sweets">Sweets</option><option value="drinks">Drinks</option><option value="deals">Deals</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" style="min-height:80px;"></textarea></div>
      <div class="form-group"><label class="form-label">Price (Rs.) *</label><input type="number" name="price" class="form-input" step="0.01" min="0" required></div>
      <div class="form-group">
        <label class="form-label">Dish Image</label>
        <div style="border:2px dashed #D1C7BA;border-radius:var(--radius-md);padding:1.25rem;text-align:center;cursor:pointer;transition:border-color 0.3s;position:relative;"
             onclick="this.querySelector('input[type=file]').click()"
             onmouseover="this.style.borderColor='var(--flame-orange)'" onmouseout="this.style.borderColor='#D1C7BA'">
          <img id="menuImagePreview" src="" alt="" style="display:none;max-height:120px;border-radius:8px;margin-bottom:0.5rem;">
          <div id="menuImagePlaceholder">
            <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;color:var(--flame-orange);display:block;margin-bottom:0.4rem;"></i>
            <span style="font-size:0.82rem;color:var(--text-muted);">Click to upload image (JPG, PNG, WebP — max 5MB)</span>
          </div>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;"
                 onchange="previewUpload(this, 'menuImagePreview', 'menuImagePlaceholder')">
        </div>
      </div>
      <div style="display:flex;gap:1.5rem;margin-bottom:1.25rem;">
        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
          <input type="checkbox" name="is_available" value="1" checked style="accent-color:var(--flame-orange);"> Available on Menu
        </label>
        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
          <input type="checkbox" name="is_featured" value="1" style="accent-color:var(--flame-orange);"> Feature on Homepage
        </label>
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Add Dish</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Add Deal -->
<div class="modal-overlay" id="addDealModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Add Deal</div><button class="modal-close" onclick="closeModal('addDealModal')">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_deal">
      <div class="form-group"><label class="form-label">Deal Title *</label><input type="text" name="title" class="form-input" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Type</label>
          <select name="deal_type" class="form-select" style="appearance:none;">
            <option value="package">Package</option><option value="weekend">Weekend</option>
            <option value="game">Game Night</option><option value="service">Service</option>
            <option value="announcement">Announcement</option><option value="special">Special</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Discount %</label><input type="number" name="discount_percent" class="form-input" min="0" max="100" value="0"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Points Multiplier</label><input type="number" name="points_multiplier" class="form-input" step="0.1" min="1" value="1.0"></div>
        <div class="form-group"><label class="form-label">Active?</label>
          <div style="margin-top:0.75rem;">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" name="is_active" value="1" checked style="accent-color:var(--flame-orange);"> Yes, activate now
            </label>
          </div>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" style="min-height:80px;"></textarea></div>
      <div class="form-group">
        <label class="form-label">Deal Image</label>
        <div style="border:2px dashed #D1C7BA;border-radius:var(--radius-md);padding:1.25rem;text-align:center;cursor:pointer;transition:border-color 0.3s;position:relative;"
             onclick="this.querySelector('input[type=file]').click()"
             onmouseover="this.style.borderColor='var(--flame-orange)'" onmouseout="this.style.borderColor='#D1C7BA'">
          <img id="dealImagePreview" src="" alt="" style="display:none;max-height:120px;border-radius:8px;margin-bottom:0.5rem;">
          <div id="dealImagePlaceholder">
            <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;color:var(--flame-orange);display:block;margin-bottom:0.4rem;"></i>
            <span style="font-size:0.82rem;color:var(--text-muted);">Click to upload image (JPG, PNG, WebP — max 5MB)</span>
          </div>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;"
                 onchange="previewUpload(this, 'dealImagePreview', 'dealImagePlaceholder')">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-input"></div>
        <div class="form-group"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-input"></div>
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Add Deal</button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<!-- Edit Worker Modal -->
<div class="modal-overlay" id="editWorkerModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Edit Worker</div><button class="modal-close" onclick="closeModal('editWorkerModal')">&times;</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_worker">
      <input type="hidden" name="worker_id" id="editWorkerId">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" id="editWorkerName" class="form-input" required></div>
      <div class="form-group"><label class="form-label">Role</label><input type="text" name="role" id="editWorkerRole" class="form-input" placeholder="e.g. Waiter"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Phone 1</label><input type="tel" name="phone1" id="editWorkerPhone1" class="form-input"></div>
        <div class="form-group"><label class="form-label">Phone 2</label><input type="tel" name="phone2" id="editWorkerPhone2" class="form-input"></div>
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Update Worker</button>
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    </form>
  </div>
</div>

<!-- Reply Message Modal -->
<div class="modal-overlay" id="replyMessageModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Reply to Customer</div><button class="modal-close" onclick="closeModal('replyMessageModal')">&times;</button></div>
    <div style="font-size:0.85rem;margin-bottom:1rem;color:var(--text-muted);">
      <strong>Customer:</strong> <span id="replyCustomerName"></span><br>
      <strong>Message:</strong> <span id="replyMessageText" style="display:block;background:var(--off-white);padding:0.5rem;border-radius:4px;margin-top:0.5rem;"></span>
    </div>
    <form method="POST" data-loading>
      <input type="hidden" name="action" value="reply_message">
      <input type="hidden" name="message_id" id="replyMessageId">
      <div class="form-group"><label class="form-label">Your Reply</label><textarea name="reply_text" class="form-input" rows="4" required placeholder="Type your reply here..."></textarea></div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Send Reply <i class="fas fa-paper-plane" style="margin-left:0.5rem;"></i></button>
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    </form>
  </div>
</div>

<!-- Edit Menu Item Modal -->
<div class="modal-overlay" id="editMenuModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Edit Menu Item</div><button class="modal-close" onclick="closeModal('editMenuModal')">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit_menu_item">
      <input type="hidden" name="item_id" id="editMenuId">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" id="editMenuName" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Category *</label>
          <select name="category" id="editMenuCategory" class="form-select" style="appearance:none;">
            <option value="starter">Starter</option><option value="main_course">Main Course</option>
            <option value="grill">Grill</option><option value="rice">Rice</option>
            <option value="bread">Bread</option><option value="dessert">Dessert</option><option value="drink">Drink</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="editMenuDesc" class="form-input" rows="2"></textarea></div>
      <div class="form-group"><label class="form-label">Price (Rs.) *</label><input type="number" name="price" id="editMenuPrice" class="form-input" step="0.01" required></div>
      <div class="form-group">
        <label class="form-label">Replace Image (optional)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
        <div style="margin-top:0.5rem;"><img id="editMenuCurrentImg" src="" style="height:60px;border-radius:6px;display:none;"></div>
      </div>
      <div style="display:flex;gap:1.5rem;margin-bottom:1.25rem;">
        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
          <input type="checkbox" name="is_available" id="editMenuAvail" value="1" style="accent-color:var(--flame-orange);"> Available on Menu
        </label>
        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
          <input type="checkbox" name="is_featured" id="editMenuFeatured" value="1" style="accent-color:var(--flame-orange);"> Feature on Homepage
        </label>
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Save Changes</button>
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    </form>
  </div>
</div>

<!-- Edit Deal Modal -->
<div class="modal-overlay" id="editDealModal">
  <div class="modal-box">
    <div class="modal-header"><div class="modal-title">Edit Deal</div><button class="modal-close" onclick="closeModal('editDealModal')">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit_deal">
      <input type="hidden" name="deal_id" id="editDealId">
      <div class="form-group"><label class="form-label">Deal Title *</label><input type="text" name="title" id="editDealTitle" class="form-input" required></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="editDealDesc" class="form-input" rows="2"></textarea></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Type</label>
          <select name="deal_type" id="editDealType" class="form-select" style="appearance:none;">
            <option value="package">Package</option><option value="weekend">Weekend</option>
            <option value="game">Game Night</option><option value="service">Service</option>
            <option value="announcement">Announcement</option><option value="special">Special</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Discount %</label><input type="number" name="discount_percent" id="editDealDiscount" class="form-input" min="0" max="100"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Points Multiplier</label><input type="number" name="points_multiplier" id="editDealMult" class="form-input" step="0.1" min="1"></div>
        <div class="form-group"><label class="form-label">Active?</label>
          <div style="margin-top:0.75rem;"><label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
            <input type="checkbox" name="is_active" id="editDealActive" value="1" style="accent-color:var(--flame-orange);"> Yes, show this deal
          </label></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" id="editDealStart" class="form-input"></div>
        <div class="form-group"><label class="form-label">End Date</label><input type="date" name="end_date" id="editDealEnd" class="form-input"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Replace Image (optional)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
        <div style="margin-top:0.5rem;"><img id="editDealCurrentImg" src="" style="height:60px;border-radius:6px;display:none;"></div>
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">Save Changes</button>
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    </form>
  </div>
</div>

<!-- Customer Ledger Modal -->
<div class="modal-overlay" id="customerLedgerModal">
  <div class="modal-box" style="max-width:700px;">
    <div class="modal-header"><div class="modal-title" id="ledgerModalTitle">Points Ledger</div><button class="modal-close" onclick="closeModal('customerLedgerModal')">&times;</button></div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <p style="font-size:0.92rem;margin:0;">Current Balance: <strong id="ledgerModalBalance" style="color:var(--flame-orange);"></strong></p>
      <button class="btn-sm-action" id="ledgerAddPointsBtn" style="background:var(--flame-orange);">
        <i class="fas fa-plus"></i> Add Points
      </button>
    </div>
    <div style="border:1px solid var(--border-light);border-radius:var(--radius-sm);overflow-y:auto;max-height:400px;">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Reason</th><th>Amount</th><th>Expiry</th></tr></thead>
        <tbody id="ledgerTableBody">
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
// Points modal — fill with customer data
function openPointsModal(id, name, points) {
  document.getElementById('pointsCustomerId').value = id;
  document.getElementById('pointsModalTitle').textContent = 'Manage Points — ' + name;
  document.getElementById('pointsModalBalance').textContent = points.toLocaleString() + ' pts';
  openModal('pointsModal');
}

window.allPointsLogs = <?= json_encode($data['points_logs'] ?? []) ?>;
function openCustomerLedger(customerId, customerName, totalPoints) {
  document.getElementById('ledgerModalTitle').textContent = 'Points Ledger — ' + customerName;
  document.getElementById('ledgerModalBalance').textContent = totalPoints.toLocaleString() + ' pts';
  
  // Set the "Add Points" button in ledger to open the points modal
  document.getElementById('ledgerAddPointsBtn').onclick = () => {
    closeModal('customerLedgerModal');
    openPointsModal(customerId, customerName, totalPoints);
  };
  
  const tbody = document.getElementById('ledgerTableBody');
  tbody.innerHTML = '';
  
  const cLogs = window.allPointsLogs.filter(log => String(log.customer_id) === String(customerId));
  if (cLogs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:1.5rem;">No history found.</td></tr>`;
  } else {
    cLogs.forEach(log => {
      const isPositive = parseFloat(log.change_amount) > 0;
      const color = isPositive ? '#16A34A' : '#DC2626';
      const sign = isPositive ? '+' : '';
      let expiresHtml = '—';
      if (log.expires_at) {
         expiresHtml = new Date(log.expires_at).toLocaleDateString();
      }
      tbody.innerHTML += `
        <tr>
          <td>${new Date(log.created_at).toLocaleDateString()}</td>
          <td>${log.reason}</td>
          <td style="color:${color};font-weight:bold;">${sign}${Math.abs(log.change_amount)} pts</td>
          <td>${expiresHtml}</td>
        </tr>
      `;
    });
  }
  openModal('customerLedgerModal');
}

// Complaint modal
function openComplaintModal(id, name) {
  document.getElementById('complaintWorkerId').value = id;
  document.getElementById('complaintModalTitle').textContent = 'File Complaint — ' + name;
  openModal('complaintModal');
}

function openEditWorker(w) {
  document.getElementById('editWorkerId').value = w.id;
  document.getElementById('editWorkerName').value = w.name;
  document.getElementById('editWorkerRole').value = w.role || '';
  document.getElementById('editWorkerPhone1').value = w.phone1 || '';
  document.getElementById('editWorkerPhone2').value = w.phone2 || '';
  openModal('editWorkerModal');
}

function openReplyModal(msgId, customerName, messageText) {
  document.getElementById('replyMessageId').value = msgId;
  document.getElementById('replyCustomerName').textContent = customerName;
  document.getElementById('replyMessageText').textContent = messageText;
  openModal('replyMessageModal');
}

// Edit inventory item — populate modal from JSON
function openEditItem(item) {
  document.getElementById('editItemId').value        = item.id;
  document.getElementById('editProductName').value   = item.product_name;
  document.getElementById('editQty').value           = item.quantity;
  document.getElementById('editUnit').value          = item.unit;
  document.getElementById('editParLevel').value      = item.par_level;
  document.getElementById('editReorderLevel').value  = item.reorder_level;
  document.getElementById('editSupplier').value      = item.supplier || '';
  document.getElementById('editDescription').value   = item.description || '';
  openModal('editItemModal');
}

// Inventory tab switcher
function switchInvTab(tabId, btn) {
  document.querySelectorAll('.admin-tab-content').forEach(t => t.style.display = 'none');
  const el = document.getElementById(tabId);
  if (el) el.style.display = 'block';
  document.querySelectorAll('.menu-filter-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
}

// Image preview for upload fields
function previewUpload(input, previewId, placeholderId) {
  const preview = document.getElementById(previewId);
  const placeholder = document.getElementById(placeholderId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Edit Menu Item modal
function openEditMenuItem(item) {
  document.getElementById('editMenuId').value       = item.id;
  document.getElementById('editMenuName').value     = item.name;
  document.getElementById('editMenuDesc').value     = item.description || '';
  document.getElementById('editMenuPrice').value    = item.price;
  document.getElementById('editMenuAvail').checked    = item.is_available == 1;
  document.getElementById('editMenuFeatured').checked = item.is_featured == 1;
  // Set category dropdown
  const catSel = document.getElementById('editMenuCategory');
  for (let opt of catSel.options) { opt.selected = (opt.value === item.category); }
  // Show current image thumbnail
  const img = document.getElementById('editMenuCurrentImg');
  if (item.image_path) { img.src = item.image_path; img.style.display = 'block'; }
  else { img.style.display = 'none'; }
  openModal('editMenuModal');
}

// Edit Deal modal
function openEditDeal(d) {
  document.getElementById('editDealId').value       = d.id;
  document.getElementById('editDealTitle').value    = d.title;
  document.getElementById('editDealDesc').value     = d.description || '';
  document.getElementById('editDealDiscount').value = d.discount_percent;
  document.getElementById('editDealMult').value     = d.points_multiplier;
  document.getElementById('editDealActive').checked = d.is_active == 1;
  document.getElementById('editDealStart').value    = d.start_date || '';
  document.getElementById('editDealEnd').value      = d.end_date   || '';
  // Set type dropdown
  const typeSel = document.getElementById('editDealType');
  for (let opt of typeSel.options) { opt.selected = (opt.value === d.deal_type); }
  // Show current image thumbnail
  const img = document.getElementById('editDealCurrentImg');
  if (d.image_path) { img.src = d.image_path; img.style.display = 'block'; }
  else { img.style.display = 'none'; }
  openModal('editDealModal');
}
</script>
</body>
</html>
