<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../image/logo.png" alt="Watersport Equipment Shop Logo">
            <h1></h1>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </header>
</body>
</html>

<style>
body, header, div, a, img {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

header {
    position:absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    z-index: 1000;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    border-radius: 15px;
    position: fixed;

}

.logo {
    display: flex;
    align-items: center;
}

.logo img {
    width: 100px;
    margin-left: 70px;
}

h1 {
    color: white;
    font-size: 24px;
    margin: 0;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logout-button {
    padding: 8px 16px;
    background-color: #ff4d4d;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.logout-button:hover {
    background-color: #cc0000;
}


</style>