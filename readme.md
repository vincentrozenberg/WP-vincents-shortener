# Vincent's Shortner

A simple URL shortener plugin for WordPress with usage statistics.

## Description

Vincent's Shortner is a lightweight WordPress plugin that allows you to create and manage short URLs directly from your WordPress admin panel. It includes features like custom short codes, usage tracking, and a frontend shortcode for public use.

## Features

- Create short URLs with custom or auto-generated short codes
- Track usage statistics (number of clicks) for each short URL
- Admin panel interface for managing short URLs
- Frontend shortcode for public URL shortening
- Checks for URL conflicts with existing WordPress permalinks
- Easy copying of short URLs to clipboard
- Responsive design with Bootstrap integration for the frontend

## Installation

1. Upload the `vincents-shortner` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin via the 'Vincent's Shortner' menu item in the WordPress admin panel

## Usage

### Admin Panel

1. Navigate to the 'Vincent's Shortner' menu item in your WordPress admin panel
2. Enter a long URL and an optional custom short code
3. Click 'Create Short URL' to generate a new short URL
4. Manage existing short URLs using the table below the form

### Frontend Shortcode

Use the `[vincent-short]` shortcode in any post or page to display the URL shortener form and table on the frontend.

## Database

The plugin creates a custom table in your WordPress database to store short URL data:

- `id`: Unique identifier for each entry
- `long_url`: The original long URL
- `short_code`: The generated or custom short code
- `created_at`: Timestamp of when the short URL was created
- `usage_count`: Number of times the short URL has been accessed

## Author

Vincent Rozenberg
- Website: [https://vincentrozenberg.com](https://vincentrozenberg.com)

## Version

1.5

## License

This project is licensed under the MIT License.

## Contributing

Contributions are welcome. Please feel free to submit a Pull Request.

