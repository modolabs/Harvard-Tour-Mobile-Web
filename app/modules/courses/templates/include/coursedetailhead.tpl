<div class="nonfocal coursetitle">
{block name="courseTitle"}
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
<h2>{$courseID} {$courseTitle}</h2>
{/block}
{block name="termTitle"}
{if $termTitle}
<p class="smallprint">{$termTitle}</p>
{/if}
{/block}
</div>
