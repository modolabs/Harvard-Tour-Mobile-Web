{include file="findInclude:common/templates/header.tpl" customHeader="" scalable=false}

{include file="findInclude:modules/transit/templates/include/map.tpl"}

{* footer *}

{foreach $inlineJavascriptFooterBlocks as $script}
  <script type="text/javascript">
    {$script} 
  </script>
{/foreach}

</div>
</body>
</html>
