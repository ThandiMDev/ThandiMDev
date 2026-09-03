<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';
$tracking_number = '';

function generateTrackingNumber() {
    return 'CF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $infrastructure_type = $_POST['infrastructure_type'];
    $community_name = $_POST['community_name'];
    $description = $_POST['description'];
    $gps_location = $_POST['gps_location'];
    $severity_level = $_POST['severity_level'];
    $user_id = $_SESSION['user_id'] ?? null;
    $tracking_number = generateTrackingNumber();
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $image_path = 'uploads/' . time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path);
    }
    
    $stmt = $pdo->prepare("INSERT INTO reports (tracking_number, infrastructure_type, community_name, description, image_path, gps_location, severity_level, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$tracking_number, $infrastructure_type, $community_name, $description, $image_path, $gps_location, $severity_level, $user_id])) {
        $success = "Report submitted successfully! Your tracking number is: <strong>$tracking_number</strong>";
        
        // Create notification for admin
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (1, 'status', 'New Report Submitted', 'New infrastructure report in $community_name')");
        $notifStmt->execute();
    } else {
        $error = "Error submitting report. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Issue - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <a href="../index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6">← Back to Home</a>
        
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Report Infrastructure Issue</h2>
            <p class="text-slate-600 mb-6">Help us identify and fix infrastructure problems in your community</p>
            
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-6 mb-6 text-center">
                    <div class="text-5xl mb-3">✅</div>
                    <p class="text-lg font-semibold mb-2"><?php echo $success; ?></p>
                    <p class="text-sm">You will be notified when action is taken on this report.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-6"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Infrastructure Type *</label>
                    <select name="infrastructure_type" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <option value="">Select type</option>
                        <option value="borehole">Borehole</option>
                        <option value="water_tank">Water Tank</option>
                        <option value="pipeline">Pipeline</option>
                        <option value="toilet_facility">Toilet Facility</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Community Name *</label>
                    <input type="text" name="community_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Severity Level *</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex items-center justify-center p-3 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                            <input type="radio" name="severity_level" value="low" class="sr-only"> Low
                        </label>
                        <label class="flex items-center justify-center p-3 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                            <input type="radio" name="severity_level" value="medium" class="sr-only" checked> Medium
                        </label>
                        <label class="flex items-center justify-center p-3 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                            <input type="radio" name="severity_level" value="high" class="sr-only"> High
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description of Issue *</label>
                    <textarea name="description" rows="5" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 border border-slate-200 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">GPS Location</label>
                    <div class="flex gap-3">
                        <input type="text" name="gps_location" id="gps_location" placeholder="Latitude, Longitude" class="flex-1 px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <button type="button" onclick="getLocation()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">📍 Capture GPS</button>
                    </div>
                </div>
                
                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Report</button>
            </form>
        </div>
    </div>
    
    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    document.getElementById('gps_location').value = `${position.coords.latitude}, ${position.coords.longitude}`;
                    alert('GPS location captured!');
                }, () => alert('Unable to get location'));
            } else {
                alert('Geolocation not supported');
            }
        }
    </script>
</body>
</html>