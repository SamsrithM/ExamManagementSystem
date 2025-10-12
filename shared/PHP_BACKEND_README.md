# Invigilator Duty Management System - PHP Backend

This document provides setup and usage instructions for the PHP backend implementation of the Invigilator Duty Management System.

## Features

- **Faculty View**: View upcoming and past invigilator duties
- **Admin Panel**: Manage exams and assign invigilator duties
- **Real-time Updates**: Mark attendance and update duty status
- **Database Integration**: MySQL database with proper relationships

## File Structure

```
ExamManagementSystem/
├── api/
│   ├── invigilator_duties.php    # API for duty management
│   ├── faculty_auth.php          # Faculty authentication
│   └── exams.php                 # Exam management API
├── admin/
│   ├── admin_login.php           # Admin login page
│   ├── admin_logout.php          # Admin logout
│   └── manage_invigilator_duties.php  # Admin panel
├── config/
│   └── database.php              # Database configuration
├── invigilator_duties_schema.sql # Database schema
├── view-invigilator-duty.html    # Updated frontend
├── invigilator-duty.js           # Updated JavaScript
└── view-invigilator-duty.css     # Updated styles
```

## Setup Instructions

### 1. Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE reg;
   USE reg;
   ```

2. **Run Schema File**:
   ```bash
   mysql -u root -p reg < invigilator_duties_schema.sql
   ```

3. **Update Database Configuration**:
   Edit `config/database.php` and update the database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'reg';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

### 2. Web Server Setup

1. **XAMPP/WAMP Setup**:
   - Install XAMPP or WAMP
   - Place the project files in the `htdocs` directory
   - Start Apache and MySQL services

2. **Alternative Setup**:
   - Use any web server with PHP 7.4+ and MySQL support
   - Ensure PHP PDO extension is enabled

### 3. Sample Data

The schema file includes sample data:
- 4 sample exams
- 8 invigilator duty assignments
- 7 exam class records
- Faculty members (from existing registration system)

## Usage

### Faculty View

1. **Access**: Navigate to `view-invigilator-duty.html`
2. **Features**:
   - View upcoming duties (within 7 days)
   - View past duties
   - Mark attendance (Present/Absent)
   - View detailed information about each duty

### Admin Panel

1. **Login**: Navigate to `admin/admin_login.php`
   - Username: `admin`
   - Password: `admin123`

2. **Features**:
   - **Manage Exams**: Create, edit, delete exams
   - **Assign Duties**: Assign faculty to invigilator duties
   - **Reports**: Generate duty reports

## API Endpoints

### Invigilator Duties API (`api/invigilator_duties.php`)

- `GET ?type=upcoming&faculty_id=X` - Get upcoming duties
- `GET ?type=past&faculty_id=X` - Get past duties
- `GET ?type=duty&duty_id=X` - Get specific duty details
- `PUT` - Update duty status (mark attendance)

### Faculty Auth API (`api/faculty_auth.php`)

- `POST` - Faculty login
- `GET ?action=profile` - Get faculty profile
- `GET ?action=list` - Get all faculty

### Exams API (`api/exams.php`)

- `GET` - Get all exams
- `GET ?type=upcoming` - Get upcoming exams
- `GET ?id=X` - Get specific exam
- `POST` - Create new exam
- `PUT` - Update exam
- `DELETE ?id=X` - Delete exam

## Database Schema

### Tables

1. **exams**: Store exam information
2. **invigilator_duties**: Store duty assignments
3. **exam_classes**: Store class information for exams
4. **faculty**: Faculty information (extends existing table)

### Key Relationships

- `invigilator_duties.exam_id` → `exams.id`
- `invigilator_duties.faculty_id` → `faculty.id`
- `exam_classes.exam_id` → `exams.id`

## Customization

### Adding New Features

1. **New API Endpoints**: Add to appropriate API files
2. **Database Changes**: Update schema and run migrations
3. **Frontend Updates**: Modify HTML/JS/CSS files

### Security Considerations

1. **Authentication**: Implement proper session management
2. **Input Validation**: Add server-side validation
3. **SQL Injection**: Use prepared statements (already implemented)
4. **Password Hashing**: Implement proper password hashing

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **API Not Working**:
   - Check PHP error logs
   - Verify file permissions
   - Ensure CORS headers are set

3. **Frontend Not Loading Data**:
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check network tab for failed requests

### Debug Mode

Enable PHP error reporting by adding to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Production Deployment

1. **Security**:
   - Change default admin credentials
   - Implement proper authentication
   - Use HTTPS
   - Validate all inputs

2. **Performance**:
   - Enable PHP opcache
   - Use database connection pooling
   - Implement caching

3. **Monitoring**:
   - Set up error logging
   - Monitor database performance
   - Track API usage

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP and MySQL error logs
3. Verify database schema matches the provided SQL file
4. Ensure all file permissions are correct

## Demo Data

The system includes demo data for testing:
- Faculty ID 1 has upcoming and past duties
- Multiple exams with different statuses
- Various duty assignments for testing

Use Faculty ID 1 in the frontend to see sample data, or create new exams and assignments through the admin panel.
