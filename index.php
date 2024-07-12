<?php

// ****************************************************************************************
// * Set timezone to CST
// * Define constant for the current version
// ****************************************************************************************
define('CURRENT_VERSION', 'v1.0.0');

// ****************************************************************************************
// * Define variables for API URL, directories, and other data
// ****************************************************************************************
$api_url         = 'https://api.github.com/repos/dynamiccookies/Simple-Backup-Utility/releases';
$colors          = [
    'blue', 'blueviolet', 'brown', 'cadetblue', 'chocolate', 'crimson', 'darkblue', 
    'darkcyan', 'darkgray', 'darkgreen', 'darkmagenta', 'darkolivegreen', 'darkorchid', 
    'darkred', 'darkslateblue', 'darkslategray', 'darkviolet', 'deeppink', 'dimgray', 
    'firebrick', 'forestgreen', 'gray', 'green', 'indianred', 'magenta', 'maroon', 
    'mediumblue', 'mediumvioletred', 'midnightblue', 'navy', 'orangered', 'palevioletred', 
    'peru', 'purple', 'rebeccapurple', 'red', 'seagreen', 'sienna', 'slategray', 'steelblue', 
    'teal', 'tomato'
];
$current_dir     = getcwd(); // Get current directory
$green           = '#28a745';
$latest_version  = get_latest_release_tag($api_url);
$message_color   = '#dc3545'; // Red by default
$message_text    = '';
$random_color    = $colors[array_rand($colors)];
$sibling_folders = get_sibling_folders($current_dir); // Get sibling folder names excluding current directory
$version_message = compare_versions(CURRENT_VERSION, $latest_version);

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
    $count = 1;
    $files = array_diff(scandir($source), array('.', '..'));

    foreach ($files as $file) {
        $src = $source . '/' . $file;
        $dst = $destination . '/' . $file;
        if (is_dir($src)) {
            $count += backup_folder($src, $dst);
        } else {
            copy($src, $dst);
            $count ++;
        }
    }
    return $count;
}

/**
 * Compares the current version with the latest version from GitHub.
 *
 * @param string $current_version The current version of the utility.
 * @param string $latest_version The latest version from GitHub.
 * @return string A message indicating the version status.
 */
function compare_versions($current_version, $latest_version) {
    // Switch on the compared versions with the 'v' prefix removed
    switch (version_compare(ltrim($current_version, 'v'), ltrim($latest_version, 'v'))) {
        case -1:
            $release_url = 'https://github.com/dynamiccookies/Simple-Backup-Utility/releases/tag/$latest_version';
            return "New version <a href='" . $release_url . "' target='_blank'>" . $latest_version . "</a> available! (<a href='#' onclick='triggerUpdate(); return false;'>Update Now</a>)";
        case 0:
            return $current_version;
        case 1:
            return 'BETA-' . $current_version . 'INSTALLED';
    }
}

/**
 * Recursively deletes a folder and its contents.
 *
 * @param string $delete_folder The path of the folder to delete.
 * @return bool True if the folder was deleted, false otherwise.
 */
function delete_backup_folder($delete_folder) {
    if (!is_dir($delete_folder)) {
        return false;
    }
    $files = array_diff(scandir($delete_folder), array('.', '..'));
    foreach ($files as $file) {
        $path = $folder . '/' . $file;
        is_dir($path) ? delete_backup_folder($path) : unlink($path);
    }
    return rmdir($delete_folder);
}

/**
 * Retrieves the list of backup folders in the current directory.
 *
 * @param string $dir The current directory path.
 * @return array The list of backup folders with their creation date and timestamp.
 */
function get_backup_folders($dir) {
    $backup_folders = array_filter(glob($dir . '/*'), 'is_dir');
    $folder_details = [];

    foreach ($backup_folders as $folder) {
        $created_date     = date('c', filectime($folder)); // ISO 8601 format for JavaScript conversion
        $folder_details[] = [
            'name'         => basename($folder),
            'created_date' => $created_date
        ];
    }

    // Sort by created_timestamp in descending order
    usort($folder_details, function($a, $b) {
        return strtotime($b['created_date']) - strtotime($a['created_date']);
    });
    return $folder_details;
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
    $folder_names    = [];
    $sibling_folders = array_filter(glob(dirname($dir) . '/*'), 'is_dir');

    foreach ($sibling_folders as $folder) {
        $folder = basename($folder);
        if ($folder !== basename($dir)) {
            $folder_names[] = $folder;
        }
    }

    usort($folder_names, function($a, $b) {
        return strcasecmp($a, $b);
    });

    return $folder_names;
}

