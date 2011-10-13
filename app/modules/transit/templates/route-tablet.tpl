{extends file="findExtends:modules/transit/templates/route.tpl"}

{block name="headerServiceLogo"}
  {$serviceLogo}
  {$serviceInfo}
{/block}
{block name="headerServiceInfo"}
{/block}
{block name="tabView"}
  <div class="tabwrapper">
    {$smarty.block.parent}
  </div>
{/block}
