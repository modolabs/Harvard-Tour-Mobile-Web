{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{capture assign="tabBody"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
Information about this course...
{if $instructors}
<div class="nonfocal">
    <h2>Instructor(s)</h2>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$instructorLinks accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}

{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}



{include file="findInclude:common/templates/footer.tpl"}
