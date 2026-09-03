<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = ($_SESSION['user_role'] == 'admin');

// Handle Report Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_report_status') {
        $report_id = $_POST['report_id'];
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $report_id]);
        
        // Create notification for user
        $user_stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
        $user_stmt->execute([$report_id]);
        $report = $user_stmt->fetch();
        if ($report['user_id']) {
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'status', 'Report Status Updated', 'Your report status has been changed to: $new_status')");
            $notif->execute([$report['user_id']]);
        }
        $success = "Report status updated!";
    }
    
    if ($_POST['action'] == 'delete_user') {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$user_id]);
        $success = "User deleted successfully!";
    }
    
    if ($_POST['action'] == 'assign_task') {
        $volunteer_id = $_POST['volunteer_id'];
        $task_name = $_POST['task_name'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        $stmt = $pdo->prepare("INSERT INTO volunteer_tasks (volunteer_id, task_name, description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$volunteer_id, $task_name, $description, $due_date]);
        $success = "Task assigned successfully!";
    }
}

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$totalReports = $pdo->query("SELECT COUNT(*) as count FROM reports")->fetch()['count'];
$pendingReports = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch()['count'];
$totalDonations = $pdo->query("SELECT SUM(amount) as total FROM donations WHERE status = 'completed'")->fetch()['total'] ?? 0;
$totalVolunteers = $pdo->query("SELECT COUNT(*) as count FROM volunteers")->fetch()['count'];

// Get recent reports
$reports = $pdo->query("SELECT r.*, u.full_name as user_name FROM reports r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 10")->fetchAll();

// Get recent donations
$donations = $pdo->query("SELECT * FROM donations ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get volunteers
$volunteers = $pdo->query("SELECT * FROM volunteers ORDER BY created_at DESC")->fetchAll();

// Get volunteer tasks
$tasks = $pdo->query("SELECT t.*, v.full_name as volunteer_name FROM volunteer_tasks t LEFT JOIN volunteers v ON t.volunteer_id = v.id ORDER BY t.assigned_at DESC")->fetchAll();

// Get users
$users = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();

// Get monthly donations for chart
$monthlyDonations = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total FROM donations WHERE status = 'completed' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 6")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Admin Dashboard</h1>
                <p class="text-slate-600">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
            <a href="../index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">← Back to Home</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-6"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="grid md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition">
                <div class="text-3xl mb-2">👥</div>
                <div class="text-2xl font-bold text-blue-600"><?php echo $totalUsers; ?></div>
                <div class="text-slate-600 text-sm">Total Users</div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition">
                <div class="text-3xl mb-2">📋</div>
                <div class="text-2xl font-bold text-orange-600"><?php echo $totalReports; ?></div>
                <div class="text-slate-600 text-sm">Total Reports</div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition">
                <div class="text-3xl mb-2">⏳</div>
                <div class="text-2xl font-bold text-red-600"><?php echo $pendingReports; ?></div>
                <div class="text-slate-600 text-sm">Pending Reports</div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition">
                <div class="text-3xl mb-2">💰</div>
                <div class="text-2xl font-bold text-green-600">R<?php echo number_format($totalDonations); ?></div>
                <div class="text-slate-600 text-sm">Total Donations</div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition">
                <div class="text-3xl mb-2">🤝</div>
                <div class="text-2xl font-bold text-purple-600"><?php echo $totalVolunteers; ?></div>
                <div class="text-slate-600 text-sm">Volunteers</div>
            </div>
        </div>
        
        <!-- Reports Management -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">📋 Infrastructure Reports</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-3">ID</th>
                            <th class="text-left p-3">Type</th>
                            <th class="text-left p-3">Community</th>
                            <th class="text-left p-3">Reported By</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-left p-3">Date</th>
                            <th class="text-left p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-3">#<?php echo $report['id']; ?></td>
                            <td class="p-3"><?php echo ucfirst(str_replace('_', ' ', $report['infrastructure_type'])); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($report['community_name']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($report['user_name'] ?? 'Guest'); ?></td>
                            <td class="p-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="action" value="update_report_status">
                                    <select name="status" onchange="this.form.submit()" class="text-sm px-2 py-1 rounded <?php 
                                        echo $report['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($report['status'] == 'resolved' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                                        <option value="pending" <?php echo $report['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $report['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="in_progress" <?php echo $report['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $report['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="rejected" <?php echo $report['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </form>
                            </td>
                            <td class="p-3 text-sm"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                            <td class="p-3">
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="text-blue-600 hover:underline text-sm">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reports)): ?>
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">No reports yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">👥 User Management</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-3">ID</th>
                            <th class="text-left p-3">Name</th>
                            <th class="text-left p-3">Email</th>
                            <th class="text-left p-3">Role</th>
                            <th class="text-left p-3">Joined</th>
                            <th class="text-left p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-3"><?php echo $user['id']; ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="p-3 text-sm"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="p-3">
                                <?php if ($user['role'] != 'admin'): ?>
                                <form method="POST" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-slate-400 text-sm">Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Donations -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">💝 Recent Donations</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-3">Donor</th>
                            <th class="text-left p-3">Amount</th>
                            <th class="text-left p-3">Method</th>
                            <th class="text-left p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                        <tr class="border-t">
                            <td class="p-3"><?php echo htmlspecialchars($donation['anonymous'] ? 'Anonymous' : $donation['donor_name']); ?></td>
                            <td class="p-3 font-semibold text-green-600">R<?php echo number_format($donation['amount'], 2); ?></td>
                            <td class="p-3"><?php echo ucfirst($donation['payment_method']); ?></td>
                            <td class="p-3 text-sm"><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donations)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">No donations yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Volunteer Management -->
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Volunteers List -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">🤝 Volunteers</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($volunteers as $volunteer): ?>
                    <div class="p-3 border rounded-lg">
                        <div class="font-semibold"><?php echo htmlspecialchars($volunteer['full_name']); ?></div>
                        <div class="text-sm text-slate-600">📧 <?php echo $volunteer['email']; ?></div>
                        <div class="text-sm text-slate-600">📞 <?php echo $volunteer['phone'] ?? 'No phone'; ?></div>
                        <div class="text-xs text-slate-400 mt-1">Skills: <?php echo htmlspecialchars($volunteer['skills'] ?? 'None'); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($volunteers)): ?>
                    <p class="text-slate-500 text-center py-6">No volunteers registered yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Assign Tasks -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">📝 Assign Task</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_task">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Select Volunteer</label>
                            <select name="volunteer_id" required class="w-full p-2 border rounded-lg">
                                <option value="">Choose volunteer...</option>
                                <?php foreach ($volunteers as $volunteer): ?>
                                <option value="<?php echo $volunteer['id']; ?>"><?php echo htmlspecialchars($volunteer['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Task Name</label>
                            <input type="text" name="task_name" required class="w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full p-2 border rounded-lg"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Due Date</label>
                            <input type="date" name="due_date" class="w-full p-2 border rounded-lg">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Assign Task</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Volunteer Tasks -->
        <div class="bg-white rounded-xl shadow-md p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">✅ Assigned Tasks</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left p-3">Volunteer</th>
                            <th class="text-left p-3">Task</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-left p-3">Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr class="border-t">
                            <td class="p-3"><?php echo htmlspecialchars($task['volunteer_name'] ?? 'Unknown'); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($task['task_name']); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?php 
                                    echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($task['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </td>
                            <td class="p-3 text-sm"><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Not set'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tasks)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">No tasks assigned yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">📊 Monthly Donations</h2>
            <div class="space-y-3">
                <?php foreach ($monthlyDonations as $month): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></span>
                        <span class="font-semibold">R<?php echo number_format($month['total'], 2); ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <?php $maxDonation = $monthlyDonations ? max(array_column($monthlyDonations, 'total')) : 1; ?>
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($month['total'] / $maxDonation) * 100; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($monthlyDonations)): ?>
                <p class="text-slate-500 text-center py-6">No donation data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>