{* Пагинация. Ожидает $category, $sort, $page, $totalPages. *}
{if $totalPages > 1}
    <nav class="pagination">
        {if $page > 1}
            <a class="pagination__link"
               href="/category/{$category->slug}?sort={$sort->value}&page={$page-1}">Назад</a>
        {/if}

        <span class="pagination__info">Страница {$page} из {$totalPages}</span>

        {if $page < $totalPages}
            <a class="pagination__link"
               href="/category/{$category->slug}?sort={$sort->value}&page={$page+1}">Вперёд</a>
        {/if}
    </nav>
{/if}
