<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Страница не найдена</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/main.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 30px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .error-description {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-message">Страница не найдена</h2>
        <p class="error-description">Запрашиваемая страница не существует или была перемещена.</p>
        <div class="d-flex justify-content-center">
            <a href="/" class="btn btn-primary me-2">
                <i class="bi bi-house-door me-2"></i>На главную
            </a>
            <button onclick="goBack()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Вернуться назад
            </button>
        </div>
        <div class="mt-4">
            <p class="text-muted small">
                Если вы считаете, что это ошибка, пожалуйста, свяжитесь с администратором.
            </p>
            <p class="text-muted small">
                Дата и время: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>