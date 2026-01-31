<?php

declare(strict_types=1);

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$routes = new RouteCollection();

// Public pages
$routes->add('home', new Route(
    '/',
    ['_script' => __DIR__ . '/../public/students/index.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('admin', new Route(
    '/admin',
    ['_script' => __DIR__ . '/../public/login_page.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('admin_slash', new Route(
    '/admin/',
    ['_script' => __DIR__ . '/../public/login_page.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('register', new Route(
    '/register',
    ['_script' => __DIR__ . '/../public/register.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
// Route alias retained to avoid broken links after deprecating welcome.php
$routes->add('welcome', new Route(
    '/welcome',
    ['_script' => __DIR__ . '/../public/menu_1.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('account_manager', new Route(
    '/account/manager',
    ['_script' => __DIR__ . '/../public/account_manager.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('admin/account_manager', new Route(
    '/admin/account/manager',
    ['_script' => __DIR__ . '/../public/account_manager.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('account_manager_action', new Route(
    '/account/manager/action',
    ['_script' => __DIR__ . '/../public/account_manager_actions.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/account_manager_action', new Route(
    '/admin/account/manager/action',
    ['_script' => __DIR__ . '/../public/account_manager_actions.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('menu_1', new Route(
    '/menu-1',
    ['_script' => __DIR__ . '/../public/menu_1.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_1', new Route(
    '/admin/menu-1',
    ['_script' => __DIR__ . '/../public/menu_1.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('menu_2', new Route(
    '/menu-2',
    ['_script' => __DIR__ . '/../public/menu_2.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_2', new Route(
    '/admin/menu-2',
    ['_script' => __DIR__ . '/../public/menu_2.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('menu_3', new Route(
    '/menu-3',
    ['_script' => __DIR__ . '/../public/menu_3.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_3', new Route(
    '/admin/menu-3',
    ['_script' => __DIR__ . '/../public/menu_3.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('menu_4', new Route(
    '/menu-4',
    ['_script' => __DIR__ . '/../public/menu_4.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_4', new Route(
    '/admin/menu-4',
    ['_script' => __DIR__ . '/../public/menu_4.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('menu_5', new Route(
    '/menu-5',
    ['_script' => __DIR__ . '/../public/menu_5.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_5', new Route(
    '/admin/menu-5',
    ['_script' => __DIR__ . '/../public/menu_5.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('menu_6', new Route(
    '/menu-6',
    ['_script' => __DIR__ . '/../public/menu_6.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('admin/menu_6', new Route(
    '/admin/menu-6',
    ['_script' => __DIR__ . '/../public/menu_6.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('logout', new Route(
    '/logout',
    ['_script' => __DIR__ . '/../public/logout.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));

// Form actions / APIs
$routes->add('process_login', new Route(
    '/process-login',
    ['_script' => __DIR__ . '/../public/process_login.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/process_login', new Route(
    '/admin/process-login',
    ['_script' => __DIR__ . '/../public/process_login.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/forgot_password', new Route(
    '/admin/forgot-password',
    ['_script' => __DIR__ . '/../public/admin_forgot_password.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/reset_password', new Route(
    '/admin/reset-password',
    ['_script' => __DIR__ . '/../public/admin_reset_password.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('process_register', new Route(
    '/process-register',
    ['_script' => __DIR__ . '/../public/process_register.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
// Student-specific auth and onboarding routes (mirror admin patterns)
$routes->add('students/process-register', new Route(
    '/students/process-register',
    ['_script' => __DIR__ . '/../public/students/process-register.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('students/login', new Route(
    '/students/login',
    ['_script' => __DIR__ . '/../public/students/login.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('students/forgot_password', new Route(
    '/students/forgot-password',
    ['_script' => __DIR__ . '/../public/students_forgot_password.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('students/reset_password', new Route(
    '/students/reset-password',
    ['_script' => __DIR__ . '/../public/students_reset_password.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
// Student homepage and profile alias
$routes->add('students/home', new Route(
    '/students/home',
    ['_script' => __DIR__ . '/../public/students/Homepage.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('students/profile-setup', new Route(
    '/students/profile-setup',
    ['_script' => __DIR__ . '/../public/students/start.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('students/process-login', new Route(
    '/students/process-login',
    ['_script' => __DIR__ . '/../public/students/process-login.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('students/verify-otp', new Route(
    '/students/verify-otp',
    ['_script' => __DIR__ . '/../public/students/verify_otp.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('students/resend-otp', new Route(
    '/students/resend-otp',
    ['_script' => __DIR__ . '/../public/students/resend_otp.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('students/start', new Route(
    '/students/start',
    ['_script' => __DIR__ . '/../public/students/start.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('students/process-profile', new Route(
    '/students/process-profile',
    ['_script' => __DIR__ . '/../public/students/process_profile.php'],
    [],
    [],
    '',
    [],
    ['POST']
));

// Students application pages
$routes->add('students/application', new Route(
    '/students/application',
    ['_script' => __DIR__ . '/../public/students/application_form.php'],
    [],
    [],
    '',
    [],
    ['GET']
));
$routes->add('students/process-application', new Route(
    '/students/process-application',
    ['_script' => __DIR__ . '/../public/students/process_application.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('students/logout', new Route(
    '/students/logout',
    ['_script' => __DIR__ . '/../public/students/logout.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('verify_otp', new Route(
    '/verify-otp',
    ['_script' => __DIR__ . '/../public/verify_otp.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('admin/verify_otp', new Route(
    '/admin/verify-otp',
    ['_script' => __DIR__ . '/../public/verify_otp.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));
$routes->add('resend_otp', new Route(
    '/resend-otp',
    ['_script' => __DIR__ . '/../public/resend_otp.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/resend_otp', new Route(
    '/admin/resend-otp',
    ['_script' => __DIR__ . '/../public/resend_otp.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('account_settings', new Route(
    '/account/settings',
    ['_script' => __DIR__ . '/../public/process_account_settings.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('admin/account_settings', new Route(
    '/admin/account/settings',
    ['_script' => __DIR__ . '/../public/process_account_settings.php'],
    [],
    [],
    '',
    [],
    ['POST']
));
$routes->add('totp_setup', new Route(
    '/totp/setup',
    ['_script' => __DIR__ . '/../public/totp_setup.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));

// Announcements API
$routes->add('announcement_api', new Route(
    '/api/announcements',
    ['_script' => __DIR__ . '/../public/announcement_actions.php'],
    [],
    [],
    '',
    [],
    ['GET', 'POST']
));

return $routes;
