<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} - {$app_name}</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    {include file='partials/header.tpl'}

    <main class="container">
        {block name='content'}{/block}
    </main>

    {include file='partials/footer.tpl'}
</body>
</html>
