<?php
namespace DataMachineQuiz\Handlers\WordPressQuizPublish;

use DataMachine\Core\Steps\Publish\Handlers\PublishHandler;
use DataMachine\Core\Steps\HandlerRegistrationTrait;
use DataMachine\Core\WordPress\WordPressSettingsResolver;
use DataMachine\Core\WordPress\TaxonomyHandler;
use DataMachine\Core\WordPress\WordPressPublishHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Quiz publishing with Schema.org structured data.
 *
 * Creates WordPress posts with embedded Quiz Schema blocks for SEO and rich snippets.
 *
 * @package DataMachineQuiz\WordPressQuizPublish
 * @since 1.0.0
 */
class WordPressQuizPublish extends PublishHandler {
    use HandlerRegistrationTrait;

    protected $taxonomy_handler;

    public function __construct() {
        parent::__construct('wordpress_quiz_publish');
        $this->taxonomy_handler = new TaxonomyHandler();
    }

    /**
     * Register the handler.
     */
    public static function register(): void {
        self::registerHandler(
            'wordpress_quiz_publish',
            'publish',
            self::class,
            __('WordPress Quiz', 'data-machine-quiz'),
            __('Publish quizzes to WordPress with Schema.org structured data', 'data-machine-quiz'),
            false,
            null,
            WordPressQuizPublishSettings::class,
            function($tools, $handler_slug, $handler_config) {
                if ($handler_slug === 'wordpress_quiz_publish') {
                    $base_params = self::get_quiz_parameters();
                    $taxonomy_params = TaxonomyHandler::getTaxonomyToolParameters($handler_config);
                    
                    $tools['wordpress_quiz_publish'] = [
                        'class' => self::class,
                        'method' => 'handle_tool_call',
                        'handler' => 'wordpress_quiz_publish',
                        'description' => 'Create WordPress quiz posts with Schema.org structured data markup for SEO-optimized interactive quiz content.',
                        'parameters' => array_merge($base_params, $taxonomy_params),
                        'handler_config' => $handler_config
                    ];
                }
                return $tools;
            }
        );
    }

