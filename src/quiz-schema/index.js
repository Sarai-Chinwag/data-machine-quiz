/**
 * Quiz Schema Block - Gutenberg Editor Interface
 *
 * @package DataMachineQuiz
 * @since 1.0.0
 */

import './style.scss';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	Button,
	SelectControl,
	ToggleControl,
	RangeControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Question Editor Component
 */
const QuestionEditor = ( { question, index, onChange, onRemove } ) => {
	const updateField = ( field, value ) => {
		onChange( { ...question, [ field ]: value } );
	};

	const updateOption = ( optIndex, value ) => {
		const newOptions = [ ...( question.options || [] ) ];
		newOptions[ optIndex ] = value;
		onChange( { ...question, options: newOptions } );
	};

	const addOption = () => {
		const newOptions = [ ...( question.options || [] ), '' ];
		onChange( { ...question, options: newOptions } );
	};

	const removeOption = ( optIndex ) => {
		const newOptions = ( question.options || [] ).filter(
			( _, i ) => i !== optIndex
		);
		// Adjust correctAnswer if needed
		let correctAnswer = question.correctAnswer || 0;
		if ( optIndex === correctAnswer ) {
			correctAnswer = 0;
		} else if ( optIndex < correctAnswer ) {
			correctAnswer--;
		}
		onChange( {
			...question,
			options: newOptions,
			correctAnswer,
		} );
	};

	return (
		<div
			style={ {
				border: '1px solid #ddd',
				borderRadius: '4px',
				padding: '16px',
				marginBottom: '16px',
				background: '#fafafa',
			} }
		>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					marginBottom: '12px',
				} }
			>
				<strong>
					{ __( 'Question', 'data-machine-quiz' ) } { index + 1 }
				</strong>
				<Button isDestructive isSmall onClick={ onRemove }>
					{ __( 'Remove', 'data-machine-quiz' ) }
				</Button>
			</div>

			<TextareaControl
				label={ __( 'Question Text', 'data-machine-quiz' ) }
				value={ question.question || '' }
				onChange={ ( value ) => updateField( 'question', value ) }
				rows={ 2 }
			/>

			<div style={ { marginBottom: '12px' } }>
				<label style={ { fontWeight: '600', marginBottom: '8px', display: 'block' } }>
					{ __( 'Options', 'data-machine-quiz' ) }
				</label>
				{ ( question.options || [] ).map( ( opt, optIdx ) => (
					<div
						key={ optIdx }
						style={ {
							display: 'flex',
							gap: '8px',
							alignItems: 'center',
							marginBottom: '4px',
						} }
					>
						<input
							type="radio"
							name={ `correct-${ index }` }
							checked={ question.correctAnswer === optIdx }
							onChange={ () =>
								updateField( 'correctAnswer', optIdx )
							}
							title={ __(
								'Mark as correct answer',
								'data-machine-quiz'
							) }
						/>
						<TextControl
							value={ opt }
							onChange={ ( value ) =>
								updateOption( optIdx, value )
							}
							placeholder={ `Option ${ optIdx + 1 }` }
							style={ { flex: 1, marginBottom: 0 } }
						/>
						{ ( question.options || [] ).length > 2 && (
							<Button
								isDestructive
								isSmall
								onClick={ () => removeOption( optIdx ) }
							>
								×
							</Button>
						) }
					</div>
				) ) }
				{ ( question.options || [] ).length < 6 && (
					<Button isSecondary isSmall onClick={ addOption }>
						{ __( '+ Add Option', 'data-machine-quiz' ) }
					</Button>
				) }
			</div>

			<TextareaControl
				label={ __( 'Explanation (shown after answering)', 'data-machine-quiz' ) }
				value={ question.explanation || '' }
				onChange={ ( value ) => updateField( 'explanation', value ) }
				rows={ 2 }
			/>

			<TextControl
				label={ __( 'Image URL (optional)', 'data-machine-quiz' ) }
				value={ question.imageUrl || '' }
				onChange={ ( value ) => updateField( 'imageUrl', value ) }
				placeholder="https://"
			/>
		</div>
	);
};

