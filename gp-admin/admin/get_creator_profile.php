<?php
include "php/header/top.php";
include "php/includes/CreatorProfileManager.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid profile ID'
    ]);
    exit;
}

$profileId = (int)$_GET['id'];

try {
    // Initialize the creator profile manager
    $creatorManager = new CreatorProfileManager($con);
    
    // Get the profile data
    $profile = $creatorManager->getProfile($profileId);
    
    if ($profile) {
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Profile not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
