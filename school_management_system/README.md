# School Management System

A comprehensive web-based school management system with role-based access control for Admin, Teachers, Cashiers, and Students.

## Features

### Admin Module (Full Access)
- **Dashboard**: Complete overview with statistics and recent activities
- **Student Management**: Add, edit, view, and manage all student information
- **Teacher Management**: Manage teaching staff and assignments
- **Fee Management**: Collect fees, manage fee structure, and view all payments
- **Expense Management**: Track and approve all school expenses
- **Reports**: Generate comprehensive reports for all modules
- **School Information**: Manage school details and settings
- **User Management**: Create and manage user accounts

### Teacher Module (Class-Related Access)
- **Dashboard**: Class-specific overview and statistics
- **My Classes**: View assigned classes and student lists
- **Attendance**: Mark and manage student attendance
- **Academic Records**: Enter and manage student grades and performance
- **Reports**: Generate class and subject-specific reports

### Cashier Module (Fee-Related Access Only, No Delete)
- **Dashboard**: Fee collection overview and statistics
- **Fee Collection**: Record fee payments with receipt generation
- **Student Search**: Search students by class or village for fee collection
- **Fee Reports**: View and export fee-related reports
- **Receipt Management**: View and reprint receipts

### Student Module (View-Only Access)
- **Dashboard**: Personal overview with academic and fee summary
- **Profile**: View complete personal information
- **Academic Records**: View grades and performance history
- **Fee History**: View all fee payments and outstanding amounts
- **Attendance**: View attendance records
- **Documents**: Access personal documents and certificates

## Installation Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Modern web browser
- PHP 7.4 or higher

### Setup Steps

1. **Start XAMPP Services**
   - Start Apache and MySQL services from XAMPP Control Panel

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `school_management`
   - Import the core database structure and data (REQUIRED):
     ```sql
     source C:/xampp/htdocs/school_management_system/config/schema_core.sql
     ```
   - Optionally, import extended features for CMS, Library, and advanced modules:
     ```sql
     source C:/xampp/htdocs/school_management_system/config/schema_extras.sql
     ```

3. **File Permissions & Directory Setup**
   - Ensure the following directories exist and have write permissions:
     ```
     uploads/
     uploads/students/
     uploads/students/thumbnails/
     assets/images/
     ```
   - On Windows (XAMPP): Right-click folders → Properties → Security → Give full control to Users
   - On Linux/Mac: `chmod 755 uploads/ uploads/students/ uploads/students/thumbnails/ assets/images/`

4. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/school_management_system`
   - Use the login credentials below

## Default Login Credentials

### Admin Access
- **Username**: admin
- **Password**: password123
- **Access**: Full system access

### Teacher Access
- **Username**: teacher1
- **Password**: password123
- **Access**: Class and academic management

### Cashier Access
- **Username**: cashier1
- **Password**: password123
- **Access**: Fee collection only (no delete permissions)

### Student Access
- **Username**: student1
- **Password**: password123
- **Access**: View personal data only

## Key Features

### Student Information Fields
- Basic Details: Name, Father's Name, Mother's Name, Date of Birth, Age (auto-calculated)
- Personal Info: Blood Group, Category, Religion, Gender
- Contact: Mobile Numbers, Email, Address, Village, Pincode
- Government IDs: Aadhar Number, Samagra ID, PAN Number, Scholar Number
- Academic: Class, Academic Year, Admission Date, Admission Number
- **Photo Management**: 
  - Upload student photos (JPG, PNG, GIF) - Required for new students
  - Automatic image optimization and thumbnail generation (200x200px)
  - Maximum file size: 5MB (configurable in system settings)
  - Secure file storage with .htaccess protection
  - Fallback to SVG placeholders when no photo is available

### Fee Management
- **Collection Methods**: Cash, Online, Cheque, Demand Draft
- **Fee Types**: Tuition, Admission, Examination, Sports, Library, Development, Transport
- **Student Filtering**: By Class or Village for easy selection
- **Receipt Generation**: Auto-generated receipt numbers
- **Payment Tracking**: Complete payment history and reports

### Expense Management
- **Categories**: Salaries, Utilities, Maintenance, Supplies, Equipment, etc.
- **Approval System**: Created by and approved by tracking
- **Voucher System**: Auto-generated voucher numbers
- **Expense Reports**: Detailed expense tracking and analysis

### Reports System
- **Student Reports**: Complete student information with filters
- **Fee Reports**: Collection summaries and detailed payment records
- **Expense Reports**: Category-wise and date-wise expense analysis
- **Financial Reports**: Income vs expense analysis with charts
- **Class-wise Reports**: Student distribution and performance
- **Village-wise Reports**: Geographic distribution analysis

### Security Features
- **Role-based Access Control**: Different permissions for each user type
- **Session Management**: Secure login and logout functionality
- **Data Protection**: Input validation and SQL injection prevention
- **Password Security**: Encrypted password storage

## Modern UI Features

### Categorized Sidebar Navigation
The system features a modern, responsive sidebar navigation with:

- **Categorized Menu Structure**: Organized by functional areas (Dashboard, Academic Management, Financial Management, etc.)
- **Role-Based Access Control**: Each user role sees only relevant menu categories
- **Glassmorphism Design**: Modern transparent sidebar with blur effects
- **Smooth Animations**: Collapsible submenus with smooth transitions
- **Mobile Responsive**: Overlay sidebar for mobile devices with scroll lock
- **Accessibility**: Full keyboard navigation and screen reader support
- **Bootstrap Icons**: Consistent iconography replacing emoji symbols

### Navigation Structure by Role

#### Admin (Full Access)
- Dashboard
- Academic Management (Students, Teachers, Classes, Subjects, Attendance)
- Financial Management (Fees, Expenses, Reports)
- Communication (Messages, Gallery, News)
- System Management (School Info, Users, Settings)

#### Teacher (Class-Focused)
- Dashboard
- Class Management (Classes, Students, Attendance)
- Academic Records (Grades, Reports)
- Profile

#### Cashier (Fee-Focused)
- Dashboard
- Fee Management (Collection, Receipts, Reports)
- Student Search
- Profile

#### Student (View-Only)
- Dashboard
- Academic (Records, Attendance, Documents)
- Financial (Fee History)
- Profile

### Developer Hooks

#### Adding New Menu Items
To add new menu items, edit the `getMenuCategories()` function in `/includes/sidebar.php`:

```php
// Example: Adding a new item to admin Academic Management
'Academic Management' => [
    // ... existing items ...
    ['icon' => 'journal-bookmark', 'title' => 'Homework', 'url' => $base_path . 'homework.php']
]
```

#### JavaScript Events
The sidebar system dispatches custom events for integration:

```javascript
// Listen for sidebar state changes
window.addEventListener('sidebar:changed', function(e) {
    console.log('Sidebar collapsed:', e.detail.collapsed);
    // Resize charts, update layouts, etc.
});

