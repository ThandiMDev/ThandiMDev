<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$notifications->execute([$user_id]);
$notifications = $notifications->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="../index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900">← Back to Home</a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">🔔 Notifications</h2>
            
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📭</div>
                    <p class="text-slate-600">No notifications yet</p>
                    <p class="text-slate-400 text-sm mt-2">When you receive updates, they will appear here</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="p-4 border rounded-lg <?php echo !$notif['is_read'] ? 'bg-blue-50 border-blue-200' : 'bg-white'; ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-slate-800"><?php echo htmlspecialchars($notif['title']); ?></h3>
                                    <p class="text-slate-600 mt-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <p class="text-xs text-slate-400 mt-2"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></p>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>