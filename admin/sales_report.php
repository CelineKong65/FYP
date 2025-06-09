<?php
session_start();
if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}
include 'db_connection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataType = $_POST['dataType'] ?? '';
    $exportFormat = $_POST['export_format'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    if (empty($startDate) || empty($endDate) || strtotime($startDate) === false || strtotime($endDate) === false || strtotime($startDate) > strtotime($endDate)) {
        echo "<script>alert('Invalid date range'); window.location.href = window.location.href;</script>";
        exit();
    }
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    if ($dataType === 'orders') {
        $query = "SELECT op.*, c.CustName FROM orderpayment op JOIN customer c ON op.CustID = c.CustID WHERE OrderDate BETWEEN ? AND ? ORDER BY OrderDate DESC";
        $filename = 'order_list';
        $title = 'Order List';
    } elseif ($dataType === 'contacts') {
        $query = "SELECT * FROM contact_record WHERE Submission_date BETWEEN ? AND ? ORDER BY Submission_date DESC";
        $filename = 'contact_list';
        $title = 'Contact Records';
    } elseif ($dataType === 'product_feedback') {
        $query = "SELECT pf.*, c.CustName, p.ProductName FROM product_feedback pf 
        JOIN customer c ON pf.CustID = c.CustID 
        JOIN product p ON pf.ProductID = p.ProductID 
        WHERE pf.FeedbackDate BETWEEN ? AND ? 
        ORDER BY pf.FeedbackDate DESC";
        $filename = 'product_feedback';
        $title = 'Product Feedback Records';
    } elseif ($dataType === 'rating_feedback') {
        $query = "SELECT fr.*, c.CustName FROM feedback_rating fr JOIN customer c ON fr.CustID = c.CustID ORDER BY fr.Rating DESC";
        $filename = 'rating_feedback';
        $title = 'Customer Rating Feedback';
    } elseif ($dataType === 'sales_report') {
        $query = "SELECT * FROM (SELECT DATE_FORMAT(OrderDate, '%Y-%m') AS month, SUM(TotalPrice) AS monthly_sales FROM orderpayment WHERE OrderDate BETWEEN ? AND ? GROUP BY DATE_FORMAT(OrderDate, '%Y-%m') ORDER BY month DESC) AS recent_months ORDER BY month ASC";
        $filename = 'sales_report';
        $title = 'Monthly Sales Report';        
    } elseif ($dataType === 'top_selling') {
        $query = "SELECT p.ProductID, p.ProductName, SUM(od.Quantity) AS total_quantity_sold, SUM(od.Quantity * od.ProductPrice) AS total_sales_value 
        FROM orderdetails od 
        JOIN orderpayment op ON od.OrderID = op.OrderID 
        JOIN product p ON od.ProductName = p.ProductName 
        WHERE op.OrderDate BETWEEN ? AND ? 
        GROUP BY p.ProductID, p.ProductName 
        ORDER BY total_quantity_sold DESC";
      $filename = 'top_selling_products';
        $title = 'Best Selling Products';    
    }
    if ($exportFormat === 'pdf') {
        require('fpdf/fpdf.php');
        $stmt = $conn->prepare($query);
        if ($dataType !== 'rating_feedback') {
            $stmt->bind_param("ss", $startDateTime, $endDateTime);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            echo "<script>alert('No records found for the selected date range'); window.location.href = window.location.href;</script>";
            exit();
        }
        $pdf = new FPDF('L');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(-80, 10);
        $pdf->Cell(70, 6, "From: $startDateTime", 0, 2, 'R');
        $pdf->Cell(70, 6, "To: $endDateTime", 0, 1, 'R');
        
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,$title,0,1,'C');
        $pdf->Ln(10);
        if ($dataType === 'orders') {
            $columns = [
                'OrderID' => ['width' => 15, 'title' => 'ID', 'align' => 'C'],
                'CustName' => ['width' => 40, 'title' => 'Customer Name', 'align' => 'L'],
                'ReceiverInfo' => ['width' => 50, 'title' => 'Receiver Info', 'align' => 'L'],
                'FullAddress' => ['width' => 70, 'title' => 'Address', 'align' => 'L'],
                'OrderDate' => ['width' => 35, 'title' => 'Order Date', 'align' => 'C'],
                'OrderStatus' => ['width' => 20, 'title' => 'Status', 'align' => 'C'],
                'PaymentMethod' => ['width' => 25, 'title' => 'Payment', 'align' => 'C'],
                'TotalPrice' => ['width' => 20, 'title' => 'Total (RM)', 'align' => 'R']
            ];
        } elseif ($dataType === 'contacts') {
            $columns = [
                'Contact_ID' => ['width' => 15, 'title' => 'ID', 'align' => 'C'],
                'CustName' => ['width' => 40, 'title' => 'Name', 'align' => 'L'],
                'CustEmail' => ['width' => 50, 'title' => 'Email', 'align' => 'L'],
                'Subject' => ['width' => 50, 'title' => 'Subject', 'align' => 'L'],
                'Message' => ['width' => 80, 'title' => 'Message', 'align' => 'L'],
                'Submission_date' => ['width' => 35, 'title' => 'Date', 'align' => 'C']
            ];
        } elseif ($dataType === 'product_feedback') {
            $columns = [
                'ProductFeedbackID' => ['width' => 15, 'title' => 'ID', 'align' => 'C'],
                'CustName' => ['width' => 40, 'title' => 'Customer Name', 'align' => 'L'],
                'ProductID' => ['width' => 20, 'title' => 'Prod ID', 'align' => 'C'],
                'ProductName' => ['width' => 60, 'title' => 'Product Name', 'align' => 'L'],
                'Rating' => ['width' => 20, 'title' => 'Rating', 'align' => 'C'],
                'Feedback' => ['width' => 80, 'title' => 'Feedback', 'align' => 'L'],
                'FeedbackDate' => ['width' => 35, 'title' => 'Date', 'align' => 'C']
            ];           
        } elseif ($dataType === 'rating_feedback') {
            $columns = [
                'FeedbackID' => ['width' => 15, 'title' => 'ID', 'align' => 'C'],
                'CustName' => ['width' => 50, 'title' => 'Customer Name', 'align' => 'L'],
                'Rating' => ['width' => 15, 'title' => 'Rating', 'align' => 'C'],
                'Feedback' => ['width' => 150, 'title' => 'Comment', 'align' => 'L'],
                'FeedbackDate' => ['width' => 35, 'title' => 'Date', 'align' => 'C']
            ];
        } elseif ($dataType === 'sales_report') {
            $columns = [
                'month' => ['width' => 50, 'title' => 'Month', 'align' => 'L'],
                'monthly_sales' => ['width' => 50, 'title' => 'Total Sales (RM)', 'align' => 'R']
            ];        
        } elseif ($dataType === 'top_selling') {
            $columns = [
                'ProductID' => ['width' => 15, 'title' => 'Prod ID', 'align' => 'C'],
                'ProductName' => ['width' => 80, 'title' => 'Product Name', 'align' => 'L'],
                'total_quantity_sold' => ['width' => 40, 'title' => 'Quantity Sold', 'align' => 'C'],
                'total_sales_value' => ['width' => 50, 'title' => 'Sales Value (RM)', 'align' => 'R']
            ];
        }
        
        $totalPriceSum = 0;
        $monthlySalesSum = 0;
        $totalSalesValue = 0;

        $headerPrinted = false;
        while ($row = $result->fetch_assoc()) {
            if ($pdf->GetY() + 30 > $pdf->GetPageHeight() - 15) {
                $pdf->AddPage();
                $headerPrinted = false;
            }
            if (!$headerPrinted) {
                $pdf->SetFont('Arial','B',10);
                foreach ($columns as $col) {
                    $pdf->Cell($col['width'], 7, $col['title'], 1, 0, $col['align']);
                }
                $pdf->Ln();
                $headerPrinted = true;
                $pdf->SetFont('Arial','',9);
            }
            if ($dataType === 'orders') {
                $totalPriceSum += floatval($row['TotalPrice']);
            } elseif ($dataType === 'sales_report') {
                $monthlySalesSum += floatval($row['monthly_sales']);
            } elseif ($dataType === 'top_selling') {
                $totalSalesValue += floatval($row['total_sales_value']);
            }
            
            $cellData = [];
            foreach ($columns as $field => $col) {
                if ($dataType === 'orders' && $field === 'ReceiverInfo') {
                    $cellData[$field] = $row['ReceiverName']."\n".$row['ReceiverContact']."\n".$row['ReceiverEmail'];
                } elseif ($dataType === 'orders' && $field === 'FullAddress') {
                    $cellData[$field] = $row['StreetAddress'].", ".$row['Postcode']." ".$row['City'].", ".$row['State'];
                } else {
                    $cellData[$field] = $row[$field] ?? '';
                }
            }
            $maxLines = 1;
            foreach ($columns as $field => $col) {
                $value = $cellData[$field];
                $lineCount = 1;
                if (!empty($value)) {
                    $valueLines = explode("\n", $value);
                    $lineCount = 0;
                    foreach ($valueLines as $line) {
                        $lineCount += ceil($pdf->GetStringWidth($line) / $col['width']);
                    }
                    $lineCount = max(1, $lineCount);
                }
                $maxLines = max($maxLines, $lineCount);
            }
            $lineHeight = 6;
            $rowHeight = $lineHeight * $maxLines;
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            if ($y + $rowHeight > $pdf->GetPageHeight() - 15) {
                $pdf->AddPage();
                $headerPrinted = false;
                continue;
            }
            foreach ($columns as $field => $col) {
                $pdf->SetXY($x, $y);
                $currentX = $pdf->GetX();
                $currentY = $pdf->GetY();
                $pdf->MultiCell($col['width'], $lineHeight, $cellData[$field], 0, $col['align']);
                $pdf->Rect($currentX, $currentY, $col['width'], $rowHeight);
                $x += $col['width'];
            }
            $pdf->SetY($y + $rowHeight);
        }
        
        if (in_array($dataType, ['orders', 'sales_report', 'top_selling'])) {
            $pdf->SetFont('Arial','B',10);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $totalWidth = 0;
        
            $i = 0;
            $columnCount = count($columns);
            foreach ($columns as $col) {
                $i++;
                if ($i < $columnCount) {
                    $totalWidth += $col['width'];
                }
            }
        
            $lastColumnWidth = end($columns)['width'];
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($totalWidth, 7, 'Total:', 1, 0, 'R');
        
            $pdf->SetXY($x + $totalWidth, $y);
            if ($dataType === 'orders') {
                $pdf->Cell($lastColumnWidth, 7, number_format($totalPriceSum, 2), 1, 1, 'R');
            } elseif ($dataType === 'sales_report') {
                $pdf->Cell($lastColumnWidth, 7, number_format($monthlySalesSum, 2), 1, 1, 'R');
            } elseif ($dataType === 'top_selling') {
                $pdf->Cell($lastColumnWidth, 7, number_format($totalSalesValue, 2), 1, 1, 'R');
            }
        }
        
        $pdf->Output('D', $filename.'.pdf');
        exit();
    } elseif ($exportFormat === 'excel') {
        $stmt = $conn->prepare($query);
        if ($dataType !== 'rating_feedback') {
            $stmt->bind_param("ss", $startDateTime, $endDateTime);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    
        if (!$result || $result->num_rows === 0) {
            echo "<script>alert('No records found for the selected date range'); window.location.href = window.location.href;</script>";
            exit();
        }
    
        if ($dataType === 'orders') {
            $columns = [
                'OrderID' => ['width' => 15, 'title' => 'ID'],
                'CustName' => ['width' => 40, 'title' => 'Customer Name'],
                'ReceiverInfo' => ['width' => 50, 'title' => 'Receiver Info'],
                'FullAddress' => ['width' => 70, 'title' => 'Address'],
                'OrderDate' => ['width' => 35, 'title' => 'Order Date'],
                'OrderStatus' => ['width' => 20, 'title' => 'Status'],
                'PaymentMethod' => ['width' => 25, 'title' => 'Payment'],
                'TotalPrice' => ['width' => 20, 'title' => 'Total (RM)']
            ];
        } elseif ($dataType === 'contacts') {
            $columns = [
                'Contact_ID' => ['width' => 15, 'title' => 'ID'],
                'CustName' => ['width' => 40, 'title' => 'Name'],
                'CustEmail' => ['width' => 50, 'title' => 'Email'],
                'Subject' => ['width' => 50, 'title' => 'Subject'],
                'Message' => ['width' => 80, 'title' => 'Message'],
                'Submission_date' => ['width' => 35, 'title' => 'Date']
            ];
        } elseif ($dataType === 'product_feedback') {
            $columns = [
                'ProductFeedbackID' => ['width' => 15, 'title' => 'ID'],
                'CustName' => ['width' => 40, 'title' => 'Customer Name'],
                'ProductID' => ['width' => 20, 'title' => 'Prod ID'],
                'ProductName' => ['width' => 60, 'title' => 'Product Name'],
                'Rating' => ['width' => 20, 'title' => 'Rating'],
                'Feedback' => ['width' => 80, 'title' => 'Feedback'],
                'FeedbackDate' => ['width' => 35, 'title' => 'Date']
            ];           
        } elseif ($dataType === 'rating_feedback') {
            $columns = [
                'FeedbackID' => ['width' => 15, 'title' => 'ID'],
                'CustName' => ['width' => 50, 'title' => 'Customer Name'],
                'Rating' => ['width' => 15, 'title' => 'Rating'],
                'Feedback' => ['width' => 150, 'title' => 'Comment'],
                'FeedbackDate' => ['width' => 35, 'title' => 'Date']
            ];
        } elseif ($dataType === 'sales_report') {
            $columns = [
                'month' => ['width' => 50, 'title' => 'Month'],
                'monthly_sales' => ['width' => 50, 'title' => 'Total Sales (RM)']
            ];        
        } elseif ($dataType === 'top_selling') {
            $columns = [
                'ProductID' => ['width' => 15, 'title' => 'Prod ID'],
                'ProductName' => ['width' => 80, 'title' => 'Product Name'],
                'total_quantity_sold' => ['width' => 40, 'title' => 'Quantity Sold'],
                'total_sales_value' => ['width' => 50, 'title' => 'Sales Value (RM)']
            ];
        }
        $totalPriceSum = 0;
        $monthlySalesSum = 0;
        $totalSalesValue = 0;

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename.xls\"");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        echo "<html>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<style>";
        echo "td { vertical-align: top; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        echo "<table border='1' style='border-collapse: collapse; font-family: Arial, sans-serif; width: 100%;'>";
    
        echo "<tr><td colspan='" . count($columns) . "' style='font-weight: bold; font-size: 16px; text-align: center;'>$title</td></tr>";
        echo "<tr><td colspan='" . count($columns) . "' style='text-align: center;'>Date Range: $startDate to $endDate</td></tr>";
        echo "<tr><td colspan='" . count($columns) . "'>&nbsp;</td></tr>";
    
        echo "<tr>";
        foreach ($columns as $field => $col) {
            $width = $col['width'] * 5;
            echo "<th style='background-color: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; width: {$width}px;'>{$col['title']}</th>";
        }
        echo "</tr>";
    
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($columns as $field => $col) {
                if ($dataType === 'orders' && $field === 'ReceiverInfo') {
                    $value = $row['ReceiverName']."\n".$row['ReceiverContact']."\n".$row['ReceiverEmail'];
                } elseif ($dataType === 'orders' && $field === 'FullAddress') {
                    $value = $row['StreetAddress'].", ".$row['Postcode']." ".$row['City'].", ".$row['State'];
                } else {
                    $value = $row[$field] ?? '';
                }

                if (in_array($field, ['TotalPrice', 'monthly_sales', 'total_sales_value'])) {
                    $rawValue = (float)$value;
                    echo "<td style='mso-number-format:\"0\.00\"; padding: 5px;'>" . number_format($rawValue, 2) . "</td>";
                    
                    if ($dataType === 'orders' && $field === 'TotalPrice') {
                        $totalPriceSum += $rawValue;
                    } elseif ($dataType === 'sales_report' && $field === 'monthly_sales') {
                        $monthlySalesSum += $rawValue;
                    } elseif ($dataType === 'top_selling' && $field === 'total_sales_value') {
                        $totalSalesValue += $rawValue;
                    }
                } else {
                    $value = nl2br(htmlspecialchars($value));
                    echo "<td style='padding: 5px;'>{$value}</td>";
                }
            }
            echo "</tr>";
        }

        if ($dataType === 'orders') {
            echo "<tr>";
            $colspan = count($columns) - 2;
            echo "<td colspan='{$colspan}'></td>";
            echo "<td style='font-weight: bold;'>Grand Total:</td>";
            echo "<td style='font-weight: bold;'>" . number_format($totalPriceSum, 2) . "</td>";
            echo "</tr>";
        } elseif ($dataType === 'sales_report') {
            echo "<tr>";
            $colspan = count($columns) - 1;
            echo "<td colspan='{$colspan}' style='font-weight: bold;'>Total</td>";
            echo "<td style='font-weight: bold;'>" . number_format($monthlySalesSum, 2) . "</td>";
            echo "</tr>";
        } elseif ($dataType === 'top_selling') {
            echo "<tr>";
            $colspan = count($columns) - 2;
            echo "<td colspan='{$colspan}'></td>";
            echo "<td style='font-weight: bold; text-align: right'>Total:</td>";
            echo "<td style='font-weight: bold;'>" . number_format($totalSalesValue, 2) . "</td>";
            echo "</tr>";
        }         
        
        echo "</table>";
        echo "</body>";
        echo "</html>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel='stylesheet' href='sales_report.css'>
    <style>
        .required{
    color: red;
}
    </style>
