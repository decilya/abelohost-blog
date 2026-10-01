{extends file='layouts/main.tpl'}

{block name='content'}
    <header class="page-header">
        <h1>{$category->name}</h1>
        {if $category->description}
            <p>{$category->description}</p>
        {/if}
        <p class="page-header__meta">Всего статей: {$totalArticles}</p>
    </header>

    <nav class="sort-form">
        <span>Сортировка:</span>
        {foreach $allSorts as $s}
            <a class="sort-link {if $s->value == $sort->value}sort-link--active{/if}"
               href="/category/{$category->slug}?sort={$s->value}">{$s->label()}</a>
        {/foreach}
    </nav>

    {if $articles == []}
        <p class="empty">В этой категории пока нет статей.</p>
    {else}
        <div class="articles-grid">
            {foreach $articles as $article}
                {include file='partials/article_card.tpl' article=$article}
            {/foreach}
        </div>

        {include file='partials/pagination.tpl'}
    {/if}
{/block}
