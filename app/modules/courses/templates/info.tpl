{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

<div id="tabscontainer">
    <ul id="tabs" class="smalltabs">
        <li> <a href="{$linkToOtherTab}"> Updates</a></li>
        <li> <a href="{$linkToResourcesTab}">Resources</a></li>
        <li class="active"> <a href="{$linkToInfoTab}"> Info</li>
    </ul>
    <div id="tabbodies"></div>
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}

<div class="nonfocal">
    <h2>Instructor(s)</h2>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$instructorLinks accessKey=false subTitleNewline=$contactsSubTitleNewline}

{if $links}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}


{include file="findInclude:common/templates/footer.tpl"}
