<?php
// JSON解析テスト
require_once('../../../wp-config.php');

global $wpdb;

$table_name = $wpdb->prefix . 'wpsr_form_fields';

// wpdbの設定を確認
echo "<h2>wpdb設定</h2>";
echo "<p>use_mysqli: " . ($wpdb->use_mysqli ? 'true' : 'false') . "</p>";
echo "<p>charset: " . $wpdb->charset . "</p>";
echo "<p>collate: " . $wpdb->collate . "</p>";

$fields = $wpdb->get_results(
    "SELECT * FROM $table_name WHERE field_type IN ('radio', 'select', 'checkbox')",
    ARRAY_A
);

echo "<h2>フィールドデータ</h2>";
foreach ($fields as $field) {
    echo "<h3>{$field['field_key']} ({$field['field_type']})</h3>";
    echo "<p>field_options: " . htmlspecialchars($field['field_options']) . "</p>";
    
    // エスケープされた文字を処理してからJSON解析
    $decoded_options = stripslashes($field['field_options']);
    $options = json_decode($decoded_options, true);
    echo "<p>Original: " . htmlspecialchars($field['field_options']) . "</p>";
    echo "<p>Stripslashed: " . htmlspecialchars($decoded_options) . "</p>";
    echo "<p>json_decode result: " . print_r($options, true) . "</p>";
    
    if (is_array($options)) {
        echo "<p>Options array:</p><ul>";
        foreach ($options as $value => $label) {
            echo "<li>Value: '$value' => Label: '$label'</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>JSON decode failed or not an array</p>";
    }
    echo "<hr>";
}
?>
