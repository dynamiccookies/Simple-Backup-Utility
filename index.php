<?php
date_default_timezone_set('America/Chicago'); // Set timezone to CST

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

$message = '';
$messageColor = "#dc3545";
$current_dir = getcwd(); // Get current directory
$folders = get_sibling_folders($current_dir); // Get sibling folder names excluding current directory

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_name = preg_replace('/\s+/', '-', trim($_POST['folder_name'])); // Replace spaces with dashes
    
    if (isset($_POST['backup']) && in_array($_POST['backup'], $folders)) {
        $source = "../{$_POST['backup']}";
    } else {
        $message = 'Invalid backup folder selected.';
    }

    if (empty($message)) {
        $folder_name = $_POST['backup'] . '_' . $input_name;
        $destination = "../" . basename($current_dir) . "/" . $folder_name;

        if (is_dir($destination)) {
            $message = "The folder '$folder_name' already exists. Backup cannot be completed.";
        } else {
            $file_count = backup_folder($source, $destination);
            if ($file_count > 0) {
                $message = "The folder '$folder_name' has been created with $file_count files.";
                $messageColor = "#28a745";
            } else {
                $message = "Failed to create the folder '$folder_name'.";
            }
        }
    }

}

$backup_folders = get_backup_folders($current_dir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup Utility</title>
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
            background-color: rgba(255, 255, 255, 0.1);
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
            width: 50%;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f0f0f0;
        }
        table tr:nth-child(odd) {
            background-color: #e9ecef;
        }
        .divider {
            border-top: 1px solid #fff;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Backup Utility</h1>
        <form method="POST">
            <input type="text" id="folder_name" name="folder_name" placeholder="Backup Name" required>
            <div class="button-container">
                <?php foreach ($folders as $folder) : ?>
                    <button type="submit" name="backup" value="<?php echo $folder; ?>">Backup <?php echo ucfirst($folder); ?></button>
                <?php endforeach; ?>
            </div>
        </form>
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="divider"></div> <!-- Horizontal divider line -->
        <h2>Existing Backups</h2>
        <table>
            <tr>
                <th>Backup</th>
                <th>Created Date (CST)</th>
            </tr>
            <?php foreach ($backup_folders as $index => $folder): ?>
                <tr style="background-color: <?php echo $index % 2 == 0 ? '#e9ecef' : '#f0f0f0'; ?>">
                    <td><?php echo htmlspecialchars($folder['name']); ?></td>
                    <td><?php echo htmlspecialchars($folder['created_date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
