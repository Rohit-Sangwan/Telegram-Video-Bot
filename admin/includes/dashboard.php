<!DOCTYPE html>
<html>
<head>
    <title>Video Bot Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>

        <!-- Dashboard Pages -->
        <?php include 'includes/pages/dashboard.php'; ?>
        <?php include 'includes/pages/users.php'; ?>
        <?php include 'includes/pages/videos.php'; ?>
        <?php include 'includes/pages/logs.php'; ?>
        <?php include 'includes/pages/settings.php'; ?>
        <?php include 'includes/pages/tools.php'; ?>
        <?php include 'includes/pages/support.php'; ?>

        <!-- Modals -->
        <?php include 'includes/modals.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="assets/dashboard.js"></script>
</body>
</html>
