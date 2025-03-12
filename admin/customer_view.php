<?php

session_start();

if (!isset($_SESSION['AdminID'])) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

$customer_query = "SELECT * FROM customer";
$customer_result = $conn->query($customer_query);

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $customer_query = "SELECT * FROM customer WHERE CustName LIKE ? OR CustEmail LIKE ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $customer_result = $stmt->get_result();
}

if (isset($_POST['update_customer'])) {
    $cust_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 0;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $profile_picture = isset($_FILES['profile_picture']['name']) ? $_FILES['profile_picture']['name'] : null;
    $profile_picture_tmp = isset($_FILES['profile_picture']['tmp_name']) ? $_FILES['profile_picture']['tmp_name'] : null;

    // Handle profile picture upload
    if (!empty($profile_picture)) {
        $image_extension = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        $image_name = $cust_id . "." . $image_extension;
        $target_dir = "../image/user/";
        $target_file = $target_dir . $image_name;

        if (!move_uploaded_file($profile_picture_tmp, $target_file)) {
            echo "<script>alert('Failed to upload image.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        $existing_picture_query = "SELECT CustProfilePicture FROM customer WHERE CustID = ?";
        $stmt = $conn->prepare($existing_picture_query);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();

        if (!empty($existing_picture) && file_exists($target_dir . $existing_picture)) {
            unlink($target_dir . $existing_picture);
        }
    } else {
        $existing_picture_query = "SELECT CustProfilePicture FROM customer WHERE CustID = ?";
        $stmt = $conn->prepare($existing_picture_query);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->bind_result($existing_picture);
        $stmt->fetch();
        $stmt->close();
        $image_name = $existing_picture;
    }

    $check_email_query = "SELECT CustID FROM customer WHERE CustEmail = ? AND CustID != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $cust_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();    

    $update_query = "UPDATE customer SET CustName = ?, CustEmail = ?, CustPhoneNum = ?, CustAddress = ?, CustProfilePicture = ? WHERE CustID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $image_name, $cust_id);

    if ($stmt->execute()) {
        echo "<script>alert('Customer updated successfully!'); window.location.href='customer_view.php';</script>";
    } else {
        echo "<script>alert('Failed to update customer.'); window.location.href='customer_view.php';</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_POST['delete_customer'])) {
    $cust_id = intval($_POST['cust_id']);

    $delete_query = "DELETE FROM customer WHERE CustID = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $cust_id);

    if ($stmt->execute()) {
        echo "<script>alert('Customer deleted successfully!'); window.location.href='customer_view.php';</script>";
    } else {
        echo "<script>alert('Failed to delete customer.'); window.location.href='customer_view.php';</script>";
    }
    exit();
}

if (isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email'])); // Normalize the email
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $profile_picture = isset($_FILES['profile_picture']['name']) ? $_FILES['profile_picture']['name'] : null;
    $profile_picture_tmp = isset($_FILES['profile_picture']['tmp_name']) ? $_FILES['profile_picture']['tmp_name'] : null;

    // Check if the email already exists
    $check_email_query = "SELECT CustID FROM customer WHERE CustEmail = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href='customer_view.php';</script>";
        exit();
    }
    $stmt->close();

    // Handle profile picture upload
    if (!empty($profile_picture)) {
        $image_extension = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($image_extension, $allowed_types)) {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, and PNG allowed.'); window.location.href='customer_view.php';</script>";
            exit();
        }

        // Insert the customer first to get the CustID
        $insert_query = "INSERT INTO customer (CustName, CustEmail, CustPassword, CustPhoneNum, CustAddress) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);

        if ($stmt->execute()) {
            $cust_id = $stmt->insert_id;
            $image_name = $cust_id . "." . $image_extension;
            $target_dir = "../image/user/";
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($profile_picture_tmp, $target_file)) {
                // Update the customer record with the profile picture filename
                $update_picture_query = "UPDATE customer SET CustProfilePicture = ? WHERE CustID = ?";
                $stmt = $conn->prepare($update_picture_query);
                $stmt->bind_param("si", $image_name, $cust_id);
                $stmt->execute();
            } else {
                echo "<script>alert('Failed to upload image.'); window.location.href='customer_view.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Failed to add customer.'); window.location.href='customer_view.php';</script>";
            exit();
        }
    } else {
        $image_name = "user.png"; // Default profile picture
        $insert_query = "INSERT INTO customer (CustName, CustEmail, CustPassword, CustPhoneNum, CustAddress, CustProfilePicture) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssssss", $name, $email, $password, $phone, $address, $image_name);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Customer added successfully!'); window.location.href='customer_view.php';</script>";
    } else {
        echo "<script>alert('Failed to add customer.'); window.location.href='customer_view.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List</title>
    <link rel="stylesheet" href="customer_view.css">
</head>
<body>
    <div class="header">
        <?php include 'header.php'; ?>
    </div>

    <div class="container">
        <div class="sidebar">
            <ul>
                <li><a href="report.php">Report</a></li>
                <li><a href="customer_view.php">Customer List</a></li>
                <li><a href="admin_view.php">Admin List</a></li>
                <li><a href="category_view.php">Category List</a></li>
                <li><a href="product_view.php">Product List</a></li>
                <li><a href="order_view.php">Order List</a></li>
                <li><a href="profile.php">My Profile</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h2>Customer List</h2>

            <?php if (isset($_SESSION['message'])): ?>
                <p class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
            <?php endif; ?>

            <form method="GET" action="" class="search">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search">Search</button>
            </form>
                    
            <div class="add">
                <button onclick="openAddModal()" class="add_btn">Add Customer</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile Picture</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Password</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customer_result->num_rows > 0): ?>
                        <?php while ($customer = $customer_result->fetch_assoc()): ?>
                            <?php
                            $cust_id = $customer['CustID'];
                            $jpgPath = "../image/user/{$cust_id}.jpg";
                            $jpegPath = "../image/user/{$cust_id}.jpeg";
                            $pngPath = "../image/user/{$cust_id}.png";

                            if (file_exists($jpgPath)) {
                                $profile_pic_path = $jpgPath;
                            } elseif (file_exists($jpegPath)) {
                                $profile_pic_path = $jpegPath;
                            } elseif (file_exists($pngPath)) {
                                $profile_pic_path = $pngPath;
                            } else {
                                $profile_pic_path = "../image/user/user.png";
                            }
                            ?>
                            <tr>
                                <td><?php echo $customer['CustID']; ?></td>
                                <td><img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" width="50"></td>
                                <td><?php echo $customer['CustName']; ?></td>
                                <td><?php echo $customer['CustEmail']; ?></td>
                                <td><?php echo $customer['CustPhoneNum']; ?></td>
                                <td><?php echo $customer['CustAddress']; ?></td>
                                <td><?php echo $customer['CustPassword']; ?></td>
                                <td>
                                    <button name="edit_customer" onclick='editCustomer(<?php echo json_encode($customer["CustID"]); ?>, <?php echo json_encode($customer["CustName"]); ?>, <?php echo json_encode($customer["CustEmail"]); ?>, <?php echo json_encode($customer["CustPhoneNum"]); ?>, <?php echo json_encode($customer["CustAddress"]); ?>, <?php echo json_encode($customer["CustProfilePicture"]); ?>, <?php echo json_encode($customer["CustPassword"]); ?>)'>Edit</button>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="cust_id" value="<?php echo $customer['CustID']; ?>">
                                        <button type="submit" name="delete_customer" onclick="return confirm('Are you sure you want to delete this customer?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;color:red;"><b>No customers found.</b></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal">
        <div class="edit-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Customer</h3>
            <form method="POST" action="" enctype="multipart/form-data" class="edit">
                <input type="hidden" name="cust_id" id="cust_id">
                <label>Profile Picture:</label>
                <input type="file" name="profile_picture" id="profile_picture">
                <label>Name:</label>
                <input type="text" name="name" id="name" required>
                <label>Email:</label>
                <input type="email" name="email" id="email" required>
                <label>Phone:</label>
                <input type="text" name="phone" id="phone" required>
                <label>Address:</label>
                <textarea name="address" id="address" required></textarea>             
                <label>Password:</label>
                <input type="password" name="password" required>
                <div class="upd_div">
                <button type="submit" name="update_customer">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addModal">
        <div class="add-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add Customer</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <label>Profile Picture:</label>
                <input type="file" name="profile_picture">
                <label>Name:</label>
                <input type="text" name="name" required>
                <label>Email:</label>
                <input type="email" name="email" required>
                <label>Phone:</label>
                <input type="text" name="phone" required>
                <label>Address:</label>
                <textarea name="address" required></textarea>
                <label>Password:</label>
                <input type="password" name="password" required>
                <div class="add_div">
                    <button type="submit" name="add_customer">Add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCustomer(id, name, email, phone, address, profile_picture) {
            document.getElementById('cust_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('address').value = address;
            document.getElementById('profile_picture').value = profile_picture;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
    </script>
</body>
</html>