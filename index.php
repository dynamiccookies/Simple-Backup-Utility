<?php

/**
 * Define constant for the current version
 */

define('CURRENT_VERSION', 'v1.2.1');

/******************************************************************************/

/**
 * Define variables for API URL, directories, and other data
 */

$api_url         = 'https://api.github.com/repos/dynamiccookies/Simple-Backup-Utility/releases';
$colors          = [
    'blue', 'blueviolet', 'brown', 'cadetblue', 'chocolate', 'crimson', 
    'darkblue', 'darkcyan', 'darkgray', 'darkgreen', 'darkmagenta', 
    'darkolivegreen', 'darkorchid', 'darkred', 'darkslateblue', 
    'darkslategray', 'darkviolet', 'deeppink', 'dimgray', 'firebrick', 
    'forestgreen', 'gray', 'green', 'indianred', 'magenta', 'maroon', 
    'mediumblue', 'mediumvioletred', 'midnightblue', 'navy', 'orangered', 
    'palevioletred', 'peru', 'purple', 'rebeccapurple', 'red', 'seagreen', 
    'sienna', 'slategray', 'steelblue', 'teal', 'tomato'
];
$green           = '#28a745';
$latest_version  = get_latest_release(
    $api_url, 
    'dynamiccookies/Simple-Backup-Utility'
);
$message_color   = '';
$message_text    = '';
$random_color    = $colors[array_rand($colors)];
$red             = '#dc3545';
$release_url     = 'https://github.com/dynamiccookies/Simple-Backup-Utility/releases/tag/'
    . $latest_version;
$sibling_folders = get_sibling_folders(__DIR__);
$version_message = compare_versions(
    CURRENT_VERSION, 
    $latest_version, 
    $release_url
);

/******************************************************************************/

/**
 * Define functions for backup, deletion, and version handling
 */

/**
 * Recursively copies files and directories from the source to the destination.
 *
 * @param string $source The source directory to back up.
 * @param string $destination The directory where the backup will be stored.
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

function compare_versions($current_version, $latest_version, $release_url) {
    // Switch on the compared versions with the 'v' prefix removed
    switch (
        version_compare(
            ltrim($current_version, 'v'), 
            ltrim($latest_version, 'v')
        )
    ) {
        case -1:
            return "New version <a href='" . $release_url
                . "' class='update' title='Release Notes' target='_blank'>"
                . $latest_version
                . "</a> available! (<a href='#' class='update' title='Install Now' onclick='triggerUpdate(); return false;'>Update Now</a>)";

        case 0:
            return "<a href='" . $release_url
                . "' class='version-link' title='Release Notes' target='_blank'>"
                . $current_version . '</a>';

        case 1:
            return 'BETA-' . $current_version . ' INSTALLED';
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
        $path = $delete_folder . '/' . $file;
        is_dir($path) ? delete_backup_folder($path) : unlink($path);
    }

    return rmdir($delete_folder);
}

/**
 * Retrieves the list of backup folders in the current directory.
 *
 * @param string $dir The current directory path.
 * @return array The list of backup folders with their creation date.
 */

function get_backup_folders($dir) {
    $backup_folders = array_filter(glob($dir . '/*'), 'is_dir');
    $folder_details = [];

    foreach ($backup_folders as $folder) {
        $folder_details[] = [
            'name'         => basename($folder),
            'created_date' => date('c', filectime($folder))
        ];
    }

    // Sort by created_date in descending order
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

function get_latest_release($url, $repo) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $repo);
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

/**
 * Generates HTML for displaying sibling folders as columns of checkboxes.
 *
 * @param array $sibling_folders An array of sibling folder names.
 * @return string The generated HTML string with checkbox columns.
 */

function print_columns($sibling_folders) {

    // Get folder count, set max columns, and calc actual columns
    $folders_count = count($sibling_folders);
    $html          = '';
    $max_columns   = 4;
    $table_columns = min($max_columns, $folders_count);

    // Print columns
    for ($i = 0; $i < $table_columns; $i++) {
        $html .= "\n\t\t\t\t<div class='checkbox-column'>";
        for ($j = $i; $j < $folders_count; $j += $table_columns) {
            $html .= "\n\t\t\t\t\t" . '<label for="backup_'
                . $sibling_folders[$j] . '">';
            $html .= '<input type="checkbox" id="backup_' . $sibling_folders[$j]
                . '" name="backup_folders[]" value="' . $sibling_folders[$j]
                . '">';
            $html .= $sibling_folders[$j];
            $html .= '</label><br>';
        }
        $html .= "\n\t\t\t\t</div>\n";
    }

    return $html;
}

/**
 * Generates HTML to display a message if the message text is provided.
 *
 * @param string $message_text The text of the message to display.
 * @return string The generated HTML string with the message or an empty string.
 */

function show_message($message_text) {

    // Return HTML message if $message_text contains data
    if ($message_text) {
        return "<div id='message' class='message'>" . $message_text . "</div>\n";
    }

    return '';
}

