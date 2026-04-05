<?php
session_start();
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirectBasedOnRole($_SESSION['role']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Restaurant Management System</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary: #3f6a3f;
            --accent: #d98b3a;
            --surface: #fff8eb;
            --text: #2f3a2f;
            --muted: #6c705f;
            --bg: #f6efe2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        .hero {
            color: #fff;
            background:
                linear-gradient(rgba(46, 79, 46, 0.78), rgba(46, 79, 46, 0.78)),
                url('assets/images/cameroon dishes.jpeg') center/cover no-repeat;
            padding: 4rem 1rem;
            text-align: center;
        }
        .hero h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(2rem, 4vw, 3rem);
        }
        .hero p {
            margin: 0 auto;
            max-width: 900px;
            color: #f6efe2;
            font-size: 1.05rem;
        }
        .hero-actions {
            margin-top: 1.4rem;
        }
        .hero-actions a {
            display: inline-block;
            margin: 0.3rem;
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            color: var(--primary);
            background: var(--surface);
        }
        .hero-actions a.secondary {
            background: var(--accent);
        }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }
        .section {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.35rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 24px rgba(69, 61, 52, 0.12);
        }
        h2 {
            margin: 0 0 0.8rem;
            font-size: 1.4rem;
            color: var(--primary);
        }
        h3 {
            margin: 0.7rem 0 0.35rem;
            font-size: 1.05rem;
            color: #5d5346;
        }
        p { margin: 0.35rem 0 0.65rem; }
        ul {
            margin: 0.35rem 0 0.65rem;
            padding-left: 1.2rem;
        }
    </style>
</head>
<body>
    <header class="hero">
        <h1>University Restaurant Management System</h1>
        <p>
            A University Restaurant Management System (URMS) is a specialized platform designed to manage and automate dining operations within colleges and universities.
        </p>
        <div class="hero-actions">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <a href="#overview" class="secondary">Learn More</a>
        </div>
    </header>

    <main class="container">
        <section class="section" id="overview">
            <h2>Overview</h2>
            <p>
                These systems handle key functions such as meal plan management, inventory tracking, point-of-sale (POS) integration, menu management, and reporting.
                They are designed to support complex meal plan structures like declining balances and fixed meal plans per day, week, or semester,
                while integrating with university IT systems such as student ID cards, barcodes, and RFID readers.
            </p>
        </section>

        <section class="section">
            <h2>Key Features of University Restaurant Management Systems</h2>

            <h3>1. Meal Plan Management</h3>
            <p>
                University restaurant systems allow institutions to manage different types of student meal plans. These include declining balance accounts
                where funds are deducted after each purchase, and fixed meal plans where students are allocated a certain number of meals over a specific period.
                The system automatically deducts meals or balances when students use their ID cards at dining locations.
            </p>

            <h3>2. Inventory and Kitchen Management</h3>
            <p>
                The system tracks inventory in real time and automatically updates stock levels whenever an order is placed. It can also send orders directly
                to kitchen display systems, improving communication between the front-end ordering system and kitchen staff. This helps reduce food waste
                and improves operational efficiency.
            </p>

            <h3>3. Centralized Menu Management</h3>
            <p>
                Administrators can create, update, and publish menus across multiple platforms such as POS terminals, digital menu boards, and websites.
                This ensures consistency across different dining locations on campus.
            </p>

            <h3>4. Multi-Location Management</h3>
            <p>
                Universities often operate multiple dining halls, cafes, and retail food outlets. A university restaurant management system allows administrators
                to monitor performance across all locations, manage pricing and menus centrally, and generate sales and performance reports for each location.
            </p>

            <h3>5. Reporting and Analytics</h3>
            <p>
                These systems generate reports on sales, inventory usage, and popular menu items. This information helps management make decisions regarding pricing,
                menu planning, staffing, and procurement.
            </p>

            <h3>6. Mobile Ordering and Self-Service</h3>
            <p>
                Modern systems also support mobile ordering, self-service kiosks, and contactless payments, reducing waiting times and improving the student dining experience.
            </p>
        </section>

        <section class="section">
            <h2>Data Security and Privacy</h2>
            <p>
                Universities must protect student data used in meal plan transactions. Systems typically follow data protection regulations and use secure payment
                processing and encryption to protect transaction data. Access to student information is restricted, and third-party vendors must follow strict
                data protection agreements.
            </p>
        </section>

        <section class="section">
            <h2>Relationship Between Meal Plans and Financial Aid</h2>
            <p>
                In many universities, meal plans are included in the Cost of Attendance (COA) under room and board expenses. Financial aid packages may cover meal plan costs,
                but the amount of financial aid usually does not change based on the specific meal plan selected by the student. If a student chooses a more expensive meal plan,
                they typically pay the difference themselves.
            </p>
        </section>

        <section class="section">
            <h2>Conclusion</h2>
            <p>
                A University Restaurant Management System integrates meal plans, POS systems, inventory management, menu management, reporting, and mobile ordering into one
                centralized platform. By automating food service operations, these systems improve efficiency, reduce administrative workload, enhance student dining experiences,
                and help universities manage multiple dining facilities effectively.
            </p>
        </section>
    </main>
</body>
</html>
