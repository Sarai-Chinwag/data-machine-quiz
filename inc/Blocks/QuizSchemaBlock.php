<?php
namespace DataMachineQuiz\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quiz Schema Gutenberg Block Implementation
 *
 * Provides Schema.org Quiz structured data and interactive quiz UI through a Gutenberg block.
 * Renders JSON-LD markup for SEO and uses WordPress Interactivity API for frontend interaction.
 *
 * @package DataMachineQuiz\Blocks
 * @since 1.0.0
 */
class QuizSchemaBlock {

	/**
	 * Register Quiz Schema block with WordPress.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		register_block_type( DATA_MACHINE_QUIZ_PLUGIN_DIR . 'build/quiz-schema', array(
			'render_callback' => array( self::class, 'render' ),
		) );
	}

	/**
	 * Render Quiz Schema block.
	 *
	 * Outputs:
	 * 1. JSON-LD structured data for Schema.org Quiz
	 * 2. Interactive quiz UI with Interactivity API directives
	 *
	 * @param array $attributes Block attributes containing quiz data
	 * @return string HTML output
	 * @since 1.0.0
	 */
	public static function render( $attributes ) {
		$defaults = array(
			'quizTitle'          => '',
			'description'        => '',
			'quizType'           => 'multiple-choice',
			'questions'          => array(),
			'passingScore'       => 70,
			'showExplanations'   => true,
			'resultDescriptions' => array(
				'excellent'  => 'You really know your stuff!',
				'good'       => 'Great job!',
				'average'    => 'Not bad!',
				'needsWork'  => 'Keep learning!',
			),
			'author'             => array( 'name' => '', 'url' => '' ),
			'datePublished'      => '',
		);

		$attributes = wp_parse_args( $attributes, $defaults );
		$questions  = $attributes['questions'];

		if ( empty( $questions ) ) {
			return '';
		}

		// Build context for Interactivity API
		$context = array(
			'questions'          => $questions,
			'quizTitle'          => $attributes['quizTitle'],
			'quizType'           => $attributes['quizType'],
			'passingScore'       => $attributes['passingScore'],
			'showExplanations'   => $attributes['showExplanations'],
			'resultDescriptions' => $attributes['resultDescriptions'],
			'totalQuestions'     => count( $questions ),
			'currentQuestion'    => 0,
			'answers'            => array_fill( 0, count( $questions ), -1 ),
			'revealed'           => array_fill( 0, count( $questions ), false ),
			'isComplete'         => false,
			'score'              => 0,
		);

		$encoded_context = wp_json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		ob_start();

		// JSON-LD Schema
		$schema = self::generate_quiz_jsonld( $attributes );
		if ( ! empty( $schema ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}

		?>
		<div
			class="wp-block-data-machine-quiz-quiz-schema"
			data-wp-interactive="data-machine-quiz"
			<?php echo wp_interactivity_data_wp_context( $context ); ?>
		>
			<div class="dmq-quiz-container">
				<div class="dmq-quiz-header">
					<h3 class="dmq-quiz-title"><?php echo esc_html( $attributes['quizTitle'] ); ?></h3>
					<?php if ( ! empty( $attributes['description'] ) ) : ?>
						<p class="dmq-quiz-description"><?php echo wp_kses_post( $attributes['description'] ); ?></p>
					<?php endif; ?>
					<div class="dmq-quiz-progress" data-wp-bind--hidden="context.isComplete">
						<span>Question <span data-wp-text="state.currentQuestionDisplay"></span> of <?php echo count( $questions ); ?></span>
						<div class="dmq-progress-bar">
							<div class="dmq-progress-fill" data-wp-style--width="state.progressWidth"></div>
						</div>
					</div>
				</div>

				<div class="dmq-quiz-body" data-wp-bind--hidden="context.isComplete">
					<?php foreach ( $questions as $i => $q ) : ?>
						<div
							class="dmq-question-card"
							data-wp-bind--hidden="!state.isQuestionVisible_<?php echo (int) $i; ?>"
							data-question-index="<?php echo (int) $i; ?>"
						>
							<?php if ( ! empty( $q['imageUrl'] ) ) : ?>
								<img class="dmq-question-image" src="<?php echo esc_url( $q['imageUrl'] ); ?>" alt="" loading="lazy" />
							<?php endif; ?>
							<p class="dmq-question-text"><?php echo esc_html( $q['question'] ); ?></p>
							<div class="dmq-options">
								<?php foreach ( $q['options'] as $j => $option ) : ?>
									<button
										class="dmq-option-btn"
										data-wp-on--click="actions.selectAnswer"
										data-wp-class--dmq-selected="state.isSelected_<?php echo (int) $i; ?>_<?php echo (int) $j; ?>"
										data-wp-class--dmq-correct="state.isCorrect_<?php echo (int) $i; ?>_<?php echo (int) $j; ?>"
										data-wp-class--dmq-wrong="state.isWrong_<?php echo (int) $i; ?>_<?php echo (int) $j; ?>"
										data-wp-bind--disabled="state.isRevealed_<?php echo (int) $i; ?>"
										data-question="<?php echo (int) $i; ?>"
										data-option="<?php echo (int) $j; ?>"
									><?php echo esc_html( $option ); ?></button>
								<?php endforeach; ?>
							</div>
							<?php if ( $attributes['showExplanations'] && ! empty( $q['explanation'] ) ) : ?>
								<div class="dmq-explanation" data-wp-bind--hidden="!state.isRevealed_<?php echo (int) $i; ?>">
									<p><?php echo wp_kses_post( $q['explanation'] ); ?></p>
								</div>
							<?php endif; ?>
							<div class="dmq-question-nav">
								<button
									class="dmq-nav-btn dmq-prev-btn"
									data-wp-on--click="actions.prevQuestion"
									data-wp-bind--hidden="state.isFirstQuestion"
								>← Previous</button>
								<button
									class="dmq-nav-btn dmq-check-btn"
									data-wp-on--click="actions.checkAnswer"
									data-wp-bind--hidden="state.isRevealed_<?php echo (int) $i; ?>"
									data-wp-bind--disabled="state.isNotAnswered_<?php echo (int) $i; ?>"
									data-question="<?php echo (int) $i; ?>"
								>Check Answer</button>
								<?php if ( $i < count( $questions ) - 1 ) : ?>
									<button
										class="dmq-nav-btn dmq-next-btn"
										data-wp-on--click="actions.nextQuestion"
										data-wp-bind--hidden="!state.isRevealed_<?php echo (int) $i; ?>"
									>Next →</button>
								<?php else : ?>
									<button
										class="dmq-nav-btn dmq-finish-btn"
										data-wp-on--click="actions.finishQuiz"
										data-wp-bind--hidden="!state.isRevealed_<?php echo (int) $i; ?>"
									>See Results</button>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="dmq-quiz-results" data-wp-bind--hidden="!context.isComplete">
					<h3 class="dmq-results-title">Quiz Complete!</h3>
					<div class="dmq-score-display">
						<span class="dmq-score-number" data-wp-text="state.scoreDisplay"></span>
					</div>
					<p class="dmq-result-message" data-wp-text="state.resultMessage"></p>
					<button class="dmq-nav-btn dmq-restart-btn" data-wp-on--click="actions.resetQuiz">Try Again</button>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate JSON-LD structured data for Schema.org Quiz.
	 *
	 * @param array $attributes Quiz block attributes
	 * @return array Schema.org Quiz structured data
	 * @since 1.0.0
	 */
	private static function generate_quiz_jsonld( $attributes ) {
		global $post;

		$schema = array(
			'@context' => 'https://schema.org/',
			'@type'    => 'Quiz',
		);

		if ( ! empty( $attributes['quizTitle'] ) ) {
			$schema['name'] = $attributes['quizTitle'];
		}

		if ( ! empty( $attributes['description'] ) ) {
			$schema['about'] = array(
				'@type' => 'Thing',
				'name'  => wp_strip_all_tags( $attributes['description'] ),
			);
		}

		if ( ! empty( $attributes['questions'] ) ) {
			$schema['hasPart'] = array();
			foreach ( $attributes['questions'] as $q ) {
				$question_schema = array(
					'@type' => 'Question',
					'name'  => $q['question'] ?? '',
				);

				$options        = $q['options'] ?? array();
				$correct_index  = $q['correctAnswer'] ?? 0;

				if ( isset( $options[ $correct_index ] ) ) {
					$question_schema['acceptedAnswer'] = array(
						'@type' => 'Answer',
						'text'  => $options[ $correct_index ],
					);
				}

				$suggested = array();
				foreach ( $options as $idx => $opt ) {
					if ( $idx !== $correct_index ) {
						$suggested[] = array(
							'@type' => 'Answer',
							'text'  => $opt,
						);
					}
				}
				if ( ! empty( $suggested ) ) {
					$question_schema['suggestedAnswer'] = $suggested;
				}

				$schema['hasPart'][] = $question_schema;
			}
		}

		if ( ! empty( $attributes['author']['name'] ) ) {
			$schema['author'] = array(
				'@type' => 'Person',
				'name'  => $attributes['author']['name'],
			);
			if ( ! empty( $attributes['author']['url'] ) ) {
				$schema['author']['url'] = $attributes['author']['url'];
			}
		}

		$schema['datePublished'] = ! empty( $attributes['datePublished'] )
			? $attributes['datePublished']
			: ( $post ? get_the_date( 'c', $post->ID ) : '' );

		return $schema;
	}
}
