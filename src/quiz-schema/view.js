/**
 * Quiz Schema Block - Frontend Interactivity
 *
 * Uses WordPress Interactivity API for lightweight, interactive quiz experience.
 *
 * @package DataMachineQuiz
 * @since 1.0.0
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'data-machine-quiz', {
	state: {
		get currentQuestionDisplay() {
			const ctx = getContext();
			return ctx.currentQuestion + 1;
		},
		get progressWidth() {
			const ctx = getContext();
			const pct = ( ( ctx.currentQuestion + 1 ) / ctx.totalQuestions ) * 100;
			return pct + '%';
		},
		get isFirstQuestion() {
			const ctx = getContext();
			return ctx.currentQuestion === 0;
		},
		get scoreDisplay() {
			const ctx = getContext();
			const pct = Math.round( ( ctx.score / ctx.totalQuestions ) * 100 );
			return ctx.score + '/' + ctx.totalQuestions + ' (' + pct + '%)';
		},
		get resultMessage() {
			const ctx = getContext();
			const pct = Math.round( ( ctx.score / ctx.totalQuestions ) * 100 );
			const rd = ctx.resultDescriptions;
			if ( pct >= 90 ) return rd.excellent;
			if ( pct >= 70 ) return rd.good;
			if ( pct >= 50 ) return rd.average;
			return rd.needsWork;
		},
	},
	actions: {
		selectAnswer( event ) {
			const ctx = getContext();
			const btn = event.target.closest( '[data-question]' );
			if ( ! btn ) return;
			const qi = parseInt( btn.dataset.question );
			const oi = parseInt( btn.dataset.option );
			if ( ctx.revealed[ qi ] ) return;
			ctx.answers = [ ...ctx.answers ];
			ctx.answers[ qi ] = oi;
		},
		checkAnswer( event ) {
			const ctx = getContext();
			const btn = event.target.closest( '[data-question]' );
			if ( ! btn ) return;
			const qi = parseInt( btn.dataset.question );
			if ( ctx.answers[ qi ] === -1 ) return;
			ctx.revealed = [ ...ctx.revealed ];
			ctx.revealed[ qi ] = true;
		},
		nextQuestion() {
			const ctx = getContext();
			if ( ctx.currentQuestion < ctx.totalQuestions - 1 ) {
				ctx.currentQuestion++;
			}
		},
		prevQuestion() {
			const ctx = getContext();
			if ( ctx.currentQuestion > 0 ) {
				ctx.currentQuestion--;
			}
		},
		finishQuiz() {
			const ctx = getContext();
			let score = 0;
			ctx.questions.forEach( ( q, i ) => {
				if ( ctx.answers[ i ] === q.correctAnswer ) {
					score++;
				}
			} );
			ctx.score = score;
			ctx.isComplete = true;
		},
		resetQuiz() {
			const ctx = getContext();
			ctx.currentQuestion = 0;
			ctx.answers = new Array( ctx.totalQuestions ).fill( -1 );
			ctx.revealed = new Array( ctx.totalQuestions ).fill( false );
			ctx.isComplete = false;
			ctx.score = 0;
		},
	},
} );

// Generate dynamic state getters for per-question/option visibility
// These are referenced in the PHP render as state.isQuestionVisible_0, state.isSelected_0_1, etc.
// The Interactivity API resolves these via the store's state object.
// We need to define them dynamically based on the context.

// Since Interactivity API doesn't support truly dynamic state keys from context,
// we use a different approach: directives check context directly.
// The PHP render uses data-wp-bind--hidden with state getters.
// We register getters for a reasonable max number of questions (50) and options (6).

for ( let qi = 0; qi < 50; qi++ ) {
	Object.defineProperty( state, `isQuestionVisible_${ qi }`, {
		get() {
			const ctx = getContext();
			return ctx.currentQuestion === qi;
		},
	} );
	Object.defineProperty( state, `isRevealed_${ qi }`, {
		get() {
			const ctx = getContext();
			return ctx.revealed[ qi ] === true;
		},
	} );
	Object.defineProperty( state, `isNotAnswered_${ qi }`, {
		get() {
			const ctx = getContext();
			return ctx.answers[ qi ] === -1;
		},
	} );
	for ( let oi = 0; oi < 6; oi++ ) {
		Object.defineProperty( state, `isSelected_${ qi }_${ oi }`, {
			get() {
				const ctx = getContext();
				return ctx.answers[ qi ] === oi;
			},
		} );
		Object.defineProperty( state, `isCorrect_${ qi }_${ oi }`, {
			get() {
				const ctx = getContext();
				return (
					ctx.revealed[ qi ] &&
					ctx.questions[ qi ].correctAnswer === oi
				);
			},
		} );
		Object.defineProperty( state, `isWrong_${ qi }_${ oi }`, {
			get() {
				const ctx = getContext();
				return (
					ctx.revealed[ qi ] &&
					ctx.answers[ qi ] === oi &&
					ctx.questions[ qi ].correctAnswer !== oi
				);
			},
		} );
	}
}
