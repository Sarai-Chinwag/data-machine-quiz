# Data Machine Quiz

A WordPress plugin that extends Data Machine to publish interactive quizzes with Schema.org structured data.

## Features

- Interactive quiz Gutenberg block with Schema.org Quiz markup
- WordPress Quiz Publish Handler for automated quiz creation
- Frontend interactivity using WordPress Interactivity API
- Comprehensive quiz data structure with questions, answers, and explanations
- SEO-optimized with rich snippets support

## Requirements

- WordPress 6.2+
- PHP 8.2+
- Data Machine plugin

## Installation

1. Install and activate the Data Machine plugin
2. Upload the `data-machine-quiz` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Run `composer install` in the plugin directory
5. Run `npm install && npm run build` for the block assets

## Usage

### Manual Quiz Creation

1. Create a new post/page
2. Add the "Quiz Schema" block
3. Configure quiz settings and questions in the block editor
4. Publish the post

### Automated Quiz Creation

Use the "WordPress Quiz Publish" handler in Data Machine to automatically generate quiz posts from AI-generated content.

## Development

### Setup

```bash
composer install
npm install
```

### Build Assets

```bash
npm run build  # Production build
npm run start  # Development watch mode
```

### Code Quality

```bash
composer run lint      # PHP linting
composer run lint:fix  # PHP auto-fix
npm run lint:js        # JS linting
npm run lint:css       # CSS linting
```

## License

GPL-2.0-or-later

## Author

Chris Huber - https://chubes.net