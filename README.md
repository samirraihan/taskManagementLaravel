<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

Task Management System

## About Installation:
This project is dockerized.

** This project will run faster on WSL Winsows SubSystem for Linux / Linux. **

1. Please clone the project from:
https://github.com/samirraihan/taskManagementLaravel.git
2. cp .env.example .env
3. php artisan key:generate
4. composer install

5. Now install docker destop / docker on your pc/laptop
6. docker-compose build
7. docker-compose up -d

8. composer install/ update (if fails to install at step 4)
9. php artisan migrate
10. Run the project on http://localhost:8050/
11. I have tested sending emails by using Mailtrap. So can test using Mailtrap.
12. I have added PostMan Collection if needed to test. Root->postmanCollection

DB details:

    DB_CONNECTION=mysql

    DB_HOST=taskmgmtlaravel_db

    DB_PORT=3306

    DB_DATABASE=taskmgmtlaravelDB

    DB_USERNAME=taskmgmtlaravel

    DB_PASSWORD=taskmgmtlaravel93

Thank You
