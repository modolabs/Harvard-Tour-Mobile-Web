{extends file="findExtends:common/templates/footer-basic.tpl"}

{block name="footerNavLinks"}
  <p class="bb"> </p>

  {if $hasHelp}
    <p class="secondary">
      <a href="help.php">{$moduleName} Help</a>
    </p>
  {/if}
  
  {if $page != 'index'}
    {html_access_key_reset index=0 force=true}
    <p class="bottomnav">
      <a href="#top">Back to top</a>
      <br />
      {html_access_key_link href="/home/"}{$strings.SITE_NAME} Home{/html_access_key_link}
      {if !$isModuleHome && $moduleID != 'home'}
        {foreach $breadcrumbs as $breadcrumb}
          <br/>
          {html_access_key_link href=$breadcrumb['url']}
            {if $breadcrumb@first}
              {if $moduleName != "Home"}{$moduleName}{/if} Home
            {else}
              {$breadcrumb['longTitle']}
            {/if}
          {/html_access_key_link}
        {/foreach}
      {/if}
      {foreach $additionalLinks as $link}
        <br/>
        {html_access_key_link href=$link['url']}{$link['title']}{/html_access_key_link}
      {/foreach}
    </p>
  {/if}
{/block}
