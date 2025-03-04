-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-03-04 08:45:21
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `fyp`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `AdminName` varchar(255) DEFAULT NULL,
  `AdminEmail` varchar(255) DEFAULT NULL,
  `AdminPassword` varchar(255) DEFAULT NULL,
  `AdminPhoneNum` varchar(20) DEFAULT NULL,
  `AdminPosition` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admin`
--

INSERT INTO `admin` (`AdminID`, `AdminName`, `AdminEmail`, `AdminPassword`, `AdminPhoneNum`, `AdminPosition`) VALUES
(1, 'Zhi Xin', 'zhixin@gmail.com', '0729', '0123456789', 'Superadmin');

-- --------------------------------------------------------

--
-- 表的结构 `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `ProductName` varchar(255) DEFAULT NULL,
  `ProductPrice` decimal(10,2) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(255) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`, `AdminID`) VALUES
(1, 'Swimming', NULL),
(2, 'Surfing and beach sports', NULL),
(3, 'Snorkeling / Scuba diving', NULL),
(4, 'Kayaking', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `customer`
--

CREATE TABLE `customer` (
  `CustID` int(11) NOT NULL,
  `CustName` varchar(255) DEFAULT NULL,
  `CustEmail` varchar(255) DEFAULT NULL,
  `CustPassword` varchar(255) DEFAULT NULL,
  `CustPhoneNum` varchar(20) DEFAULT NULL,
  `CustAddress` text DEFAULT NULL,
  `CustProfilePicture` varchar(255) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `customer`
--

INSERT INTO `customer` (`CustID`, `CustName`, `CustEmail`, `CustPassword`, `CustPhoneNum`, `CustAddress`, `CustProfilePicture`, `AdminID`) VALUES
(1, 'kong', 'leeching@gmail.com', '$2y$10$IZHbog9mFjBGz6V3coZBhenKqIfJ7U4pvDNoycowmMUeDH6X06P36', '010-228 2675', 'Ixora apartment', NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `feedback_rating`
--

CREATE TABLE `feedback_rating` (
  `FeedbackID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `Feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `orderpayment`
--

CREATE TABLE `orderpayment` (
  `OrderID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `CustName` varchar(255) DEFAULT NULL,
  `CustEmail` varchar(255) DEFAULT NULL,
  `CustAddress` text DEFAULT NULL,
  `OrderDate` datetime DEFAULT NULL,
  `TotalPrice` decimal(10,2) DEFAULT NULL,
  `OrderMethod` varchar(100) DEFAULT NULL,
  `CardNum` varchar(20) DEFAULT NULL,
  `CardCVV` varchar(5) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `password_reset`
--

