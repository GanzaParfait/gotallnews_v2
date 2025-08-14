<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../php/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['videoId'])) {
        throw new Exception('Video ID is required');
    }

    $videoId = (int) $input['videoId'];
    $action = $input['action'] ?? 'start';  // start, update, complete
    $duration = $input['duration'] ?? 0;
    $watchPercentage = $input['watchPercentage'] ?? 0;

    // Get user info (if logged in)
    $userId = null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Check if user is logged in (you can implement your own session logic)
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    // Detect device type
    $deviceType = 'desktop';
    if (preg_match('/(android|iphone|ipad|mobile)/i', $userAgent)) {
        $deviceType = 'mobile';
    } elseif (preg_match('/(tablet|ipad)/i', $userAgent)) {
        $deviceType = 'tablet';
    }

    if ($action === 'start') {
        // Create new view record
        $sql = 'INSERT INTO short_video_views (VideoID, UserID, IPAddress, UserAgent, DeviceType, ViewStartTime) 
                VALUES (?, ?, ?, ?, ?, NOW())';

        $stmt = $con->prepare($sql);
        $stmt->bind_param('iisss', $videoId, $userId, $ipAddress, $userAgent, $deviceType);

        if ($stmt->execute()) {
            $viewId = $con->insert_id;

            // Store view ID in session for tracking
            if (!isset($_SESSION['video_views'])) {
                $_SESSION['video_views'] = [];
            }
            $_SESSION['video_views'][$videoId] = $viewId;

            echo json_encode([
                'success' => true,
                'viewId' => $viewId,
                'message' => 'View tracking started'
            ]);
        } else {
            throw new Exception('Failed to start view tracking');
        }
    } elseif ($action === 'update') {
        // Update existing view with duration
        if (isset($_SESSION['video_views'][$videoId])) {
            $viewId = $_SESSION['video_views'][$videoId];

            $sql = 'UPDATE short_video_views 
                    SET DurationWatched = ?, WatchPercentage = ?, ViewEndTime = NOW() 
                    WHERE ViewID = ?';

            $stmt = $con->prepare($sql);
            $stmt->bind_param('idi', $duration, $watchPercentage, $viewId);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'View updated'
                ]);
            } else {
                throw new Exception('Failed to update view');
            }
        } else {
            throw new Exception('No active view found for this video');
        }
    } elseif ($action === 'complete') {
        // Mark view as completed
        if (isset($_SESSION['video_views'][$videoId])) {
            $viewId = $_SESSION['video_views'][$videoId];

            $sql = 'UPDATE short_video_views 
                    SET DurationWatched = ?, WatchPercentage = ?, IsCompleted = 1, ViewEndTime = NOW() 
                    WHERE ViewID = ?';

            $stmt = $con->prepare($sql);
            $stmt->bind_param('idi', $duration, $watchPercentage, $viewId);

            if ($stmt->execute()) {
                // Update video views count
                $updateViews = 'UPDATE video_posts SET Views = Views + 1 WHERE VideoID = ?';
                $stmt2 = $con->prepare($updateViews);
                $stmt2->bind_param('i', $videoId);
                $stmt2->execute();

                // Remove from session
                unset($_SESSION['video_views'][$videoId]);

                echo json_encode([
                    'success' => true,
                    'message' => 'View completed'
                ]);
            } else {
                throw new Exception('Failed to complete view');
            }
        } else {
            throw new Exception('No active view found for this video');
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
