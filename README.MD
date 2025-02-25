# ACF JSON Controller

A lightweight PHP component for managing ACF JSON synchronization in WordPress themes.

## Description

ACF JSON Controller is a WordPress theme component that manages ACF (Advanced Custom Fields) JSON files synchronization. It automatically configures paths for saving and loading JSON files, provides admin notifications for field updates, and enables detailed HTML escaping logging.

## Features

- Automatic configuration of ACF JSON save/load paths in your theme directory
- Admin notification when field synchronization is available
- Detailed logging of HTML escaping for debugging purposes
- Disables automatic `<p>` tags in the WYSIWYG editor
- Configures HTML escaping in ACF fields

## Requirements:

- PHP 8.0+
- WordPress 6.0+
- Advanced Custom Fields PRO 6.3.7+

## Installation

### 1. Create file structure

Place the `class-acf.php` file in your theme's directory:

```
your-theme/
├── acf-json/ # Directory for storing JSON files
├── inc/
│ ├── controllers/
│ │ └── class-acf.php # Place the controller code here
│ └── ...
└── functions.php # Initialize the controller here
```

### 2. Include the controller in your theme

Add the following code to your theme's `functions.php`:

```php
/**
 * Initialize theme controllers
 */
function theme_init_controllers() {
    // Include ACF controller
    require_once get_template_directory() . '/inc/controllers/class-acf.php';

    // Initialize ACF controller
    new \YourTheme\Controller\ACF();
}
add_action('after_setup_theme', 'theme_init_controllers');
```

### 3. Create acf-json directory

Ensure the acf-json directory exists in your theme's root with write permissions.

## How It Works

The controller handles several key aspects of ACF JSON synchronization:

- **JSON FileManagement:** Configures paths for saving and loading ACF JSON files
- **Sync Notifications:** Displays admin notices when field synchronization is available
- **HTML Escaping:** Sets up HTML escaping and detailed logging
- **WYSIWYG Cleanup:** Removes automatic paragraph tags from ACF WYSIWYG editor

### Synchronization Process

1. Create or edit an ACF field group
2. Save changes (JSON file is automatically created in the **acf-json** directory)
3. When migrating to another site, the system will detect differences between PHP and JSON field versions
4. An admin notification will appear showing available synchronizations
5. Click the link to apply the changes

### Benefits of ACF JSON

1. **Version Control:** JSON files can be tracked in version control systems (Git)
2. **Performance:** Loading fields from JSON files is faster than from the database
3. **Team Collaboration:** Simplifies team work on field structures
4. **Easy Migration:** Streamlines field configuration transfer between environments
5. **Change Tracking:** Visualization of field changes through Git diff

## Customization

### Changing JSON storage path

To modify the path for the **acf-json** directory, edit the **acf_json_save_callback** and **acf_json_load_callback** methods:

```php
public function acf_json_save_callback(string $path): string {
    // Change to your custom path
    $path = get_stylesheet_directory() . '/custom-path/acf-json';

    return $path;
}

public function acf_json_load_callback(array $paths): array {
    unset($paths[0]);
    // Change to your custom path
    $paths[] = get_stylesheet_directory() . '/custom-path/acf-json';

    return $paths;
}
```

Modifying notification text
To change the synchronization notification text, edit the acf_sync_notice method:

```php
public function acf_sync_notice(): void {
    // Your code...
    if ($sync_count > 0) { ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php printf(
                    __('ACF Pro field synchronization available', 'your-theme-textdomain') .
                    ' <code>(%d)</code> <a href="%s">' .
                    __('Synchronize now', 'your-theme-textdomain') . '</a>',
                    $sync_count,
                    admin_url('edit.php?post_type=acf-field-group&post_status=sync')
                ); ?>
            </p>
        </div>
    <?php }
}
```

### HTML Escaping Debugging

The controller includes detailed logging of any HTML content changes in ACF fields. All logs are written to the standard PHP error log with an **_ACF HTML Escaping Debug_** tag.

### License

This code is released under the MIT License.
