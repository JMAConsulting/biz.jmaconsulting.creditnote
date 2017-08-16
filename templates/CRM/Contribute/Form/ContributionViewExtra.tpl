<table style="display:none !important;">
  {if $creditnote_id}
    <tr class = 'crm-contribution_creditnote_id'>
      <td class="label">{ts}Credit Note Id{/ts}</td>
      <td>{$creditnote_id}</td>
    </tr>
  {/if}
  {if $usedFrom}
    <tr class = 'crm-contribution_used_from'>
      <td class="label">{ts}Credit Note used from{/ts}</td>
      <td>{$usedFrom}</td>
    </tr>
  {/if}
  {if $usedFor}
    <tr class = 'crm-contribution_used_for'>
      <td class="label">{ts}Credit Note used for{/ts}</td>
      <td>{$usedFor}</td>
    </tr>
  {/if}
</table>


{literal}
<script type="text/javascript">
CRM.$(function($) {
  $('div.crm-contribution-view-form-block table.crm-info-panel:first').append($('.crm-contribution_creditnote_id'));
  $('div.crm-contribution-view-form-block table.crm-info-panel:first').append($('.crm-contribution_used_from'));
  $('div.crm-contribution-view-form-block table.crm-info-panel:first').append($('.crm-contribution_used_for'));
});
</script>
{/literal}