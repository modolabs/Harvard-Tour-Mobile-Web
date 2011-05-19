{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
{/block}

{block name="loginHTML"}
{/block}

{block name="footerNavLinks"}
  {if $page != 'index'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> | <a href="../home/">{$strings.SITE_NAME} home</a>
    </div>
  {/if}
{/block}

{block name="footerKurogo"}{/block}
