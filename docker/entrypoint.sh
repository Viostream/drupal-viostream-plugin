#!/bin/bash
set -e

echo "Starting Drupal with Viostream module..."

# Ensure we're in the correct directory
cd /var/www/html

# Wait for the web server to be ready
sleep 2

# Check if Drupal is installed, if not, install it
if [ ! -f "/var/www/html/web/sites/default/files/drupal.sqlite" ]; then
    echo "Installing Drupal with Viostream module..."
    
    # Create necessary directories with proper permissions
    mkdir -p /var/www/html/web/sites/default/files/private
    chmod -R 777 /var/www/html/web/sites/default/files
    chmod 755 /var/www/html/web/sites/default
    
    # Install Drupal using drush
    drush site:install standard \
        --db-url=sqlite://sites/default/files/drupal.sqlite \
        --site-name="Viostream Demo Site" \
        --account-name=admin \
        --account-pass=admin \
        --yes
    
    echo "Drupal installation complete. Enabling modules..."
    
    # Enable required modules
    drush en field link filter -y
    drush en viostream -y
    
    # Clear all caches
    drush cr
    
    # Set proper ownership
    chown -R www-data:www-data /var/www/html/web/sites/default/files
    
    echo "==================================="
    echo "Drupal setup complete!"
    echo "URL: http://localhost"
    echo "Admin User: admin"
    echo "Admin Pass: admin"
    echo "==================================="
    echo ""
    echo "To set up the Viostream module:"
    echo ""
    echo "1. Configure API credentials:"
    echo "   - Go to Configuration > Media > Viostream Settings"
    echo "   - Enter your Access Key (starts with VC-) and API Key"
    echo "   - Click Test Connection to verify"
    echo "   - Save configuration"
    echo ""
    echo "2. Enable CKEditor 5 integration (embed videos in rich text):"
    echo "   - Go to Configuration > Content authoring > Text formats"
    echo "   - Edit a text format (e.g. Full HTML)"
    echo "   - Enable the 'Viostream Video Embed' filter"
    echo "   - Drag the Viostream Video button into the CKEditor toolbar"
    echo "   - Save"
    echo "   - Edit any content and click the Viostream button in the toolbar"
    echo ""
    echo "3. Or use the field widget (single video per field):"
    echo "   - Go to Structure > Content types > Article > Manage fields"
    echo "   - Add a Link field (e.g. 'Viostream Video')"
    echo "   - In Manage form display, set widget to 'Viostream Browser'"
    echo "   - In Manage display, set formatter to 'Viostream Video'"
    echo ""
    echo "==================================="
else
    echo "Drupal already installed, starting Apache..."
fi

# Start Apache
exec "$@"