</head>
<body>
    <div class="header">
        <?php include 'header.php'; ?>
    </div>

    <div class="container">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="main-content">
            <h2>Sales Report</h2>
        
            <form action="" method="POST" id="exportForm">
                <label for="dataType">Report Type:<span class="required">*</span></label>
                <select name="dataType" id="dataType" class="bcm-dropdown" required>
                    <option value="" disabled selected>--- Select a list ---</option>
                    <option value="orders">Order List</option>
                    <option value="contacts">Contact Records</option>
                    <option value="product_feedback">Product Feedback</option>
                    <option value="rating_feedback">Rating Feedback</option>
                    <option value="sales_report">Monthly Sales Report</option>
                    <option value="top_selling">Top Selling Products</option>
                </select>

                <label for="startDate">Start Date:<span class="required">*</span></label>
                <input type="date" id="startDate" name="start_date" class="bcm-input" required>
                <div id="startDateError" class="error-message"></div>

                <label for="endDate">End Date:<span class="required">*</span></label>
                <input type="date" id="endDate" name="end_date" class="bcm-input" required>
                <div id="endDateError" class="error-message"></div>

                <label for="exportFormat">Export Format:<span class="required">*</span></label>
                <select name="export_format" id="exportFormat" class="bcm-dropdown" required>
                    <option value="" disabled selected>--- Select format ---</option>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>

                <button type="submit" class="bcm-button">Export</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            let isValid = true;
            
            document.getElementById('startDateError').style.display = 'none';
            document.getElementById('endDateError').style.display = 'none';
            
            if (!startDate) {
                document.getElementById('startDateError').textContent = 'Please select a start date';
                document.getElementById('startDateError').style.display = 'block';
                isValid = false;
            }
            
            if (!endDate) {
                document.getElementById('endDateError').textContent = 'Please select an end date';
                document.getElementById('endDateError').style.display = 'block';
                isValid = false;
            }
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (start >= end) {
                    document.getElementById('endDateError').textContent = 'End date must be after start date (cannot be the same)';
                    document.getElementById('endDateError').style.display = 'block';
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        document.getElementById('startDate').addEventListener('change', validateDates);
        document.getElementById('endDate').addEventListener('change', validateDates);
        
        function validateDates() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            document.getElementById('startDateError').style.display = 'none';
            document.getElementById('endDateError').style.display = 'none';
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (start > end) {
                    document.getElementById('endDateError').textContent = 'End date cannot be before start date';
                    document.getElementById('endDateError').style.display = 'block';
                }
            }
        }
        window.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').setAttribute('max', today);
            document.getElementById('endDate').setAttribute('max', today);
        });

    </script>
</body>
</html>