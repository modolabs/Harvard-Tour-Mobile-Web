{include file="findInclude:common/templates/header.tpl"}

{block name="browseHeader"}{/block}
{block name="browseList"}
{if $folderName}
<h2 class="nonfocal">{$folderName}</h2>
{/if}
{if !$browseLinks && !folderLinks}
{"NO_CONTENT"|getLocalizedString}
{else}
{if $folderLinks}
<h3 class="nonfocal">{"FOLDERS"|getLocalizedString}</h3>
{include file="findInclude:modules/courses/templates/include/updatesList.tpl" updates=$folderLinks}
{/if}
{if $browseLinks}
<h3 class="nonfocal">{"CONTENT"|getLocalizedString}</h3>
{include file="findInclude:modules/courses/templates/include/updatesList.tpl" updates=$browseLinks}
{/if}
{/if}
{/block}
{block name="browseFooter"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
