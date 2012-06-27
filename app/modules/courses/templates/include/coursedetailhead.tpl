<div class="nonfocal coursetitle">
{block name="courseTitle"}
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
<h2>{$courseTitle}</h2>
{block name="courseID"}
<div class="smallprint">{$courseID}</div>
{/block}
{/block}
{block name="termTitle"}
{if $termTitle}
<p class="smallprint">{$termTitle}</p>
{/if}
{/block}
</div>