// Access sidebar API
if (window.SidebarController) {
    window.SidebarController.toggleSubmenu('academic-management');
    window.SidebarController.collapseAll();
}
```

#### CSS Customization
Modify CSS variables in `/assets/css/modern-ui.css`:

```css
:root {
    --sidebar-width: 260px;         /* Desktop sidebar width */
    --sidebar-collapsed-width: 60px; /* Collapsed sidebar width */
    --primary-color: #3b82f6;       /* Main brand color */
    --transition-normal: 0.3s;      /* Animation speed */
}
```

## Technical Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Modern CSS with glassmorphism design and CSS custom properties
- **Icons**: Bootstrap Icons v1.11.0
- **Responsive**: Mobile-first design with advanced accessibility features

## File Structure
```
school_management_system/
├── admin/          # Admin module files
├── teacher/        # Teacher module files
├── cashier/        # Cashier module files
├── student/        # Student module files
├── assets/         # CSS, JS, and image files
├── config/         # Database configuration
├── includes/       # Common PHP includes
├── uploads/        # File upload storage
├── reports/        # Generated reports
├── index.php       # Public home page
├── login.php       # Login page
└── logout.php      # Logout functionality
```

## Customization

### Adding New Fee Types
Edit the fee type options in the respective fee collection forms in both admin and cashier modules.

### Adding New Subjects
Use the admin panel to add new subjects and assign them to classes and teachers.

### Modifying User Roles
Edit the database schema to add new roles or modify existing permissions.

### Styling Changes
Modify the CSS variables in `assets/css/style.css` to change colors, fonts, and layout.

### Modern Sidebar Implementation

#### Files Modified/Created
- `/includes/sidebar.php` - PHP menu generation with role-based filtering
- `/assets/css/modern-ui.css` - Enhanced with glassmorphism sidebar styling
- `/assets/js/sidebar.js` - Complete sidebar controller with mobile support
- All dashboard files updated with mobile overlay and new includes

#### Key Features Implemented
1. **Responsive Design**: Mobile overlay with scroll lock and backdrop blur
2. **Smooth Animations**: Cubic-bezier transitions with GPU acceleration
3. **Accessibility**: ARIA attributes, focus management, and keyboard navigation
4. **Performance**: localStorage state persistence and optimized DOM manipulation
5. **Backward Compatibility**: Works with existing modern-ui.js functionality

#### Keyboard Shortcuts
- `Tab`: Navigate through sidebar links
- `Enter/Space`: Activate menu toggles
- `Escape`: Close mobile sidebar
- `Arrow Keys`: Navigate submenu items

#### Browser Support
- Chrome 88+, Firefox 78+, Safari 14+, Edge 88+
- Graceful degradation for older browsers
- No-JavaScript fallback with expanded sidebar

## Support

For technical support or customization requests, please contact the development team.

## License

This school management system is developed for educational institutions. All rights reserved.
