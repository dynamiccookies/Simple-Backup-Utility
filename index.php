<?php
// ****************************************************************************************
// * Set timezone to CST
// * Define constant for the current version
// ****************************************************************************************
date_default_timezone_set('America/Chicago');
define('CURRENT_VERSION', 'v0.1.6');

// ****************************************************************************************
// * Define variables for API URL, directories, and other data
// ****************************************************************************************
$apiUrl         = 'https://api.github.com/repos/dynamiccookies/Simple-Backup-Utility/releases';
$current_dir    = getcwd(); // Get current directory
$folders        = get_sibling_folders($current_dir); // Get sibling folder names excluding current directory
$latestVersion  = get_latest_release_tag($apiUrl);
$message        = '';
$messageColor   = "#dc3545";
$versionMessage = compare_versions(CURRENT_VERSION, $latestVersion);
$backup_folders = get_backup_folders($current_dir);
$colors         = [
    "blue", "blueviolet", "brown", "cadetblue", "chocolate", "crimson", "darkblue", 
    "darkcyan", "darkgray", "darkgreen", "darkmagenta", "darkolivegreen", "darkorchid", 
    "darkred", "darkslateblue", "darkslategray", "darkviolet", "deeppink", "dimgray", 
    "firebrick", "forestgreen", "gray", "green", "indianred", "magenta", "maroon", 
    "mediumblue", "mediumvioletred", "midnightblue", "navy", "orangered", "palevioletred", 
    "peru", "purple", "rebeccapurple", "red", "seagreen", "sienna", "slategray", "steelblue", 
    "teal", "tomato"
];
$random_color = $colors[array_rand($colors)];

// ****************************************************************************************
// * Define functions for backup, deletion, and version handling
// ****************************************************************************************
/**
 * Recursively copies files and directories from the source to the destination.
 *
 * @param string $source The source directory to back up.
 * @param string $destination The destination directory where the backup will be stored.
 * @return int The number of files copied.
 */
function backup_folder($source, $destination) {
    if (!is_dir($source)) {
        return 0;
    }
    @mkdir($destination, 0777, true);
    $files = array_diff(scandir($source), array('.', '..'));
    foreach ($files as $file) {
        $src = "$source/$file";
        $dst = "$destination/$file";
        if (is_dir($src)) {
            backup_folder($src, $dst);
        } else {
            copy($src, $dst);
        }
    }
    return count($files);
}

/**
 * Compares the current version with the latest version from GitHub.
 *
 * @param string $currentVersion The current version of the utility.
 * @param string $latestVersion The latest version from GitHub.
 * @return string A message indicating the version status.
 */
function compare_versions($currentVersion, $latestVersion) {
    // Remove 'v' prefix for comparison
    $currentVersionClean = ltrim($currentVersion, 'v');
    $latestVersionClean  = ltrim($latestVersion, 'v');
    
    // Compare versions and switch on the result
    switch (version_compare($currentVersionClean, $latestVersionClean)) {
        case -1:
            return "New version <a href='https://github.com/dynamiccookies/Simple-Backup-Utility/releases/tag/$latestVersion' target='_blank'>$latestVersion</a> available!";
        case 0:
            return $currentVersion;
        case 1:
            return "BETA-$currentVersion INSTALLED";
    }
}

/**
 * Recursively deletes a folder and its contents.
 *
 * @param string $folder The path of the folder to delete.
 * @return bool True if the folder was deleted, false otherwise.
 */
function delete_backup_folder($folder) {
    if (!is_dir($folder)) {
        return false;
    }
    $files = array_diff(scandir($folder), array('.', '..'));
    foreach ($files as $file) {
        $path = "$folder/$file";
        is_dir($path) ? delete_backup_folder($path) : unlink($path);
    }
    return rmdir($folder);
}

/**
 * Retrieves the list of backup folders in the current directory.
 *
 * @param string $dir The current directory path.
 * @return array The list of backup folders with their creation date and timestamp.
 */
function get_backup_folders($dir) {
    $folders = array_filter(glob($dir . '/*'), 'is_dir');
    $backup_folders = [];
    foreach ($folders as $folder) {
        $folder_name = basename($folder);
        $created_timestamp = filectime($folder); // Raw timestamp for sorting
        $created_date = date('m/d/y h:i:s A', $created_timestamp); // Human-readable format for display
        
        $backup_folders[] = [
            'name' => $folder_name,
            'created_date' => $created_date,
            'created_timestamp' => $created_timestamp // Add timestamp for sorting
        ];
    }
    // Sort by created_timestamp in descending order
    usort($backup_folders, function($a, $b) {
        return $b['created_timestamp'] - $a['created_timestamp'];
    });
    return $backup_folders;
}

/**
 * Gets the latest release tag from a GitHub repository.
 *
 * @param string $url The GitHub API URL to fetch the releases.
 * @return string The latest release tag name.
 */
function get_latest_release_tag($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'dynamiccookies/Simple-Backup-Utility');
    $response = curl_exec($ch);
    curl_close($ch);

    $releases = json_decode($response, true);
    return isset($releases[0]['tag_name']) ? $releases[0]['tag_name'] : '';
}

/**
 * Retrieves the names of sibling directories excluding the current directory.
 *
 * @param string $dir The current directory path.
 * @return array The list of sibling directory names.
 */
function get_sibling_folders($dir) {
    $parent_dir = dirname($dir);
    $folders = array_filter(glob($parent_dir . '/*'), 'is_dir');
    $sibling_folders = [];
    foreach ($folders as $folder) {
        $folder_name = basename($folder);
        if ($folder_name !== basename($dir)) {
            $sibling_folders[] = $folder_name;
        }
    }
    return $sibling_folders;
}

