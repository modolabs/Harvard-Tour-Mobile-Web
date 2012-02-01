<div id="tabscontainer">
    <ul id="tabs" class="smalltabs">
        {foreach $courseTabs as $tabID=>$tabData}
        <li{if $page==$tabID} class="active"{/if}> <a href="{$tabData['url']}">{$tabData['title']}</a></li>
        {/foreach}
    </ul>
    <div id="tabbodies">
        <div class="tabbody">    
            {$tabBody}
        </div>
    </div>
</div>
