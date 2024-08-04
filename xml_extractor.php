<?php
session_start();

// Защита паролем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === '0000') {
        $_SESSION['authenticated'] = true;
    } else {
        $error_msg = 'Incorrect password.';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

function highlight_html($content) {
    return '<pre style="max-width:900px;max-height:600px;overflow:scroll;"><code>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</code></pre>';
}

$highlightedContent = '';
$encodedContent = '';
$xmlError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['xmlInput'])) {
        $xmlInput = $_POST['xmlInput'];

        // Проверка на наличие XML-инъекций
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlInput);

        if ($xml === false) {
            $xmlError = 'Некорректный XML формат. Ошибки: ';
            foreach (libxml_get_errors() as $error) {
                $xmlError .= htmlspecialchars($error->message, ENT_QUOTES, 'UTF-8') . ' ';
            }
            libxml_clear_errors();
        } else {
            try {
                $content = (string) $xml->content; // Смотрим тег <content>

                // Преобразуем спецсимволы в их исходное представление
                $decodedContent = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

                // Подсветим HTML-разметку
                $highlightedContent = highlight_html($decodedContent);
            } catch (Exception $e) {
                $highlightedContent = 'Ошибка обработки XML: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    } elseif (isset($_POST['htmlContent'])) {
        $htmlContent = $_POST['htmlContent'];

        // Преобразуем HTML обратно в формат CDATA
        $encodedContent = '<![CDATA[' . htmlspecialchars($htmlContent, ENT_QUOTES, 'UTF-8') . ']]>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>XML CDATA Extractor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        form {
            margin-bottom: 20px;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border: 1px solid #ccc;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        code {
            color: #d63384;
        }
    </style>
</head>
<body>

<h1>XML CDATA Extractor</h1>
<?php if (!isset($_SESSION['authenticated'])): ?>
    <form method="post">
        <label for="password">Enter Password:</label>
        <input type="password" id="password" name="password" required>
        <input type="submit" value="Login">
        <?php if (isset($error_msg)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </form>
<?php else: ?>
    <form method="POST">
        <textarea name="xmlInput" rows="10" cols="80"><?php echo isset($xmlInput) ? htmlspecialchars($xmlInput, ENT_QUOTES, 'UTF-8') : ''; ?></textarea><br><br>
        <input type="submit" value="Отправить">
    </form>

    <?php if (!empty($xmlError)): ?>
        <p style="color: red;"><?php echo $xmlError; ?></p>
    <?php elseif (!empty($highlightedContent)): ?>
        <h2>Результат:</h2>
        <?php echo $highlightedContent; ?>

        <h3>Преобразовать HTML обратно в CDATA:</h3>
        <form method="POST">
            <textarea name="htmlContent" rows="10" cols="80"><?php echo htmlspecialchars($decodedContent, ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>
            <input type="submit" value="Преобразовать">
        </form>
    <?php endif; ?>

    <?php if (!empty($encodedContent)): ?>
        <h2>Результат CDATA:</h2>
        <pre><?php echo htmlspecialchars($encodedContent, ENT_QUOTES, 'UTF-8'); ?></pre>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
