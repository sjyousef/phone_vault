<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'PhoneVault' ?> | PhoneVault</title>
    <!-- Header & Sidebar Framework (Bootstrap 5) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Core Design System & Global Styles -->
    <link rel="stylesheet" href="/Second_Hand_Phone_Store/assets/css/custom.css">
    <link rel="stylesheet" href="/Second_Hand_Phone_Store/assets/css/footer.css">
    
    <!-- Page-Specific Plain Vanilla CSS (Easy Debugging & Modular Architecture) -->
    <?php if (!empty($pageStyle)): ?>
    <link rel="stylesheet" href="/Second_Hand_Phone_Store/assets/css/<?= htmlspecialchars($pageStyle) ?>">
    <?php endif; ?>
</head>
<body>
