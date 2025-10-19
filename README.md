#  Exam Management System - Folder Structure

This document explains the organized folder structure of the Exam Management System.

## 📁 Project Structure

```
ExamManagementSystem/
├── index.html                          # Main entry point
├── admin/                              # Admin-related files
│   ├── admin_login.php                 # PHP admin login
│   ├── admin_logout.php                # PHP admin logout
│   ├── adminlogin.html                 # HTML admin login
│   └── manage_invigilator_duties.php   # Admin panel
├── api/                                # PHP API endpoints
│   ├── exams.php                       # Exam management API
│   ├── faculty_auth.php                # Faculty authentication API
│   └── invigilator_duties.php          # Invigilator duties API
├── assets/                             # Static assets (images, etc.)
├── config/                             # Configuration files
│   ├── database.php                    # Database configuration
│   ├── invigilator_duties_schema.sql   # Database schema
│   └── registration_table.sql          # Registration schema
├── faculty/                            # Faculty-related files
│   ├── create-test.html                # Create test page
│   ├── creating_test.html              # Test creation form
│   ├── faculty_front_page.html         # Faculty dashboard
│   ├── facultylogin.html               # Faculty login
│   ├── invigilator-duty.js             # Invigilator duties JavaScript
│   ├── reveiw_question.html            # Review questions
│   ├── view-invigilator-duty.css       # Invigilator duties styles
│   └── view-invigilator-duty.html      # View invigilator duties
├── shared/                             # Shared/common files
│   ├── data_entered_success.html       # Success page
│   ├── edit_profile.html               # Profile editing
│   ├── frontpage.html                  # Landing page
│   ├── PHP_BACKEND_README.md           # PHP backend documentation
│   ├── profile_srk.html                # Profile page
│   ├── README.md                       # Original README
│   ├── registration.js                 # Registration JavaScript
│   └── registration_page.html          # Registration page
└── student/                            # Student-related files
    ├── student_front_page.html         # Student dashboard
    └── studentlogin.html               # Student login
```

## 🎯 Folder Purposes

### `/admin/`
Contains all admin-related functionality:
- **PHP files**: Admin authentication and management panel
- **HTML files**: Admin login interface
- **Features**: Exam management, duty assignment, reports

### `/api/`
Contains all PHP API endpoints:
- **RESTful APIs**: For frontend-backend communication
- **Authentication**: Faculty and admin authentication
- **CRUD Operations**: Create, read, update, delete operations

### `/config/`
Contains configuration and database files:
- **Database config**: Connection settings
- **SQL schemas**: Database structure files
- **Settings**: System configuration

### `/faculty/`
Contains faculty-specific functionality:
- **Dashboard**: Faculty main interface
- **Test creation**: Create and manage tests
- **Invigilator duties**: View and manage duties
- **Authentication**: Faculty login

### `/shared/`
Contains files used by multiple user types:
- **Landing page**: Main entry point
- **Registration**: User registration system
- **Profile management**: User profile editing
- **Documentation**: System documentation

### `/student/`
Contains student-specific functionality:
- **Dashboard**: Student main interface
- **Authentication**: Student login
- **Exam access**: Student exam interface

### `/assets/`
Reserved for static assets:
- **Images**: Icons, logos, graphics
- **Files**: Documents, templates
- **Media**: Videos, audio files

## 🔗 File References

### Updated References
All file references have been updated to work with the new folder structure:

- **Relative paths**: Updated to use `../` for parent directory access
- **API calls**: Updated to use `../api/` from faculty folder
- **Navigation links**: Updated to point to correct folders
- **Asset references**: Updated for new structure

### Key Reference Updates
1. **Faculty JavaScript**: `API_BASE_URL = '../api/'`
2. **Login pages**: Back links point to `../shared/frontpage.html`
3. **Main page**: Login buttons point to respective folders
4. **Admin panel**: Links updated for new structure

## 🚀 Access Points

### Main Entry Points
1. **Root**: `index.html` - Main landing page
2. **Faculty**: `faculty/facultylogin.html` - Faculty login
3. **Student**: `student/studentlogin.html` - Student login
4. **Admin**: `admin/adminlogin.html` - Admin login
5. **Admin Panel**: `admin/admin_login.php` - PHP admin panel

### Quick Access Links
- **Faculty Dashboard**: `faculty/faculty_front_page.html`
- **Invigilator Duties**: `faculty/view-invigilator-duty.html`
- **Admin Panel**: `admin/manage_invigilator_duties.php`
- **Registration**: `shared/registration_page.html`

## 📋 Setup Instructions

### 1. Web Server Setup
Place the entire `ExamManagementSystem` folder in your web server directory:
- **XAMPP**: `htdocs/ExamManagementSystem/`
- **WAMP**: `www/ExamManagementSystem/`
- **Other**: Your web server's document root

### 2. Database Setup
1. Run the SQL files in `/config/` folder
2. Update database credentials in `/config/database.php`
3. Ensure MySQL service is running

### 3. Access the System
1. Navigate to your web server URL
2. Go to `ExamManagementSystem/`
3. Use `index.html` as the main entry point

## 🔧 Development Notes

### Adding New Features
1. **Faculty features**: Add to `/faculty/` folder
2. **Student features**: Add to `/student/` folder
3. **Admin features**: Add to `/admin/` folder
4. **Shared features**: Add to `/shared/` folder
5. **API endpoints**: Add to `/api/` folder

### File Naming Conventions
- **HTML files**: Use lowercase with hyphens (`faculty-login.html`)
- **PHP files**: Use lowercase with underscores (`faculty_auth.php`)
- **CSS files**: Use lowercase with hyphens (`view-invigilator-duty.css`)
- **JS files**: Use lowercase with hyphens (`invigilator-duty.js`)

### Best Practices
1. **Keep related files together**: Group by functionality
2. **Use relative paths**: For portability
3. **Update references**: When moving files
4. **Document changes**: Update this README

## 🐛 Troubleshooting

### Common Issues
1. **Broken links**: Check relative path references
2. **API not working**: Verify API_BASE_URL in JavaScript
3. **CSS not loading**: Check CSS file paths
4. **Images not showing**: Verify asset paths

### File Path Issues
- **From faculty folder**: Use `../` to access parent directory
- **From student folder**: Use `../` to access parent directory
- **From admin folder**: Use `../` to access parent directory
- **From shared folder**: Use `../` to access parent directory

## 📞 Support

For issues with the folder structure:
1. Check file paths and references
2. Verify all files are in correct folders
3. Test navigation between pages
4. Check browser console for errors

The organized structure makes the system more maintainable and easier to navigate for developers and users alike.