    /**
     * Execute quiz publishing.
     *
     * @param array $parameters AI tool parameters
     * @param array $handler_config Handler configuration
     * @return array Success/failure response
     * @since 1.0.0
     */
    protected function executePublish(array $parameters, array $handler_config): array {
        // Parent PublishHandler ensures job_id and engine are present
        $job_id = $parameters['job_id'];
        $engine = $parameters['engine'];

        if ( empty( $parameters['post_title'] ) ) {
            return $this->errorResponse('Quiz post title is required');
        }

        if ( empty( $handler_config ) ) {
            return $this->errorResponse('Empty handler configuration for wordpress_quiz_publish');
        }

        $post_status = WordPressSettingsResolver::getPostStatus($handler_config);
        $post_author = WordPressSettingsResolver::getPostAuthor($handler_config);
        $post_type = $handler_config['post_type'] ?? 'post';

        if ( empty( $post_type ) ) {
            return $this->errorResponse('Missing required post_type in handler configuration');
        }

        if ( empty( $post_status ) ) {
            return $this->errorResponse('Missing required post_status in handler configuration');
        }

        if ( empty( $post_author ) ) {
            return $this->errorResponse('Missing required post_author in handler configuration');
        }

        $quiz_block_result = $this->create_quiz_schema_block( $parameters, $handler_config );

        if ( ! $quiz_block_result['success'] ) {
            return $this->errorResponse('Failed to create quiz block: ' . $quiz_block_result['error']);
        }

        $content = wp_unslash( $parameters['post_content'] ?? '' );
        
        // Apply source attribution using core helper
        $content = WordPressPublishHelper::applySourceAttribution($content, $engine->getSourceUrl(), $handler_config);
        
        // Append quiz block
        $content .= "\n\n" . $quiz_block_result['block'];
        
        // Filter content for security
        $content = wp_filter_post_kses( $content );

        $post_data = [
            'post_title' => sanitize_text_field( $parameters['post_title'] ),
            'post_content' => $content,
            'post_status' => $post_status,
            'post_author' => $post_author,
            'post_type' => $post_type
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $error_msg = 'Failed to create post: ' . ( is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Invalid post ID' );
            return $this->errorResponse($error_msg);
        }

        $this->storePostTrackingMeta( $post_id, $handler_config );

        // Attach featured image if available and configured
        WordPressPublishHelper::attachImageToPost($post_id, $engine->getImagePath(), $handler_config);

        // Use shared taxonomy processing for standard public taxonomies.
        $taxonomy_results = $this->taxonomy_handler->processTaxonomies( $post_id, $parameters, $handler_config, $engine->all() );

        // Store post_id in engine data for downstream handlers
        datamachine_merge_engine_data( (int) $job_id, [
            'post_id' => $post_id,
            'published_url' => get_permalink($post_id)
        ] );

        return $this->successResponse([
            'post_id' => $post_id,
            'post_title' => $parameters['post_title'],
            'post_url' => get_permalink( $post_id ),
            'edit_url' => get_edit_post_link( $post_id, 'raw' ),
            'taxonomy_results' => $taxonomy_results
        ]);
    }
    
    /**
     * Get base quiz parameters.
     *
     * @return array
     */
    private static function get_quiz_parameters(): array {
        return [
            'post_title' => [
                'type' => 'string',
                'required' => true,
                'description' => 'The title of the blog post'
            ],
            'post_content' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Quiz article content formatted as WordPress Gutenberg blocks. Use <!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph --> for paragraphs.

HEADINGS - Use proper heading hierarchy for quiz sections:
• H2: <!-- wp:heading --><h2 class="wp-block-heading">Introduction</h2><!-- /wp:heading -->
• H3: <!-- wp:heading {"level":3} --><h3 class="wp-block-heading">About the Quiz</h3><!-- /wp:heading -->
• H4: <!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Instructions</h4><!-- /wp:heading -->

LISTS - Use correct block syntax:
• Unordered lists: <!-- wp:list --><ul class="wp-block-list"><li>Item 1</li><li>Item 2</li></ul><!-- /wp:list -->
• Ordered lists: <!-- wp:list {"ordered":true} --><ol class="wp-block-list"><li>Step 1</li><li>Step 2</li></ol><!-- /wp:list -->

Use ordered lists for quiz instructions and cooking steps to ensure proper formatting and semantic HTML.'
            ],
            'quizTitle' => [
                'type' => 'string',
                'required' => true,
                'description' => 'The display name of the quiz'
            ],
            'description' => [
                'type' => 'string',
                'description' => 'A description of the quiz'
            ],
            'quizType' => [
                'type' => 'string',
                'enum' => ['multiple-choice', 'true-false', 'personality'],
                'default' => 'multiple-choice',
                'description' => 'Type of quiz (multiple-choice, true-false, or personality)'
            ],
            'questions' => [
                'type' => 'array',
                'required' => true,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => ['type' => 'string', 'required' => true],
                        'options' => ['type' => 'array', 'items' => ['type' => 'string'], 'required' => true, 'minItems' => 2, 'maxItems' => 6],
                        'correctAnswer' => ['type' => 'integer', 'required' => true, 'minimum' => 0, 'description' => '0-based index of correct answer'],
                        'explanation' => ['type' => 'string', 'description' => 'Explanation shown after answering'],
                        'imageUrl' => ['type' => 'string', 'description' => 'Optional image URL for the question']
                    ]
                ],
                'description' => 'Array of question objects with question text, answer options, correct answer index, and optional explanation/image'
            ],
            'passingScore' => [
                'type' => 'integer',
                'default' => 70,
                'description' => 'Percentage score needed to pass the quiz'
            ],
            'showExplanations' => [
                'type' => 'boolean',
                'default' => true,
                'description' => 'Whether to show explanations after each answer'
            ],
            'resultDescriptions' => [
                'type' => 'object',
                'properties' => [
                    'excellent' => ['type' => 'string', 'default' => 'You really know your stuff!'],
                    'good' => ['type' => 'string', 'default' => 'Great job!'],
                    'average' => ['type' => 'string', 'default' => 'Not bad!'],
                    'needsWork' => ['type' => 'string', 'default' => 'Keep learning!']
                ],
                'description' => 'Score range descriptions for results (90-100%, 70-89%, 50-69%, 0-49%)'
            ],
            // job_id is injected automatically by ToolParameters::buildParameters from the pipeline payload.
            // Do NOT list it as a tool parameter — the AI will hallucinate a fake value that overrides the real one.
        ];
    }

    /**
     * Create Quiz Schema Gutenberg block from AI parameters.
     *
     * Transforms AI-provided quiz data into a properly formatted Gutenberg block
     * with comprehensive Schema.org Quiz attributes. Handles sanitization,
     * validation, and JSON encoding for block attributes.
     *
     * @param array $parameters     AI tool parameters containing quiz data
     * @param array $handler_config Handler configuration for author attribution
     * @return array Success/failure response with generated block HTML
     * @since 1.0.0
     */
    private function create_quiz_schema_block( array $parameters, array $handler_config = [] ): array {
        $quiz_data = [
            'quizTitle' => sanitize_text_field( $parameters['quizTitle'] ?? '' ),
            'description' => wp_kses_post( $parameters['description'] ?? '' ),
            'quizType' => sanitize_text_field( $parameters['quizType'] ?? 'multiple-choice' ),
            'questions' => $this->sanitize_questions( $parameters['questions'] ?? [] ),
            'passingScore' => absint( $parameters['passingScore'] ?? 70 ),
            'showExplanations' => (bool) ( $parameters['showExplanations'] ?? true ),
            'resultDescriptions' => $this->sanitize_result_descriptions( $parameters['resultDescriptions'] ?? [] )
        ];
        
        $author_id = $handler_config['post_author'];
        $author_user = get_userdata( $author_id );
        $author_name = $author_user ? $author_user->display_name : null;
        if ( $author_name ) {
            $quiz_data['author'] = [
                'name' => sanitize_text_field( $author_name ),
                'url' => esc_url_raw( get_author_posts_url( $author_id ) )
            ];
        }
        
        $quiz_data['datePublished'] = sanitize_text_field( $parameters['datePublished'] ?? '' ) ?: current_time( 'c' );
        
        $block_attributes = wp_json_encode( $quiz_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        
        if ( $block_attributes === false ) {
            return [
                'success' => false,
                'error' => 'Failed to encode quiz data as JSON: ' . json_last_error_msg()
            ];
        }
        
        $block_html = '<!-- wp:data-machine-quiz/quiz-schema ' . $block_attributes . ' -->' . "\n" .
                      '<!-- /wp:data-machine-quiz/quiz-schema -->';
        
        return [
            'success' => true,
            'block' => $block_html
        ];
    }
    
    private function sanitize_questions( $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }
        
        $sanitized = [];
        foreach ( $input as $question ) {
            if ( ! is_array( $question ) ) {
                continue;
            }
            
            $sanitized_question = [
                'question' => sanitize_text_field( $question['question'] ?? '' ),
                'options' => array_map( 'sanitize_text_field', $question['options'] ?? [] ),
                'correctAnswer' => absint( $question['correctAnswer'] ?? 0 ),
                'explanation' => wp_kses_post( $question['explanation'] ?? '' ),
                'imageUrl' => esc_url_raw( $question['imageUrl'] ?? '' )
            ];
            
            $sanitized[] = $sanitized_question;
        }
        
        return $sanitized;
    }
    
    private function sanitize_result_descriptions( $input ): array {
        if ( ! is_array( $input ) ) {
            return [
                'excellent' => 'You really know your stuff!',
                'good' => 'Great job!',
                'average' => 'Not bad!',
                'needsWork' => 'Keep learning!'
            ];
        }
        
        return [
            'excellent' => sanitize_text_field( $input['excellent'] ?? 'You really know your stuff!' ),
            'good' => sanitize_text_field( $input['good'] ?? 'Great job!' ),
            'average' => sanitize_text_field( $input['average'] ?? 'Not bad!' ),
            'needsWork' => sanitize_text_field( $input['needsWork'] ?? 'Keep learning!' )
        ];
    }
    
    // Taxonomy assignment is handled centrally by TaxonomyHandler via applyTaxonomies().

}