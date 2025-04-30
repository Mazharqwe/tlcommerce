-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 18, 2023 at 10:49 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tl_commerce_real_demo`
--

--
-- Dumping data for table `tl_email_templates`
--

INSERT INTO `tl_email_templates` (`id`, `name`, `details`, `created_at`, `updated_at`, `module_name`) VALUES
(13, 'New Order Mail', 'New order mail send to Admin', '2023-06-15 06:10:57', NULL, 'ecommerce'),
(14, 'Admin General Email', 'Email template for admin for multipurpose', '2023-06-15 09:16:58', '2023-06-15 09:21:57', 'ecommerce');

--
-- Dumping data for table `tl_email_template_properties`
--

INSERT INTO `tl_email_template_properties` (`id`, `email_type`, `subject`, `body`, `created_at`, `updated_at`) VALUES
(10, 13, 'New Order', '<table cellspacing=\"0\" border=\"0\" cellpadding=\"0\" width=\"100%\" bgcolor=\"#f2f3f8\" style=\"@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: \'Open Sans\', sans-serif;\">\n        <tbody><tr>\n            <td>\n                <table style=\"background-color: #f2f3f8; max-width:670px;  margin:0 auto;\" width=\"95%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\n                    <tbody><tr>\n                        <td style=\"height:80px;\"> </td>\n                    </tr>\n                    \n                    <tr>\n                        <td style=\"text-align:center;background-color:#ef2543;padding:10px;width:95%\">\n                            <a href=\"_site_link_\" title=\"logo\">\n                            <img width=\"180\" src=\"_system_logo_url_\" title=\"logo\" alt=\"logo\">\n                          </a>\n                        </td>\n                    </tr>\n                    \n                    \n                    <tr>\n                        <td style=\"padding:25px 25px;background:#fff\">\n                            <div style=\"text-align:center\">\n                                <h1 style=\"color:#1e1e2d; font-weight:500; margin:0;font-size:25px;font-family:\'Rubik\',sans-serif;\">New Order Placed!</h1>\n                                <span style=\"display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;\"></span>\n                            </div>\n\n                            <div class=\"content-body\">\n                                <p style=\"color:#455056; font-size:15px;line-height:24px; margin:0;\">\n                                New order  _order_code_  has been placed successfully.\n                                </p>\n                            </div>\n                            <div style=\"text-align:center\">\n                                <a href=\"_tracking_url_\" style=\"background:#ef2543;text-decoration:none !important; font-weight:500; margin-top:35px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:5px;\">View Order Details</a>\n                            </div>\n                        </td>\n\n                    </tr>\n                    <tr>\n                        \n                        <td>\n                            _order_details_\n                        </td>\n                    </tr>\n                    \n                    \n                    <tr>\n                        <td style=\"margin-top:20px;text-align: center;text-align:center;background-color:#3a3a3abf;padding:18px;width:95%\">\n                            <a href=\"_site_link_\" style=\"line-height:18px; margin:0 0 0; text-align: center;color:#dbe5eb; text-decoration:none !important;font-size:14px\"> \n                            _footer_text_\n                             </a>\n                        </td>\n                    </tr>\n                    \n                    <tr>\n                        <td style=\"height:80px;\"> </td>\n                    </tr>\n                </tbody></table>\n            </td>\n        </tr>\n    </tbody></table>', '2023-06-15 06:17:34', '2023-06-15 06:25:18'),
(11, 14, 'Admin General Message', '<table style=\"@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: \'Open Sans\', sans-serif;\" bgcolor=\"#f2f3f8\" width=\"100%\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\">\n        <tbody><tr>\n            <td>\n                <table cellspacing=\"0\" cellpadding=\"0\" align=\"center\" border=\"0\" width=\"95%\" style=\"background-color: #f2f3f8; max-width:670px;  margin:0 auto;\">\n                    <tbody><tr>\n                        <td style=\"height:80px;\"> </td>\n                    </tr>\n                    \n                    <tr>\n                        <td style=\"text-align:center;background-color:#ef2543;padding:10px;width:95%\">\n                            <a title=\"logo\" href=\"_site_link_\">\n                            <img alt=\"logo\" title=\"logo\" src=\"_system_logo_url_\" width=\"180\">\n                          </a>\n                        </td>\n                    </tr>\n                    \n                    \n                    <tr>\n                        <td style=\"padding:25px 25px;background:#fff\">\n                            <div style=\"text-align:center\">\n                                <h1 style=\"color:#1e1e2d; font-weight:500; margin:0;font-size:25px;font-family:\'Rubik\',sans-serif;\">_mail_title_</h1>\n                                <span style=\"display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;\"></span>\n                            </div>\n\n                            <div class=\"content-body\">\n                                <p style=\"color:#455056; font-size:15px;line-height:24px; margin:0;float:center\">\n                                  _message_\n                                </p>\n                            </div>\n                            <div style=\"text-align:center\">\n                                <a style=\"background:#ef2543;text-decoration:none !important; font-weight:500; margin-top:35px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:5px;\" href=\"_action_url_\">_btn_title_</a>\n                            </div>\n                        </td>\n                    </tr>\n                    \n                    \n                    <tr>\n                        <td style=\"text-align: center;text-align:center;background-color:#3a3a3abf;padding:18px;width:95%\">\n                            <a style=\"line-height:18px; margin:0 0 0; text-align: center;color:#dbe5eb; text-decoration:none !important;font-size:14px\" href=\"_site_link_\"> \n                            _footer_text_\n                             </a>\n                        </td>\n                    </tr>\n                    \n                    <tr>\n                        <td style=\"height:80px;\"> </td>\n                    </tr>\n                </tbody></table>\n            </td>\n    </tr></tbody></table>', '2023-06-15 09:21:35', '2023-06-15 09:23:43');

--
-- Dumping data for table `tl_email_template_variable`
--

INSERT INTO `tl_email_template_variable` (`id`, `name`, `details`, `template_id`, `created_at`, `updated_at`) VALUES
(32, '_order_code_', 'Order Code', 13, '2023-06-15 06:15:57', NULL),
(33, '_tracking_url_', 'Order details page link', 13, '2023-06-15 06:15:57', NULL),
(34, '_order_details_', 'Order Details', 13, '2023-06-15 06:16:25', NULL),
(36, '_message_', 'Mail Content', 14, '2023-06-15 09:18:50', NULL),
(37, '_mail_title_', 'Title of email', 14, '2023-06-15 09:20:29', '2023-06-15 09:20:29'),
(38, '_btn_title_', 'Button title of action button', 14, '2023-06-15 09:33:31', '2023-06-15 09:33:31'),
(39, '_action_url_', 'Action link for email', 14, '2023-06-15 09:20:02', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
