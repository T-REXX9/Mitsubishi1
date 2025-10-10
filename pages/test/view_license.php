<?php
// Include the session initialization file
include_once('../../includes/init.php');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	// Return a placeholder image
	header('Content-Type: image/png');
	$errorImage = imagecreatetruecolor(400, 200);
	$bgColor = imagecolorallocate($errorImage, 240, 240, 240);
	$textColor = imagecolorallocate($errorImage, 100, 100, 100);
	imagefill($errorImage, 0, 0, $bgColor);
	imagestring($errorImage, 5, 150, 90, 'Invalid Request', $textColor);
	imagepng($errorImage);
	imagedestroy($errorImage);
	exit();
}

$request_id = intval($_GET['id']);

try {
	// Fetch the driver's license BLOB data from test_drive_requests table
	$stmt = $pdo->prepare("SELECT drivers_license FROM test_drive_requests WHERE id = ?");
	$stmt->execute([$request_id]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($result && $result['drivers_license']) {
		// Get image info from BLOB
		$imageData = $result['drivers_license'];
		$imageInfo = getimagesizefromstring($imageData);

		if ($imageInfo !== false) {
			// Set appropriate content type
			header('Content-Type: ' . $imageInfo['mime']);
			header('Content-Length: ' . strlen($imageData));
			header('Cache-Control: private, max-age=3600');

			// Output the image
			echo $imageData;
		} else {
			// If not a valid image, show error image
			header('Content-Type: image/png');
			$errorImage = imagecreatetruecolor(400, 200);
			$bgColor = imagecolorallocate($errorImage, 240, 240, 240);
			$textColor = imagecolorallocate($errorImage, 100, 100, 100);
			imagefill($errorImage, 0, 0, $bgColor);
			imagestring($errorImage, 5, 120, 90, 'Invalid Image Format', $textColor);
			imagepng($errorImage);
			imagedestroy($errorImage);
		}
	} else {
		// No license found, show placeholder
		header('Content-Type: image/png');
		$placeholderImage = imagecreatetruecolor(400, 200);
		$bgColor = imagecolorallocate($placeholderImage, 245, 245, 245);
		$textColor = imagecolorallocate($placeholderImage, 150, 150, 150);
		imagefill($placeholderImage, 0, 0, $bgColor);
		imagestring($placeholderImage, 5, 100, 90, 'No License Available', $textColor);
		imagepng($placeholderImage);
		imagedestroy($placeholderImage);
	}
} catch (Exception $e) {
	// Error occurred, show error image
	header('Content-Type: image/png');
	$errorImage = imagecreatetruecolor(400, 200);
	$bgColor = imagecolorallocate($errorImage, 255, 230, 230);
	$textColor = imagecolorallocate($errorImage, 200, 50, 50);
	imagefill($errorImage, 0, 0, $bgColor);
	imagestring($errorImage, 5, 150, 90, 'Error Loading', $textColor);
	imagepng($errorImage);
	imagedestroy($errorImage);

	error_log("Error viewing license: " . $e->getMessage());
}
