# Viostream Drupal Module - Docker Setup

This directory contains Docker configuration files to run a complete Drupal environment with the Viostream module pre-installed.

## Quick Start

1. **Build the Docker image:**
```bash
docker build -t viostream-drupal .
```

2. **Run the container:**
```bash
docker run -p 8080:80 --name viostream-demo viostream-drupal
```

3. **Access the site:**
- URL: **http://localhost:8080**
- Admin User: `admin`
- Admin Password: `admin`

The setup is **completely automated** - Drupal will be installed with your Viostream module enabled on first run!

## Configure Viostream API Credentials

Before using the media browser, connect to the Viostream API:

1. **Go to Configuration > Media > Viostream Settings**
   (or visit `/admin/config/media/viostream`)
2. **Enter your Access Key** (starts with `VC-`) from Viostream Developer Tools
3. **Enter your API Key** from Viostream Developer Tools
4. **Click "Test Connection"** to verify credentials
5. **Click "Save configuration"**

## Setting Up the Viostream Field

You'll need to do a quick manual setup:

1. **Go to Structure > Content types > Article > Manage fields**
2. **Click "Add field"**
3. **Select "Link" as the field type**
4. **Set the field label to "Viostream Video"**
5. **Save the field**
6. **Go to the "Manage form display" tab**
7. **Set the widget to "Viostream Browser"** for the media browser experience
8. **Go to the "Manage display" tab**
9. **Set the formatter to "Viostream Video"**
10. **Click the settings gear** to configure player options:
    - Width: `100%` or specific pixel size
    - Height: `400` (or desired height)
    - Responsive: Enable for 16:9 aspect ratio
    - Controls: Enable player controls
    - Autoplay/Muted: Configure as needed

## Testing the Module

1. **Go to Content > Add content > Article**
2. **Fill in the title and body**
3. **Click the "Browse Viostream" button** on the video field
4. **Search and select a video** from the modal media browser
   - Or manually enter a URL: `https://share.viostream.com/VIDEO_ID`
5. **Save and view** the article to see your embedded video

## Development Mode

For development with live code changes:
```bash
docker run -p 8080:80 \
  -v $(pwd):/var/www/html/web/modules/contrib/viostream \
  --name viostream-dev \
  viostream-drupal
```

## Container Management

**Stop the container:**
```bash
docker stop viostream-demo
```

**Start it again:**
```bash
docker start viostream-demo
```

**Remove container and image:**
```bash
docker rm viostream-demo
docker rmi viostream-drupal
```

## What's Included

- **Drupal 10** with standard installation profile
- **SQLite database** (no external database needed)
- **Your Viostream module** pre-installed and enabled
  - API client for Viostream API v3
  - Admin settings form for API credentials
  - Media browser widget for visual video selection
  - Field formatter for video embedding
- **Admin account** ready to use (admin/admin)
- **Apache web server** configured for Drupal
- **Drush** for command-line management

## Troubleshooting

If the container exits immediately, check the logs:
```bash
docker logs viostream-demo
```

The most common issue is port 8080 being in use. Try a different port:
```bash
docker run -p 8081:80 --name viostream-demo viostream-drupal
```