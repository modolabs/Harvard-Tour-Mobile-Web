{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{capture assign="tabBody"}
{if $location}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
{/if}
{if $description}
<h3 class="nonfocal">Description</h3>
<p class="nonfocal">{$description}</p>
{/if}
{if $instructors}
<h3 class="nonfocal">Instructor(s)</h3>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$instructors accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}

{if $links}
<h3 class="nonfocal">Links</h3>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}



{include file="findInclude:common/templates/footer.tpl"}
