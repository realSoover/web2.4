<?php
header('WWW-Authenticate: Basic realm="Admin Panel"');
header('HTTP/1.0 401 Unauthorized');
echo "Вы вышли из системы. <a href='index.php'>Войти снова</a>";
exit;
?>