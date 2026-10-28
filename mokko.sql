-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Окт 28 2026 г., 10:03
-- Версия сервера: 10.3.16-MariaDB
-- Версия PHP: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `mokko`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(100) DEFAULT 'РќР°Р·РІР°РЅРёРµ',
  `status` enum('on','off') DEFAULT 'on',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `uid`, `name`, `status`, `created_at`) VALUES
(1, 2, 'Анальные вещи', 'on', '2026-10-24 17:29:20'),
(3, 2, 'Goyslop', 'on', '2026-10-27 10:42:18');

-- --------------------------------------------------------

--
-- Структура таблицы `checks`
--

CREATE TABLE `checks` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `place_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `items` text DEFAULT '[]',
  `total` decimal(10,2) DEFAULT 0.00,
  `pay_method` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `checks`
--

INSERT INTO `checks` (`id`, `uid`, `place_id`, `shift_id`, `items`, `total`, `pay_method`, `created_at`) VALUES
(1, 2, 2, 1, '[[1,6,1,1,\"2400\"]]', '2400.00', 1, '2026-10-24 17:31:34'),
(2, 2, 2, 11, '[[1,6,2,1,\"2400\"]]', '2400.00', 1, '2026-10-24 17:45:42'),
(3, 2, 2, 13, '[[1,6,7,1,400.00]]', '2400.00', 1, '2026-10-24 18:11:55'),
(7, 2, 2, 15, '[[1,3,11,1,\"400.00\"]]', '1200.00', 1, '2026-10-26 16:23:39'),
(8, 2, 2, 15, '[]', '0.00', 1, '2026-10-26 16:23:44'),
(9, 2, 2, 15, '[]', '0.00', 1, '2026-10-26 16:23:48'),
(10, 2, 2, 15, '[[1,1,13,2,\"400.00\"]]', '400.00', 2, '2026-10-26 16:23:53'),
(11, 2, 2, 15, '[]', '0.00', 1, '2026-10-26 16:23:57'),
(12, 2, 2, 120, '[[1,4,12,2,\"400.00\"],[13,5,12,2,\"0\"]]', '1600.00', 2, '2026-10-27 11:54:07'),
(13, 2, 2, 120, '[]', '0.00', 1, '2026-10-27 11:54:12'),
(14, 2, 2, 120, '[]', '0.00', 1, '2026-10-27 11:54:21'),
(15, 2, 2, 120, '[]', '0.00', 1, '2026-10-27 11:54:25'),
(16, 2, 2, 120, '[]', '0.00', 1, '2026-10-27 11:54:30'),
(17, 2, 2, 122, '[[1,5,29,2,\"400.00\"]]', '2000.00', 2, '2026-10-27 11:58:15'),
(18, 2, 2, 123, '[[1,6,28,2,\"400.00\"]]', '2400.00', 2, '2026-10-27 11:59:18'),
(19, 2, 2, 123, '[[1,4,19,2,\"400.00\"]]', '1600.00', 2, '2026-10-27 12:00:49'),
(20, 2, 2, 123, '[[1,6,22,2,\"400.00\"]]', '2400.00', 2, '2026-10-27 12:00:53'),
(21, 2, 2, 121, '[[1,4,10,2,\"400.00\"]]', '1600.00', 2, '2026-10-27 12:08:20'),
(22, 2, 2, 121, '[[1,12,14,1,\"400.00\"],[17,1,14,1,\"0.00\"],[12,1,14,1,\"0.00\"]]', '4800.00', 1, '2026-10-27 12:27:23'),
(23, 2, 2, 113, '[[1,1,39,2,\"400.00\"]]', '400.00', 2, '2026-10-27 20:46:05'),
(24, 2, 2, 113, '[[1,1,40,2,\"400.00\"]]', '400.00', 2, '2026-10-27 20:57:45'),
(25, 2, 2, 125, '[[16,5,43,2,\"0\"],[11,3,43,2,\"0\"],[6,6,43,2,\"0\"],[1,7,43,2,\"2800\"],[12,1,43,2,\"0.00\"],[9,1,43,2,\"0.00\"],[15,2,43,2,\"0\"]]', '2800.00', 2, '2026-10-27 21:50:43');

-- --------------------------------------------------------

--
-- Структура таблицы `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `place_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT '',
  `phone` varchar(20) DEFAULT '',
  `items` text DEFAULT '[]',
  `price` decimal(10,2) DEFAULT 0.00,
  `status` enum('open','paid') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `guests`
--

INSERT INTO `guests` (`id`, `uid`, `place_id`, `name`, `phone`, `items`, `price`, `status`, `created_at`) VALUES
(1, 2, 2, 'T 1', '', '[]', '0.00', 'paid', '2026-10-24 17:31:23'),
(2, 2, 2, 'T 1', '', '[]', '0.00', 'paid', '2026-10-24 17:45:19'),
(3, 2, 2, 'T 2', '', '[]', '0.00', 'open', '2026-10-24 17:45:55'),
(4, 2, 2, 'T 3', '', '[]', '0.00', 'open', '2026-10-24 17:45:57'),
(5, 2, 2, 'Жаба', '', '[[13,1,5],[1,1,5],[7,1,5]]', '1400.00', 'open', '2026-10-24 17:46:10'),
(6, 2, 2, 'T 5', '', '[]', '0.00', 'open', '2026-10-24 17:46:25'),
(7, 2, 2, 'T 1', '', '[[1,6,7]]', '2400.00', 'paid', '2026-10-24 18:11:47'),
(8, 2, 2, 'T 2', '', '[]', '0.00', 'open', '2026-10-24 18:11:49'),
(10, 2, 2, 'T 1', '', '[[1,4,10]]', '1600.00', 'paid', '2026-10-26 16:23:28'),
(11, 2, 2, 'T 2', '', '[[1,3,11]]', '1200.00', 'paid', '2026-10-26 16:23:30'),
(12, 2, 2, 'T 3', '', '[[1,4,12],[13,3,12]]', '400.00', 'paid', '2026-10-26 16:23:32'),
(13, 2, 2, 'T 4', '', '[]', '0.00', 'paid', '2026-10-26 16:23:33'),
(14, 2, 2, 'T 1', '', '[[1,12,14]]', '4800.00', 'paid', '2026-10-26 18:47:09'),
(15, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 18:48:58'),
(16, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 18:50:55'),
(17, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:01:25'),
(18, 2, 2, 'T 1', '', '[[11,1,18],[1,1,18]]', '400.00', 'open', '2026-10-26 19:01:43'),
(19, 2, 2, 'T 1', '', '[[1,4,19]]', '1600.00', 'paid', '2026-10-26 19:16:24'),
(20, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:18:03'),
(21, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:20:34'),
(22, 2, 2, 'T 1', '', '[[1,6,22]]', '2400.00', 'paid', '2026-10-26 19:26:35'),
(23, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:31:46'),
(24, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:35:55'),
(25, 2, 2, 'T 1', '', '[]', '0.00', 'open', '2026-10-26 19:36:59'),
(26, 2, 2, 'J 1', '', '[]', '0.00', 'open', '2026-10-27 09:41:07'),
(27, 2, 2, 'J 1', '', '[[6,1,27],[1,5,27]]', '2000.00', 'open', '2026-10-27 09:43:58'),
(28, 2, 2, 'J 1', '', '[[1,6,28]]', '2400.00', 'paid', '2026-10-27 09:49:44'),
(29, 2, 2, 'J 1', '', '[[1,5,29]]', '2000.00', 'paid', '2026-10-27 09:57:18'),
(30, 2, 2, 'J 1', '', '[[1,12,30]]', '4800.00', 'open', '2026-10-27 10:21:13'),
(31, 2, 2, 'J 1', '', '[[1,5,31]]', '2000.00', 'open', '2026-10-27 10:32:18'),
(32, 2, 2, 'J 1', '', '[]', '0.00', 'open', '2026-10-27 10:35:47'),
(33, 2, 2, 'J 2', '', '[]', '0.00', 'open', '2026-10-27 10:36:49'),
(34, 2, 2, 'J 1', '', '[[6,1,34],[1,1,34]]', '400.00', 'open', '2026-10-27 10:43:44'),
(35, 2, 2, 'пися', '', '[[1,1,35],[10,1,35],[15,1,35],[11,1,35]]', '400.00', 'open', '2026-10-27 16:43:25'),
(36, 2, 2, 'J 1', '', '[[1,7,36],[10,8,36],[12,2,36],[6,5,36],[8,14,36],[13,5,36],[15,2,36],[9,2,36],[11,4,36]]', '2800.00', 'open', '2026-10-27 19:37:21'),
(37, 2, 2, 'J 2', '', '[[1,1,37]]', '400.00', 'open', '2026-10-27 19:37:49'),
(38, 2, 2, 'J 1', '', '[]', '0.00', 'open', '2026-10-27 20:42:13'),
(39, 2, 2, 'M 1', '', '[]', '0.00', 'paid', '2026-10-27 20:46:01'),
(40, 2, 2, 'M 1', '', '[]', '0.00', 'paid', '2026-10-27 20:57:41'),
(41, 2, 2, 'J 1', '', '[[1,1,41],[10,1,41],[15,1,41],[11,1,41]]', '400.00', 'open', '2026-10-27 21:14:52'),
(42, 2, 2, 'J 2', '', '[]', '0.00', 'open', '2026-10-27 21:14:56'),
(43, 2, 2, 'J 1', '', '[]', '0.00', 'paid', '2026-10-27 21:50:32');

-- --------------------------------------------------------

--
-- Структура таблицы `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT 'РќР°Р·РІР°РЅРёРµ',
  `price` decimal(10,2) DEFAULT 0.00,
  `mass` decimal(10,2) DEFAULT 0.00,
  `em` tinyint(4) DEFAULT 1,
  `ingredients` text DEFAULT '[]',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `items`