/******************************************************************************/

/**
 * Handle POST requests for backup folder creation, folder deletion, and 
 * updating the application
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if a backup action is requested
    if (isset($_POST['backup'])) {

        // Check if any folders are selected for backup
        if (
            !isset($_POST['backup_folders']) || empty($_POST['backup_folders'])
        ) {

            // Set error message if no folders were selected
            $message_color = $red;
            $message_text  = 'No folders selected for backup.';

        } else {

            // Loop through selected folders and initiate backup
            foreach ($_POST['backup_folders'] as $selected_folder) {

                // Define the source path for backup
                $source = '../' . $selected_folder;

                // Define the destination folder name and path
                $folder_name = $selected_folder . '_'
                    . preg_replace('/\s+/', '-', trim($_POST['folder_name']));

                // Check if the destination folder already exists
                if (is_dir($folder_name)) {

                    // Set error message if the destination folder already exists
                    $message_color = $red;
                    $message_text .= "The folder '" . $folder_name
                        . "' already exists. Backup cannot be completed.<br>";

                } else {

                    // Perform the backup operation
                    $file_count = backup_folder($source, $folder_name);

                    // Check if files are successfully backed up
                    if ($file_count > 0) {

                        // If $message_color is not already $red, set to $green
                        if (message_color != $red) {
                            $message_color = $green;
                        }
                        
                        // Set success message if backup operation is successful
                        $message_text .= "The folder '" . $folder_name
                            . "' has been created with " . ($file_count - 1)
                            . ' files/folders.<br>';

                    } else {

                        // Set error message if backup operation fails
                        $message_color = $red;
                        $message_text .= "ERROR: '" . $folder_name
                            . "' is not a valid directory!<br>";
                    }
                }
            }
        }

    // Check if a delete action is requested
    } elseif (isset($_POST['delete'])) {

        if (!is_array($_POST['delete'])) {
            $_POST['delete'] = json_decode($_POST['delete'], true);
        }

        // Loop through each folder selected
        foreach ($_POST['delete'] as $dir) {

            // Construct the path to the folder and attempt to delete it
            if (delete_backup_folder(__DIR__ . '/' . $dir)) {

                // If $message_color is not already $red, set to $green
                if (message_color != $red) {
                    $message_color = $green;
                }

                // Set success message if deletion is successful
                $message_text  .= "Folder '" . $dir . "' has been deleted.<br>";

            } else {

                // Set error message if deletion fails
                $message_color = $red;
                $message_text .= "Failed to delete folder '" . $dir . "'.<br>";
            }

        }

    // Check if an update action is requested
    } elseif (isset($_POST['update'])) {

        // Fetch release information from GitHub API
        $release_info = file_get_contents(
            $api_url, 
            false, 
            stream_context_create(
                ['http' => ['method' => 'GET','header' => 'User-Agent: PHP']]
            )
        );

        // Download the release and save the zip file to disk
        file_put_contents(
            basename(__FILE__), 
            file_get_contents(
                json_decode(
                    $release_info, 
                    true
                )[0]['assets'][0]['browser_download_url']
            )
        );

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();

    } else {
        $message_color = $red;
        $message_text  = 'How did you get here?';
    }
}

// Set $backup_folders after folder backup or delete
$backup_folders = get_backup_folders(__DIR__);
/******************************************************************************/
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
        button.backup {
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
        .hidden {
            display: none;
        }
        .version-info {
            position: fixed;
            bottom: 0;
            right: 0;
            margin: 10px;
            font-size: small;
        }
        .version-info a.update {
            color: yellow;
            font-weight: bold;
        }
        .version-info a.version-link, 
        .version-info a.version-link:visited,
        .version-info a.version-link:hover,
        .version-info a.version-link:active {
            color: white;
            text-decoration: none;
        }
       <?php
            //Conditionally add message CSS
            if ($message_text) {
        ?>

        .message {
            background-color: <?= $message_color; ?>;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: opacity 2s ease-out;
            box-shadow: 0 8px 8px 1px rgba(0, 0, 0, .2);
            font-weight: bold;
        }
        .fade-out {
            opacity: 0;
        }
        <?php 
            }

            // Conditionally add Existing Backups CSS
            if (!empty($backup_folders)) { 
        ?>

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
            height: 38px;
        }
        table tr:nth-child(even) {
            background-color: snow;
        }
        table tr:nth-child(odd) {
            background-color: lightgray;
        }
        table th:nth-child(1), table td:nth-child(1) {
            min-width: 54px;
        }
        table th:nth-child(2), table th:nth-child(3), table th:nth-child(4),
        table td:nth-child(2), table td:nth-child(3), table td:nth-child(4) {
            width: 27%;
        }
        table th:nth-child(5), table td:nth-child(5) {
            width: 10%;
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
        <?php } ?>

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

                // Test the container's width
                if (container.clientWidth < container.scrollWidth) {
                    container.style.minWidth = container.scrollWidth + 'px';
                }
            }
        });

        // Show trash icon in column header when 2+ checkboxes checked
        document.addEventListener('DOMContentLoaded', (event) => {
            const checkboxes = document.querySelectorAll('.row-checkbox')
            const button     = document.getElementById('multi-delete');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                    if (checkedCheckboxes.length >= 2) {
                        button.classList.remove('hidden');
                    } else {
                        button.classList.add('hidden');
                    }
                });
            });
        });

        // Function to trigger deleting multiple folders
        function triggerMultiDelete() {
            if (confirm('Are you sure you want to delete the selected folders?')) {
                const checkboxes = document.querySelectorAll('td input[type="checkbox"]:checked');
                const folders    = Array.from(checkboxes).map(cb => cb.value);

                const form  = document.createElement('form');
                form.method = 'post';
                form.action = '.';

                const updateInput = document.createElement('input');
                updateInput.type  = 'hidden';
                updateInput.name  = 'delete';
                updateInput.value = JSON.stringify(folders);

                form.appendChild(updateInput);
                document.body.appendChild(form);
                form.submit();
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
        <?php
            // Conditionally add Existing Backups JavaScript
            if (!empty($backup_folders)) { 
        ?>

        // Function to confirm folder deletion and submit the form
        function confirmSingleDelete(folderName, formId) {
            if (confirm('Are you sure you want to delete the folder "' + folderName + '"?')) {
                document.getElementById(formId).submit();
            }
        }

        // Function to convert ISO 8601 date to local timezone and format
        function convertToLocalTime(isoDate) {
            const date = new Date(isoDate);
            return date.toLocaleString().replace(',', '');
        }

        // Convert all dates in the table to local timezone
        window.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('.created-date').forEach(element => {
                const isoDate       = element.getAttribute('data-iso-date');
                element.textContent = convertToLocalTime(isoDate);
            });
        });
        <?php
            }

            // Conditionally add message div JavaScript
            if ($message_text) {
        ?>

        // Show message for 10 seconds
        setTimeout(function() {
            var messageDiv = document.getElementById('message');
            messageDiv.classList.add('fade-out');

            // Hide message container after 2 second fade out
            setTimeout(function() {
                messageDiv.style.display = 'none';
            }, 2000);
        }, 10000);
        <?php } ?>

    </script>
