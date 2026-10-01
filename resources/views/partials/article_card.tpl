{* Карточка статьи в списках. Ожидает переменную $article. *}
<article class="article-card">
    <a href="/article/{$article->slug}" class="article-card__image">
        <img src="{$article->imageUrl()}" alt="{$article->title}" loading="lazy">
    </a>
    <div class="article-card__body">
        <h3 class="article-card__title">
            <a href="/article/{$article->slug}">{$article->title}</a>
        </h3>
        {if $article->description}
            <p class="article-card__description">{$article->description}</p>
        {/if}
        <div class="article-card__meta">
            <span>{$article->formattedDate()}</span>
            <span>{$article->formattedViews()} просмотров</span>
        </div>
    </div>
</article>
