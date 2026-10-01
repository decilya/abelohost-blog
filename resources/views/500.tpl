{extends file='layouts/main.tpl'}

{block name='content'}
    <div class="error-page">
        <h1>500</h1>
        <p>Внутренняя ошибка сервера.</p>
        {if isset($error_message)}
            <pre>{$error_message}</pre>
        {/if}
        <a class="btn" href="/">На главную</a>
    </div>
{/block}