// ****************************************************************************************
// * Handle POST requests for folder deletion and backup creation
// ****************************************************************************************
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a delete action is requested
    if (isset($_POST['delete'])) {
        // Construct the path to the folder to delete
        $folder_to_delete = $current_dir . '/' . $_POST['delete'];
        
        // Attempt to delete the folder
        if (delete_backup_folder($folder_to_delete)) {
            // Set success message if deletion is successful
            $message = "The folder '{$_POST['delete']}' has been deleted.";
            $messageColor = "#28a745"; // Green color for success message
        } else {
            // Set error message if deletion fails
            $message = "Failed to delete the folder '{$_POST['delete']}'.";
        }
    // Process backup creation if 'delete' action is not requested
    } else {
        
        // Sanitize and prepare the backup folder name
        $input_name = preg_replace('/\s+/', '-', trim($_POST['folder_name'])); // Replace spaces with dashes
        
        // Check if a valid backup folder is selected
        if (isset($_POST['backup']) && in_array($_POST['backup'], $folders)) {
            // Define the source path for backup
            $source = "../{$_POST['backup']}";
        } else {
            // Set error message for invalid backup folder selection
            $message = 'Invalid backup folder selected.';
        }

        // Proceed if no error message is set
        if (empty($message)) {
            // Define the destination folder name and path
            $folder_name = $_POST['backup'] . '_' . $input_name;
            $destination = "../" . basename($current_dir) . "/" . $folder_name;

            // Check if the destination folder already exists
            if (is_dir($destination)) {
                // Set error message if the destination folder already exists
                $message = "The folder '$folder_name' already exists. Backup cannot be completed.";
            } else {
                // Perform the backup operation
                $file_count = backup_folder($source, $destination);
                
                // Check if files are successfully backed up
                if ($file_count > 0) {
                    // Set success message if backup operation is successful
                    $message = "The folder '$folder_name' has been created with $file_count files.";
                    $messageColor = "#28a745"; // Green color for success message
                } else {
                    // Set error message if backup operation fails
                    $message = "Failed to create the folder '$folder_name'.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup Utility</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #fff;
            border-radius: 10px;
            background-color: <?php echo $random_color; ?>;
        }
        h1, h2 {
            color: #fff;
        }
        form {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input[type="text"] {
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 75%;
            box-sizing: border-box;
            margin-bottom: 10px;
            text-align: center;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .button-container button, .message {
            box-shadow: 0 8px 8px 1px rgba(0, 0, 0, .2);
            font-weight: bold;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #007bff;
            color: #fff;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            background-color: <?php echo "$messageColor"; ?>;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #fff;
            padding: 10px;
            color: #000;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: snow;
        }
        table tr:nth-child(odd) {
            background-color: lightgray;
        }
        table th:nth-child(1), table th:nth-child(2),
        table td:nth-child(1), table td:nth-child(2) {
            width: 45%;
        }
        table th:nth-child(3), table td:nth-child(3) {
            width: 10%;
        }
        .divider {
            border-top: 1px solid #fff;
            margin: 20px 0;
        }
        .inline-form {
            display: inline;
        }
        .trash-icon {
            cursor: pointer;
            background: none;
            border: none;
            color: #dc3545;
            font-size: 16px;
        }
        .trash-icon:hover {
            color: #c82333;
        }
        .version-info {
            position: fixed;
            bottom: 0;
            right: 0;
            margin: 10px;
            font-size: small;
        }
		.version-info a {
			color: yellow;
			font-weight: bold;
		}
    </style>
    <script>
	    // Function to confirm folder deletion and submit the form
        function confirmDelete(folderName, formId) {
            if (confirm(`Are you sure you want to delete the folder "${folderName}"?`)) {
                document.getElementById(formId).submit();
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Backup Utility</h1>

        <!-- Backup form for creating new backups -->
        <form method="POST">
            <input type="text" id="folder_name" name="folder_name" placeholder="Backup Name" required>
            <div class="button-container">
                <?php foreach ($folders as $folder) : ?>
                    <button type="submit" name="backup" value="<?php echo $folder; ?>">Backup <?php echo ucfirst($folder); ?></button>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- Display message if there is any -->
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Horizontal divider line -->
        <div class="divider"></div>

        <h2>Existing Backups</h2>

        <!-- Table displaying existing backups -->
        <table>
            <tr>
                <th>Backup</th>
                <th>Created Date (CST)</th>
                <th>Delete</th>
            </tr>
            <?php foreach ($backup_folders as $index => $folder): ?>
                <tr>
                    <td><?php echo htmlspecialchars($folder['name']); ?></td>
                    <td><?php echo htmlspecialchars($folder['created_date']); ?></td>
                    <td>
                        <!-- Form for deleting a backup folder -->
                        <form method="POST" class="inline-form" id="delete-form-<?php echo $index; ?>">
                            <input type="hidden" name="delete" value="<?php echo htmlspecialchars($folder['name']); ?>">
                            <button type="button" class="trash-icon" onclick="confirmDelete('<?php echo htmlspecialchars($folder['name']); ?>', 'delete-form-<?php echo $index; ?>')">
                                <i class="fa fa-trash"></i> <!-- Trash icon for delete button -->
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Display version information at the bottom right corner -->
    <div class="version-info"><?php echo $versionMessage; ?></div>
</body>

</html>
