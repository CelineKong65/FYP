-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2025 at 04:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fyp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
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
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `AdminName`, `AdminEmail`, `AdminPassword`, `AdminPhoneNum`, `AdminPosition`) VALUES
(1, 'pan', 'panzhixin99@gmail.com', '123', '011-31353635', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
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
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(255) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`, `AdminID`) VALUES
(1, 'Swimming', 1),
(2, 'Surfing and beach sports', 1),
(3, 'Snorkeling / Scuba diving', 1),
(4, 'Kayaking ', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
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

-- --------------------------------------------------------

--
-- Table structure for table `feedback_rating`
--

CREATE TABLE `feedback_rating` (
  `FeedbackID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `Feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderpayment`
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
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `ResetID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `CustEmail` varchar(255) DEFAULT NULL,
  `Token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(255) DEFAULT NULL,
  `ProductPrice` decimal(10,2) DEFAULT NULL,
  `ProductSize` varchar(50) DEFAULT NULL,
  `ProductStock` int(11) DEFAULT NULL,
  `ProductPicture` varchar(255) DEFAULT NULL,
  `ProductColor` varchar(50) DEFAULT NULL,
  `ProductDesc` text DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping`
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
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `WishID` int(11) NOT NULL,
  `CustID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `AdminEmail` (`AdminEmail`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CustID`),
  ADD UNIQUE KEY `CustEmail` (`CustEmail`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `feedback_rating`
--
ALTER TABLE `feedback_rating`
  ADD PRIMARY KEY (`FeedbackID`),
  ADD KEY `CustID` (`CustID`);

--
-- Indexes for table `orderpayment`
--
ALTER TABLE `orderpayment`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`ResetID`),
  ADD KEY `CustID` (`CustID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `shipping`
--
ALTER TABLE `shipping`
  ADD PRIMARY KEY (`ShippingID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`WishID`),
  ADD KEY `CustID` (`CustID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `CustID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_rating`
--
ALTER TABLE `feedback_rating`
  MODIFY `FeedbackID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderpayment`
--
ALTER TABLE `orderpayment`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping`
--
ALTER TABLE `shipping`
  MODIFY `ShippingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `WishID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `feedback_rating`
--
ALTER TABLE `feedback_rating`
  ADD CONSTRAINT `feedback_rating_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`);

--
-- Constraints for table `orderpayment`
--
ALTER TABLE `orderpayment`
  ADD CONSTRAINT `orderpayment_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `orderpayment_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `shipping`
--
ALTER TABLE `shipping`
  ADD CONSTRAINT `shipping_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orderpayment` (`OrderID`),
  ADD CONSTRAINT `shipping_ibfk_2` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `shipping_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`CustID`) REFERENCES `customer` (`CustID`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
