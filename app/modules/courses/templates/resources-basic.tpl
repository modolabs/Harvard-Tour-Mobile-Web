{extends file="findExtends:modules/courses/templates/resources.tpl"}

{block name="groupSelector"}
{if $resourcesGroupLinks}
<p class="tabstrip {$resourcesTabCount}tabs" id="{$tabstripId}-tabstrip">
{foreach $resourcesGroupLinks as $index => $groupLink}
{if $resourcesGroup == $index}<strong>{else}<a href="{$groupLink.url}">{/if}{$groupLink.title}{if $resourcesGroup == $index}</strong>{else}</a>{/if}{if !$groupLink@last}&nbsp;|&nbsp;{/if}
{/foreach}
</p>
{/if}
{/block}
