{block name="courseTitle"}
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
<h2 class="nonfocal">{$courseTitle}</h2>
{block name="courseID"}
<div class="smallprint nonfocal">{$courseID}</div>
{/block}
{/block}
{block name="termTitle"}
{if $termTitle && $showTermTitle}
<p class="smallprint nonfocal">{$termTitle}</p>
{/if}
{/block}