CREATE TABLE `password_reset` (
  `ResetID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `CustEmail` varchar(255) DEFAULT NULL,
  `Token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(255) DEFAULT NULL,
  `ProductPrice` decimal(10,2) DEFAULT NULL,
  `ProductStock` int(11) DEFAULT NULL,
  `ProductDesc` text DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product`
--

INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPrice`, `ProductStock`, `ProductDesc`, `CategoryID`, `AdminID`) VALUES
(1, 'Boy Swimsuit Boxer', 35.00, 20, 'A stylish and comfortable swimwear option designed for young swimmers.', 1, NULL),
(2, 'Boy Swimsuit Jammer', 50.00, 20, 'A sleek and performance-oriented swimwear designed for competitive and recreational swimming.', 1, NULL),
(3, 'Boy Swimsuit Long Sleeved', 89.00, 20, 'A protective and stylish swimwear option designed for maximum comfort and coverage.', 1, NULL),
(4, 'Boy Swimsuit Short Sleeved', 80.00, 20, 'A comfortable and protective swimwear option designed for active young swimmers.', 1, NULL),
(5, 'Girl Swimsuit One Piece 1', 80.00, 20, 'A stylish and comfortable swimwear option designed for young swimmers.', 1, NULL),
(6, 'Girl Swimsuit One Piece 2', 79.00, 20, 'A stylish and comfortable swimwear option designed for young swimmers.', 1, NULL),
(7, 'Girl Swimsuit One Piece 3', 79.00, 20, 'A stylish and comfortable swimwear option designed for young swimmers.', 1, NULL),
(8, 'Girl Swimsuit One Piece 4', 79.00, 20, 'A stylish and comfortable swimwear option designed for young swimmers.', 1, NULL),
(9, 'Goggles', 19.00, 20, 'Essential eyewear for swimmers, providing clear vision and eye protection while swimming.', 1, NULL),
(10, 'Men Swimsuit Brief', 29.00, 20, 'A classic and performance-oriented swimwear option designed for speed, comfort, and flexibility.', 1, NULL),
(11, 'Men Swimsuit Short', 39.00, 20, 'A versatile and comfortable swimwear option designed for both casual and active swimmers.', 1, NULL),
(12, 'Swim Cap Long Hair', 25.00, 20, 'Designed to comfortably fit and protect swimmers with long hair.', 1, NULL),
(13, 'Swim Cap', 15.00, 20, 'Essential swimming accessory designed to protect hair and reduce drag.', 1, NULL),
(14, 'Women Swimsuit Bikini', 50.00, 20, 'A trendy and comfortable two-piece swimwear option.', 1, NULL),
(15, 'Women Swimsuit Long Sleeved', 59.00, 20, 'A stylish and functional swimwear option with extra coverage.', 1, NULL),
(16, 'Women Swimsuit One Piece', 49.00, 20, 'A stylish and comfortable swimwear option with full coverage.', 1, NULL),
(17, 'Beach Volleyball Net', 400.00, 20, 'A sturdy and weather-resistant net designed for outdoor volleyball games.', 2, NULL),
(18, 'Beach Volleyball', 110.00, 20, 'A specially designed ball for outdoor play on sand courts.', 2, NULL),
(19, 'Bodyboard', 200.00, 20, 'A fun and versatile water sports board designed for riding waves.', 2, NULL),
(20, 'Longboard', 450.00, 20, 'A classic surfboard known for its stability and smooth ride.', 2, NULL),
(21, 'Shortboard', 330.00, 20, 'A high-performance surfboard designed for speed and maneuverability.', 2, NULL),
(22, 'Fins', 105.00, 20, 'Essential swimming and diving gear for propulsion and control.', 3, NULL),
(23, 'Diving Mask', 70.00, 20, 'An essential piece of gear for scuba diving and snorkeling.', 3, NULL),
(24, 'Easybreath Surface Mask', 95.00, 20, 'A full-face snorkeling mask for effortless breathing.', 3, NULL),
(25, 'Snorkels', 40.00, 20, 'Essential piece of swimming and diving gear.', 3, NULL),
(26, 'Wetsuit', 359.00, 20, 'A specialized suit for warmth and protection in water activities.', 3, NULL),
(27, 'Kayak Paddle', 120.00, 20, 'Essential tool for kayaking.', 4, NULL),
(28, 'Kayak', 1599.00, 20, 'A sleek and versatile watercraft for paddling.', 4, NULL),
(29, 'Life Jacket', 138.00, 20, 'A safety essential for buoyancy and protection.', 4, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `product_color`
--

CREATE TABLE `product_color` (
  `ProductColorID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Color` varchar(50) NOT NULL,
  `Picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product_color`
--

INSERT INTO `product_color` (`ProductColorID`, `ProductID`, `Color`, `Picture`) VALUES
(1, 1, 'black', 'Boy-Swimsuit-Boxer.png'),
(2, 1, 'blue', 'boy-swimsuit-boxer3.png'),
(3, 2, 'blue', 'boy-swimsuit-jammer.png'),
(4, 2, 'black', 'boy-swimsuit-jammer4.png'),
(5, 3, 'green', 'Boy-Swimsuit-Long-Sleeved.png'),
(6, 4, 'darkblue', 'Boy-Swimsuit-Short-Sleeved.png'),
(7, 5, 'purple', 'Girl-Swimsuit-One-Piece-1.png'),
(8, 6, 'pink', 'Girl-Swimsuit-One-Piece-2.png'),
(9, 7, 'green', 'Girl-Swimsuit-One-Piece-3.png'),
(10, 8, 'black', 'Girl-Swimsuit-One-Piece-4.png'),
(11, 9, 'lightgreen', 'Goggles.png'),
(12, 9, 'pink', 'goggle2.png'),
(13, 9, 'darkblue', 'goggle3.png'),
(14, 10, 'black', 'men-swimsuit-brief.png'),
(15, 10, 'darkblue', 'men-swimsuit-brief3.png'),
(16, 11, 'lightgreen', 'men-swimsuit-short.png'),
(17, 11, 'darkblue', 'men-swimsuit-short2.png'),
(18, 12, 'pink', 'Swim-Cap-Long-Hair.png'),
(19, 12, 'black', 'swimcap-longhair2.png'),
(20, 13, 'blue', 'Swim-Cap.png'),
(21, 13, 'black', 'swimcap5.png'),
(22, 14, 'black', 'Women-Swimsuit-Bikini.png'),
(23, 14, 'pink', 'women-swimsuit-bikini3.png'),
(24, 15, 'pink', 'Women-Swimsuit-Long-Sleeved.png'),
(25, 15, 'black', 'women-swimsuit-longsleeve3.png'),
(26, 16, 'orange', 'Women-Swimsuit-One-Piece.png'),
(27, 16, 'green', 'women-swimsuit-onepiece2.png'),
(28, 17, 'yellow', 'Beach-Volleyball-Net.jpg'),
(29, 18, 'blue', 'Beach-Volleyball.png'),
(30, 19, 'green', 'Bodyboard.jpg'),
(31, 19, 'darkblue', 'bodyboards2.jpg'),
(32, 19, 'yellow', 'bodyboards3.jpg'),
(33, 20, 'lightgreen', 'Longboard.jpg'),
(34, 20, 'skyblue', 'long-boards2.jpg'),
(35, 20, 'darkblue', 'long-boards3.jpg'),
(36, 21, 'yellow', 'Shortboard.jpg'),
(37, 21, 'green', 'shortboards2.jpg'),
(38, 21, 'grey', 'shortboards3.jpg'),
(39, 22, 'blue', 'Fins.png'),
(40, 22, 'black', 'Fins3.png'),
(41, 23, 'black', 'Diving-Mask.png'),
(42, 24, 'black', 'Easybreath-Surface-Mask.png'),
(43, 25, 'black', 'Snorkels.png'),
(44, 25, 'blue', 'Snorkels2.png'),
(45, 25, 'red', 'Snorkels3.png'),
(46, 26, 'blue', 'Wetsuit.png'),
(47, 26, 'purple', 'Wetsuits2.png'),
(48, 26, 'black', 'Wetsuits3.png'),
(49, 27, 'black', 'Kayak-Paddle.jpg'),
(50, 27, 'blue', 'kayak-paddle2.jpg'),
(51, 27, 'yellow', 'kayak-paddle3.jpg'),
(52, 28, 'blue', 'Kayak.jpg'),
(53, 29, 'orange', 'Life-Jacket.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `product_size`
--

CREATE TABLE `product_size` (
  `ProductSizeID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Size` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product_size`
--

INSERT INTO `product_size` (`ProductSizeID`, `ProductID`, `Size`) VALUES
(1, 1, 'S'),
(2, 1, 'M'),
(3, 1, 'L'),
(4, 1, 'XL'),
(5, 2, 'S'),
(6, 2, 'M'),
(7, 2, 'L'),
(8, 2, 'XL'),
(9, 3, 'S'),
(10, 3, 'M'),
(11, 3, 'L'),
(12, 3, 'XL'),
(13, 4, 'S'),
(14, 4, 'M'),
(15, 4, 'L'),
(16, 4, 'XL'),
(17, 5, 'S'),
(18, 5, 'M'),
(19, 5, 'L'),
(20, 5, 'XL'),
(21, 6, 'S'),
(22, 6, 'M'),
(23, 6, 'L'),
(24, 6, 'XL'),
(25, 7, 'S'),
(26, 7, 'M'),
(27, 7, 'L'),
(28, 7, 'XL'),
(29, 8, 'S'),
(30, 8, 'M'),
(31, 8, 'L'),
(32, 8, 'XL'),
(33, 9, NULL),
(34, 10, 'S'),
(35, 10, 'M'),
(36, 10, 'L'),
(37, 10, 'XL'),
(38, 11, 'S'),
(39, 11, 'M'),
(40, 11, 'L'),
(41, 11, 'XL'),
(42, 12, NULL),
(43, 13, NULL),
(44, 14, 'S'),
(45, 14, 'M'),
(46, 14, 'L'),
(47, 14, 'XL'),
(48, 15, 'S'),
(49, 15, 'M'),
(50, 15, 'L'),
(51, 15, 'XL'),
(52, 16, 'S'),
(53, 16, 'M'),
(54, 16, 'L'),
(55, 16, 'XL'),
(56, 17, NULL),
(57, 18, NULL),
(58, 19, NULL),
(59, 20, NULL),
(60, 21, NULL),
(61, 22, NULL),
(62, 23, NULL),
(63, 24, NULL),
(64, 25, NULL),
(65, 26, 'S'),
(66, 26, 'M'),
(67, 26, 'L'),
(68, 26, 'XL'),
(69, 27, NULL),
(70, 28, NULL),
(71, 29, 'S'),
(72, 29, 'M'),
(73, 29, 'L'),
(74, 29, 'XL');

-- --------------------------------------------------------

--
-- 表的结构 `shipping`
--

CREATE TABLE `shipping` (
  `ShippingID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `CustID` int(11) DEFAULT NULL,
  `ShippingAddress` text DEFAULT NULL,
  `ShippingMethod` varchar(100) DEFAULT NULL,
  `TrackingNum` varchar(50) DEFAULT NULL,
  `ShippingStatus` varchar(100) DEFAULT NULL,
  `EstimateDeliveryDate` date DEFAULT NULL,
  `ActualDeliveryDate` date DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `wishlist`
--

CREATE TABLE `wishlist` (
  `WishID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `AdminEmail` (`AdminEmail`);

--
-- 表的索引 `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- 表的索引 `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CustID`),
  ADD UNIQUE KEY `CustEmail` (`CustEmail`),
  ADD KEY `AdminID` (`AdminID`);

--
-- 表的索引 `feedback_rating`
--
ALTER TABLE `feedback_rating`
  ADD PRIMARY KEY (`FeedbackID`),
  ADD KEY `CustID` (`CustID`);

--
-- 表的索引 `orderpayment`
--
ALTER TABLE `orderpayment`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- 表的索引 `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`ResetID`),
  ADD KEY `CustID` (`CustID`);

--
-- 表的索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- 表的索引 `product_color`
--
ALTER TABLE `product_color`
  ADD PRIMARY KEY (`ProductColorID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `product_size`
--
ALTER TABLE `product_size`
  ADD PRIMARY KEY (`ProductSizeID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `shipping`
--
ALTER TABLE `shipping`
  ADD PRIMARY KEY (`ShippingID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- 表的索引 `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`WishID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `customer`
--
ALTER TABLE `customer`
  MODIFY `CustID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `feedback_rating`
--
ALTER TABLE `feedback_rating`
  MODIFY `FeedbackID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `orderpayment`
--
ALTER TABLE `orderpayment`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- 使用表AUTO_INCREMENT `product_color`
--
ALTER TABLE `product_color`
  MODIFY `ProductColorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- 使用表AUTO_INCREMENT `product_size`
--
ALTER TABLE `product_size`
  MODIFY `ProductSizeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- 使用表AUTO_INCREMENT `shipping`
--
ALTER TABLE `shipping`
  MODIFY `ShippingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `WishID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- 限制表 `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- 限制表 `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- 限制表 `feedback_rating`
--
ALTER TABLE `feedback_rating`
  ADD CONSTRAINT `feedback_rating_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`);

--
-- 限制表 `orderpayment`
--
ALTER TABLE `orderpayment`
  ADD CONSTRAINT `orderpayment_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `orderpayment_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- 限制表 `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`);

--
-- 限制表 `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- 限制表 `product_color`
--
ALTER TABLE `product_color`
  ADD CONSTRAINT `product_color_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE;

--
-- 限制表 `product_size`
--
ALTER TABLE `product_size`
  ADD CONSTRAINT `product_size_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE;

--
-- 限制表 `shipping`
--
ALTER TABLE `shipping`
  ADD CONSTRAINT `shipping_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orderpayment` (`OrderID`),
  ADD CONSTRAINT `shipping_ibfk_2` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `shipping_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- 限制表 `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
