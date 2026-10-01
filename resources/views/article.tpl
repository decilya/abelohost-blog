{extends file='layouts/main.tpl'}

{block name='content'}
    <article class="article">
        <header>
            <h1 class="article__title">{$article->title}</h1>
            <div class="article__meta">
                <span>{$article->formattedDate()}</span>
                <span>{$views} просмотров</span>
                {if $article->categories}
                    <span>
                        Категории:
                        {foreach $article->categories as $c}
                            <a href="/category/{$c->slug}">{$c->name}</a>{if !$c@last}, {/if}
                        {/foreach}
                    </span>
                {/if}
            </div>
        </header>

        {if $article->hasImage()}
            <div class="article__image">
                <img src="{$article->imageUrl()}" alt="{$article->title}">
            </div>
        {/if}

        {if $article->description}
            <p class="article__lead">{$article->description}</p>
        {/if}

        {* content_html подготовлен в контроллере: escape + nl2br. *}
        <div class="article__content">{$content_html nofilter}</div>
    </article>

    {if $similar}
        <section class="similar">
            <h2>Похожие статьи</h2>
            <div class="articles-grid">
                {foreach $similar as $s}
                    {include file='partials/article_card.tpl' article=$s}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}