// ****************************************************************************************
// * Handle POST requests for backup creation, folder deletion, and updating application
// ****************************************************************************************
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if a delete action is requested
    if (isset($_POST['delete'])) {
        // Construct the path to the folder and attempt to delete it
        if (delete_backup_folder($current_dir . '/' . $_POST['delete'])) {
            // Set success message if deletion is successful
            $message_text  = "The folder '" . $_POST['delete'] . "' has been deleted.";
            $message_color = $green;
        } else {
            // Set error message if deletion fails
            $message_text = "Failed to delete the folder '" . $_POST['delete'] . "'.";
        }

    // Check if an update action is requested
    } elseif (isset($_POST['update'])) {

        // Fetch release information from GitHub API
        $release_info = file_get_contents($api_url, false, stream_context_create(['http' => ['method' => 'GET','header' => 'User-Agent: PHP']]));

        // Download the release and save the zip file to disk
        file_put_contents(basename(__FILE__), file_get_contents(json_decode($release_info, true)[0]['assets'][0]['browser_download_url']));

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();

    // Check if a backup action is requested
    } elseif (isset($_POST['backup'])) {

        // Check if any folders are selected for backup
        if (!isset($_POST['backup_folders']) || empty($_POST['backup_folders'])) {
            $message_text = 'No folders selected for backup.';
        } else {
            // Loop through selected folders and initiate backup
            foreach ($_POST['backup_folders'] as $selected_folder) {
                // Define the source path for backup
                $source = '../' . $selected_folder;

                // Define the destination folder name and path
                $folder_name = $selected_folder . '_' . preg_replace('/\s+/', '-', trim($_POST['folder_name']));

                // Check if the destination folder already exists
                if (is_dir($folder_name)) {
                    // Set error message if the destination folder already exists
                    $message_text .= "The folder '" . $folder_name . "' already exists. Backup cannot be completed.<br>";
                } else {
                    // Perform the backup operation
                    $file_count = backup_folder($source, $folder_name);

                    // Check if files are successfully backed up
                    if ($file_count > 0) {
                        // Set success message if backup operation is successful
                        $message_text .= "The folder '" . $folder_name . "' has been created with " . ($file_count - 1) . ' files/folders.<br>';
                        $message_color = $green;
                    } else {
                        // Set error message if backup operation fails
                        $message_text .= "ERROR: '" . $folder_name . "' is not a valid directory!<br>";
                    }
                }
            }
        }
    } else {
        $message_text = 'How did you get here?';
    }
}

