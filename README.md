# Satellite Tracker Information System

A comprehensive web application for tracking satellites in close-to-real-time, processing satellite data, and generating statistical reports. This system provides tools for importing, managing, exporting and visualizing satellite information with role-based access control.

## Project Overview

This information system provides:
- Close-to-real-time satellite tracking and visualization
- Data import from various satellite data formats
- Statistical analysis and data visualization
- User management with role-based access control
- Data export in standard formats (JSON, XML)

## Project Structure

```
VBIS-main/
├── controllers/       # Application controllers (MVC pattern)
├── core/              # Core framework files and system components
├── models/            # Data models for database interaction
├── mysql_dump_files/  # Database backup and initialization files
├── public/            # Web accessible files
│   ├── assets/        # CSS, JS, images, and libraries
│   │   ├── css/       # Stylesheets and Material Design icons
│   │   ├── img/       # Image resources
│   │   ├── js/        # JavaScript files and libraries
│   │   └── libs/      # Third-party libraries (jQuery, OpenLayers)
│   ├── sattelite-tracker/ # Satellite tracking visualization component (based on kkingsbe/sattelite-tracker)
│   ├── .htaccess      # Apache URL rewriting rules
│   └── index.php      # Application entry point
├── uploads/           # Protected storage for user uploaded files
├── vendor/            # Composer dependencies
├── views/             # View templates
│   ├── account/       # User account management views
│   ├── layouts/       # Page layout templates
│   ├── reports/       # Statistical reports and visualizations
│   └── satellites/    # Satellite data management views
├── composer.json      # Composer configuration
└── import_demo_data.php # Utility for importing demonstration data
```

## User Access Levels

The system implements role-based access control with two user levels:

1. **Regular Users**
   - Access requires login
   - View satellite listings and details
   - Manage personal account settings
   - View satellite statistics

2. **Administrators**
   - Full system access
   - Import and manage satellite data
   - Remove duplicate entries
   - View advanced statistics and reports
   - Manage user accounts

## Features

### Authentication System
- Secure login with email and password
- User registration with validation
- Global authentication protection for all pages
- Automatic redirection to login page for unauthenticated users
- Session management and security

### Satellite Management
- Comprehensive satellite listings with filtering
- Detailed satellite information pages
- Satellite categorization and organization

### Data Import and Processing
- Support for multiple satellite data formats:
  - Standard TLE (Two-Line Element) files
  - 3LE (Three-Line Element) files
  - XML formats including OMM (Orbit Mean-Elements Message)
- Automatic format detection and processing
- Duplicate detection and handling
- Large dataset support with batch processing

### Real-time Satellite Tracking
- Interactive map visualization using OpenLayers
- Close-to-real-time orbital calculations with satellite.js
- Position tracking and visualization

### Statistical Analysis and Reporting
- Data filtering by multiple criteria
- Statistical calculations and summaries
- Advanced data visualization:
  - Bar charts for numerical distribution
  - Pie charts for percentage analysis
  - Scatter plots for parameter correlation
- Import statistics and performance metrics

### Data Export
- Export satellite data in JSON/XML format
- Export statistical reports and analysis in JSON/XML format

## Technical Implementation

### Backend
- PHP 7.4+ with custom MVC framework
- MySQL database for data storage
- RESTful URL structure and routing

### Frontend
- HTML5 and CSS3 with responsive design
- JavaScript for interactive elements
- Chart.js for data visualization
- Material Design Icons for consistent UI

### Security Features
- Protected file storage
- Role-based access control
- Input validation and sanitization
- Secure password handling
- Global authentication check for all pages
- Automatic redirection to login page for unauthenticated users

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Composer for dependency management

### Installation Steps

1. Ensure your web server (XAMPP, WAMP, etc.) is installed and running.

2. Place the application files in your web server's document root or a subdirectory:
   ```
   C:\xampp\htdocs\VBIS-main\
   ```

3. Install dependencies using Composer:
   ```
   cd C:\xampp\htdocs\VBIS-main
   composer install
   ```

4. Create a MySQL database for the application.

5. Import the database schema from the mysql_dump_files directory:
   ```
   mysql -u username -p database_name < mysql_dump_files/vbis_schema.sql
   ```

6. Configure your database connection in `core/DbConnection.php`.

7. Make sure Apache has mod_rewrite enabled and AllowOverride is set to All in your Apache configuration.

8. Access the application at:
   ```
   http://localhost/VBIS-main/public/
   ```

## Main URLs

- Base URL: `/VBIS-main/public/`
- Login: `/VBIS-main/public/login`
- Registration: `/VBIS-main/public/registration`
- Satellites List: `/VBIS-main/public/satellites`
- Satellite Detail: `/VBIS-main/public/satelliteDetail?id=X`
- Satellite Statistics: `/VBIS-main/public/satelliteStatistics`
- Import Satellites: `/VBIS-main/public/importSatellites` (admin only)
- Import Statistics: `/VBIS-main/public/importStatistics` (admin only)
- Remove Duplicates: `/VBIS-main/public/removeDuplicates` (admin only)
- Manage Accounts: `/VBIS-main/public/accounts` (admin only)

## Authentication System

The application implements a comprehensive authentication system with the following features:

- **Global Authentication Check**: All pages and resources are protected by a centralized authentication system.
- **Automatic Redirection**: Unauthenticated users are automatically redirected to the login page for any protected resource.
- **Role-Based Access**: Beyond basic authentication, specific pages require appropriate user roles.
- **Public Path Exceptions**: Certain paths like login, registration, and static assets are accessible without authentication.
- **Session Management**: Secure session handling with proper validation and timeout controls.

This ensures that all application functionality is protected behind authentication, providing a secure user experience while maintaining ease of access to authorized users.

## Troubleshooting

### URL Rewriting Issues

If you encounter 404 errors or URL routing problems:

1. Ensure mod_rewrite is enabled in Apache:
   ```
   sudo a2enmod rewrite
   sudo service apache2 restart
   ```

2. Check that .htaccess files are being processed (AllowOverride All in Apache config).

3. Verify the RewriteBase in public/.htaccess is set correctly to `/VBIS-main/public/`.

### Database Connection Issues

If you have database connection problems:

1. Verify your database credentials in `core/DbConnection.php`.
2. Run the test_connection.php script to diagnose connection issues.

### Import Performance Issues

For large data imports:

1. Increase PHP execution time limit in your php.ini file:
   ```
   max_execution_time = 600
   ```
2. Increase PHP memory limit if needed:
   ```
   memory_limit = 256M
   ```

## External Tools and Libraries

This project utilizes the following external resources:

- [satellite-tracker](https://github.com/kkingsbe/sattelite-tracker) - Base satellite tracking component
- [OpenLayers](https://openlayers.org/) - Interactive map visualization library
- [Material Design Icons](https://github.com/Templarian/MaterialDesign-Webfont) - Icon set for UI elements
- [Chart.js](https://www.chartjs.org/) - JavaScript charting library for data visualization
- [Bootstrap](https://getbootstrap.com/) - Frontend framework for responsive UI components
- [jQuery](https://jquery.com/) - JavaScript library for DOM manipulation

## License

This project is licensed under the GNU GPL v3 License - see the LICENSE file for details.
Portions of the project sourced externally are licensed under the MIT License - see the LICENSE.OLD file for details.
