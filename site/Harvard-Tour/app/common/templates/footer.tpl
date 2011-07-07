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

{block name="footerKurogo"}
  <div class="h375logo">
    <a href="http://375.harvard.edu" target="_new">
      <img src="/modules/tour/images/h375.png" width="60" height="20" alt="Harvard 375" />
    </a>
  </div>
{/block}