?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Backup Utility</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .container {
            max-width: 90%;
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #fff;
            border-radius: 10px;
            background-color: <?= $random_color; ?>;
            display: inline-block;
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
        input[type='text'] {
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
        button.backup, .message {
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
            background-color: <?= $message_color; ?>;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: opacity 2s ease-out;
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
            white-space: nowrap;
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
        table th:nth-child(1), table th:nth-child(2), table th:nth-child(3),
        table td:nth-child(1), table td:nth-child(2), table td:nth-child(3) {
            width: 30%;
        }
        table th:nth-child(4), table td:nth-child(4) {
            width: 10%;
        }
        .checkbox-columns {
            display: flex;
            gap: 20px;
            text-align: left;
            margin: 25px 25px 15px 25px;
        }
        .checkbox-column label {
            display: block;
            white-space: nowrap;
            margin-bottom: 5px;
        }
        .divider {
            border-top: 1px solid #fff;
            margin: 20px 0;
        }
        .fade-out {
            opacity: 0;
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
        // Set container's min-width based on the content's width
        document.addEventListener('DOMContentLoaded', function() {

            // Select the container element
            var container = document.querySelector('.container');

            // Set the initial width of the container
            updateContainerWidth();

            // Listen for window resize events to adjust the width
            window.addEventListener('resize', updateContainerWidth);

            // Update the container's min-width based on its scrollWidth.
            function updateContainerWidth() {
                if (container.clientWidth + 42 < container.scrollWidth + 40) {
                    container.style.minWidth = container.scrollWidth + 'px';
                }
            }
        });

        // Hide message after 10 seconds
        setTimeout(function() {
            var messageDiv = document.getElementById('message');
            if (messageDiv) {
                messageDiv.classList.add('fade-out');
                setTimeout(function() {
                    messageDiv.style.display = 'none';
                }, 2000);
            }
        }, 10000);

        // Function to confirm folder deletion and submit the form
        function confirmDelete(folderName, formId) {
            if (confirm('Are you sure you want to delete the folder "' + folderName + '"?')) {
                document.getElementById(formId).submit();
            }
        }

        // Function to trigger update from GitHub repository's latest release
        function triggerUpdate() {
            const form  = document.createElement('form');
            form.method = 'post';
            form.action = '.';

            const updateInput = document.createElement('input');
            updateInput.type  = 'hidden';
            updateInput.name  = 'update';
            updateInput.value = 'true';

            form.appendChild(updateInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Function to convert ISO 8601 date to local timezone and format
        function convertToLocalTime(isoDate) {
            const date = new Date(isoDate);
            return date.toLocaleString().replace(',', '');
        }

        // Convert all dates in the table to local timezone
        window.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('.created-date').forEach(element => {
                const isoDate = element.getAttribute('data-iso-date');
                element.textContent = convertToLocalTime(isoDate);
            });
        });
    </script>
</head>
<body>
    <div class='container'>
        <h1>Backup Utility</h1>

        <!-- Backup form for creating new backups -->
        <form method='POST'>
            <input type='text' id='folder_name' name='folder_name' placeholder='Backup Name' required>
            <div class='checkbox-columns'><?php
                $folders_count = count($sibling_folders);
                $max_columns   = 4; // Maximum number of columns
                $table_columns = min($max_columns, $folders_count); // Calculate number of columns

                // Print columns
                for ($i = 0; $i < $table_columns; $i++) {
                    echo "\n\t\t\t\t<div class='checkbox-column'>";
                    for ($j = $i; $j < $folders_count; $j += $table_columns) {
                        echo "\n\t\t\t\t\t" . '<label for="backup_' . $sibling_folders[$j] . '">';
                        echo '<input type="checkbox" id="backup_' . $sibling_folders[$j] . '" name="backup_folders[]" value="' . $sibling_folders[$j] . '">';
                        echo $sibling_folders[$j];
                        echo '</label><br>';
                    }
                    echo "\n\t\t\t\t</div>";
                }
                ?>

            </div>
            <button type='submit' name='backup' class='backup'>Backup Selected Folders</button>
        </form>

        <!-- Display message if there is any -->
        <?php if ($message_text) echo "<div id='message' class='message'>" . $message_text . '</div>'; ?>

        <!-- Horizontal divider line -->
        <div class='divider'></div>

        <?php
            $backup_folders = get_backup_folders($current_dir);

            if (empty($backup_folders)) {
                echo '<h2>No Backups Found</h2>';
            } else {
        ?>

        <h2>Existing Backups</h2>

        <!-- Table displaying existing backups -->
        <table>
            <tr>
                <th>Backup Folder</th>
                <th>Description</th>
                <th>Created Date</th>
                <th>Delete</th>
            </tr>
            <?php
                foreach ($backup_folders as $index => $folder):
            ?><tr>
                <td><?= htmlspecialchars(explode('_', $folder['name'], 2)[0]); ?></td>
                <td><?= htmlspecialchars(explode('_', $folder['name'], 2)[1]); ?></td>
                <td class='created-date' data-iso-date='<?= $folder['created_date']; ?>'><?= $folder['created_date']; ?></td>
                <td>
                    <form method='POST' class='inline-form' id='delete-form-<?= $index; ?>'>
                        <input type='hidden' name='delete' value='<?= htmlspecialchars($folder['name']); ?>'>
                        <button type='button' class='trash-icon' onclick="confirmDelete('<?= htmlspecialchars($folder['name']); ?>', 'delete-form-<?= $index; ?>')">
                            <i class='fa fa-trash'></i> <!-- Trash icon for delete button -->
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>

        </table>
        <?php } ?>
    </div>

    <!-- Display version information -->
    <div class='version-info'><?= $version_message; ?></div>
</body>
</html>
