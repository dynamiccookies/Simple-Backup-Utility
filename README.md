# Simple Backup Utility
![GitHub](https://img.shields.io/github/license/dynamiccookies/Simple-Backup-Utility?style=for-the-badge "License")
![GitHub file size in bytes](https://img.shields.io/github/size/dynamiccookies/Simple-Backup-Utility/index.php?style=for-the-badge)
![GitHub Release Date](https://img.shields.io/github/release-date/dynamiccookies/Simple-Backup-Utility?style=for-the-badge "Release Date")
![GitHub release (latest SemVer including pre-releases)](https://img.shields.io/github/v/release/dynamiccookies/Simple-Backup-Utility?display_name=tag&include_prereleases&sort=semver&style=for-the-badge "Release Version")
[<img alt="Deployed with FTP Deploy Action" src="https://img.shields.io/badge/Deployed With-FTP DEPLOY ACTION-%3CCOLOR%3E?style=for-the-badge&color=0077b6">](https://github.com/SamKirkland/FTP-Deploy-Action)

Simple Backup Utility is a PHP script that allows you to create and manage backups of sibling folders within the same directory.

## Features

- **Backup Creation**: Easily create backups of specific folders.
- **Multiple Folder Backup**: Select and backup multiple folders simultaneously.
- **Backup Deletion**: Remove existing backup folders.
- **User-Friendly Interface**: Intuitive web interface for straightforward operation.
- **Easy Update**: Built-in update feature for seamless updates to the latest version.

## Requirements

- PHP (version 5.6 or higher recommended)
- Web server (e.g., Apache, Nginx)

## Installation

1. Clone the repository or download the [latest release](https://github.com/dynamiccookies/Simple-Backup-Utility/releases/latest).
2. Place the script (`index.php`) in the directory where you want to manage backups.
3. Ensure the directory has appropriate permissions for creating and deleting files and folders.

## Usage

### Creating a Backup

1. Access the script via a web browser after installation.
2. Enter a name for your backup(s) in the "Backup Name" field.
3. Place a checkmark next to each of the folders you'd like to backup.
4. Click the "Backup Selected Folders" button.

### Deleting a Backup

1. Click the trash icon next to the Existing Backups entry.

### Updating to Latest Version

Only available if you see the New Version Available message in the bottom right corner of the screen.

1. Click the (Update Now) link in the New Version Available message.
2. When the page reloads, the new version number should be shown.

## Folder Structure

The Simple Backup Utility expects the following parent/child directory structure:
- Project Directory
  - prod
  - staging
  - test
  - dev
  - backups
    - **index.php** (this script)
    - prod_release-v1
    - staging_benchmark-tests
    - test_data-fixtures
    - dev_feature-branch-backup

The script displays all of its parent directory's sibling folders. In this example, prod, staging, test, and dev.

The backup process names the selected directory using the format `<source folder>_<backup-name>` based on the selected directory to backup.

**Note:** The names of all files and folders are arbitrary and can be customized as needed, including `index.php`.


## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For any issues, questions, or feature requests, please [open an issue](https://github.com/dynamiccookies/Simple-Backup-Utility/issues/new).