registerBlockType( 'data-machine-quiz/quiz-schema', {
	title: __( 'Quiz Schema', 'data-machine-quiz' ),
	icon: 'welcome-learn-more',
	category: 'common',
	description: __( 'Interactive quiz with Schema.org structured data', 'data-machine-quiz' ),

	edit: ( { attributes, setAttributes } ) => {
		const {
			quizTitle,
			description,
			quizType,
			questions,
			passingScore,
			showExplanations,
			resultDescriptions,
		} = attributes;

		const blockProps = useBlockProps();

		const updateQuestion = ( index, newQuestion ) => {
			const newQuestions = [ ...questions ];
			newQuestions[ index ] = newQuestion;
			setAttributes( { questions: newQuestions } );
		};

		const removeQuestion = ( index ) => {
			setAttributes( {
				questions: questions.filter( ( _, i ) => i !== index ),
			} );
		};

		const addQuestion = () => {
			setAttributes( {
				questions: [
					...questions,
					{
						question: '',
						options: [ '', '' ],
						correctAnswer: 0,
						explanation: '',
						imageUrl: '',
					},
				],
			} );
		};

		return (
			<div { ...blockProps }>
				<div
					style={ {
						background: '#f8f9fa',
						border: '1px solid #e0e0e0',
						borderRadius: '4px',
						padding: '16px',
						marginBottom: '20px',
					} }
				>
					<h3 style={ { margin: '0 0 8px 0', color: '#1e1e1e' } }>
						🧠{ ' ' }
						{ __( 'Quiz Schema Block', 'data-machine-quiz' ) }
					</h3>
					<p
						style={ {
							margin: 0,
							color: '#666',
							fontSize: '14px',
						} }
					>
						{ __(
							'This block generates an interactive quiz with Schema.org structured data for SEO. Configure questions below or via the sidebar.',
							'data-machine-quiz'
						) }
					</p>
				</div>

				<InspectorControls>
					<PanelBody
						title={ __( 'Quiz Settings', 'data-machine-quiz' ) }
					>
						<SelectControl
							label={ __( 'Quiz Type', 'data-machine-quiz' ) }
							value={ quizType }
							options={ [
								{
									label: __(
										'Multiple Choice',
										'data-machine-quiz'
									),
									value: 'multiple-choice',
								},
								{
									label: __(
										'True/False',
										'data-machine-quiz'
									),
									value: 'true-false',
								},
								{
									label: __(
										'Personality',
										'data-machine-quiz'
									),
									value: 'personality',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { quizType: value } )
							}
						/>
						<RangeControl
							label={ __(
								'Passing Score (%)',
								'data-machine-quiz'
							) }
							value={ passingScore }
							onChange={ ( value ) =>
								setAttributes( { passingScore: value } )
							}
							min={ 0 }
							max={ 100 }
						/>
						<ToggleControl
							label={ __(
								'Show Explanations',
								'data-machine-quiz'
							) }
							checked={ showExplanations }
							onChange={ ( value ) =>
								setAttributes( { showExplanations: value } )
							}
						/>
					</PanelBody>
					<PanelBody
						title={ __(
							'Result Messages',
							'data-machine-quiz'
						) }
						initialOpen={ false }
					>
						<TextControl
							label={ __(
								'Excellent (90-100%)',
								'data-machine-quiz'
							) }
							value={ resultDescriptions.excellent || '' }
							onChange={ ( value ) =>
								setAttributes( {
									resultDescriptions: {
										...resultDescriptions,
										excellent: value,
									},
								} )
							}
						/>
						<TextControl
							label={ __(
								'Good (70-89%)',
								'data-machine-quiz'
							) }
							value={ resultDescriptions.good || '' }
							onChange={ ( value ) =>
								setAttributes( {
									resultDescriptions: {
										...resultDescriptions,
										good: value,
									},
								} )
							}
						/>
						<TextControl
							label={ __(
								'Average (50-69%)',
								'data-machine-quiz'
							) }
							value={ resultDescriptions.average || '' }
							onChange={ ( value ) =>
								setAttributes( {
									resultDescriptions: {
										...resultDescriptions,
										average: value,
									},
								} )
							}
						/>
						<TextControl
							label={ __(
								'Needs Work (0-49%)',
								'data-machine-quiz'
							) }
							value={ resultDescriptions.needsWork || '' }
							onChange={ ( value ) =>
								setAttributes( {
									resultDescriptions: {
										...resultDescriptions,
										needsWork: value,
									},
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>

				<div style={ { marginBottom: '24px' } }>
					<TextControl
						label={ __( 'Quiz Title', 'data-machine-quiz' ) }
						value={ quizTitle }
						onChange={ ( value ) =>
							setAttributes( { quizTitle: value } )
						}
						style={ { marginBottom: '12px' } }
					/>

					<TextareaControl
						label={ __( 'Description', 'data-machine-quiz' ) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
						rows={ 3 }
						style={ { marginBottom: '12px' } }
					/>
				</div>

				<div style={ { marginBottom: '24px' } }>
					<h4
						style={ {
							marginBottom: '12px',
							color: '#1e1e1e',
						} }
					>
						{ __( 'Questions', 'data-machine-quiz' ) } ({ ' ' }
						{ questions.length } )
					</h4>

					{ questions.map( ( question, index ) => (
						<QuestionEditor
							key={ index }
							question={ question }
							index={ index }
							onChange={ ( newQ ) =>
								updateQuestion( index, newQ )
							}
							onRemove={ () => removeQuestion( index ) }
						/>
					) ) }

					<Button isPrimary onClick={ addQuestion }>
						{ __( '+ Add Question', 'data-machine-quiz' ) }
					</Button>
				</div>

				{ questions.length > 0 && (
					<div
						style={ {
							background: '#e8f5e9',
							padding: '12px',
							borderRadius: '4px',
							fontSize: '14px',
						} }
					>
						✅{ ' ' }
						{ questions.length }{ ' ' }
						{ questions.length === 1
							? 'question'
							: 'questions' }{ ' ' }
						configured • Type: { quizType } • Pass: { passingScore }%
					</div>
				) }
			</div>
		);
	},

	save: () => null,
} );
