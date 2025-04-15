# Junior Backend Developer Test - Power Commerce

## Overview

This document outlines the work completed during the 7-day Junior Backend Developer test that I took as part of the Power Commerce interview process. The goal was to build a simple backend system capable of handling user authentication, transaction management, and account summary retrieval.

---

## Completed Tasks

### 1. **User Registration & Authentication**

- **Implemented User Registration:**
  - **Endpoint:** `POST /auth/register`
  - Validates user data (e.g., ensuring email uniqueness, password strength).
  - Registers users in the MySQL database.

- **Implemented User Login:**
  - **Endpoint:** `POST /auth/login`
  - Authenticates the user and returns a JWT token for subsequent requests.
  - Ensured secure password handling (hashing).

### 2. **Transaction Management**

- **Implemented Deposit:**
  - **Endpoint:** `POST /transactions/deposit`
  - Allows authenticated users to deposit funds.
  - Validates deposits (e.g., no negative amounts).

- **Implemented Withdrawal:**
  - **Endpoint:** `POST /transactions/withdraw`
  - Allows authenticated users to withdraw funds, with validation ensuring sufficient balance.

- **Implemented Account Summary:**
  - **Endpoint:** `GET /transactions/summary`
  - Retrieves a summary of the user’s account, including:
    - Average deposit and withdrawal amounts.
    - Total balance.
    - Last 7 transactions.

### 3. **Data Validation**
- Implemented input validation for deposits and withdrawals (e.g., no negative values for deposits, ensuring sufficient funds for withdrawals).
- Applied proper error handling to return informative responses for invalid actions (e.g., insufficient balance for withdrawal).

### 4. **Database Integration**

- Set up MySQL database tables for users and transactions.
- Seeded the database with AI-generated sample data for users and transactions.
- Ensured referential integrity between users and their transactions.

### 5. **API Documentation**

- **Swagger-PHP API Documentation:**
  - Documented all API endpoints, including required parameters and responses.
  - Setup Swagger for interactive documentation.

---

## Obligations During Implementation

### **Code Quality:**
- Ensured code is clean and well-structured, adhering to best practices.
- Focused on readability and maintainability of the codebase.

### **Security Best Practices:**

#### **JWT-Based Authentication:**
- Implemented JWT for secure login and session management.
- Ensured token validity with proper expiration times and secure storage on the client side.

#### **Input Validation & Sanitization:**
- Applied strict input validation and sanitization rules across the application, checking for valid email formats, password strength, and acceptable numerical ranges for transactions.

#### **Password Security:**
- Used **bcrypt** for password hashing to ensure user credentials are stored securely.
- Integrated **Zxcvbn** for password strength evaluation to prevent weak passwords during user registration.

#### **SQL Injection Protection:**
- Used **SQL query binding** to avoid SQL injection vulnerabilities, ensuring user input is properly sanitized and validated before being executed in the database.

#### **Error Handling & Responses:**
- Followed security protocols when generating error messages and API responses, ensuring sensitive information is not exposed (e.g., avoiding stack traces or detailed internal errors in production).

#### **Authentication & Authorization:**
- Ensured that routes requiring user authentication are properly protected by JWT middleware, allowing access only to authenticated users.


### **Testing:**
- Wrote unit tests for all critical endpoints (e.g., user registration, login, deposit, withdrawal, account summary).
- Focused on testing edge cases like invalid data, empty inputs, and error handling.
- Added SQL injection tests for additional security assurance.

---

## API Usage Instructions

1. **Clone the repository:**

    > If you don't have Git installed, download it from [git-scm.com](https://git-scm.com/downloads)

    ```bash
    git clone https://github.com/DurakovicB/SimpleLogin.git
    ```

2. **Install dependencies:**

    > If you don't have Composer installed, download it from [getcomposer.org](https://getcomposer.org/download/)

    ```bash
    composer install
    ```


3. **Setup Environment Variables:**
   - Copy the `.env.example` file to `.env`.
   - Update the `.env` file with your local database credentials and JWT secret.

4. **Install and Set Up MySQL:**

    - Install MySQL Server 8.0.33 - or the nearest version available (if not already installed).
    https://dev.mysql.com/downloads/installer/
    
    - You can also install MySQL Workbench (or any database management tool compatible with MySQL):
    https://dev.mysql.com/downloads/workbench/
    
    - Create a MySQL connection

5.  **Run the SQL Script to Set Up the Database Schema:**

    - Run the schema creation script  `/database/setup_database.sql` as a MySQL query.


6.  **Run the server - PHP:**
    - The project was built using PHP 8.0.29; 8.1 works fine as well.
    Download ZIP (for Windows):
    https://windows.php.net/downloads/releases/php-8.1.32-Win32-vs16-x64.zip

    -Start the local server:
    ```bash
    php -S localhost:8000 -t public
    ```

7.  **Run Tests**
    - You must have the server running and the database properly set up with test data.

    ***For Bash/Linux/Mac:***
    ```bash
    ./vendor/bin/phpunit 
    ```

    ***For Windows CMD:***
    ```cmd
    php -d memory_limit=512M vendor\bin\phpunit 
    ```
    - Using --debug after these commands will show which tests are being run.