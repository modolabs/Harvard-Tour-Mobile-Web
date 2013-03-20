{extends file="findExtends:modules/{$moduleID}/templates/index.tpl"}

{block name="tabView"}
  <div class="focal">
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}