--

INSERT INTO `items` (`id`, `uid`, `category_id`, `name`, `price`, `mass`, `em`, `ingredients`, `created_at`) VALUES
(1, 2, 1, 'Говно из жопы', '400.00', '200.00', 1, '[{\"id\":\"1\",\"mass\":\"100\"}]', '2026-10-24 17:29:24'),
(3, 1, 0, 'Название', '0.00', '0.00', 1, '[]', '2026-10-26 16:13:51'),
(6, 2, 1, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:00'),
(7, 2, 3, 'SPERM', '1000.00', '0.00', 1, '[]', '2026-10-27 10:42:27'),
(8, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(9, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(10, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(11, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(12, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(13, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:46'),
(14, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:47'),
(15, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:47'),
(16, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:47'),
(17, 2, 3, 'Название', '0.00', '0.00', 1, '[]', '2026-10-27 10:42:47');

-- --------------------------------------------------------

--
-- Структура таблицы `lager`
--

CREATE TABLE `lager` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(255) DEFAULT 'РќР°Р·РІР°РЅРёРµ',
  `em` tinyint(4) DEFAULT 1,
  `active` tinyint(4) DEFAULT 1,
  `mass` text DEFAULT '[]',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `lager`
--

INSERT INTO `lager` (`id`, `uid`, `name`, `em`, `active`, `mass`, `created_at`) VALUES
(1, 2, 'Спермочка', 1, 1, '[{\"place\":2,\"mass\":10000}]', '2026-10-24 17:30:10'),
(2, 2, 'Спермочка', 1, 1, '[{\"place\":2,\"mass\":1000}]', '2026-10-24 17:30:33'),
(3, 2, 'Название', 1, 1, '[{\"place\":2,\"mass\":1000}]', '2026-10-24 17:57:24');

-- --------------------------------------------------------

--
-- Структура таблицы `places`
--

CREATE TABLE `places` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT '',
  `inn` varchar(20) DEFAULT '',
  `kpp` varchar(20) DEFAULT '',
  `ltd` varchar(255) DEFAULT '',
  `phone` varchar(20) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `places`
--

INSERT INTO `places` (`id`, `user_id`, `name`, `address`, `inn`, `kpp`, `ltd`, `phone`, `created_at`) VALUES
(1, 1, 'Главная кофейня', '', '', '', '', '', '2026-10-24 17:03:57'),
(2, 2, 'Кофейня 1', '', '', '', '', '', '2026-10-24 17:14:58');

-- --------------------------------------------------------

--
-- Структура таблицы `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `place_id` int(11) DEFAULT NULL,
  `hours` int(11) DEFAULT 0,
  `min` int(11) DEFAULT 0,
  `sec` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `shifts`
--

INSERT INTO `shifts` (`id`, `uid`, `place_id`, `hours`, `min`, `sec`, `created_at`, `closed_at`, `staff_id`) VALUES
(1, 2, 2, 0, 0, 0, '2026-10-24 17:31:13', NULL, NULL),
(2, 2, 2, 0, 0, 0, '2026-10-24 17:31:46', NULL, NULL),
(3, 2, 2, 0, 0, 0, '2026-10-24 17:31:52', NULL, NULL),
(4, 2, 2, 0, 0, 0, '2026-10-24 17:31:56', NULL, NULL),
(5, 2, 2, 0, 0, 0, '2026-10-24 17:32:07', NULL, NULL),
(6, 2, 2, 0, 0, 0, '2026-10-24 17:32:18', NULL, NULL),
(7, 2, 2, 0, 0, 0, '2026-10-24 17:36:33', NULL, NULL),
(8, 2, 2, 0, 0, 0, '2026-10-24 17:37:50', NULL, NULL),
(9, 2, 2, 0, 0, 0, '2026-10-24 17:44:29', NULL, NULL),
(10, 2, 2, 0, 0, 0, '2026-10-24 17:44:37', NULL, NULL),
(11, 2, 2, 0, 0, 0, '2026-10-24 17:44:58', NULL, NULL),
(12, 2, 2, 0, 11, 15, '2026-10-24 17:55:17', '2026-10-24 17:55:23', NULL),
(13, 2, 2, 0, 11, 50, '2026-10-24 18:11:36', '2026-10-24 18:12:09', NULL),
(14, 2, 2, 0, 17, 39, '2026-10-24 18:26:22', '2026-10-24 18:29:03', NULL),
(15, 2, 2, 0, 0, 44, '2026-10-26 16:23:25', '2026-10-26 16:24:09', NULL),
(16, 2, 2, 0, 1, 18, '2026-10-26 18:26:34', '2026-10-26 18:27:06', NULL),
(17, 1, 1, 0, 0, 0, '2026-10-26 18:42:19', NULL, NULL),
(18, 1, 1, 0, 0, 0, '2026-10-26 18:45:55', NULL, NULL),
(19, 2, 2, 0, 1, 49, '2026-10-26 18:47:03', '2026-10-26 18:47:33', NULL),
(20, 1, 1, 0, 0, 0, '2026-10-26 18:48:29', NULL, NULL),
(21, 2, 2, 0, 0, 0, '2026-10-26 18:48:30', NULL, NULL),
(22, 2, 2, 0, 0, 0, '2026-10-26 18:48:49', NULL, NULL),
(23, 1, 1, 0, 0, 0, '2026-10-26 18:50:18', NULL, NULL),
(24, 1, 1, 0, 0, 0, '2026-10-26 18:50:19', NULL, NULL),
(25, 2, 2, 0, 5, 3, '2026-10-26 18:50:49', '2026-10-26 18:51:48', NULL),
(26, 1, 1, 0, 0, 0, '2026-10-26 18:51:46', NULL, NULL),
(27, 1, 1, 0, 0, 0, '2026-10-26 18:52:53', NULL, NULL),
(28, 1, 1, 0, 0, 0, '2026-10-26 18:56:08', NULL, NULL),
(29, 2, 2, 0, 0, 0, '2026-10-26 19:01:24', NULL, NULL),
(30, 2, 2, 0, 6, 15, '2026-10-26 19:01:35', '2026-10-26 19:02:38', NULL),
(31, 2, 2, 0, 0, 0, '2026-10-26 19:08:38', NULL, NULL),
(32, 2, 2, 0, 0, 0, '2026-10-26 19:12:12', NULL, NULL),
(33, 2, 2, 0, 0, 0, '2026-10-26 19:16:19', NULL, NULL),
(34, 2, 2, 0, 0, 0, '2026-10-26 19:17:19', NULL, NULL),
(35, 2, 2, 0, 0, 0, '2026-10-26 19:17:58', NULL, NULL),
(36, 2, 2, 0, 0, 0, '2026-10-26 19:20:31', NULL, NULL),
(37, 2, 2, 0, 0, 0, '2026-10-26 19:20:34', NULL, NULL),
(38, 2, 2, 0, 0, 0, '2026-10-26 19:20:49', NULL, NULL),
(39, 2, 2, 0, 0, 0, '2026-10-26 19:23:39', NULL, NULL),
(40, 2, 2, 0, 0, 0, '2026-10-26 19:24:09', NULL, NULL),
(41, 2, 2, 0, 0, 0, '2026-10-26 19:24:24', NULL, NULL),
(42, 2, 2, 0, 0, 0, '2026-10-26 19:24:46', NULL, NULL),
(43, 2, 2, 0, 0, 0, '2026-10-26 19:25:05', NULL, NULL),
(44, 2, 2, 0, 0, 0, '2026-10-26 19:25:05', NULL, NULL),
(45, 2, 2, 0, 0, 0, '2026-10-26 19:25:20', NULL, NULL),
(46, 2, 2, 0, 0, 0, '2026-10-26 19:26:41', NULL, NULL),
(47, 2, 2, 0, 0, 0, '2026-10-26 19:26:57', NULL, NULL),
(48, 2, 2, 0, 0, 0, '2026-10-26 19:27:12', NULL, NULL),
(49, 2, 2, 0, 0, 0, '2026-10-26 19:27:25', NULL, NULL),
(50, 2, 2, 0, 0, 0, '2026-10-26 19:27:28', NULL, NULL),
(51, 2, 2, 0, 0, 0, '2026-10-26 19:27:51', NULL, NULL),
(52, 2, 2, 0, 0, 0, '2026-10-26 19:27:54', NULL, NULL),
(53, 2, 2, 0, 0, 0, '2026-10-26 19:28:18', NULL, NULL),
(54, 2, 2, 0, 0, 0, '2026-10-26 19:28:33', NULL, NULL),
(55, 2, 2, 0, 0, 0, '2026-10-26 19:29:33', NULL, NULL),
(56, 2, 2, 0, 0, 0, '2026-10-26 19:29:35', NULL, NULL),
(57, 2, 2, 0, 0, 0, '2026-10-26 19:29:43', NULL, NULL),
(58, 2, 2, 0, 0, 0, '2026-10-26 19:29:58', NULL, NULL),
(59, 2, 2, 0, 0, 0, '2026-10-26 19:30:09', NULL, NULL),
(60, 2, 2, 0, 0, 0, '2026-10-26 19:30:49', NULL, NULL),
(61, 2, 2, 0, 0, 0, '2026-10-26 19:30:59', NULL, NULL),
(62, 2, 2, 0, 16, 24, '2026-10-26 19:31:43', '2026-10-26 19:33:09', NULL),
(63, 2, 2, 0, 0, 0, '2026-10-26 19:32:49', NULL, NULL),
(64, 2, 2, 0, 0, 0, '2026-10-26 19:33:20', NULL, NULL),
(65, 2, 2, 0, 0, 0, '2026-10-26 19:33:31', NULL, NULL),
(66, 2, 2, 0, 0, 0, '2026-10-26 19:34:21', NULL, NULL),
(67, 2, 2, 0, 0, 0, '2026-10-26 19:34:49', NULL, NULL),
(68, 2, 2, 0, 0, 0, '2026-10-26 19:34:56', NULL, NULL),
(69, 2, 2, 0, 0, 0, '2026-10-26 19:35:23', NULL, NULL),
(70, 2, 2, 0, 11, 26, '2026-10-26 19:36:53', '2026-10-26 19:38:35', NULL),
(71, 2, 2, 0, 0, 0, '2026-10-26 19:39:28', NULL, NULL),
(72, 2, 2, 0, 0, 0, '2026-10-26 19:39:43', NULL, NULL),
(73, 2, 2, 0, 8, 26, '2026-10-26 19:40:12', '2026-10-26 19:40:46', NULL),
(74, 2, 2, 0, 0, 0, '2026-10-27 09:36:06', NULL, NULL),
(75, 2, 2, 0, 0, 0, '2026-10-27 09:37:32', NULL, NULL),
(76, 2, 2, 0, 0, 0, '2026-10-27 09:38:33', NULL, NULL),
(77, 2, 2, 0, 0, 0, '2026-10-27 09:39:15', NULL, NULL),
(78, 2, 2, 0, 0, 0, '2026-10-27 09:40:41', NULL, NULL),
(79, 2, 2, 0, 0, 0, '2026-10-27 09:40:54', NULL, NULL),
(80, 2, 2, 0, 0, 0, '2026-10-27 09:41:03', NULL, NULL),
(81, 2, 2, 0, 0, 0, '2026-10-27 09:41:20', NULL, NULL),
(82, 2, 2, 0, 0, 0, '2026-10-27 09:41:40', NULL, NULL),
(83, 2, 2, 0, 0, 0, '2026-10-27 09:43:43', NULL, NULL),
(84, 2, 2, 0, 0, 0, '2026-10-27 09:43:49', NULL, NULL),
(85, 2, 2, 0, 0, 0, '2026-10-27 09:44:04', NULL, NULL),
(86, 2, 2, 0, 0, 0, '2026-10-27 09:47:14', NULL, NULL),
(87, 2, 2, 0, 0, 0, '2026-10-27 09:47:16', NULL, NULL),
(88, 2, 2, 0, 0, 0, '2026-10-27 09:48:13', NULL, NULL),
(89, 2, 2, 0, 0, 0, '2026-10-27 09:48:16', NULL, NULL),
(90, 2, 2, 0, 0, 0, '2026-10-27 09:49:29', NULL, NULL),
(91, 2, 2, 0, 0, 0, '2026-10-27 09:52:06', NULL, NULL),
(92, 2, 2, 0, 0, 0, '2026-10-27 09:52:06', NULL, NULL),
(93, 2, 2, 0, 0, 0, '2026-10-27 09:52:36', NULL, NULL),
(94, 2, 2, 0, 0, 0, '2026-10-27 09:53:34', NULL, NULL),
(95, 2, 2, 0, 0, 0, '2026-10-27 09:55:27', NULL, NULL),
(96, 2, 2, 0, 0, 0, '2026-10-27 09:56:29', NULL, NULL),
(97, 2, 2, 0, 0, 0, '2026-10-27 09:57:06', NULL, NULL),
(98, 2, 2, 0, 0, 0, '2026-10-27 09:57:53', NULL, NULL),
(99, 2, 2, 0, 0, 0, '2026-10-27 09:58:08', NULL, NULL),
(100, 2, 2, 0, 20, 50, '2026-10-27 10:02:02', '2026-10-27 10:02:06', NULL),
(101, 2, 2, 0, 22, 1, '2026-10-27 10:21:04', '2026-10-27 10:22:14', NULL),
(102, 2, 2, 0, 23, 12, '2026-10-27 10:26:12', '2026-10-27 10:27:21', NULL),
(103, 2, 2, 0, 23, 24, '2026-10-27 10:29:13', '2026-10-27 10:29:24', NULL),
(104, 2, 2, 0, 0, 0, '2026-10-27 10:32:14', NULL, NULL),
(105, 2, 2, 0, 0, 0, '2026-10-27 10:34:42', NULL, NULL),
(106, 2, 2, 0, 0, 0, '2026-10-27 10:35:46', NULL, NULL),
(107, 2, 2, 0, 0, 0, '2026-10-27 10:37:10', NULL, NULL),
(108, 2, 2, 0, 0, 0, '2026-10-27 10:38:22', NULL, NULL),
(109, 2, 2, 0, 0, 0, '2026-10-27 10:40:40', NULL, NULL),
(110, 2, 2, 0, 0, 0, '2026-10-27 10:41:18', NULL, NULL),
(111, 2, 2, 0, 0, 0, '2026-10-27 10:43:34', NULL, NULL),
(112, 2, 2, 10, 27, 42, '2026-10-27 10:47:20', '2026-10-27 21:15:02', NULL),
(113, 2, 2, 0, 1, 30, '2026-10-27 10:47:52', '2026-10-27 21:05:01', NULL),
(114, 2, 2, 9, 48, 55, '2026-10-27 10:52:58', '2026-10-27 20:41:54', NULL),
(115, 2, 2, 0, 39, 55, '2026-10-27 11:40:45', '2026-10-27 11:40:50', NULL),
(116, 2, 2, 7, 57, 1, '2026-10-27 11:40:57', '2026-10-27 19:37:59', NULL),
(117, 2, 2, 5, 1, 57, '2026-10-27 11:41:57', '2026-10-27 16:43:55', NULL),
(118, 2, 2, 0, 41, 58, '2026-10-27 11:48:07', '2026-10-27 11:48:10', NULL),
(119, 2, 2, 4, 52, 29, '2026-10-27 11:51:03', '2026-10-27 16:43:33', NULL),
(120, 2, 2, 0, 45, 54, '2026-10-27 11:53:22', '2026-10-27 11:55:16', NULL),
(121, 2, 2, 0, 29, 39, '2026-10-27 11:57:50', '2026-10-27 12:27:29', NULL),
(122, 2, 2, 0, 49, 12, '2026-10-27 11:58:08', '2026-10-27 11:58:34', NULL),
(123, 2, 2, 0, 54, 45, '2026-10-27 11:59:08', '2026-10-27 12:04:14', NULL),
(124, 2, 2, 0, 0, 8, '2026-10-27 21:19:35', '2026-10-27 21:19:43', NULL),
(125, 2, 2, 0, 31, 9, '2026-10-27 21:19:50', '2026-10-27 21:50:59', NULL),
(126, 2, 2, 0, 0, 0, '2026-10-27 21:51:08', NULL, NULL),
(127, 2, 2, 0, 4, 39, '2026-10-27 22:44:14', '2026-10-27 22:48:54', 1),
(128, 2, 2, 0, 3, 5, '2026-10-27 22:50:24', '2026-10-27 22:53:29', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(100) DEFAULT '',
  `lastname` varchar(100) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `phone` varchar(20) DEFAULT '',
  `rate` decimal(10,2) DEFAULT 0.00,
  `password_hash` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `staff`
--

INSERT INTO `staff` (`id`, `uid`, `name`, `lastname`, `email`, `phone`, `rate`, `password_hash`, `created_at`) VALUES
(1, 2, 'Zhopa', 'PISKA', 'sazhaev.alexandr@gmail.com', '89638550984', '100.00', '$2y$10$e4eOg7P9yrYk1uQD1ocf2.ok2ciuPaU16ShdIYAoU3D/F5slu1fwm', '2026-10-24 17:29:00');

-- --------------------------------------------------------

--
-- Структура таблицы `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `email` varchar(254) NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `lastname` varchar(100) DEFAULT '',
  `phone` varchar(20) DEFAULT '',
  `wallet` int(11) DEFAULT 0,
  `country_code` varchar(5) NOT NULL DEFAULT 'ru',
  `role` enum('admin','staff') NOT NULL DEFAULT 'admin',
  `status` enum('new','active','disable','low_wallet') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `lastname`, `phone`, `wallet`, `country_code`, `role`, `status`, `created_at`) VALUES
(1, 'admin@mokko.local', '$2y$10$eH0mO93YzZOoyoFC4KCKteAWWjS1g5z0DEcK3SAv/Bt1.6Mok6CH6', 'Администратор', '', '', 0, 'ru', 'admin', 'active', '2026-10-24 17:03:57'),
(2, 'Dnse22@ya.ru', '$2y$10$ywIL7GP22MRDuuD.KmhtmuvOLIbRshg7n5ctoFkfwvZ6SQFs/c3LG', 'Alexandr', '', '', 0, 'ru', 'admin', 'active', '2026-10-24 17:06:36');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid` (`uid`);

--
-- Индексы таблицы `checks`
--
ALTER TABLE `checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid_place` (`uid`,`place_id`),
  ADD KEY `idx_shift` (`shift_id`);

--
-- Индексы таблицы `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid_place` (`uid`,`place_id`);

--
-- Индексы таблицы `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid` (`uid`),
  ADD KEY `idx_category` (`category_id`);

--
-- Индексы таблицы `lager`
--
ALTER TABLE `lager`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid` (`uid`);

--
-- Индексы таблицы `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid` (`uid`);

--
-- Индексы таблицы `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid` (`uid`);

--
-- Индексы таблицы `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `checks`
--
ALTER TABLE `checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT для таблицы `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `lager`
--
ALTER TABLE `lager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `places`
--
ALTER TABLE `places`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT для таблицы `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `places`
--
ALTER TABLE `places`
  ADD CONSTRAINT `places_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
