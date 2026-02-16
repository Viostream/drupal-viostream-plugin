# Changelog

All notable changes to the Viostream Drupal/GovCMS module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-16

### Added
- Initial release of Viostream module for Drupal/GovCMS
- Field formatter plugin for Link and Text fields
- Support for multiple Viostream URL formats
- Configurable player settings:
  - Width and height customization
  - Responsive mode with 16:9 aspect ratio
  - Autoplay support
  - Mute option
  - Player controls toggle
- Twig template for video rendering
- CSS styling for responsive video embedding
- Comprehensive documentation:
  - README with installation and usage guide
  - Quick Start guide
  - Troubleshooting section
- Drupal 8, 9, 10, and 11 compatibility
- GovCMS compatibility

### Technical Details
- Implements `FieldFormatterInterface` for field display
- Supports `link`, `string`, and `string_long` field types
- Uses Drupal 8+ plugin system
- Follows Drupal coding standards
- Includes configuration schema for settings validation

[1.0.0]: https://github.com/Viostream/govcms-viostream-plugin/releases/tag/v1.0.0
