# 🩸 Blood Bank Management System

A comprehensive web-based application designed to streamline and digitize blood bank operations in healthcare facilities. Built with modern web technologies to manage the complete blood donation lifecycle from donor registration to blood distribution.

![Blood Bank System](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

## 🌟 Features

### 👥 Donor Management
- **Comprehensive Donor Profiles**: Personal information, medical history, blood group, and donation records
- **Eligibility Tracking**: Automatic calculation of donation intervals based on medical guidelines
- **Search & Filter**: Advanced search functionality by donor ID, name, phone, or blood group
- **Donation History**: Complete tracking of past donations and medical screenings

### 🩸 Blood Collection Workflow
- **Pre-Donation Screening**: Medical examination with vital signs recording
- **Safety Checklists**: Comprehensive pre and post-collection safety protocols
- **Automated Bag Numbering**: Unique bag number generation with barcode support
- **Component Management**: Support for whole blood, RBC, plasma, and platelets
- **Expiry Calculation**: Automatic expiry date calculation based on component type

### 📦 Inventory Management
- **Real-Time Tracking**: Live inventory status by blood group and component type
- **Storage Management**: Temperature-controlled storage location tracking
- **Expiry Alerts**: Automated notifications for expiring blood units
- **Low Stock Warnings**: Inventory level monitoring and alerts
- **Wastage Tracking**: Complete audit trail for disposed units

### 🏥 Request Processing
- **Hospital Requests**: Streamlined blood request submission system
- **Approval Workflow**: Multi-level approval process with notifications
- **Distribution Tracking**: Complete documentation from request to delivery
- **Emergency Requests**: Priority handling for urgent blood requirements

### 🔒 Security & Compliance
- **User Authentication**: Secure login system with role-based access
- **Data Validation**: Comprehensive input validation and sanitization
- **Audit Trails**: Complete logging of all system activities
- **Regulatory Compliance**: Adherence to blood bank standards and guidelines

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0
- **AJAX**: jQuery 3.6.0
- **Server**: Apache/Nginx

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser with JavaScript enabled

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/KingPrerak/blood-bank.git
   cd blood-bank
   ```

2. **Database Setup**
   ```bash
   # Create database and import schema
   mysql -u username -p
   CREATE DATABASE bloodbank_management;
   USE bloodbank_management;
   SOURCE database/bloodbank_schema.sql;
   ```

3. **Configuration**
   ```php
   // Update config/config.php with your database credentials
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'bloodbank_management');
   ```

4. **Web Server Setup**
   - Place files in your web server directory (htdocs/www)
   - Ensure proper permissions for file uploads
   - Configure virtual host if needed

5. **Access the Application**
   ```
   http://localhost/bloodbank
   ```

## 👤 Default Login

- **Username**: `admin`
- **Password**: `admin123`

*Please change default credentials after first login*

## 🗂️ Project Structure

```
blood-bank/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── ajax/
│   ├── search_donor.php
│   ├── process_blood_collection.php
│   └── ...
├── config/
│   └── config.php
├── pages/
│   ├── donor-registration.php
│   ├── blood-collection.php
│   └── ...
├── database/
│   └── bloodbank_schema.sql
├── dashboard.php
├── index.php
└── README.md
```

## 🔧 Configuration

### Database Configuration
Update `config/config.php` with your database settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'bloodbank_management');

// Application Settings
define('DONATION_INTERVAL_DAYS', 90);
define('MIN_DONATION_AGE', 18);
define('MAX_DONATION_AGE', 65);
```

## 🧪 Testing

Run the setup verification script:
```
http://localhost/bloodbank/setup_enhanced_blood_collection.php
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Prerak Patel**
- GitHub: [@KingPrerak](https://github.com/KingPrerak)
- LinkedIn: [Prerak Patel](https://linkedin.com/in/prerak-patel)

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Font Awesome for the icon library
- jQuery team for the JavaScript library
- PHP community for excellent documentation

## 📞 Support

If you encounter any issues or have questions:

1. Check the [Issues](https://github.com/KingPrerak/blood-bank/issues) page
2. Create a new issue with detailed description
3. Contact the maintainer

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Enhanced blood collection workflow
- **v1.2.0** - Added inventory management features
- **v1.3.0** - Improved security and validation

## 🚨 Important Notes

- This system is designed for educational and healthcare purposes
- Ensure compliance with local healthcare regulations
- Regular backups are recommended for production use
- Change default credentials before deployment

---

⭐ **Star this repository if you find it helpful!**

Made with ❤️ for healthcare facilities worldwide
