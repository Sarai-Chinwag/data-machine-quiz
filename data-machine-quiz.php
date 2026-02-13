<?php
/**
 * Plugin Name: Data Machine Quiz
 * Plugin URI: https://github.com/Sarai-Chinwag/data-machine-quiz
 * Description: Extends Data Machine to publish interactive quizzes with Schema.org structured data via WordPress Quiz Publish Handler and Quiz Schema Gutenberg Block.
 * Version: 1.0.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: data-machine-quiz
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.2
 * Requires Plugins: data-machine
 * Network: false
 *
 * @package DataMachineQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
define( 'DATA_MACHINE_QUIZ_VERSION', '1.0.0' );
define( 'DATA_MACHINE_QUIZ_PLUGIN_FILE', __FILE__ );
define( 'DATA_MACHINE_QUIZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DATA_MACHINE_QUIZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( DATA_MACHINE_QUIZ_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once DATA_MACHINE_QUIZ_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Initialize DM-Quiz plugin functionality.
 *
 * Loads translation textdomain, registers Data Machine handler filters,
 * and initializes Quiz Schema Gutenberg block. Called on WordPress 'init' hook.
 *
 * @since 1.0.0
 */
function data_machine_quiz_init() {
    load_plugin_textdomain( 'data-machine-quiz', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    // Register Quiz Schema Block
    if ( class_exists( 'DataMachineQuiz\Blocks\QuizSchemaBlock' ) ) {
        DataMachineQuiz\Blocks\QuizSchemaBlock::register();
    }

    // Register handlers
    if ( class_exists( 'DataMachineQuiz\Handlers\WordPressQuizPublish\WordPressQuizPublish' ) ) {
        DataMachineQuiz\Handlers\WordPressQuizPublish\WordPressQuizPublish::register();
    }
}

/**
 * Plugin activation callback.
 *
 * Flushes rewrite rules to ensure proper URL structure.
 * Plugin dependency handled by WordPress via Requires Plugins header.
 *
 * @since 1.0.0
 */
function data_machine_quiz_activate() {
    flush_rewrite_rules();
}

/**
 * Plugin deactivation callback.
 *
 * Performs cleanup operations including flushing rewrite rules
 * to remove any custom URL structures.
 *
 * @since 1.0.0
 */
function data_machine_quiz_deactivate() {
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'data_machine_quiz_activate' );
register_deactivation_hook( __FILE__, 'data_machine_quiz_deactivate' );

add_action( 'init', 'data_machine_quiz_init' );