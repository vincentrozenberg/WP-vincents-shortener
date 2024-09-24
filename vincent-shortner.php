<?php
/**
 * Plugin Name: Vincent's Shortner
 * Description: A simple URL shortener plugin with usage statistics
 * Version: 1.5
 * Author: Vincent Rozenberg
 * Author URI: https://vincentrozenberg.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Create or update the database table
function vincent_shortner_install_or_upgrade() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vincent_shortner';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        long_url text NOT NULL,
        short_code varchar(10) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        usage_count int DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY short_code (short_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check if usage_count column exists, if not, add it
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'usage_count'");
    if (empty($row)) {
        $wpdb->query("ALTER TABLE $table_name ADD usage_count int DEFAULT 0 NOT NULL");
    }
}
register_activation_hook(__FILE__, 'vincent_shortner_install_or_upgrade');
add_action('plugins_loaded', 'vincent_shortner_install_or_upgrade');

// Add menu item to the admin panel
function vincent_shortner_menu() {
    add_menu_page('Vincent Shortner', 'Vincent Shortner', 'manage_options', 'vincent-shortner', 'vincent_shortner_page', 'dashicons-admin-links');
}
add_action('admin_menu', 'vincent_shortner_menu');

// Check if URL already exists or conflicts with WordPress permalinks
function vincent_shortner_url_exists($long_url, $short_code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vincent_shortner';

    // Check if the long URL already exists
    $existing_url = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE long_url = %s", $long_url));
    if ($existing_url) {
        return 'The long URL already exists in the system.';
    }

    // Check if the short code conflicts with WordPress permalinks
    $page = get_page_by_path($short_code);
    if ($page) {
        return 'The short code conflicts with an existing url.';
    }

    return false;
}

// Admin page content
function vincent_shortner_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vincent_shortner';
    $message = '';

    // Handle form submission
    if (isset($_POST['vincent_shortner_submit'])) {
        $long_url = esc_url_raw($_POST['long_url']);
        $short_code = sanitize_text_field($_POST['short_code']);

        if (empty($short_code)) {
            $short_code = substr(md5(uniqid()), 0, 6);
        }

        $url_exists = vincent_shortner_url_exists($long_url, $short_code);
        if ($url_exists) {
            $message = '<div class="error"><p>' . $url_exists . '</p></div>';
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'long_url' => $long_url,
                    'short_code' => $short_code,
                    'usage_count' => 0
                )
            );
            $message = '<div class="updated"><p>Short URL created successfully.</p></div>';
        }
    }

    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $wpdb->delete($table_name, array('id' => $id));
        $message = '<div class="updated"><p>Short URL deleted successfully.</p></div>';
    }

    // Fetch all short URLs
    $short_urls = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // Display the admin page
    ?>
    <div class="wrap">
        <h3>Vincent Shortner</h3>
        <?php echo $message; ?>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="long_url">Long URL</label></th>
                    <td><input type="url" name="long_url" id="long_url" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="short_code">Short Code (optional)</label></th>
                    <td><input type="text" name="short_code" id="short_code" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="vincent_shortner_submit"  value="Create Short URL">
            </p>
        </form>

        <h3>Existing Short URLs</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Long</th>
                    <th>Short</th>
                    <th>Date</th>
                    <th>Clicks</th>
                    <th>Copy</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($short_urls as $url): ?>
                    <tr>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo esc_url($url->long_url); ?>
                        </td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php 
                            $short_url = home_url('/' . $url->short_code);
                            echo '<a href="' . esc_url($short_url) . '" target="_blank">/' . esc_html($url->short_code) . '</a>';
                            ?>
                        </td>
                        <td><?php echo esc_html($url->created_at); ?></td>
                        <td><?php echo esc_html($url->usage_count); ?></td>
                        <td>
                            <button onclick="copyToClipboard('<?php echo esc_js($short_url); ?>')">Copy</button>
                        </td>
                        <td>
                            <a href="?page=vincent-shortner&action=delete&id=<?php echo $url->id; ?>"  onclick="return confirm('Are you sure you want to delete this short URL?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Short URL copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    </script>
    <?php
}

// Shortcode to display the form and table on frontend
function vincent_shortner_shortcode() {
    ob_start();
    
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    
    // Display form and table
    ?>
    <div class="vincent-shortner-frontend">
        <?php
        vincent_shortner_page();
        ?>
    </div>
    <style>
        .vincent-shortner-frontend .wrap {
            margin: 0;
        }
        .vincent-shortner-frontend .wp-list-table {
            width: 100%;
        }
        .vincent-shortner-frontend td {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vincent-shortner-frontend th,
        .vincent-shortner-frontend td {
            padding: 8px;
        }
        .vincent-shortner-frontend .button {
            padding: 4px 8px;
            font-size: 12px;
            text-decoration: none;
            color: #0071a1;
            border: 1px solid #0071a1;
            border-radius: 2px;
            background: #f3f5f6;
            text-shadow: none;
            font-weight: 600;
            cursor: pointer;
        }
        .vincent-shortner-frontend .button:hover {
            background: #f1f1f1;
            border-color: #016087;
            color: #016087;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('vincent-short', 'vincent_shortner_shortcode');

// Handle short URL redirects and update usage count
function vincent_shortner_redirect() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vincent_shortner';

    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    
    if (!empty($request_uri)) {
        $short_code = sanitize_text_field($request_uri);
        $result = $wpdb->get_row($wpdb->prepare("SELECT id, long_url, usage_count FROM $table_name WHERE short_code = %s", $short_code));

        if ($result) {
            // Update usage count
            $wpdb->update(
                $table_name,
                array('usage_count' => $result->usage_count + 1),
                array('id' => $result->id)
            );

            // Redirect to the long URL
            wp_redirect(esc_url_raw($result->long_url));
            exit;
        }
    }
}
add_action('init', 'vincent_shortner_redirect');