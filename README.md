# E-KSWP (Koperasi Syariah Warga Polban Elektronik)

## About E-KSWP
E-KSWP (Koperasi Syariah Warga Polban Elektronik) is a web application built using the Laravel framework and Inertia. It serves as a platform for managing cooperative activities, providing users with a seamless experience for various cooperative-related tasks.

Features of E-KSWP include:
- User authentication and registration
- Cooperative member management
- Savings management
- Murabahah financing management
- Murabahah installment tracking

## Installation
To set up the E-KSWP application locally, follow these steps:
1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/e-kswp.git
   ```
2. Navigate to the project directory:
   ```bash
   cd e-kswp
    ```
3. Install the dependencies using Composer:
    ```bash
    composer install
    ```
4. Copy the example environment file and configure it:
    ```bash
    cp .env.example .env
    ```
    Update the `.env` file with your database and other configuration settings.
5. Generate an application key:
    ```bash
    php artisan key:generate
    ```
6. Run the database migrations:
    ```bash
    php artisan migrate
    ```
7. Run the database seeders to populate initial data:
    ```bash
    php artisan db:seed
    ```
8. Start the development server:
    ```bash
    php artisan serve
    npm run dev
    ```

## Copyright
This project is developed and maintained by Final Project Team KoTA-203 2026. All rights reserved.
[Team Members]
- Alanna Tanisya Anwar (231511034)
- Dhira Ramadini (231511041)
- Erina Dwi Yanti (231511043)
