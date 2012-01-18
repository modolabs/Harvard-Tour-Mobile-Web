{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

<ul id="tabs">
<li> <a href="{$linkToOtherTab}"> Updates</a></li>
<li> <a href="{$linkToResourcesTab}">Resources</a></li>
<li class="active"> <a href="{$linkToInfoTab}"> Info</li>
</ul>
<br/>
<ul class="nav">
<li>Optional description field: {$description}</li>
<ul/>
<h2 class="nonfocal">Instructor(s)</h2>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$instructorLish accessKey=false subTitleNewline=$contactsSubTitleNewline}

{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links}
{/if}
{include file="findInclude:common/templates/footer.tpl"}
