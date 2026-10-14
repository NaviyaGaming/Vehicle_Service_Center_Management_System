<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        // Clear chat token and any other session storage on logout
        try {
            localStorage.removeItem('chat_token');
            localStorage.removeItem('torquepoint_user'); // clear stale fake-login data
        } catch (e) {}
        // Redirect back to home page
        window.location.href = 'home/home.php';
    </script>
</head>
<body></body>
</html>