</head>
<body>
    <div class='container'>
        <h1>Backup Utility</h1>

        <!-- Backup form for creating new backups -->
        <form method='POST'>
            <input type='text' id='folder_name' name='folder_name' placeholder='Backup Name' required>
            <div class='checkbox-columns'>
                <?= print_columns($sibling_folders); ?>
            </div>
            <button type='submit' name='backup' class='backup'>Backup Selected Folders</button>
        </form>

        <!-- Display message if there is any -->
        <?= show_message($message_text); ?>

        <!-- Horizontal divider line -->
        <div class='divider'></div>
        <?php
            if (empty($backup_folders)) {
                echo '<h2>No Backups Found</h2>';
            } else {
        ?>

        <h2>Existing Backups</h2>

        <!-- Table displaying existing backups -->
        <table>
            <tr>
                <th>
                    <button id='multi-delete' class='hidden' onclick='triggerMultiDelete()'>
                        <i class='fa fa-trash'></i>
                    </button>
                </th>
                <th>Name</th>
                <th>Description</th>
                <th>Created Date</th>
                <th>Delete</th>
            </tr>
            <?php
                foreach ($backup_folders as $index => $folder) {
                    $folder_name  = htmlspecialchars($folder['name']);
                    $created_date = htmlspecialchars($folder['created_date']);
            ?>

            <tr>
                <td><input type='checkbox' name='delete[]' class='row-checkbox' value='<?= $folder_name; ?>'></td>
                <td><?= explode('_', $folder_name, 2)[0]; ?></td>
                <td><?= explode('_', $folder_name, 2)[1]; ?></td>
                <td class='created-date' data-iso-date='<?= $folder['created_date']; ?>'><?= $created_date; ?></td>
                <td>
                    <form method='POST' class='inline-form' id='delete-form-<?= $index; ?>'>
                        <input type='hidden' name='delete[]' value='<?= $folder_name; ?>'>
                        <button type='button' class='trash-icon' onclick="confirmSingleDelete('<?= $folder_name; ?>', 'delete-form-<?= $index; ?>')">
                            <i class='fa fa-trash'></i> <!-- Trash icon for delete button -->
                        </button>
                    </form>
                </td>
            </tr>
            <?php } ?>

        </table>
        <?php } ?>

    </div>

    <!-- Display version information -->
    <div class='version-info'><?= $version_message; ?></div>
    
    <!--
    Simple Backup Utility
    Repository: https://github.com/dynamiccookies/Simple-Backup-Utility
    License: MIT
    -->
</body>
</html>
