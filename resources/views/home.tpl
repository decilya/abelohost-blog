{extends file='layouts/main.tpl'}

{block name='content'}
    <h1>Блог</h1>

    {if $groups == []}
        <p class="empty">Пока нет ни одной статьи. Запустите сидинг: php bin/seed.php</p>
    {else}
        {foreach $groups as $group}
            <section class="category-block">
                <header class="category-block__header">
                    <h2>{$group.category->name}</h2>
                    <a class="btn btn--small" href="/category/{$group.category->slug}">Все статьи</a>
                </header>

                {if $group.category->description}
                    <p class="category-block__description">{$group.category->description}</p>
                {/if}

                <div class="articles-grid">
                    {foreach $group.articles as $article}
                        {include file='partials/article_card.tpl' article=$article}
                    {/foreach}
                </div>
            </section>
        {/foreach}
    {/if}
{/block}
