{$timeString = ''}
{foreach $predictions as $prediction}
  {$mins = floor(($prediction - time())/60)}
  {if $mins < 1}
    {$mins = '&lt; 1'}
  {/if}
  {if !$prediction@first}
    {if $prediction@last}
      {$times = $times|cat:' &amp; '}
    {else}
      {$times = $times|cat:', '}
    {/if}
  {/if}
  {$times = $times|cat:$mins}
{/foreach}
{if $times}
  Arriving in {$times} mins
{/if}
